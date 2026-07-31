<?php

namespace App\Domain\Services\Policies;

use App\Domain\Services\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function view(User $user, Service $service): bool
    {
        return $user->hasRole('super_admin') || $service->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Service $service): bool
    {
        return $this->view($user, $service);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->view($user, $service);
    }
}
