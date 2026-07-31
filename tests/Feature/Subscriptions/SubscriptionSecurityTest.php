<?php

namespace Tests\Feature\Subscriptions;

use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createOwnerWithSpa(string $spaName): array
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => $spaName, 'phone' => '9876543210', 'state' => 'Karnataka']);

        return [$owner, Spa::withoutGlobalScopes()->where('name', $spaName)->firstOrFail()];
    }

    public function test_a_spa_owner_cannot_verify_or_activate_another_spas_payment(): void
    {
        [$ownerA, $spaA] = $this->createOwnerWithSpa('Spa A');
        [$ownerB, $spaB] = $this->createOwnerWithSpa('Spa B');

        $paymentA = SubscriptionPayment::withoutGlobalScopes()->create([
            'spa_id' => $spaA->id,
            'subscription_id' => $spaA->subscription->id,
            'plan_code' => 'lifetime',
            'method' => 'razorpay',
            'status' => 'pending',
            'amount' => 24999,
            'razorpay_order_id' => 'order_belongs_to_spa_a',
        ]);

        // Owner B (a different tenant) attempts to verify spa A's order — this must never
        // activate spa A's subscription, regardless of what signature is supplied.
        $response = $this->actingAs($ownerB)->post('/subscription/razorpay/verify', [
            'razorpay_order_id' => 'order_belongs_to_spa_a',
            'razorpay_payment_id' => 'pay_whatever',
            'razorpay_signature' => 'sig_whatever',
        ]);

        $response->assertStatus(404);
        $this->assertSame('pending', $paymentA->fresh()->status);
        $this->assertSame('trialing', $spaA->subscription->fresh()->status);
    }

    public function test_the_financial_rate_limiter_throttles_repeated_manual_payment_submissions(): void
    {
        [$owner] = $this->createOwnerWithSpa('Rate Limited Spa');

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($owner)->post('/subscription/manual', ['plan' => 'monthly'])->assertRedirect();
        }

        $this->actingAs($owner)->post('/subscription/manual', ['plan' => 'monthly'])->assertStatus(429);
    }
}
