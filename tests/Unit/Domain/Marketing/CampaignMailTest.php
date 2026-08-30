<?php

namespace Tests\Unit\Domain\Marketing;

use App\Domain\Marketing\Mail\CampaignMail;
use App\Domain\Marketing\Models\EmailCampaign;
use App\Domain\Marketing\Models\EmailCampaignRecipient;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignMailTest extends TestCase
{
    use RefreshDatabase;

    private function createRecipient(array $spaFields = []): EmailCampaignRecipient
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $spa = $owner->spas()->firstOrFail();
        if ($spaFields) {
            $spa->update($spaFields);
        }

        $campaign = EmailCampaign::create([
            'spa_id' => $spa->id,
            'created_by_user_id' => $owner->id,
            'name' => 'Test Campaign',
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
            'audience_filter' => ['type' => 'all'],
            'status' => 'draft',
        ]);

        return EmailCampaignRecipient::create([
            'spa_id' => $spa->id,
            'email_campaign_id' => $campaign->id,
            'email' => 'anjali@example.com',
            'tracking_token' => str_repeat('a', 40),
        ]);
    }

    public function test_it_falls_back_to_the_spas_name_and_the_platform_from_address_when_no_custom_from_is_set(): void
    {
        $recipient = $this->createRecipient();

        $envelope = (new CampaignMail($recipient))->envelope();

        $this->assertSame('Radiance Day Spa', $envelope->from->name);
        $this->assertSame(config('mail.from.address'), $envelope->from->address);
    }

    public function test_it_uses_the_spas_own_from_address_and_name_when_set(): void
    {
        $recipient = $this->createRecipient([
            'mail_from_address' => 'hello@radiancespa.example.com',
            'mail_from_name' => 'Radiance Day Spa Team',
        ]);

        $envelope = (new CampaignMail($recipient))->envelope();

        $this->assertSame('Radiance Day Spa Team', $envelope->from->name);
        $this->assertSame('hello@radiancespa.example.com', $envelope->from->address);
    }
}
