<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleBasedRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_lands_on_the_admin_dashboard_after_login(): void
    {
        $admin = User::factory()->create(['password' => Hash::make('password123')]);
        $admin->assignRole('super_admin');

        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'password123']);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_stale_intended_url_for_the_wrong_role_does_not_hijack_the_redirect(): void
    {
        // A guest tries to hit the spa-owner dashboard first — Laravel's auth
        // middleware stashes it as the "intended" URL in the session.
        $this->get('/dashboard')->assertRedirect(route('login'));

        $admin = User::factory()->create(['password' => Hash::make('password123')]);
        $admin->assignRole('super_admin');

        // Logging in as super_admin must NOT honor that stale spa-owner intended URL.
        $response = $this->post('/login', ['email' => $admin->email, 'password' => 'password123']);

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_stale_intended_url_for_a_matching_role_is_still_honored(): void
    {
        $owner = User::factory()->create(['password' => Hash::make('password123')]);
        $owner->assignRole('spa_owner');

        $this->get('/spa/profile')->assertRedirect(route('login'));

        $response = $this->post('/login', ['email' => $owner->email, 'password' => 'password123']);

        $response->assertRedirect(route('spa.profile.show'));
    }
}
