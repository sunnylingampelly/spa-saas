<?php

namespace Tests\Feature\Customers;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Repositories\CustomerHistoryRepositoryInterface;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Service $service;

    private string $firstVisitDate;

    private string $secondVisitDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->firstVisitDate = now()->addDays(60)->format('Y-m-d');
        $this->secondVisitDate = now()->addDays(67)->format('Y-m-d'); // 7 days after firstVisitDate

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 1000]);
        $this->service = Service::firstOrFail();
    }

    private function billAndPay(string $startsAt): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'starts_at' => $startsAt,
        ]);
        $appointment = Appointment::latest('id')->first();
        $this->actingAs($this->owner)->patch("/appointments/{$appointment->id}/status", ['status' => 'completed']);

        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'appointment_id' => $appointment->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);
        $invoice = \App\Domain\Billing\Models\Invoice::latest('id')->first();

        $this->actingAs($this->owner)->post("/invoices/{$invoice->id}/payments", [
            'payments' => [['method' => 'cash', 'amount' => (float) $invoice->total_amount]],
        ]);
    }

    public function test_a_customer_with_no_history_shows_zeroed_stats(): void
    {
        $repository = app(CustomerHistoryRepositoryInterface::class);
        $stats = $repository->statsFor($this->customer->id);

        $this->assertSame(0.0, $stats['lifetimeSpend']);
        $this->assertNull($stats['averageBill']);
        $this->assertSame(0, $stats['visitCount']);
        $this->assertNull($stats['visitFrequencyDays']);
    }

    public function test_lifetime_spend_and_average_bill_reflect_paid_invoices(): void
    {
        $this->billAndPay("{$this->firstVisitDate} 10:00:00");
        $this->billAndPay("{$this->secondVisitDate} 10:00:00");

        $repository = app(CustomerHistoryRepositoryInterface::class);
        $stats = $repository->statsFor($this->customer->id);

        $this->assertEqualsWithDelta(2360.0, $stats['lifetimeSpend'], 0.01); // 2 x (1000 + 18% GST)
        $this->assertEqualsWithDelta(1180.0, $stats['averageBill'], 0.01);
        $this->assertSame(2, $stats['visitCount']);
        $this->assertSame(7.0, $stats['visitFrequencyDays']);
    }

    public function test_a_cancelled_invoice_does_not_count_toward_lifetime_spend(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->customer->id,
            'items' => [['service_id' => $this->service->id, 'quantity' => 1]],
        ]);
        $invoice = \App\Domain\Billing\Models\Invoice::first();
        $this->actingAs($this->owner)->post("/invoices/{$invoice->id}/cancel");

        $repository = app(CustomerHistoryRepositoryInterface::class);
        $stats = $repository->statsFor($this->customer->id);

        $this->assertSame(0.0, $stats['lifetimeSpend']);
    }

    public function test_the_customer_show_page_includes_history(): void
    {
        $this->billAndPay("{$this->firstVisitDate} 10:00:00");

        $response = $this->actingAs($this->owner)->get("/customers/{$this->customer->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('history.stats.visitCount', 1)
            ->has('history.recentAppointments', 1)
            ->has('history.recentInvoices', 1)
        );
    }
}
