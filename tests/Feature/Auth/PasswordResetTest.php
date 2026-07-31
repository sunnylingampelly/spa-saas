<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_reset_link_sends_the_notification_with_a_working_token(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->post('/forgot-password', ['email' => 'owner@example.com'])->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_reset_link_actually_changes_the_password_and_allows_login(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'password' => bcrypt('OldPassword123')]);

        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'owner@example.com',
            'password' => 'BrandNewPass99',
            'password_confirmation' => 'BrandNewPass99',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('BrandNewPass99', $user->fresh()->password));

        $this->post('/login', ['email' => 'owner@example.com', 'password' => 'BrandNewPass99'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_an_invalid_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'password' => bcrypt('OldPassword123')]);

        $this->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'owner@example.com',
            'password' => 'BrandNewPass99',
            'password_confirmation' => 'BrandNewPass99',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('OldPassword123', $user->fresh()->password));
    }
}
