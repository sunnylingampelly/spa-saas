<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Customers\Actions\AdjustWalletAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RefundPaymentAction
{
    public function __construct(private readonly AdjustWalletAction $adjustWalletAction) {}

    public function execute(Invoice $invoice, string $method, float $amount, ?string $reason, ?int $userId): Invoice
    {
        return DB::transaction(function () use ($invoice, $method, $amount, $reason, $userId) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($amount > (float) $locked->paid_amount + 0.01) {
                throw ValidationException::withMessages(['amount' => 'Refund amount exceeds what has been paid.']);
            }

            if ($method === 'wallet' && ! $locked->customer_id) {
                throw ValidationException::withMessages(['method' => 'Guest bills have no wallet to refund into — choose another method.']);
            }

            Payment::create([
                'invoice_id' => $locked->id,
                'created_by_user_id' => $userId,
                'type' => 'refund',
                'method' => $method,
                'amount' => $amount,
                'reason' => $reason,
                'paid_at' => now(),
            ]);

            if ($method === 'wallet') {
                try {
                    $this->adjustWalletAction->execute(
                        $locked->customer,
                        'credit',
                        $amount,
                        "Refund for invoice {$locked->invoice_number}",
                        $userId,
                    );
                } catch (InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['method' => $e->getMessage()]);
                }
            }

            $paidAmount = round((float) $locked->paid_amount - $amount, 2);
            $balanceAmount = round((float) $locked->total_amount - $paidAmount, 2);

            $status = match (true) {
                $paidAmount <= 0.01 => 'refunded',
                $balanceAmount > 0.01 => 'partially_paid',
                default => 'paid',
            };

            $locked->update([
                'paid_amount' => max($paidAmount, 0),
                'balance_amount' => max($balanceAmount, 0),
                'status' => $status,
            ]);

            return $locked->fresh(['items', 'payments']);
        });
    }
}
