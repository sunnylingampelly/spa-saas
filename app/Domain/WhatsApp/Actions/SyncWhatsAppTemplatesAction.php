<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\WhatsApp\Services\WhatsAppClient;

/**
 * Meta's own template approval happens asynchronously (usually minutes, sometimes up to a day).
 * The webhook's message_template_status_update event keeps this current automatically, but a
 * spa owner shouldn't have to wait for that to land — this gives an immediate manual refresh.
 */
class SyncWhatsAppTemplatesAction
{
    public function execute(Spa $spa): int
    {
        $remote = WhatsAppClient::forSpa($spa)->listTemplates($spa->whatsapp_business_account_id);
        $updated = 0;

        foreach ($remote as $entry) {
            $template = WhatsAppTemplate::where('spa_id', $spa->id)
                ->where('meta_template_id', $entry['id'] ?? null)
                ->first();

            if (! $template || ! isset($entry['status'])) {
                continue;
            }

            $status = strtolower($entry['status']);
            $reason = $entry['rejected_reason'] ?? null;

            if ($template->status !== $status || $template->rejected_reason !== $reason) {
                $template->update(['status' => $status, 'rejected_reason' => $reason]);
                $updated++;
            }
        }

        return $updated;
    }
}
