<?php

namespace Tests\Feature\Appointments;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Employee $employee;

    private Service $service;

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
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/employees', ['name' => 'Priya Therapist']);
        $this->employee = Employee::firstOrFail();

        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 2000]);
        $this->service = Service::firstOrFail();
    }

    public function test_an_owner_can_book_an_appointment_and_end_time_is_computed_from_service_duration(): void
    {
        $response = $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => "{$this->day} 10:00:00",
        ]);

        $appointment = Appointment::first();

        $this->assertNotNull($appointment);
        $this->assertSame("{$this->day} 10:00:00", $appointment->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame("{$this->day} 11:00:00", $appointment->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('booked', $appointment->status);
        $response->assertRedirect(route('appointments.index', ['date' => $this->day]));
    }

    public function test_status_can_be_updated(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'starts_at' => "{$this->day} 10:00:00",
        ]);
        $appointment = Appointment::first();

        $this->actingAs($this->owner)->patch("/appointments/{$appointment->id}/status", ['status' => 'completed']);

        $this->assertSame('completed', $appointment->fresh()->status);
    }

    public function test_cancelling_records_a_reason(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'starts_at' => "{$this->day} 10:00:00",
        ]);
        $appointment = Appointment::first();

        $this->actingAs($this->owner)->patch("/appointments/{$appointment->id}/status", [
            'status' => 'cancelled',
            'cancelled_reason' => 'Customer requested',
        ]);

        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertSame('Customer requested', $appointment->fresh()->cancelled_reason);
    }

    public function test_an_appointment_can_be_rescheduled(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => "{$this->day} 10:00:00",
        ]);
        $appointment = Appointment::first();

        $this->actingAs($this->owner)->patch("/appointments/{$appointment->id}/reschedule", [
            'starts_at' => "{$this->day} 14:00:00",
        ]);

        $fresh = $appointment->fresh();
        $this->assertSame("{$this->day} 14:00:00", $fresh->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame("{$this->day} 15:00:00", $fresh->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_an_appointment_can_be_deleted(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'service_id' => $this->service->id,
            'starts_at' => "{$this->day} 10:00:00",
        ]);
        $appointment = Appointment::first();

        $this->actingAs($this->owner)->delete("/appointments/{$appointment->id}");

        $this->assertSoftDeleted('appointments', ['id' => $appointment->id]);
    }
}
