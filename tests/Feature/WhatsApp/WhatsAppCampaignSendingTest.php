<?php

namespace Tests\Feature\WhatsApp;

use App\Domain\WhatsApp\Jobs\SendWhatsAppCampaignMessageJob;
use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppCampaignRecipient;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppCampaignSendingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private int $spaId;

    private WhatsAppTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $spa = $this->owner->spas()->firstOrFail();
        $this->spaId = $spa->id;

        $spa->update(['whatsapp_phone_number_id' => '123456', 'whatsapp_access_token' => 'secret-token']);

        $this->template = WhatsAppTemplate::create([
            'spa_id' => $this->spaId,
            'meta_template_id' => 'meta-1',
            'name' => 'festive_offer',
            'category' => 'marketing',
            'language' => 'en',
            'body_text' => 'Hi {{1}}, enjoy {{2}} off!',
            'status' => 'approved',
        ]);
    }

    private function createDraftCampaign(array $audienceFilter = ['type' => 'all']): WhatsAppCampaign
    {
        $this->actingAs($this->owner)->post('/whatsapp-campaigns', [
            'name' => 'August Promo',
            'whatsapp_template_id' => $this->template->id,
            'variable_values' => [
                ['source' => 'customer_name', 'value' => ''],
                ['source' => 'static', 'value' => '20%'],
            ],
            'audience_filter' => $audienceFilter,
        ])->assertRedirect();

        return WhatsAppCampaign::firstOrFail();
    }

    public function test_creating_a_campaign_saves_it_as_a_draft(): void
    {
        $campaign = $this->createDraftCampaign();

        $this->assertSame('draft', $campaign->status);
        $this->assertSame(0, $campaign->recipients_count);
    }

    public function test_a_pending_template_cannot_be_attached_to_a_campaign(): void
    {
        $this->template->update(['status' => 'pending']);

        $response = $this->actingAs($this->owner)->post('/whatsapp-campaigns', [
            'name' => 'August Promo',
            'whatsapp_template_id' => $this->template->id,
            'variable_values' => [],
            'audience_filter' => ['type' => 'all'],
        ]);

        // findOrFail/approval-check happens inside the action, not the request validator —
        // Laravel's own ValidationException handling redirects back with the campaign error.
        $response->assertSessionHasErrors('whatsapp_template_id');
        $this->assertSame(0, WhatsAppCampaign::count());
    }

    public function test_sending_creates_one_pending_recipient_per_matching_customer_and_dispatches_a_job_each(): void
    {
        Queue::fake();

        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'whatsapp_number' => '919000000001']);
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Rohan', 'phone' => '919000000002', 'whatsapp_number' => null]);
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'No Number', 'phone' => null, 'whatsapp_number' => null]);

        $campaign = $this->createDraftCampaign();

        $this->actingAs($this->owner)->post("/whatsapp-campaigns/{$campaign->id}/send")->assertRedirect();

        $campaign->refresh();
        $this->assertSame('sending', $campaign->status);
        $this->assertSame(2, $campaign->recipients_count);
        $this->assertSame(2, WhatsAppCampaignRecipient::count());
        $this->assertSame(2, WhatsAppCampaignRecipient::where('status', 'pending')->count());

        Queue::assertPushed(SendWhatsAppCampaignMessageJob::class, 2);
    }

    public function test_sending_an_already_sent_campaign_is_rejected(): void
    {
        Queue::fake();
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'whatsapp_number' => '919000000001']);

        $campaign = $this->createDraftCampaign();
        $this->actingAs($this->owner)->post("/whatsapp-campaigns/{$campaign->id}/send");

        $response = $this->actingAs($this->owner)->post("/whatsapp-campaigns/{$campaign->id}/send");

        $response->assertSessionHasErrors('campaign');
        $this->assertSame(1, WhatsAppCampaignRecipient::count(), 'A second send must never create duplicate recipients.');
    }

    public function test_sending_to_an_empty_audience_is_rejected_and_creates_nothing(): void
    {
        $campaign = $this->createDraftCampaign(['type' => 'vip']); // no VIP customers exist

        $response = $this->actingAs($this->owner)->post("/whatsapp-campaigns/{$campaign->id}/send");

        $response->assertSessionHasErrors('campaign');
        $this->assertSame('draft', $campaign->fresh()->status);
        $this->assertSame(0, WhatsAppCampaignRecipient::count());
    }

    public function test_the_job_sends_the_message_and_marks_the_recipient_and_campaign_sent(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.ABC']]], 200)]);

        $customer = CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'whatsapp_number' => '919000000001']);
        $campaign = $this->createDraftCampaign();
        $recipient = WhatsAppCampaignRecipient::create([
            'whatsapp_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'phone_number' => $customer->whatsapp_number,
        ]);

        (new SendWhatsAppCampaignMessageJob($recipient))->handle();

        $recipient->refresh();
        $this->assertSame('sent', $recipient->status);
        $this->assertSame('wamid.ABC', $recipient->meta_message_id);
        $this->assertNotNull($recipient->sent_at);
        $this->assertSame(1, $campaign->fresh()->sent_count);

        Http::assertSent(fn ($request) => $request['template']['components'][0]['parameters'][0]['text'] === 'Anjali'
            && $request['template']['components'][0]['parameters'][1]['text'] === '20%');
    }

    public function test_a_send_failure_marks_the_recipient_failed_and_increments_failed_count(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Recipient is not a valid WhatsApp user']], 400)]);

        $customer = CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'whatsapp_number' => '919000000001']);
        $campaign = $this->createDraftCampaign();
        $recipient = WhatsAppCampaignRecipient::create([
            'whatsapp_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'phone_number' => $customer->whatsapp_number,
        ]);

        (new SendWhatsAppCampaignMessageJob($recipient))->handle();

        $recipient->refresh();
        $this->assertSame('failed', $recipient->status);
        $this->assertStringContainsString('Recipient is not a valid WhatsApp user', $recipient->error_message);
        $this->assertSame(1, $campaign->fresh()->failed_count);
        $this->assertSame(0, $campaign->fresh()->sent_count);
    }

    public function test_the_show_page_renders_with_recipients_loaded(): void
    {
        // A real HTTP hit on the show endpoint — WhatsAppCampaign::recipients() and
        // WhatsAppTemplate::campaigns() both need an explicit foreign key (Eloquent's guess
        // from the class name splits "WhatsApp" into two words), which only a real query
        // through the relation — not just direct model creation — would ever surface.
        Queue::fake();
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'whatsapp_number' => '919000000001']);

        $campaign = $this->createDraftCampaign();
        $this->actingAs($this->owner)->post("/whatsapp-campaigns/{$campaign->id}/send");

        $response = $this->actingAs($this->owner)->get("/whatsapp-campaigns/{$campaign->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('campaign.name', 'August Promo')
            ->where('recipients.data.0.customer.name', 'Anjali')
        );
    }

    public function test_a_recipient_already_processed_is_never_resent(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.X']]], 200)]);

        $customer = CustomerFactory::new()->create(['spa_id' => $this->spaId, 'whatsapp_number' => '919000000001']);
        $campaign = $this->createDraftCampaign();
        $recipient = WhatsAppCampaignRecipient::create([
            'whatsapp_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'phone_number' => $customer->whatsapp_number,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        (new SendWhatsAppCampaignMessageJob($recipient))->handle();

        Http::assertNothingSent();
        $this->assertSame(0, $campaign->fresh()->sent_count);
    }
}
