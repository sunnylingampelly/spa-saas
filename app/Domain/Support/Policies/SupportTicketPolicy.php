<?php

namespace App\Domain\Support\Policies;

use App\Domain\Support\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function view(User $user, SupportTicket $ticket): bool
    {
        return $user->hasRole('super_admin') || $ticket->spa->users()->where('users.id', $user->id)->exists();
    }
}
