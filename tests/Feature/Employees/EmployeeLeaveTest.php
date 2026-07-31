<?php

namespace Tests\Feature\Employees;

use App\Domain\Employees\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeLeaveTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->owner)->post('/employees', ['name' => 'Anita Rao']);
        $this->employee = Employee::firstOrFail();
    }

    public function test_a_leave_can_be_recorded_for_an_employee(): void
    {
        $this->actingAs($this->owner)->post("/employees/{$this->employee->id}/leaves", [
            'leave_type' => 'sick',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'reason' => 'Fever',
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_leaves', [
            'employee_id' => $this->employee->id,
            'leave_type' => 'sick',
            'status' => 'approved',
        ]);
    }

    public function test_end_date_must_not_be_before_start_date(): void
    {
        $response = $this->actingAs($this->owner)->post("/employees/{$this->employee->id}/leaves", [
            'leave_type' => 'casual',
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('end_date');
    }
}
