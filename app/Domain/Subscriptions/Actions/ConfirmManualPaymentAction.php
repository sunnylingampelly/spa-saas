<?php

namespace App\Domain\Subscriptions\Actions;

use App\Domain\Subscriptions\Models\SubscriptionPayment;

class ConfirmManualPaymentAction
{
    public function __construct(private readonly ActivateSubscriptionFromPaymentAction $activateSubscription) {}

    public function execute(SubscriptionPayment $payment, int $confirmedByUserId): SubscriptionPayment
    {
        if ($payment->status !== 'paid') {
            $payment->update(['confirmed_by_user_id' => $confirmedByUserId]);
        }

        return $this->activateSubscription->execute($payment);
    }
}
