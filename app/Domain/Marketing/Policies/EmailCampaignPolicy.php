<?php

namespace App\Domain\Marketing\Policies;

use App\Domain\Marketing\Models\EmailCampaign;
use App\Models\User;

class EmailCampaignPolicy
{
    public function view(User $user, EmailCampaign $campaign): bool
    {
        return $user->hasRole('super_admin') || $campaign->spa->users()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, EmailCampaign $campaign): bool
    {
        return $this->view($user, $campaign) && $campaign->status === 'draft';
    }
}
