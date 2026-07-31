<?php

namespace Tests\Feature\Billing;

use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionReportTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/employees', [
            'name' => 'Priya Therapist',
            'commission_type' => 'percentage',
            'commission_value' => 10,
        ]);
        $this->employee = Employee::firstOrFail();
    }

    private function billAndPay(int $serviceId, ?int $employeeId, float $expectedTotal): Invoice
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $serviceId, 'employee_id' => $employeeId, 'quantity' => 1]],
        ]);
        $invoice = Invoice::latest('id')->first();

        $this->actingAs($this->owner)->post("/invoices/{$invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => $expectedTotal]],
        ]);

        return $invoice->fresh();
    }

    public function test_services_own_commission_rate_wins_over_the_employees(): void
    {
        // Service has its own 20% commission configured — should win over the employee's 10%.
        $this->actingAs($this->owner)->post('/services', [
            'name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18,
            'commission_type' => 'percentage', 'commission_value' => 20,
        ]);
        $service = Service::firstOrFail();

        $this->billAndPay($service->id, $this->employee->id, 1180);

        $response = $this->actingAs($this->owner)->get('/reports/commissions?from=2020-01-01&to=2030-01-01');

        $response->assertInertia(fn ($page) => $page
            ->where('rows.0.employee_id', $this->employee->id)
            ->where('rows.0.commission', 200) // 20% of 1000 line total (pre-tax)
        );
    }

    public function test_the_employees_commission_is_used_when_the_service_has_none_configured(): void
    {
        // Service has no commission configured (defaults to 0) — falls back to employee's 10%.
        $this->actingAs($this->owner)->post('/services', ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        $this->billAndPay($service->id, $this->employee->id, 1180);

        $response = $this->actingAs($this->owner)->get('/reports/commissions?from=2020-01-01&to=2030-01-01');

        $response->assertInertia(fn ($page) => $page->where('rows.0.commission', 100)); // 10% of 1000
    }

    public function test_items_with_no_assigned_employee_are_excluded(): void
    {
        $this->actingAs($this->owner)->post('/services', ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        $this->billAndPay($service->id, null, 1180);

        $response = $this->actingAs($this->owner)->get('/reports/commissions?from=2020-01-01&to=2030-01-01');

        $response->assertInertia(fn ($page) => $page->has('rows', 0));
    }

    public function test_unpaid_invoices_do_not_count_toward_commission(): void
    {
        $this->actingAs($this->owner)->post('/services', ['name' => 'Swedish Massage', 'duration_minutes' => 60, 'price' => 1000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        // Bill but never pay.
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $service->id, 'employee_id' => $this->employee->id, 'quantity' => 1]],
        ]);

        $response = $this->actingAs($this->owner)->get('/reports/commissions?from=2020-01-01&to=2030-01-01');

        $response->assertInertia(fn ($page) => $page->has('rows', 0));
    }
}
