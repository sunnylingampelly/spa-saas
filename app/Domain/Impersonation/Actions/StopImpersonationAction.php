<?php

namespace App\Domain\Impersonation\Actions;

use App\Domain\Impersonation\Models\Impersonation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class StopImpersonationAction
{
    public function execute(int $impersonatorId, int $targetUserId): void
    {
        Impersonation::withoutGlobalScopes()
            ->where('super_admin_user_id', $impersonatorId)
            ->where('target_user_id', $targetUserId)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first()
            ?->update(['ended_at' => now()]);

        session()->forget('impersonator_id');
        Auth::login(User::findOrFail($impersonatorId));
    }
}
