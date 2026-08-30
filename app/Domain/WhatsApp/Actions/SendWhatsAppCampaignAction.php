<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\WhatsApp\Jobs\SendWhatsAppCampaignMessageJob;
use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppCampaignRecipient;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Mirrors App\Domain\Marketing\Actions\SendEmailCampaignAction almost exactly — see that class
 * for why recipient rows are committed before any job is dispatched.
 */
class SendWhatsAppCampaignAction
{
    public function __construct(private readonly BuildWhatsAppAudienceAction $audience) {}

    public function execute(WhatsAppCampaign $campaign): WhatsAppCampaign
    {
        if ($campaign->status !== 'draft') {
            throw ValidationException::withMessages(['campaign' => 'This campaign has already been sent.']);
        }

        if (! $campaign->template->isApproved()) {
            throw ValidationException::withMessages(['campaign' => "This campaign's template isn't approved by Meta yet — it can't be sent."]);
        }

        $customers = $this->audience->query($campaign->audience_filter)->get(['id', 'whatsapp_number', 'phone']);

        if ($customers->isEmpty()) {
            throw ValidationException::withMessages(['campaign' => "No customers match this campaign's audience — nothing to send."]);
        }

        $recipients = DB::transaction(function () use ($campaign, $customers) {
            $recipients = $customers->map(fn ($customer) => WhatsAppCampaignRecipient::create([
                'whatsapp_campaign_id' => $campaign->id,
                'customer_id' => $customer->id,
                'phone_number' => $customer->whatsapp_number ?: $customer->phone,
            ]));

            $campaign->update([
                'status' => 'sending',
                'sent_at' => now(),
                'recipients_count' => $recipients->count(),
            ]);

            return $recipients;
        });

        $recipients->each(fn (WhatsAppCampaignRecipient $recipient) => SendWhatsAppCampaignMessageJob::dispatch($recipient));

        return $campaign->fresh();
    }
}
