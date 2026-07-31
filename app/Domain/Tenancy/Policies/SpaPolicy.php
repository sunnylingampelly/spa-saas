<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Tenancy\Models\Spa;
use App\Models\User;

class SpaPolicy
{
    public function view(User $user, Spa $spa): bool
    {
        return $user->hasRole('super_admin') || $spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Spa $spa): bool
    {
        return $user->hasRole('super_admin')
            || $spa->users()->where('users.id', $user->id)->wherePivot('role_label', 'owner')->exists();
    }
}
