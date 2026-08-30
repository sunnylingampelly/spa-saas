<?php

namespace App\Domain\WhatsApp\Actions;

use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use Illuminate\Validation\ValidationException;

class CreateWhatsAppCampaignAction
{
    /**
     * @param  array{name: string, whatsapp_template_id: int, variable_values: array, audience_filter: array, created_by_user_id: int}  $data
     */
    public function execute(array $data): WhatsAppCampaign
    {
        // findOrFail (not a raw exists() validation rule) so WhatsAppTemplate's TenantScope
        // actually enforces this — a plain "id exists in this table" check doesn't respect
        // scopes, and would let a spa attach another spa's template by guessing its id.
        $template = WhatsAppTemplate::findOrFail($data['whatsapp_template_id']);

        if (! $template->isApproved()) {
            throw ValidationException::withMessages(['whatsapp_template_id' => 'Only a Meta-approved template can be used for a campaign.']);
        }

        return WhatsAppCampaign::create([
            'created_by_user_id' => $data['created_by_user_id'],
            'whatsapp_template_id' => $template->id,
            'name' => $data['name'],
            'variable_values' => $data['variable_values'],
            'audience_filter' => $data['audience_filter'],
            'status' => 'draft',
        ]);
    }
}
