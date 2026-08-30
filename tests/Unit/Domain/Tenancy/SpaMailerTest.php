<?php

namespace Tests\Unit\Domain\Tenancy;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\Tenancy\Services\SpaMailer;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaMailerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createSpa(string $name, array $smtpFields = []): Spa
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => $name, 'phone' => '9876543210', 'state' => 'Karnataka']);

        $spa = $owner->spas()->firstOrFail();
        if ($smtpFields) {
            $spa->update($smtpFields);
        }

        return $spa->fresh();
    }

    public function test_a_spa_with_no_smtp_host_resolves_to_the_platform_mailer(): void
    {
        $spa = $this->createSpa('No SMTP Spa');

        $this->assertSame(env('MAIL_MAILER', 'log'), SpaMailer::mailerFor($spa));
        $this->assertFalse(SpaMailer::isConfigured($spa));
    }

    public function test_a_spa_with_smtp_configured_resolves_to_a_distinct_mailer_registered_with_its_own_settings(): void
    {
        $spa = $this->createSpa('Configured SMTP Spa', [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => '2525',
            'smtp_username' => 'someone',
            'smtp_password' => 'secret',
            'smtp_encryption' => 'tls',
        ]);

        $name = SpaMailer::mailerFor($spa);

        $this->assertSame("spa_smtp_{$spa->id}", $name);
        $this->assertTrue(SpaMailer::isConfigured($spa));
        $this->assertSame('smtp.example.com', config("mail.mailers.{$name}.host"));
        $this->assertSame('2525', config("mail.mailers.{$name}.port"));
        $this->assertSame('someone', config("mail.mailers.{$name}.username"));
        $this->assertSame('secret', config("mail.mailers.{$name}.password"));
        $this->assertSame('tls', config("mail.mailers.{$name}.encryption"));
    }

    public function test_two_different_spas_never_resolve_to_the_same_mailer_name(): void
    {
        $spaA = $this->createSpa('Spa A', ['smtp_host' => 'smtp.a.example.com']);
        $spaB = $this->createSpa('Spa B', ['smtp_host' => 'smtp.b.example.com']);

        $nameA = SpaMailer::mailerFor($spaA);
        $nameB = SpaMailer::mailerFor($spaB);

        $this->assertNotSame($nameA, $nameB);
        $this->assertSame('smtp.a.example.com', config("mail.mailers.{$nameA}.host"));
        $this->assertSame('smtp.b.example.com', config("mail.mailers.{$nameB}.host"));
    }
}
