<?php

namespace App\Domain\WhatsApp\Policies;

use App\Domain\WhatsApp\Models\WhatsAppCampaign;
use App\Models\User;

class WhatsAppCampaignPolicy
{
    public function view(User $user, WhatsAppCampaign $campaign): bool
    {
        return $user->hasRole('super_admin') || $campaign->spa->users()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, WhatsAppCampaign $campaign): bool
    {
        return $this->view($user, $campaign) && $campaign->status === 'draft';
    }
}
