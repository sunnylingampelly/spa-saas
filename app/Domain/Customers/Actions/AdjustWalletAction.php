<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Customers\Models\CustomerWalletTransaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdjustWalletAction
{
    public function execute(Customer $customer, string $type, float $amount, ?string $reason, ?int $userId): CustomerWalletTransaction
    {
        return DB::transaction(function () use ($customer, $type, $amount, $reason, $userId) {
            /** @var Customer $locked */
            $locked = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $balanceAfter = $type === 'credit'
                ? $locked->wallet_balance + $amount
                : $locked->wallet_balance - $amount;

            if ($balanceAfter < 0) {
                throw new InvalidArgumentException('Wallet balance cannot go negative.');
            }

            $locked->update(['wallet_balance' => $balanceAfter]);

            return CustomerWalletTransaction::create([
                'customer_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'reason' => $reason,
                'created_by_user_id' => $userId,
            ]);
        });
    }
}
