<?php

namespace Tests\Feature\Marketing;

use App\Domain\Marketing\Jobs\SendCampaignEmailJob;
use App\Domain\Marketing\Mail\CampaignMail;
use App\Domain\Marketing\Models\EmailCampaign;
use App\Domain\Marketing\Models\EmailCampaignRecipient;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Database\Factories\CustomerFactory;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailCampaignSendingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private int $spaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('spa_owner');
        $this->actingAs($this->owner)->post('/onboarding/create-spa', ['name' => 'Radiance Day Spa', 'phone' => '9876543210', 'state' => 'Karnataka']);
        $this->spaId = Spa::withoutGlobalScopes()->firstOrFail()->id;
    }

    private function createDraftCampaign(array $audienceFilter = ['type' => 'all']): EmailCampaign
    {
        $this->actingAs($this->owner)->post('/email-campaigns', [
            'name' => 'August Promo',
            'subject' => 'A little something for you',
            'body_html' => '<p>Hi {{customer_name}}, enjoy 20% off!</p><p><a href="https://example.com/book">Book now</a></p>',
            'audience_filter' => $audienceFilter,
        ])->assertRedirect();

        return EmailCampaign::firstOrFail();
    }

    public function test_creating_a_campaign_saves_it_as_a_draft(): void
    {
        $campaign = $this->createDraftCampaign();

        $this->assertSame('draft', $campaign->status);
        $this->assertSame(0, $campaign->recipients_count);
    }

    public function test_sending_creates_one_pending_recipient_per_matching_customer_and_dispatches_a_job_each(): void
    {
        Queue::fake();

        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'email' => 'anjali@example.com']);
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Rohan', 'email' => 'rohan@example.com']);
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'No Email', 'email' => null]);

        $campaign = $this->createDraftCampaign();

        $this->actingAs($this->owner)->post("/email-campaigns/{$campaign->id}/send")->assertRedirect();

        $campaign->refresh();
        $this->assertSame('sending', $campaign->status);
        $this->assertSame(2, $campaign->recipients_count);
        $this->assertSame(2, EmailCampaignRecipient::count());
        $this->assertSame(2, EmailCampaignRecipient::where('status', 'pending')->count());
        $this->assertSame(2, EmailCampaignRecipient::distinct()->count('tracking_token'));

        Queue::assertPushed(SendCampaignEmailJob::class, 2);
    }

    public function test_sending_an_already_sent_campaign_is_rejected(): void
    {
        Queue::fake();
        CustomerFactory::new()->create(['spa_id' => $this->spaId, 'email' => 'anjali@example.com']);

        $campaign = $this->createDraftCampaign();
        $this->actingAs($this->owner)->post("/email-campaigns/{$campaign->id}/send");

        $response = $this->actingAs($this->owner)->post("/email-campaigns/{$campaign->id}/send");

        $response->assertSessionHasErrors('campaign');
        $this->assertSame(1, EmailCampaignRecipient::count(), 'A second send must never create duplicate recipients.');
    }

    public function test_sending_to_an_empty_audience_is_rejected_and_creates_nothing(): void
    {
        $campaign = $this->createDraftCampaign(['type' => 'vip']); // no VIP customers exist

        $response = $this->actingAs($this->owner)->post("/email-campaigns/{$campaign->id}/send");

        $response->assertSessionHasErrors('campaign');
        $this->assertSame('draft', $campaign->fresh()->status);
        $this->assertSame(0, EmailCampaignRecipient::count());
    }

    public function test_the_job_sends_the_mail_and_marks_the_recipient_and_campaign_sent(): void
    {
        Mail::fake();

        $customer = CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'email' => 'anjali@example.com']);
        $campaign = $this->createDraftCampaign();
        $recipient = EmailCampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'tracking_token' => str_repeat('a', 40),
        ]);

        (new SendCampaignEmailJob($recipient))->handle();

        Mail::assertSent(CampaignMail::class, fn ($mail) => $mail->recipient->is($recipient));

        $recipient->refresh();
        $this->assertSame('sent', $recipient->status);
        $this->assertNotNull($recipient->sent_at);
        $this->assertSame(1, $campaign->fresh()->sent_count);
    }

    public function test_a_send_failure_marks_the_recipient_failed_and_increments_bounced_count(): void
    {
        // Force a real send failure via this spa's own SMTP settings (an unreachable host) —
        // SendCampaignEmailJob resolves the mailer per-spa now, so a broken *global* mailer
        // config would no longer be reached at all.
        Spa::withoutGlobalScopes()->find($this->spaId)->update([
            'smtp_host' => '127.0.0.1',
            'smtp_port' => '1',
        ]);

        $customer = CustomerFactory::new()->create(['spa_id' => $this->spaId, 'name' => 'Anjali', 'email' => 'anjali@example.com']);
        $campaign = $this->createDraftCampaign();
        $recipient = EmailCampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'tracking_token' => str_repeat('b', 40),
        ]);

        (new SendCampaignEmailJob($recipient))->handle();

        $recipient->refresh();
        $this->assertSame('failed', $recipient->status);
        $this->assertNotNull($recipient->error_message);
        $this->assertSame(1, $campaign->fresh()->bounced_count);
        $this->assertSame(0, $campaign->fresh()->sent_count);
    }

    public function test_a_recipient_already_processed_is_never_resent(): void
    {
        Mail::fake();

        $customer = CustomerFactory::new()->create(['spa_id' => $this->spaId, 'email' => 'anjali@example.com']);
        $campaign = $this->createDraftCampaign();
        $recipient = EmailCampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'customer_id' => $customer->id,
            'email' => $customer->email,
            'tracking_token' => str_repeat('c', 40),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        (new SendCampaignEmailJob($recipient))->handle();

        Mail::assertNotSent(CampaignMail::class);
        $this->assertSame(0, $campaign->fresh()->sent_count);
    }
}
