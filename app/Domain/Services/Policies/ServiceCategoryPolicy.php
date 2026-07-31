<?php

namespace App\Domain\Services\Policies;

use App\Domain\Services\Models\ServiceCategory;
use App\Models\User;

class ServiceCategoryPolicy
{
    public function view(User $user, ServiceCategory $category): bool
    {
        return $user->hasRole('super_admin') || $category->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, ServiceCategory $category): bool
    {
        return $this->view($user, $category);
    }

    public function delete(User $user, ServiceCategory $category): bool
    {
        return $this->view($user, $category);
    }
}
