<?php

namespace Tests\Feature\Subscriptions;

use App\Domain\Subscriptions\Actions\ActivateSubscriptionFromPaymentAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Real-world business case: "Radiance Day Spa", run by owner Priya. These tests walk
 * through the actual lifecycle of her subscription — first payment, renewing early,
 * an accidental double payment attempt, upgrading to Lifetime, and what happens once
 * she's on Lifetime — to lock down that paying twice for something already active
 * never happens, and that renewing never silently discards time already paid for.
 */
class SubscriptionRenewalGuardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Spa $spa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', [
            'name' => 'Radiance Day Spa',
            'phone' => '9876543210',
            'state' => 'Karnataka',
        ]);
        $this->spa = Spa::withoutGlobalScopes()->firstOrFail();
    }

    private function activatePaidPayment(string $planCode): SubscriptionPayment
    {
        $payment = SubscriptionPayment::create([
            'spa_id' => $this->spa->id,
            'subscription_id' => $this->spa->subscription->id,
            'plan_code' => $planCode,
            'method' => 'manual',
            'status' => 'pending',
            'amount' => $planCode === 'monthly' ? 1499 : config("subscriptions.plans.{$planCode}.price"),
        ]);

        app(ActivateSubscriptionFromPaymentAction::class)->execute($payment);

        return $payment->fresh();
    }

    public function test_priyas_first_payment_activates_a_fresh_monthly_period_from_today(): void
    {
        $this->activatePaidPayment('monthly');
        $subscription = $this->spa->subscription->fresh();

        $this->assertSame('active', $subscription->status);
        $this->assertSame('monthly', $subscription->plan_code);
        $this->assertTrue($subscription->current_period_ends_at->isSameDay(now()->addMonthNoOverflow()));
    }

    public function test_renewing_monthly_far_from_expiry_is_blocked_and_charges_nothing(): void
    {
        // 'monthly' can no longer even be selected as a purchase (see config/subscriptions.php),
        // so this can't be exercised through the HTTP endpoint anymore — the guard itself is
        // still generic to any plan_code though, including existing monthly subscribers.
        $this->activatePaidPayment('monthly'); // now active, ~30 days remaining

        $reason = $this->spa->subscription->fresh()->blockingReasonForPurchase('monthly');

        $this->assertNotNull($reason);
        $this->assertStringContainsString('already active until', $reason);
    }

    public function test_the_same_guard_blocks_a_redundant_manual_upi_claim_too(): void
    {
        $this->activatePaidPayment('monthly');

        $response = $this->actingAs($this->owner)->post('/subscription/manual', ['plan' => 'monthly']);

        $response->assertSessionHasErrors('plan');
        $this->assertSame(1, SubscriptionPayment::withoutGlobalScopes()->count());
    }

    public function test_renewing_monthly_within_the_window_is_allowed_and_extends_from_current_expiry_not_from_today(): void
    {
        $this->activatePaidPayment('monthly');
        $subscription = $this->spa->subscription->fresh();
        $originalExpiry = $subscription->current_period_ends_at;

        // Fast-forward Priya to 5 days before her plan lapses — inside the renewal window.
        $subscription->update(['current_period_ends_at' => now()->addDays(5)]);

        $renewalPayment = SubscriptionPayment::create([
            'spa_id' => $this->spa->id,
            'subscription_id' => $subscription->id,
            'plan_code' => 'monthly',
            'method' => 'manual',
            'status' => 'pending',
            'amount' => 1499, // Monthly is no longer purchasable, but existing subscribers on it still exist.
        ]);
        app(ActivateSubscriptionFromPaymentAction::class)->execute($renewalPayment);

        $renewed = $subscription->fresh();

        // Extended from the 5-days-away expiry, not reset to "today + 1 month" — she keeps
        // every day she already paid for, on top of the new month.
        $this->assertTrue($renewed->current_period_ends_at->isSameDay(now()->addDays(5)->addMonthNoOverflow()));
        $this->assertGreaterThan(now()->addMonthNoOverflow()->subDay(), $renewed->current_period_ends_at);
    }

    public function test_a_lapsed_monthly_plan_restarts_from_today_rather_than_the_old_expired_date(): void
    {
        $this->activatePaidPayment('monthly');
        $subscription = $this->spa->subscription->fresh();

        // Priya's plan lapsed 10 days ago — she's been locked out since.
        $subscription->update(['current_period_ends_at' => now()->subDays(10), 'status' => 'past_due']);

        $newPayment = SubscriptionPayment::create([
            'spa_id' => $this->spa->id,
            'subscription_id' => $subscription->id,
            'plan_code' => 'monthly',
            'method' => 'manual',
            'status' => 'pending',
            'amount' => 1499, // Monthly is no longer purchasable, but existing subscribers on it still exist.
        ]);
        app(ActivateSubscriptionFromPaymentAction::class)->execute($newPayment);

        $reactivated = $subscription->fresh();

        $this->assertSame('active', $reactivated->status);
        $this->assertTrue($reactivated->current_period_ends_at->isSameDay(now()->addMonthNoOverflow()));
    }

    public function test_priya_can_upgrade_to_lifetime_at_any_point_in_her_monthly_cycle(): void
    {
        $this->activatePaidPayment('monthly'); // ~30 days remaining, nowhere near the renewal window

        // A pending Lifetime order already sitting there (as if a first click had just
        // created it) lets us assert the upgrade path isn't blocked without a live
        // Razorpay API call — the guard check runs before order creation either way.
        $pendingUpgrade = SubscriptionPayment::create([
            'spa_id' => $this->spa->id,
            'subscription_id' => $this->spa->subscription->id,
            'plan_code' => 'lifetime',
            'method' => 'razorpay',
            'status' => 'pending',
            'amount' => config('subscriptions.plans.lifetime.price'),
            'razorpay_order_id' => 'order_upgrade_attempt',
        ]);

        $response = $this->actingAs($this->owner)->postJson('/subscription/razorpay/order', ['plan' => 'lifetime']);

        $response->assertOk();
        $response->assertJsonPath('order_id', 'order_upgrade_attempt');
        $response->assertJsonPath('payment_id', $pendingUpgrade->id);
    }

    public function test_upgrading_to_lifetime_clears_the_expiry_date(): void
    {
        $this->activatePaidPayment('monthly');
        $this->activatePaidPayment('lifetime');

        $subscription = $this->spa->subscription->fresh();

        $this->assertSame('lifetime', $subscription->plan_code);
        $this->assertNull($subscription->current_period_ends_at);
    }

    public function test_once_on_lifetime_any_further_payment_attempt_is_blocked(): void
    {
        $this->activatePaidPayment('lifetime');

        $lifetimeAttempt = $this->actingAs($this->owner)->postJson('/subscription/razorpay/order', ['plan' => 'lifetime']);

        $lifetimeAttempt->assertStatus(422);
        $this->assertStringContainsString('lifetime access', $lifetimeAttempt->json('message'));
        $this->assertSame(1, SubscriptionPayment::withoutGlobalScopes()->count());
    }

    public function test_a_double_click_reuses_the_pending_order_instead_of_opening_a_second_one(): void
    {
        // Simulate the first click having already created a live, unpaid order.
        $existing = SubscriptionPayment::create([
            'spa_id' => $this->spa->id,
            'subscription_id' => $this->spa->subscription->id,
            'plan_code' => 'lifetime',
            'method' => 'razorpay',
            'status' => 'pending',
            'amount' => 10000,
            'razorpay_order_id' => 'order_first_click',
        ]);

        // Priya double-clicks — this must reuse the same order, never call Razorpay again.
        $response = $this->actingAs($this->owner)->postJson('/subscription/razorpay/order', ['plan' => 'lifetime']);

        $response->assertOk();
        $response->assertJsonPath('order_id', 'order_first_click');
        $response->assertJsonPath('payment_id', $existing->id);
        $this->assertSame(1, SubscriptionPayment::withoutGlobalScopes()->count(), 'No second payment row should exist.');
    }
}
