<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Subscriptions\Models\Subscription;
use Illuminate\Support\Carbon;

class AdminUpdateSubscriptionAction
{
    public function execute(Subscription $subscription, string $planCode, string $status, ?Carbon $periodEndsAt): Subscription
    {
        $subscription->update([
            'plan_code' => $planCode,
            'status' => $status,
            'current_period_ends_at' => $periodEndsAt,
            'cancelled_at' => $status === 'cancelled'
                ? ($subscription->cancelled_at ?? now())
                : null,
        ]);

        return $subscription->fresh();
    }
}
