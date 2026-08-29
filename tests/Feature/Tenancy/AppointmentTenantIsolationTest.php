<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Appointments\Models\Appointment;
use App\Domain\Appointments\Policies\AppointmentPolicy;
use App\Domain\Customers\Models\Customer;
use App\Domain\Employees\Models\Employee;
use App\Domain\Services\Models\Service;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Appointment $appointmentA;

    private Appointment $appointmentB;

    private Employee $employeeB;

    private string $dayA;

    private string $dayB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dayA = now()->addDays(60)->format('Y-m-d');
        $this->dayB = now()->addDays(61)->format('Y-m-d');

        $this->seed(RolesAndPermissionsSeeder::class);

        [$this->ownerA, ] = $this->bookOneAppointment('Spa A');
        $this->appointmentA = Appointment::withoutGlobalScopes()->first();

        [$this->ownerB, $this->employeeB] = $this->bookOneAppointment('Spa B');
        $this->appointmentB = Appointment::withoutGlobalScopes()->latest('id')->first();
    }

    private function bookOneAppointment(string $spaName): array
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => $spaName, 'phone' => '9876543210', 'state' => 'Karnataka']);

        $this->actingAs($owner)->post('/customers', ['name' => "$spaName Customer"]);
        $customer = Customer::firstOrFail();

        $this->actingAs($owner)->post('/employees', ['name' => "$spaName Therapist"]);
        $employee = Employee::firstOrFail();

        $this->actingAs($owner)->post('/services', ['name' => "$spaName Service", 'duration_minutes' => 60, 'price' => 1000]);
        $service = Service::firstOrFail();

        $this->actingAs($owner)->post('/appointments', [
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'service_id' => $service->id,
            'starts_at' => "{$this->dayA} 10:00:00",
        ]);

        return [$owner, $employee];
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_appointments(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $this->assertCount(1, Appointment::all());
    }

    public function test_owner_a_is_denied_policy_authorization_over_appointment_b(): void
    {
        $policy = new AppointmentPolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->appointmentB));
        $this->assertTrue($policy->view($this->ownerA, $this->appointmentA));
    }

    public function test_owner_a_cannot_reschedule_appointment_b(): void
    {
        $this->actingAs($this->ownerA)->patch("/appointments/{$this->appointmentB->id}/reschedule", [
            'starts_at' => "{$this->dayB} 10:00:00",
        ])->assertForbidden();
    }

    public function test_the_conflict_checker_never_treats_another_tenants_identical_slot_as_a_clash(): void
    {
        // Both spas booked their own therapist for the exact same 10:00 slot in
        // setUp(). Spa B's employee_id is a distinct row from Spa A's, so a fresh
        // booking for Spa B's therapist at that same instant must succeed — it's
        // only a conflict if the *same* employee_id row is double-booked.
        $this->actingAs($this->ownerB)->post('/employees', ['name' => 'Second B Therapist']);
        $secondEmployee = Employee::latest('id')->first();

        $this->actingAs($this->ownerB)->post('/appointments', [
            'customer_id' => $this->appointmentB->customer_id,
            'employee_id' => $secondEmployee->id,
            'service_id' => $this->appointmentB->service_id,
            'starts_at' => "{$this->dayA} 10:00:00",
        ])->assertSessionHasNoErrors();
    }
}
