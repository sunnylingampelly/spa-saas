<?php

namespace Tests\Feature\Marketing;

use App\Domain\Marketing\Models\EmailCampaign;
use App\Domain\Marketing\Models\EmailCampaignRecipient;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailCampaignTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeRecipient(string $token): EmailCampaignRecipient
    {
        $owner = User::factory()->create();
        $owner->assignRole('spa_owner');
        $this->actingAs($owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $spa = Spa::withoutGlobalScopes()->firstOrFail();

        $customer = CustomerFactory::new()->create(['spa_id' => $spa->id, 'email' => 'anjali@example.com']);
        $campaign = EmailCampaign::withoutGlobalScopes()->create([
            'spa_id' => $spa->id,
            'created_by_user_id' => $owner->id,
            'name' => 'Test Campaign',
            'subject' => 'Hello',
            'body_html' => '<p>Hi</p>',
            'audience_filter' => ['type' => 'all'],
            'status' => 'sending',
        ]);

        return EmailCampaignRecipient::withoutGlobalScopes()->create([
            'spa_id' => $spa->id,
            'email_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'tracking_token' => $token,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_the_open_pixel_records_the_first_open_and_returns_a_real_gif(): void
    {
        $recipient = $this->makeRecipient(str_repeat('a', 40));

        $response = $this->get("/email/track/open/{$recipient->tracking_token}");

        $response->assertOk();
        $this->assertSame('image/gif', $response->headers->get('Content-Type'));

        $recipient->refresh();
        $this->assertNotNull($recipient->opened_at);
        $this->assertSame(1, $recipient->open_count);
        $this->assertSame(1, $recipient->campaign->fresh()->opened_count);
    }

    public function test_opening_twice_only_counts_the_campaigns_opened_total_once(): void
    {
        $recipient = $this->makeRecipient(str_repeat('b', 40));

        $this->get("/email/track/open/{$recipient->tracking_token}");
        $this->get("/email/track/open/{$recipient->tracking_token}");

        $recipient->refresh();
        $this->assertSame(2, $recipient->open_count, 'Per-recipient open_count should still increment every time.');
        $this->assertSame(1, $recipient->campaign->fresh()->opened_count, 'Campaign-level opened_count only counts unique opens.');
    }

    public function test_the_click_link_redirects_and_records_the_click(): void
    {
        $recipient = $this->makeRecipient(str_repeat('c', 40));

        $response = $this->get('/email/track/click/'.$recipient->tracking_token.'?url='.urlencode('https://example.com/book'));

        $response->assertRedirect('https://example.com/book');

        $recipient->refresh();
        $this->assertNotNull($recipient->clicked_at);
        $this->assertSame(1, $recipient->campaign->fresh()->clicked_count);
    }

    public function test_a_click_link_refuses_to_redirect_anywhere_but_http_or_https(): void
    {
        $recipient = $this->makeRecipient(str_repeat('d', 40));

        $response = $this->get('/email/track/click/'.$recipient->tracking_token.'?url='.urlencode('javascript:alert(1)'));

        $response->assertNotFound();
        $this->assertNull($recipient->fresh()->clicked_at);
    }

    public function test_unsubscribing_opts_the_customer_out_and_excludes_them_from_future_audiences(): void
    {
        $recipient = $this->makeRecipient(str_repeat('e', 40));

        $response = $this->get("/email/unsubscribe/{$recipient->tracking_token}");

        $response->assertOk();
        $recipient->refresh();
        $this->assertNotNull($recipient->unsubscribed_at);
        $this->assertSame(1, $recipient->campaign->fresh()->unsubscribed_count);

        $customer = $recipient->customer->fresh();
        $this->assertTrue((bool) $customer->marketing_opt_out);
        $this->assertNotNull($customer->marketing_opt_out_at);
    }

    public function test_an_invalid_tracking_token_is_a_clean_404_not_a_server_error(): void
    {
        $this->get('/email/track/open/'.str_repeat('z', 40))->assertNotFound();
        $this->get('/email/track/click/'.str_repeat('z', 40).'?url=https://example.com')->assertNotFound();
        $this->get('/email/unsubscribe/'.str_repeat('z', 40))->assertNotFound();
    }

    public function test_one_recipients_token_can_never_affect_another_recipients_record(): void
    {
        $recipientA = $this->makeRecipient(str_repeat('f', 40));
        $recipientB = $this->makeRecipient(str_repeat('g', 40));

        $this->get("/email/track/open/{$recipientA->tracking_token}");

        $this->assertNotNull($recipientA->fresh()->opened_at);
        $this->assertNull($recipientB->fresh()->opened_at);
    }
}
