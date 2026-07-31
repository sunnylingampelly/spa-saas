<?php

namespace App\Domain\Expenses\Policies;

use App\Domain\Expenses\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function view(User $user, Expense $expense): bool
    {
        return $user->hasRole('super_admin') || $expense->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Expense $expense): bool
    {
        return $this->view($user, $expense);
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $this->view($user, $expense);
    }
}
