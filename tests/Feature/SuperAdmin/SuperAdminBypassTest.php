<?php

namespace Tests\Feature\SuperAdmin;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminBypassTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_can_see_spas_across_every_tenant(): void
    {
        $ownerA = User::factory()->create();
        $ownerA->assignRole('spa_owner');
        $this->actingAs($ownerA)->post('/onboarding/create-spa', ['name' => 'Spa A', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $ownerB = User::factory()->create();
        $ownerB->assignRole('spa_owner');
        $this->actingAs($ownerB)->post('/onboarding/create-spa', ['name' => 'Spa B', 'phone' => '9876543210', 'state' => 'Karnataka']);

        $admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $this->assertSame(2, Spa::withoutGlobalScopes()->count());
    }

    public function test_a_spa_owner_cannot_access_the_super_admin_area(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');

        $response = $this->actingAs($owner)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_a_super_admin_is_not_forced_through_spa_context(): void
    {
        $admin = User::factory()->create(['two_factor_confirmed_at' => now()]);
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_a_super_admin_without_two_factor_enabled_is_redirected_to_set_it_up(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertRedirect(route('admin.two-factor.setup'));
    }

    public function test_the_two_factor_setup_page_itself_stays_reachable_without_two_factor_enabled(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('admin.two-factor.setup'))->assertOk();
    }
}
