<?php

namespace Tests\Feature\Appointments;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Appointment $appointment;

    private string $day;

    protected function setUp(): void
    {
        parent::setUp();

        $this->day = now()->addDays(60)->format('Y-m-d');

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/employees', ['name' => 'Priya Therapist']);
        $employee = Employee::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 2000, 'gst_rate' => 18]);
        $service = Service::firstOrFail();

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'starts_at' => "{$this->day} 10:00:00",
        ]);
        $this->appointment = Appointment::firstOrFail();
    }

    public function test_the_create_bill_page_prefills_from_an_appointment(): void
    {
        $response = $this->actingAs($this->owner)->get("/invoices/create?appointment_id={$this->appointment->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('appointment.id', $this->appointment->id)
            ->where('appointment.customer.name', 'Anjali Mehta')
        );
    }

    public function test_billing_from_an_appointment_links_the_invoice_back_to_it(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->appointment->customer_id,
            'appointment_id' => $this->appointment->id,
            'items' => [['service_id' => $this->appointment->service_id, 'employee_id' => $this->appointment->employee_id, 'quantity' => 1]],
        ]);

        $invoice = Invoice::firstOrFail();

        $this->assertSame($this->appointment->id, $invoice->appointment_id);
        $this->assertTrue($this->appointment->invoice()->exists());
        $this->assertSame($invoice->id, $this->appointment->invoice()->first()->id);
    }

    public function test_the_appointments_list_shows_the_linked_invoice_once_billed(): void
    {
        $this->actingAs($this->owner)->post('/invoices', [
            'customer_id' => $this->appointment->customer_id,
            'appointment_id' => $this->appointment->id,
            'items' => [['service_id' => $this->appointment->service_id, 'quantity' => 1]],
        ]);

        $response = $this->actingAs($this->owner)->get("/appointments?date={$this->day}");

        $response->assertInertia(fn ($page) => $page->where('appointments.0.invoice.id', Invoice::first()->id));
    }
}
