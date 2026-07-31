<?php

namespace Tests\Feature\Dashboard;

use App\Domain\Customers\Models\Customer;
use App\Domain\Dashboard\Services\DashboardMetricsService;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Services\Models\Service;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Spa $spa;

    private Customer $customer;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = Spa::withoutGlobalScopes()->firstOrFail();

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $this->service = Service::firstOrFail();
    }

    public function test_todays_revenue_and_bills_reflect_a_bill_paid_today(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);
        $invoice = Invoice::firstOrFail();
        $this->actingAs($this->owner)->post("/invoices/{$invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1180]],
        ]);

        $metrics = app(DashboardMetricsService::class)->forSpa($this->spa->fresh());

        $this->assertSame(1180.0, $metrics['today']['revenue']);
        $this->assertSame(1, $metrics['today']['bills']);
        $this->assertSame(180.0, $metrics['today']['gst']);
        $this->assertSame(0.0, $metrics['pendingPayments']);
    }

    public function test_an_unpaid_bill_counts_toward_pending_payments_but_not_revenue(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);

        $metrics = app(DashboardMetricsService::class)->forSpa($this->spa->fresh());

        $this->assertSame(0.0, $metrics['today']['revenue']);
        $this->assertSame(1180.0, $metrics['pendingPayments']);
    }

    public function test_a_walk_in_appointment_counts_toward_todays_walk_ins(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'booking_type' => 'walk_in',
            'starts_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $metrics = app(DashboardMetricsService::class)->forSpa($this->spa->fresh());

        $this->assertSame(1, $metrics['today']['appointments']);
        $this->assertSame(1, $metrics['today']['walkIns']);
    }

    public function test_expenses_reduce_profit_for_the_month(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);
        $invoice = Invoice::firstOrFail();
        $this->actingAs($this->owner)->post("/invoices/{$invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => 1180]],
        ]);

        $this->actingAs($this->owner)->post('/expenses', [
            'category' => 'Rent', 'amount' => 500, 'expense_date' => now()->toDateString(),
        ]);

        $metrics = app(DashboardMetricsService::class)->forSpa($this->spa->fresh());

        $this->assertSame(500.0, $metrics['expensesThisMonth']);
        $this->assertSame(680.0, $metrics['profitThisMonth']); // 1180 revenue - 500 expenses
    }

    public function test_revenue_and_customer_growth_trends_cover_exactly_thirty_days_ending_today(): void
    {
        $metrics = app(DashboardMetricsService::class)->forSpa($this->spa->fresh());

        $this->assertCount(30, $metrics['revenueTrend']);
        $this->assertCount(30, $metrics['customerGrowthTrend']);
        $this->assertSame(now()->toDateString(), $metrics['revenueTrend'][29]['date']);
    }

    public function test_the_dashboard_page_renders_with_metrics(): void
    {
        $response = $this->actingAs($this->owner)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('metrics.today')
            ->has('metrics.revenueTrend', 30)
            ->has('metrics.popularServices')
        );
    }
}
