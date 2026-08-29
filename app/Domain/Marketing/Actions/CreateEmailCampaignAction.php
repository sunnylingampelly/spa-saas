<?php

namespace App\Domain\Marketing\Actions;

use App\Domain\Marketing\Models\EmailCampaign;

class CreateEmailCampaignAction
{
    /**
     * @param  array{name: string, subject: string, preheader: ?string, body_html: string, audience_filter: array, created_by_user_id: int}  $data
     */
    public function execute(array $data): EmailCampaign
    {
        return EmailCampaign::create([
            'created_by_user_id' => $data['created_by_user_id'],
            'name' => $data['name'],
            'subject' => $data['subject'],
            'preheader' => $data['preheader'] ?? null,
            'body_html' => $data['body_html'],
            'audience_filter' => $data['audience_filter'],
            'status' => 'draft',
        ]);
    }
}
