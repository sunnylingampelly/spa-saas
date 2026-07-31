<?php

namespace Tests\Feature\Auth;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_rejects_a_password_that_is_too_short_or_lacks_complexity(): void
    {
        $response = $this->post('/register', [
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'password' => 'lowercase', // no digits, no uppercase, and under 10 chars
            'password_confirmation' => 'lowercase',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_accepts_a_password_meeting_the_policy(): void
    {
        $response = $this->post('/register', [
            'name' => 'Priya Sharma',
            'email' => 'priya@example.com',
            'password' => 'Sunrise2026',
            'password_confirmation' => 'Sunrise2026',
        ]);

        $response->assertSessionDoesntHaveErrors('password');
        $this->assertAuthenticated();
    }
}
