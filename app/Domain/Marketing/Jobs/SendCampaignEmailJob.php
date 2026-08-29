<?php

namespace App\Domain\Marketing\Jobs;

use App\Domain\Marketing\Mail\CampaignMail;
use App\Domain\Marketing\Models\EmailCampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly EmailCampaignRecipient $recipient) {}

    public function handle(): void
    {
        $recipient = $this->recipient->fresh();

        // Already handled (retried after a partial failure, or the campaign/recipient was
        // removed since this job was queued) — never re-send to someone already marked sent.
        if (! $recipient || $recipient->status !== 'pending') {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new CampaignMail($recipient));

            $recipient->update(['status' => 'sent', 'sent_at' => now()]);
            $recipient->campaign()->increment('sent_count');
        } catch (Throwable $e) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);
            $recipient->campaign()->increment('bounced_count');
        }
    }
}
