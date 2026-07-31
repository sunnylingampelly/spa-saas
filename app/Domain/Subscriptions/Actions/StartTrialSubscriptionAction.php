<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Subscriptions\Models\Subscription;
use App\Domain\Tenancy\Models\Spa;

class StartTrialSubscriptionAction
{
    public function execute(Spa $spa, int $ownerUserId): Subscription
    {
        $startsAt = now();

        return Subscription::create([
            'spa_id' => $spa->id,
            'created_by_user_id' => $ownerUserId,
            'plan_code' => 'trial',
            'status' => 'trialing',
            'starts_at' => $startsAt,
            'current_period_ends_at' => $startsAt->copy()->addDays(config('subscriptions.trial_days', 14)),
        ]);
    }
}
