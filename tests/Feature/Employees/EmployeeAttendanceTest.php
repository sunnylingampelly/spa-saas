<?php

namespace Tests\Feature\Employees;

use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Repositories\EmployeeAttendanceRepositoryInterface;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAttendanceTest extends TestCase
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

    public function test_attendance_can_be_marked_in_bulk(): void
    {
        $this->actingAs($this->owner)->post('/employees/attendance', [
            'attendance_date' => now()->toDateString(),
            'entries' => [
                ['employee_id' => $this->employee->id, 'status' => 'present'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $this->employee->id,
            'status' => 'present',
        ]);
    }

    public function test_marking_attendance_twice_for_the_same_day_updates_not_duplicates(): void
    {
        $date = now()->toDateString();

        $this->actingAs($this->owner)->post('/employees/attendance', [
            'attendance_date' => $date,
            'entries' => [['employee_id' => $this->employee->id, 'status' => 'present']],
        ]);

        $this->actingAs($this->owner)->post('/employees/attendance', [
            'attendance_date' => $date,
            'entries' => [['employee_id' => $this->employee->id, 'status' => 'absent']],
        ]);

        $this->assertSame(1, $this->employee->attendances()->count());
        $this->assertSame('absent', $this->employee->attendances()->first()->status);
    }

    public function test_attendance_summary_counts_by_status(): void
    {
        $this->actingAs($this->owner)->post('/employees/attendance', [
            'attendance_date' => now()->subDay()->toDateString(),
            'entries' => [['employee_id' => $this->employee->id, 'status' => 'present']],
        ]);
        $this->actingAs($this->owner)->post('/employees/attendance', [
            'attendance_date' => now()->toDateString(),
            'entries' => [['employee_id' => $this->employee->id, 'status' => 'present']],
        ]);

        $summary = app(EmployeeAttendanceRepositoryInterface::class)
            ->summaryForEmployee($this->employee->id, now()->subDays(30), now());

        $this->assertSame(2, $summary['present']);
    }
}
