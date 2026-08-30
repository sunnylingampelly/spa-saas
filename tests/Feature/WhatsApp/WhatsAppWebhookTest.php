<?php

namespace Tests\Feature\WhatsApp;

use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppCampaignRecipient;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Spa $spa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spa = $owner->spas()->firstOrFail();
    }

    public function test_the_get_handshake_echoes_the_challenge_back_when_the_token_matches(): void
    {
        $url = route('webhooks.whatsapp.verify', $this->spa->whatsapp_webhook_token);

        $response = $this->get("{$url}?hub_mode=subscribe&hub_verify_token={$this->spa->whatsapp_webhook_token}&hub_challenge=12345");

        $response->assertOk();
        $response->assertSeeText('12345');
    }

    public function test_the_get_handshake_is_rejected_when_the_token_does_not_match(): void
    {
        $url = route('webhooks.whatsapp.verify', $this->spa->whatsapp_webhook_token);

        $response = $this->get("{$url}?hub_mode=subscribe&hub_verify_token=wrong-token&hub_challenge=12345");

        $response->assertStatus(403);
    }

    private function statusPayload(string $messageId, string $status): array
    {
        return [
            'entry' => [[
                'id' => 'waba-1',
                'changes' => [[
                    'field' => 'messages',
                    'value' => ['statuses' => [['id' => $messageId, 'status' => $status]]],
                ]],
            ]],
        ];
    }

    private function createRecipient(string $status = 'sent'): WhatsAppCampaignRecipient
    {
        $template = WhatsAppTemplate::create([
            'spa_id' => $this->spa->id, 'name' => 'promo', 'category' => 'marketing',
            'language' => 'en', 'body_text' => 'Hi there', 'status' => 'approved',
        ]);
        $campaign = WhatsAppCampaign::create([
            'spa_id' => $this->spa->id, 'created_by_user_id' => $this->spa->owner_user_id,
            'whatsapp_template_id' => $template->id, 'name' => 'Promo',
            'variable_values' => [], 'audience_filter' => ['type' => 'all'], 'status' => 'sending',
        ]);

        return WhatsAppCampaignRecipient::create([
            'spa_id' => $this->spa->id, 'whatsapp_campaign_id' => $campaign->id,
            'phone_number' => '919000000001', 'meta_message_id' => 'wamid.ABC',
            'status' => $status, 'sent_at' => now(),
        ]);
    }

    public function test_a_delivered_status_update_marks_the_recipient_delivered_and_increments_the_campaigns_counter(): void
    {
        $recipient = $this->createRecipient('sent');
        $url = route('webhooks.whatsapp.receive', $this->spa->whatsapp_webhook_token);

        $this->postJson($url, $this->statusPayload('wamid.ABC', 'delivered'))->assertOk();

        $recipient->refresh();
        $this->assertSame('delivered', $recipient->status);
        $this->assertNotNull($recipient->delivered_at);
        $this->assertSame(1, $recipient->campaign->fresh()->delivered_count);
    }

    public function test_a_redelivered_status_event_never_double_increments_the_counter(): void
    {
        $recipient = $this->createRecipient('sent');
        $url = route('webhooks.whatsapp.receive', $this->spa->whatsapp_webhook_token);

        $this->postJson($url, $this->statusPayload('wamid.ABC', 'delivered'))->assertOk();
        $this->postJson($url, $this->statusPayload('wamid.ABC', 'delivered'))->assertOk(); // Meta redelivers the same event

        $this->assertSame(1, $recipient->campaign->fresh()->delivered_count);
    }

    public function test_read_after_delivered_advances_correctly(): void
    {
        $recipient = $this->createRecipient('delivered');
        $url = route('webhooks.whatsapp.receive', $this->spa->whatsapp_webhook_token);

        $this->postJson($url, $this->statusPayload('wamid.ABC', 'read'))->assertOk();

        $recipient->refresh();
        $this->assertSame('read', $recipient->status);
        $this->assertNotNull($recipient->read_at);
        $this->assertSame(1, $recipient->campaign->fresh()->read_count);
    }

    public function test_a_failed_event_is_ignored_once_the_message_was_already_delivered(): void
    {
        $recipient = $this->createRecipient('delivered');
        $url = route('webhooks.whatsapp.receive', $this->spa->whatsapp_webhook_token);

        $this->postJson($url, $this->statusPayload('wamid.ABC', 'failed'))->assertOk();

        $recipient->refresh();
        $this->assertSame('delivered', $recipient->status, 'A message already delivered cannot later have failed to arrive.');
        $this->assertSame(0, $recipient->campaign->fresh()->failed_count);
    }

    public function test_a_template_status_update_marks_the_local_template_rejected_with_the_reason(): void
    {
        $template = WhatsAppTemplate::create([
            'spa_id' => $this->spa->id, 'meta_template_id' => 'meta-999', 'name' => 'promo',
            'category' => 'marketing', 'language' => 'en', 'body_text' => 'Hi there', 'status' => 'pending',
        ]);

        $url = route('webhooks.whatsapp.receive', $this->spa->whatsapp_webhook_token);

        $this->postJson($url, [
            'entry' => [[
                'id' => 'waba-1',
                'changes' => [[
                    'field' => 'message_template_status_update',
                    'value' => ['message_template_id' => 'meta-999', 'event' => 'REJECTED', 'reason' => 'INVALID_FORMAT'],
                ]],
            ]],
        ])->assertOk();

        $template->refresh();
        $this->assertSame('rejected', $template->status);
        $this->assertSame('INVALID_FORMAT', $template->rejected_reason);
    }
}
