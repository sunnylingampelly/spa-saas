<?php

namespace Tests\Feature\Subscriptions;

use App\Domain\Subscriptions\Actions\ConfirmManualPaymentAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionEnforcementTest extends TestCase
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
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = Spa::withoutGlobalScopes()->firstOrFail();
    }

    public function test_a_new_spa_gets_a_trialing_subscription_automatically(): void
    {
        $subscription = $this->spa->subscription;

        $this->assertNotNull($subscription);
        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('trial', $subscription->plan_code);
        $this->assertTrue($subscription->hasAccess());
        $this->assertTrue(now()->lt($subscription->current_period_ends_at));
    }

    public function test_dashboard_is_reachable_during_the_trial(): void
    {
        $this->actingAs($this->owner)->get('/dashboard')->assertOk();
    }

    public function test_dashboard_redirects_to_subscription_page_once_trial_has_expired(): void
    {
        $this->spa->subscription->update(['current_period_ends_at' => now()->subDay()]);

        $this->actingAs($this->owner)->get('/dashboard')->assertRedirect(route('subscription.show'));
    }

    public function test_subscription_and_spa_profile_pages_stay_reachable_after_trial_expires(): void
    {
        $this->spa->subscription->update(['current_period_ends_at' => now()->subDay()]);

        $this->actingAs($this->owner)->get('/subscription')->assertOk();
        $this->actingAs($this->owner)->get('/spa/profile')->assertOk();
    }

    public function test_an_active_lifetime_subscription_with_no_expiry_always_has_access(): void
    {
        $this->spa->subscription->update([
            'plan_code' => 'lifetime',
            'status' => 'active',
            'current_period_ends_at' => null,
        ]);

        $this->actingAs($this->owner)->get('/dashboard')->assertOk();
    }

    public function test_a_cancelled_subscription_has_no_access_even_before_any_expiry_date(): void
    {
        $this->spa->subscription->update(['status' => 'cancelled']);

        $this->actingAs($this->owner)->get('/dashboard')->assertRedirect(route('subscription.show'));
    }

    public function test_submitting_a_manual_payment_creates_a_pending_record(): void
    {
        $this->actingAs($this->owner)->post('/subscription/manual', [
            'plan' => 'monthly',
            'proof_note' => 'UPI ref 123456',
        ])->assertRedirect();

        $payment = SubscriptionPayment::firstOrFail();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('manual', $payment->method);
        $this->assertSame('monthly', $payment->plan_code);
        $this->assertSame(1499.0, (float) $payment->amount);
    }

    public function test_confirming_a_manual_payment_activates_the_subscription(): void
    {
        $this->actingAs($this->owner)->post('/subscription/manual', ['plan' => 'lifetime']);
        $payment = SubscriptionPayment::firstOrFail();

        $admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin)
            ->post(route('admin.pending-payments.confirm', $payment->id))
            ->assertRedirect();

        $payment->refresh();
        $subscription = $this->spa->subscription->fresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame($admin->id, $payment->confirmed_by_user_id);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('lifetime', $subscription->plan_code);
        $this->assertNull($subscription->current_period_ends_at);
    }

    public function test_confirming_an_already_paid_payment_is_idempotent(): void
    {
        $this->actingAs($this->owner)->post('/subscription/manual', ['plan' => 'monthly']);
        $payment = SubscriptionPayment::firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        app(ConfirmManualPaymentAction::class)->execute($payment, $admin->id);
        $firstConfirmedAt = $payment->fresh()->paid_at;

        // Confirming again must not re-trigger activation side effects (e.g. push out the renewal date again).
        app(ConfirmManualPaymentAction::class)->execute($payment->fresh(), $admin->id);

        $this->assertEquals($firstConfirmedAt, $payment->fresh()->paid_at);
    }
}
