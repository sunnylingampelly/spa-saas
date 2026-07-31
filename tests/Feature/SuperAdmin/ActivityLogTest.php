<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Employees\Models\Employee;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $this->admin->assignRole('super_admin');
    }

    private function createSpaWithEmployee(string $spaName, string $employeeName): Spa
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => $spaName, 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($owner)->post('/employees', ['name' => $employeeName]);

        return Spa::withoutGlobalScopes()->where('name', $spaName)->firstOrFail();
    }

    public function test_the_global_activity_log_is_reachable_and_includes_activity_from_every_spa(): void
    {
        $this->createSpaWithEmployee('Spa A', 'Employee A');
        $this->createSpaWithEmployee('Spa B', 'Employee B');

        $employeeAId = Employee::withoutGlobalScopes()->where('name', 'Employee A')->value('id');
        $employeeBId = Employee::withoutGlobalScopes()->where('name', 'Employee B')->value('id');

        $response = $this->actingAs($this->admin)->get(route('admin.activity.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['subject_type'] === Employee::class && (string) $row['subject_id'] === (string) $employeeAId
            ) && collect($rows)->contains(
                fn ($row) => $row['subject_type'] === Employee::class && (string) $row['subject_id'] === (string) $employeeBId
            ))
        );
    }

    public function test_filtering_by_spa_excludes_every_other_spas_activity(): void
    {
        $spaA = $this->createSpaWithEmployee('Spa A', 'Employee A');
        $this->createSpaWithEmployee('Spa B', 'Employee B');

        $employeeAId = Employee::withoutGlobalScopes()->where('name', 'Employee A')->value('id');
        $employeeBId = Employee::withoutGlobalScopes()->where('name', 'Employee B')->value('id');

        $response = $this->actingAs($this->admin)->get(route('admin.activity.index', ['spa_id' => $spaA->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('activities.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['subject_type'] === Employee::class && (string) $row['subject_id'] === (string) $employeeAId
            ) && ! collect($rows)->contains(
                fn ($row) => $row['subject_type'] === Employee::class && (string) $row['subject_id'] === (string) $employeeBId
            ))
        );
    }
}
