<?php

namespace App\Domain\WhatsApp\Services;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\WhatsApp\Exceptions\WhatsAppApiException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * A thin wrapper over Meta's WhatsApp Cloud API (plain REST/JSON — no SDK needed). Every spa
 * brings their own WhatsApp Business Account: their own phone_number_id, WABA id, and a
 * permanent System User access token, pasted into Settings exactly like their own Razorpay keys
 * or SMTP credentials. There is no shared/platform-level WhatsApp account and no ambient
 * default — callers must always go through forSpa().
 */
class WhatsAppClient
{
    public function __construct(
        private readonly ?string $phoneNumberId = null,
        private readonly ?string $accessToken = null,
    ) {}

    public static function forSpa(Spa $spa): self
    {
        return new self($spa->whatsapp_phone_number_id, $spa->whatsapp_access_token);
    }

    public function isConfigured(): bool
    {
        return filled($this->phoneNumberId) && filled($this->accessToken);
    }

    /**
     * Confirms the credentials actually work and fetches the connected number's display details
     * — used both by "Test Connection" and to cache whatsapp_display_phone_number/verified_name.
     *
     * @return array{display_phone_number: ?string, verified_name: ?string}
     */
    public function fetchPhoneNumberDetails(): array
    {
        $response = $this->http()->get("/{$this->phoneNumberId}", [
            'fields' => 'display_phone_number,verified_name',
        ]);

        $this->throwIfFailed($response);

        return [
            'display_phone_number' => $response->json('display_phone_number'),
            'verified_name' => $response->json('verified_name'),
        ];
    }

    /**
     * Sends a pre-approved template message — the only kind of message this app ever sends.
     * Free-form text is only valid inside a live 24-hour customer-service window, which a
     * broadcast campaign is not.
     *
     * @param  array<int, array{type: string, parameters?: array}>  $components
     * @return array{message_id: ?string}
     */
    public function sendTemplateMessage(string $to, string $templateName, string $language, array $components = []): array
    {
        $response = $this->http()->post("/{$this->phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $language],
                'components' => $components,
            ],
        ]);

        $this->throwIfFailed($response);

        return ['message_id' => $response->json('messages.0.id')];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listTemplates(string $wabaId): array
    {
        $response = $this->http()->get("/{$wabaId}/message_templates", ['limit' => 100]);

        $this->throwIfFailed($response);

        return $response->json('data') ?? [];
    }

    /**
     * Submits a new template to Meta for approval. Returns Meta's assigned id and initial
     * status (always "PENDING" — approval itself happens asynchronously).
     *
     * @param  array<string, mixed>  $payload  {name, category, language, components}
     * @return array{id: ?string, status: ?string}
     */
    public function createTemplate(string $wabaId, array $payload): array
    {
        $response = $this->http()->post("/{$wabaId}/message_templates", $payload);

        $this->throwIfFailed($response);

        return ['id' => $response->json('id'), 'status' => $response->json('status')];
    }

    private function http(): PendingRequest
    {
        $version = config('services.whatsapp.graph_version', 'v21.0');

        return Http::withToken((string) $this->accessToken)
            ->baseUrl("https://graph.facebook.com/{$version}")
            ->acceptJson();
    }

    private function throwIfFailed($response): void
    {
        if ($response->failed()) {
            throw WhatsAppApiException::fromResponseBody($response->json() ?? [], $response->status());
        }
    }
}
