<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SpaEmailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Spa $spa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Test Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = $this->owner->spas()->firstOrFail();
    }

    private function validPayload(): array
    {
        return [
            'smtp_host' => 'smtp.mailtrap.io',
            'smtp_port' => '587',
            'smtp_username' => 'owner_username',
            'smtp_password' => 'owner_super_secret',
            'smtp_encryption' => 'tls',
            'mail_from_address' => 'hello@radiancespa.example.com',
            'mail_from_name' => 'Radiance Day Spa',
        ];
    }

    public function test_owner_can_save_their_own_smtp_credentials(): void
    {
        $this->actingAs($this->owner)->put('/spa/email-settings', $this->validPayload())->assertRedirect();

        $fresh = $this->spa->fresh();
        $this->assertSame('smtp.mailtrap.io', $fresh->smtp_host);
        $this->assertSame('587', $fresh->smtp_port);
        $this->assertSame('owner_username', $fresh->smtp_username);
        $this->assertSame('owner_super_secret', $fresh->smtp_password);
        $this->assertSame('tls', $fresh->smtp_encryption);
        $this->assertSame('hello@radiancespa.example.com', $fresh->mail_from_address);
        $this->assertSame('Radiance Day Spa', $fresh->mail_from_name);
    }

    public function test_the_password_never_appears_in_the_page_props_after_saving(): void
    {
        $this->actingAs($this->owner)->put('/spa/email-settings', $this->validPayload());

        $response = $this->actingAs($this->owner)->get('/spa/profile');

        $response->assertInertia(fn ($page) => $page
            ->where('smtpConfigured', true)
            ->missing('spa.smtp_password')
        );

        $response->assertDontSee('owner_super_secret');
    }

    public function test_submitting_a_blank_password_leaves_the_existing_password_untouched(): void
    {
        $this->actingAs($this->owner)->put('/spa/email-settings', $this->validPayload());

        // Owner updates just the host later, leaving the password blank (as the form always
        // renders it) — the previously saved password must survive untouched.
        $this->actingAs($this->owner)->put('/spa/email-settings', [
            ...$this->validPayload(),
            'smtp_host' => 'smtp.rotated.example.com',
            'smtp_password' => '',
        ]);

        $fresh = $this->spa->fresh();
        $this->assertSame('smtp.rotated.example.com', $fresh->smtp_host);
        $this->assertSame('owner_super_secret', $fresh->smtp_password);
    }

    public function test_disconnect_clears_every_smtp_field(): void
    {
        $this->actingAs($this->owner)->put('/spa/email-settings', $this->validPayload());

        $this->actingAs($this->owner)->delete('/spa/email-settings')->assertRedirect();

        $fresh = $this->spa->fresh();
        $this->assertNull($fresh->smtp_host);
        $this->assertNull($fresh->smtp_port);
        $this->assertNull($fresh->smtp_username);
        $this->assertNull($fresh->smtp_password);
        $this->assertNull($fresh->smtp_encryption);
        $this->assertNull($fresh->mail_from_address);
        $this->assertNull($fresh->mail_from_name);
    }

    public function test_sending_a_test_email_uses_the_spas_own_mailer(): void
    {
        Mail::fake();

        $this->actingAs($this->owner)->put('/spa/email-settings', $this->validPayload());

        $response = $this->actingAs($this->owner)->post('/spa/email-settings/test');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_a_test_email_failure_flashes_the_real_error_instead_of_pretending_it_worked(): void
    {
        $this->actingAs($this->owner)->put('/spa/email-settings', [
            ...$this->validPayload(),
            'smtp_host' => '127.0.0.1',
            'smtp_port' => '1',
        ]);

        $response = $this->actingAs($this->owner)->post('/spa/email-settings/test');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString("Couldn't send a test email", session('error'));
    }
}
