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

class AppointmentConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Customer $customer;

    private Employee $employee;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($this->owner)->post('/customers', ['name' => 'Anjali Mehta']);
        $this->customer = Customer::firstOrFail();

        $this->actingAs($this->owner)->post('/employees', ['name' => 'Priya Therapist']);
        $this->employee = Employee::firstOrFail();

        // 60-minute service
        $this->actingAs($this->owner)->post('/services', ['name' => 'Deep Tissue Massage', 'duration_minutes' => 60, 'price' => 2000]);
        $this->service = Service::firstOrFail();

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => '2026-08-05 10:00:00',
        ]);
    }

    public function test_an_overlapping_booking_for_the_same_therapist_is_rejected(): void
    {
        $response = $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => '2026-08-05 10:30:00', // overlaps 10:00-11:00
        ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertSame(1, Appointment::count());
    }

    public function test_a_back_to_back_booking_immediately_after_is_allowed(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => '2026-08-05 11:00:00', // starts exactly when the first ends
        ])->assertRedirect();

        $this->assertSame(2, Appointment::count());
    }

    public function test_the_same_time_slot_is_free_for_a_different_therapist(): void
    {
        $this->actingAs($this->owner)->post('/employees', ['name' => 'Second Therapist']);
        $secondEmployee = Employee::where('name', 'Second Therapist')->firstOrFail();

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $secondEmployee->id,
            'service_id' => $this->service->id,
            'starts_at' => '2026-08-05 10:00:00',
        ])->assertRedirect();

        $this->assertSame(2, Appointment::count());
    }

    public function test_a_cancelled_appointment_does_not_block_the_slot(): void
    {
        $existing = Appointment::first();
        $this->actingAs($this->owner)->patch("/appointments/{$existing->id}/status", ['status' => 'cancelled']);

        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => '2026-08-05 10:30:00',
        ])->assertRedirect();

        $this->assertSame(2, Appointment::count());
    }

    public function test_rescheduling_into_a_conflicting_slot_is_rejected(): void
    {
        $this->actingAs($this->owner)->post('/appointments', [
            'customer_id' => $this->customer->id,
            'employee_id' => $this->employee->id,
            'service_id' => $this->service->id,
            'starts_at' => '2026-08-05 14:00:00',
        ]);
        $second = Appointment::latest('id')->first();

        $response = $this->actingAs($this->owner)->patch("/appointments/{$second->id}/reschedule", [
            'starts_at' => '2026-08-05 10:15:00',
        ]);

        $response->assertSessionHasErrors('starts_at');
        $this->assertSame('2026-08-05 14:00:00', $second->fresh()->starts_at->format('Y-m-d H:i:s'));
    }
}
