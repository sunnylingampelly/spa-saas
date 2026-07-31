<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerRewardPointTransaction;
use Illuminate\Support\Facades\DB;

class AwardRewardPointsAction
{
    public function execute(Customer $customer, int $points, ?int $invoiceId, ?string $reason, ?int $userId): ?CustomerRewardPointTransaction
    {
        if ($points <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customer, $points, $invoiceId, $reason, $userId) {
            /** @var Customer $locked */
            $locked = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $balanceAfter = $locked->reward_points + $points;

            $locked->update(['reward_points' => $balanceAfter]);

            return CustomerRewardPointTransaction::create([
                'customer_id' => $locked->id,
                'invoice_id' => $invoiceId,
                'type' => 'earn',
                'points' => $points,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'created_by_user_id' => $userId,
            ]);
        });
    }
}
