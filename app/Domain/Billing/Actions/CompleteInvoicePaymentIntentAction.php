<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\DTOs\RecordPaymentData;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CompleteInvoicePaymentIntentAction
{
    public function __construct(
        private readonly RecordPaymentAction $recordPaymentAction,
    ) {}

    public function execute(InvoicePaymentIntent $intent, string $razorpayPaymentId, string $razorpaySignature): void
    {
        DB::transaction(function () use ($intent, $razorpayPaymentId, $razorpaySignature) {
            /** @var InvoicePaymentIntent $locked */
            $locked = InvoicePaymentIntent::query()->whereKey($intent->id)->lockForUpdate()->firstOrFail();

            // The client-side verify call and the webhook safety net race to complete the same
            // intent — this must not double-credit the invoice with two Payment rows.
            if ($locked->status === 'paid') {
                return;
            }

            try {
                $this->recordPaymentAction->execute(
                    $locked->invoice,
                    [new RecordPaymentData(method: 'razorpay', amount: (float) $locked->amount, referenceNumber: $razorpayPaymentId)],
                    null,
                );

                $locked->update([
                    'status' => 'paid',
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $razorpaySignature,
                ]);
            } catch (Throwable $e) {
                $locked->update([
                    'status' => 'failed',
                    'razorpay_payment_id' => $razorpayPaymentId,
                    'razorpay_signature' => $razorpaySignature,
                ]);

                Log::error('Failed to complete invoice payment intent after a verified Razorpay payment.', [
                    'invoice_payment_intent_id' => $locked->id,
                    'invoice_id' => $locked->invoice_id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }
        });
    }
}
