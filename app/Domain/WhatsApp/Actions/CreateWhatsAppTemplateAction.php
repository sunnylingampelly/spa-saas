<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\Tenancy\Models\Spa;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Domain\WhatsApp\Services\WhatsAppClient;

class CreateWhatsAppTemplateAction
{
    /**
     * @param  array{name: string, category: string, language: string, header_text: ?string, body_text: string, footer_text: ?string, buttons: ?array, variable_samples: ?array}  $data
     */
    public function execute(Spa $spa, array $data): WhatsAppTemplate
    {
        $result = WhatsAppClient::forSpa($spa)->createTemplate($spa->whatsapp_business_account_id, [
            'name' => $data['name'],
            'category' => strtoupper($data['category']),
            'language' => $data['language'],
            'components' => $this->buildComponents($data),
        ]);

        return WhatsAppTemplate::create([
            'meta_template_id' => $result['id'],
            'name' => $data['name'],
            'category' => $data['category'],
            'language' => $data['language'],
            'header_text' => $data['header_text'] ?? null,
            'body_text' => $data['body_text'],
            'footer_text' => $data['footer_text'] ?? null,
            'buttons' => $data['buttons'] ?? null,
            'variable_samples' => $data['variable_samples'] ?? null,
            'status' => $result['status'] ? strtolower($result['status']) : 'pending',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildComponents(array $data): array
    {
        $components = [];

        if (filled($data['header_text'] ?? null)) {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $data['header_text']];
        }

        $body = ['type' => 'BODY', 'text' => $data['body_text']];
        if (filled($data['variable_samples'] ?? null)) {
            // Meta expects one example set of values, one per {{n}} placeholder in order.
            $body['example'] = ['body_text' => [$data['variable_samples']]];
        }
        $components[] = $body;

        if (filled($data['footer_text'] ?? null)) {
            $components[] = ['type' => 'FOOTER', 'text' => $data['footer_text']];
        }

        if (filled($data['buttons'] ?? null)) {
            $components[] = ['type' => 'BUTTONS', 'buttons' => $data['buttons']];
        }

        return $components;
    }
}
