<?php

namespace App\Domain\Employees\Policies;

use App\Domain\Employees\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function view(User $user, Employee $employee): bool
    {
        return $user->hasRole('super_admin') || $employee->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->view($user, $employee);
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $this->view($user, $employee);
    }
}
