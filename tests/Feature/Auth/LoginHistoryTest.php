<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_successful_login_is_recorded_in_login_history(): void
    {
        $user = User::factory()->create(['email' => 'priya@example.com', 'password' => Hash::make('password123')]);

        $this->post('/login', ['email' => 'priya@example.com', 'password' => 'password123']);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $user->id,
            'login_method' => 'password',
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('device_histories', [
            'user_id' => $user->id,
        ]);
    }

    public function test_a_failed_login_is_recorded_as_failed(): void
    {
        User::factory()->create(['email' => 'priya@example.com', 'password' => Hash::make('password123')]);

        $this->post('/login', ['email' => 'priya@example.com', 'password' => 'wrong-password']);

        $this->assertDatabaseHas('login_histories', [
            'login_method' => 'password',
            'status' => 'failed',
        ]);
    }
}
