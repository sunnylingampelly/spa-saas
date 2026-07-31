<?php

namespace Tests\Feature\Employees;

use App\Domain\Employees\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
    }

    public function test_an_owner_can_create_an_employee_with_a_generated_code(): void
    {
        $response = $this->actingAs($this->owner)->post('/employees', [
            'name' => 'Anita Rao',
            'phone' => '9876543210',
            'department' => 'Therapy',
            'designation' => 'Senior Therapist',
        ]);

        $employee = Employee::where('name', 'Anita Rao')->first();

        $this->assertNotNull($employee);
        $this->assertSame('EMP-0001', $employee->employee_code);
        $this->assertSame('active', $employee->status);
        $response->assertRedirect(route('employees.show', $employee));
    }

    public function test_employee_codes_increment_per_spa(): void
    {
        $this->actingAs($this->owner)->post('/employees', ['name' => 'First Employee']);
        $this->actingAs($this->owner)->post('/employees', ['name' => 'Second Employee']);

        $this->assertDatabaseHas('employees', ['name' => 'First Employee', 'employee_code' => 'EMP-0001']);
        $this->assertDatabaseHas('employees', ['name' => 'Second Employee', 'employee_code' => 'EMP-0002']);
    }

    public function test_an_owner_can_update_an_employee(): void
    {
        $this->actingAs($this->owner)->post('/employees', ['name' => 'Anita Rao']);
        $employee = Employee::first();

        $this->actingAs($this->owner)->put("/employees/{$employee->id}", [
            'name' => 'Anita Rao',
            'designation' => 'Lead Therapist',
        ])->assertRedirect(route('employees.show', $employee));

        $this->assertSame('Lead Therapist', $employee->fresh()->designation);
    }

    public function test_an_owner_can_toggle_employee_status(): void
    {
        $this->actingAs($this->owner)->post('/employees', ['name' => 'Anita Rao']);
        $employee = Employee::first();

        $this->actingAs($this->owner)->patch("/employees/{$employee->id}/status", ['status' => 'inactive']);

        $this->assertSame('inactive', $employee->fresh()->status);
    }

    public function test_an_owner_can_delete_an_employee(): void
    {
        $this->actingAs($this->owner)->post('/employees', ['name' => 'Anita Rao']);
        $employee = Employee::first();

        $this->actingAs($this->owner)->delete("/employees/{$employee->id}")
            ->assertRedirect(route('employees.index'));

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }
}
