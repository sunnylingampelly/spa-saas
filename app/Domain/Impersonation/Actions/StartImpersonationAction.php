<?php

namespace App\Domain\Impersonation\Actions;

use App\Domain\Impersonation\Models\Impersonation;
use App\Domain\Tenancy\Models\Spa;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class StartImpersonationAction
{
    public function execute(Spa $spa, User $superAdmin): Impersonation
    {
        $owner = $spa->owner;

        // Never allow impersonating another super admin — this feature exists to let support
        // see what a spa owner sees, not to let one admin quietly act as another.
        if (! $owner || $owner->hasRole('super_admin')) {
            throw ValidationException::withMessages(['spa' => 'This spa has no impersonatable owner.']);
        }

        $impersonation = Impersonation::create([
            'super_admin_user_id' => $superAdmin->id,
            'spa_id' => $spa->id,
            'target_user_id' => $owner->id,
            'started_at' => now(),
        ]);

        session(['impersonator_id' => $superAdmin->id]);
        Auth::login($owner);

        return $impersonation;
    }
}
