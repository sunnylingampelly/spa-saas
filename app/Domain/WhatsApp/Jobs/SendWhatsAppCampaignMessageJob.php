<?php

namespace App\Domain\WhatsApp\Jobs;

use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppCampaignRecipient;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\WhatsApp\Services\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Mirrors App\Domain\Marketing\Jobs\SendCampaignEmailJob exactly — same "already handled, never
 * re-send" guard, same success/failure bookkeeping shape.
 */
class SendWhatsAppCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly WhatsAppCampaignRecipient $recipient) {}

    public function handle(): void
    {
        $recipient = $this->recipient->fresh();

        if (! $recipient || $recipient->status !== 'pending') {
            return;
        }

        $campaign = $recipient->campaign;
        $template = $campaign->template;

        try {
            $result = WhatsAppClient::forSpa($campaign->spa)->sendTemplateMessage(
                $recipient->phone_number,
                $template->name,
                $template->language,
                $this->resolveComponents($campaign, $template, $recipient),
            );

            $recipient->update(['status' => 'sent', 'sent_at' => now(), 'meta_message_id' => $result['message_id']]);
            $campaign->increment('sent_count');
        } catch (Throwable $e) {
            $recipient->update([
                'status' => 'failed',
                'error_message' => substr($e->getMessage(), 0, 500),
            ]);
            $campaign->increment('failed_count');
        }
    }

    /**
     * @return array<int, array{type: string, parameters: array}>
     */
    private function resolveComponents(WhatsAppCampaign $campaign, WhatsAppTemplate $template, WhatsAppCampaignRecipient $recipient): array
    {
        if ($template->variableCount() === 0) {
            return [];
        }

        $parameters = collect($campaign->variable_values)->map(function (array $config) use ($recipient) {
            $value = match ($config['source'] ?? 'static') {
                'customer_name' => $recipient->customer?->name ?? 'there',
                default => (string) ($config['value'] ?? ''),
            };

            return ['type' => 'text', 'text' => $value];
        })->all();

        return [['type' => 'body', 'parameters' => $parameters]];
    }
}
