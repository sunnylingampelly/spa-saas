<?php

namespace App\Domain\Marketing\Actions;

use App\Domain\Marketing\Jobs\SendCampaignEmailJob;
use App\Domain\Marketing\Models\EmailCampaign;
use App\Domain\Marketing\Models\EmailCampaignRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendEmailCampaignAction
{
    public function __construct(private readonly BuildCampaignAudienceAction $audience) {}

    public function execute(EmailCampaign $campaign): EmailCampaign
    {
        if ($campaign->status !== 'draft') {
            throw ValidationException::withMessages(['campaign' => 'This campaign has already been sent.']);
        }

        $customers = $this->audience->query($campaign->audience_filter)->get(['id', 'email']);

        if ($customers->isEmpty()) {
            throw ValidationException::withMessages(['campaign' => "No customers match this campaign's audience — nothing to send."]);
        }

        // Recipient rows are committed first, and jobs are only dispatched once that
        // transaction has actually committed — dispatching from inside an open transaction
        // risks a queue worker (polling independently) picking a job up before the recipient
        // row it depends on is even visible yet.
        $recipients = DB::transaction(function () use ($campaign, $customers) {
            $recipients = $customers->map(fn ($customer) => EmailCampaignRecipient::create([
                'email_campaign_id' => $campaign->id,
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'tracking_token' => Str::random(40),
            ]));

            $campaign->update([
                'status' => 'sending',
                'sent_at' => now(),
                'recipients_count' => $recipients->count(),
            ]);

            return $recipients;
        });

        $recipients->each(fn (EmailCampaignRecipient $recipient) => SendCampaignEmailJob::dispatch($recipient));

        return $campaign->fresh();
    }
}
