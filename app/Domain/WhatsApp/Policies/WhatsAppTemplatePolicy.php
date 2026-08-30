<?php

namespace App\Domain\WhatsApp\Policies;

use App\Domain\WhatsApp\Models\WhatsAppTemplate;
use App\Models\User;

class WhatsAppTemplatePolicy
{
    public function view(User $user, WhatsAppTemplate $template): bool
    {
        return $user->hasRole('super_admin') || $template->spa->users()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, WhatsAppTemplate $template): bool
    {
        return $this->view($user, $template) && $template->status !== 'approved' && $template->campaigns()->doesntExist();
    }
}
