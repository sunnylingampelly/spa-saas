<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_user_can_register_and_is_assigned_the_spa_owner_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $user = User::where('email', 'priya@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('spa_owner'));
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect();
    }

    public function test_a_newly_registered_user_with_no_spa_is_redirected_to_onboarding(): void
    {
        $this->post('/register', [
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response = $this->get('/dashboard');

        $response->assertRedirect(route('onboarding.create-spa.show'));
    }

    public function test_registering_with_an_email_already_in_use_shows_a_clear_message(): void
    {
        User::factory()->create(['email' => 'priya@example.com']);

        $response = $this->post('/register', [
            'name' => 'Someone Else',
            'email' => 'priya@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'This email is already registered — try logging in instead.',
        ]);
        $this->assertGuest();
    }
}
