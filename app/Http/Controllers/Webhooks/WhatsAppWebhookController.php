<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\WhatsApp\Models\WhatsAppCampaignRecipient;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Each spa's own Meta App points its webhook at this spa's own URL (built from their
 * whatsapp_webhook_token) — there's no shared platform-level Meta App yet (see the BYO-WABA
 * credentials note on WhatsAppClient), so unlike a typical multi-tenant webhook there's nothing
 * to disambiguate: the URL itself already identifies the spa via route-model binding, exactly
 * like RazorpayWebhookController::handleForSpa.
 *
 * This request runs with no active tenant context (no spa.context middleware on a public route),
 * so App\Domain\Tenancy\Scopes\TenantScope is a no-op here — every query below filters by
 * spa_id explicitly rather than relying on it.
 */
class WhatsAppWebhookController extends Controller
{
    /**
     * Advancing further than this recorded value is the only thing that ever applies — a
     * redelivered or out-of-order webhook event can never double-count or regress a status.
     */
    private const STATUS_ORDER = ['pending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3, 'failed' => 4];

    /**
     * Meta's one-time subscription handshake, triggered whenever the spa (re)saves their
     * webhook config in Meta's App dashboard.
     */
    public function verify(Request $request, Spa $spa): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge', '');

        if ($mode === 'subscribe' && filled($token) && hash_equals((string) $spa->whatsapp_webhook_token, (string) $token)) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request, Spa $spa): Response
    {
        $payload = $request->json()->all();

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                if (($change['field'] ?? null) === 'messages') {
                    foreach ($value['statuses'] ?? [] as $status) {
                        $this->applyStatusUpdate($spa, $status);
                    }
                    // Inbound customer replies (value['messages']) are deliberately not stored
                    // or processed — there's no inbox in this pass. Meta only requires a 200
                    // response; it does not require that every field be acted on.
                } elseif (($change['field'] ?? null) === 'message_template_status_update') {
                    $this->applyTemplateStatusUpdate($spa, $value);
                }
            }
        }

        return response('ok', 200);
    }

    private function applyStatusUpdate(Spa $spa, array $status): void
    {
        $messageId = $status['id'] ?? null;
        $newStatus = $status['status'] ?? null;

        if (! $messageId || ! isset(self::STATUS_ORDER[$newStatus])) {
            return;
        }

        $recipient = WhatsAppCampaignRecipient::where('spa_id', $spa->id)
            ->where('meta_message_id', $messageId)
            ->first();

        if (! $recipient) {
            return;
        }

        // failed only ever applies before delivery succeeded — a message already delivered or
        // read has, by definition, not failed to arrive.
        $alreadyPast = $newStatus === 'failed'
            ? in_array($recipient->status, ['delivered', 'read', 'failed'], true)
            : self::STATUS_ORDER[$newStatus] <= self::STATUS_ORDER[$recipient->status];

        if ($alreadyPast) {
            return;
        }

        $updates = ['status' => $newStatus];

        match ($newStatus) {
            'delivered' => $updates['delivered_at'] = now(),
            'read' => $updates['read_at'] = now(),
            'failed' => $updates['error_message'] = $status['errors'][0]['title'] ?? 'Delivery failed.',
            default => null,
        };

        $recipient->update($updates);

        if (in_array($newStatus, ['delivered', 'read', 'failed'], true)) {
            $recipient->campaign()->increment("{$newStatus}_count");
        }
    }

    private function applyTemplateStatusUpdate(Spa $spa, array $value): void
    {
        $metaTemplateId = $value['message_template_id'] ?? null;

        $status = match (strtoupper($value['event'] ?? '')) {
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            'PAUSED', 'DISABLED' => 'paused',
            default => null,
        };

        if (! $metaTemplateId || ! $status) {
            return;
        }

        WhatsAppTemplate::where('spa_id', $spa->id)
            ->where('meta_template_id', $metaTemplateId)
            ->first()
            ?->update(['status' => $status, 'rejected_reason' => $value['reason'] ?? null]);
    }
}
