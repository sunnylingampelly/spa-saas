<?php

namespace App\Domain\Billing\Policies;

use App\Domain\Billing\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('super_admin') || $invoice->spa->users()->where('users.id', $user->id)->exists();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }
}
