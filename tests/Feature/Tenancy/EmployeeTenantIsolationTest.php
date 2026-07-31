<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Employees\Models\Employee;
use App\Domain\Employees\Policies\EmployeePolicy;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Employee $employeeA;

    private Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('spa_owner');
        $this->actingAs($this->ownerA)->post('/onboarding/create-spa', ['name' => 'Spa A', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerA)->post('/employees', ['name' => 'Employee A']);
        $this->employeeA = Employee::withoutGlobalScopes()->where('name', 'Employee A')->firstOrFail();

        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('spa_owner');
        $this->actingAs($this->ownerB)->post('/onboarding/create-spa', ['name' => 'Spa B', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerB)->post('/employees', ['name' => 'Employee B']);
        $this->employeeB = Employee::withoutGlobalScopes()->where('name', 'Employee B')->firstOrFail();
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_employees(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $visible = Employee::all();

        $this->assertCount(1, $visible);
        $this->assertSame('Employee A', $visible->first()->name);
    }

    public function test_owner_a_is_denied_policy_authorization_over_employee_b(): void
    {
        $policy = new EmployeePolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->employeeB));
        $this->assertTrue($policy->view($this->ownerA, $this->employeeA));
    }

    public function test_owner_a_cannot_view_employee_b_via_http(): void
    {
        // Laravel's SubstituteBindings middleware (part of the framework's default 'web'
        // group) resolves route-model-bound {employee} BEFORE our route-specific
        // spa.context middleware sets the TenantContext — so the tenant global scope
        // isn't active yet at binding time and doesn't hide Employee B's row here.
        // The EmployeePolicy is what actually denies this, hence 403 not 404.
        $this->actingAs($this->ownerA)->get("/employees/{$this->employeeB->id}")->assertForbidden();
    }

    public function test_super_admin_can_see_employees_across_every_tenant(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->assertSame(2, Employee::withoutGlobalScopes()->count());
        $this->assertNotNull(Spa::withoutGlobalScopes()->find($this->employeeA->spa_id));
    }
}
