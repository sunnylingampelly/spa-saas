<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createSpa(string $name): Spa
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => $name, 'phone' => '9876543210', 'state' => 'Karnataka']);

        return Spa::withoutGlobalScopes()->where('name', $name)->firstOrFail();
    }

    public function test_platform_metrics_reflect_seeded_subscriptions_and_payments(): void
    {
        $monthlySpa = $this->createSpa('Monthly Spa');
        $monthlySpa->subscription->update(['plan_code' => 'monthly', 'status' => 'active', 'current_period_ends_at' => now()->addMonth()]);
        SubscriptionPayment::withoutGlobalScopes()->create([
            'spa_id' => $monthlySpa->id,
            'subscription_id' => $monthlySpa->subscription->id,
            'plan_code' => 'monthly',
            'method' => 'manual',
            'status' => 'paid',
            'amount' => 1499,
            'paid_at' => now(),
        ]);

        $lifetimeSpa = $this->createSpa('Lifetime Spa');
        $lifetimeSpa->subscription->update(['plan_code' => 'lifetime', 'status' => 'active', 'current_period_ends_at' => null]);
        SubscriptionPayment::withoutGlobalScopes()->create([
            'spa_id' => $lifetimeSpa->id,
            'subscription_id' => $lifetimeSpa->subscription->id,
            'plan_code' => 'lifetime',
            'method' => 'manual',
            'status' => 'paid',
            'amount' => 24999,
            'paid_at' => now(),
        ]);

        $this->createSpa('Still Trialing Spa');

        $metrics = app(\App\Domain\Subscriptions\Services\PlatformMetricsService::class)->forPlatform();

        $this->assertSame(3, $metrics['totalSpas']);
        $this->assertSame(1, $metrics['trialSpas']);
        $this->assertSame(2, $metrics['activeSpas']);
        $this->assertSame(0, $metrics['suspendedSpas']);
        $this->assertSame(1499.0, $metrics['mrr']);
        $this->assertSame(26498.0, $metrics['totalRevenueCollected']);
        $this->assertSame(26498.0, $metrics['revenueThisMonth']);
        $this->assertSame(1, $metrics['planDistribution']['monthly'] ?? null);
        $this->assertSame(1, $metrics['planDistribution']['lifetime'] ?? null);
        $this->assertSame(100.0, $metrics['trialConversionRate']);
        $this->assertCount(30, $metrics['revenueTrend']);
    }
}
