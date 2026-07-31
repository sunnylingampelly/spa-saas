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

            $subscription->update([
                'plan_code' => $payment->plan_code,
                'status' => 'active',
                'current_period_ends_at' => $payment->plan_code === 'lifetime'
                    ? null
                    : now()->addMonthNoOverflow(),
            ]);

            return $payment->fresh();
        });
    }
}
