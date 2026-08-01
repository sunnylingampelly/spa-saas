<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Subscriptions\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;

class ActivateSubscriptionFromPaymentAction
{
    public function execute(SubscriptionPayment $payment): SubscriptionPayment
    {
        if ($payment->status === 'paid') {
            return $payment;
        }

        return DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'paid', 'paid_at' => now()]);

            $subscription = $payment->subscription;

            // A renewal of the plan the spa is already actively on extends from its
            // current expiry rather than from today, so paying early (or a moment before
            // it lapses) never discards time already paid for. Any other case — a fresh
            // purchase, a lapsed plan, or an upgrade — starts the new period from now.
            $isRenewingActivePeriod = $subscription->status === 'active'
                && $subscription->plan_code === $payment->plan_code
                && $subscription->current_period_ends_at?->isFuture();

            $periodStart = $isRenewingActivePeriod ? $subscription->current_period_ends_at : now();

            $subscription->update([
                'plan_code' => $payment->plan_code,
                'status' => 'active',
                'current_period_ends_at' => $payment->plan_code === 'lifetime'
                    ? null
                    : $periodStart->copy()->addMonthNoOverflow(),
            ]);

            return $payment->fresh();
        });
    }
}
