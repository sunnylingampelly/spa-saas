<?php

namespace App\Domain\Customers\Policies;

use App\Domain\Customers\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function view(User $user, Customer $customer): bool
    {
        return $user->hasRole('super_admin') || $customer->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }
}
