<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Services\Models\Service;
use App\Domain\Services\Policies\ServicePolicy;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerA;

    private User $ownerB;

    private Service $serviceA;

    private Service $serviceB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ownerA = User::factory()->create();
        $this->ownerA->assignRole('spa_owner');
        $this->actingAs($this->ownerA)->post('/onboarding/create-spa', ['name' => 'Spa A', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerA)->post('/services', ['name' => 'Service A', 'duration_minutes' => 60, 'price' => 1000]);
        $this->serviceA = Service::withoutGlobalScopes()->where('name', 'Service A')->firstOrFail();

        $this->ownerB = User::factory()->create();
        $this->ownerB->assignRole('spa_owner');
        $this->actingAs($this->ownerB)->post('/onboarding/create-spa', ['name' => 'Spa B', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->actingAs($this->ownerB)->post('/services', ['name' => 'Service B', 'duration_minutes' => 60, 'price' => 1000]);
        $this->serviceB = Service::withoutGlobalScopes()->where('name', 'Service B')->firstOrFail();
    }

    public function test_the_tenant_scope_only_returns_the_current_spas_services(): void
    {
        $this->actingAs($this->ownerA)->get('/dashboard');

        $visible = Service::all();

        $this->assertCount(1, $visible);
        $this->assertSame('Service A', $visible->first()->name);
    }

    public function test_owner_a_is_denied_policy_authorization_over_service_b(): void
    {
        $policy = new ServicePolicy;

        $this->assertFalse($policy->view($this->ownerA, $this->serviceB));
        $this->assertTrue($policy->view($this->ownerA, $this->serviceA));
    }

    public function test_owner_a_cannot_edit_service_b_via_http(): void
    {
        // See EmployeeTenantIsolationTest::test_owner_a_cannot_view_employee_b_via_http —
        // SubstituteBindings resolves the route model before spa.context sets the tenant
        // scope, so ServicePolicy is what denies this (403), not scope invisibility (404).
        $this->actingAs($this->ownerA)->get("/services/{$this->serviceB->id}/edit")->assertForbidden();
    }

    public function test_super_admin_can_see_services_across_every_tenant(): void
    {
        $this->assertSame(2, Service::withoutGlobalScopes()->count());
    }
}
