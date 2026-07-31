<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\Payment;
use App\Domain\Customers\Actions\AdjustWalletAction;
use App\Domain\Customers\Actions\AwardRewardPointsAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class RecordPaymentAction
{
    public function __construct(
        private readonly AdjustWalletAction $adjustWalletAction,
        private readonly AwardRewardPointsAction $awardRewardPointsAction,
    ) {}

    /**
     * @param  array<\App\Domain\Billing\DTOs\RecordPaymentData>  $payments
     */
    public function execute(Invoice $invoice, array $payments, ?int $userId): Invoice
    {
        return DB::transaction(function () use ($invoice, $payments, $userId) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['cancelled', 'refunded'], true)) {
                throw ValidationException::withMessages(['status' => 'This invoice can no longer accept payments.']);
            }

            $usesWallet = collect($payments)->contains(fn ($p) => $p->method === 'wallet');

            if ($usesWallet && ! $locked->customer_id) {
                throw ValidationException::withMessages(['method' => 'Guest bills have no wallet to charge — choose another payment method.']);
            }

            $totalNewPayments = round(array_sum(array_map(fn ($p) => $p->amount, $payments)), 2);

            if ($totalNewPayments > (float) $locked->balance_amount + 0.01) {
                throw ValidationException::withMessages(['amount' => 'Payment amount exceeds the outstanding balance.']);
            }

            foreach ($payments as $payment) {
                Payment::create([
                    'invoice_id' => $locked->id,
                    'created_by_user_id' => $userId,
                    'type' => 'payment',
                    'method' => $payment->method,
                    'amount' => $payment->amount,
                    'reference_number' => $payment->referenceNumber,
                    'paid_at' => now(),
                ]);

                if ($payment->method === 'wallet') {
                    try {
                        $this->adjustWalletAction->execute(
                            $locked->customer,
                            'debit',
                            $payment->amount,
                            "Payment for invoice {$locked->invoice_number}",
                            $userId,
                        );
                    } catch (InvalidArgumentException $e) {
                        throw ValidationException::withMessages(['method' => $e->getMessage()]);
                    }
                }
            }

            $previousStatus = $locked->status;
            $paidAmount = round((float) $locked->paid_amount + $totalNewPayments, 2);
            $balanceAmount = round((float) $locked->total_amount - $paidAmount, 2);
            $newStatus = $balanceAmount <= 0.01 ? 'paid' : 'partially_paid';

            $locked->update([
                'paid_amount' => $paidAmount,
                'balance_amount' => max($balanceAmount, 0),
                'status' => $newStatus,
            ]);

            if ($previousStatus !== 'paid' && $newStatus === 'paid' && $locked->customer_id) {
                $points = (int) floor((float) $locked->total_amount / config('loyalty.rupees_per_point'));

                $this->awardRewardPointsAction->execute(
                    $locked->customer,
                    $points,
                    $locked->id,
                    "Invoice {$locked->invoice_number} paid in full",
                    $userId,
                );
            }

            return $locked->fresh(['items', 'payments']);
        });
    }
}
