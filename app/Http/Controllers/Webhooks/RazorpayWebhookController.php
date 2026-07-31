<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Billing\Actions\CompleteInvoicePaymentIntentAction;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use App\Domain\Subscriptions\Actions\ActivateSubscriptionFromPaymentAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Subscriptions\Services\RazorpayGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Razorpay recommends treating the webhook as the source of truth rather than relying solely
 * on the client-side redirect/verify call — this is a redundant confirmation path for the same
 * "payment.captured" event the checkout flow already verifies synchronously.
 */
class RazorpayWebhookController extends Controller
{
    public function handle(
        Request $request,
        RazorpayGateway $gateway,
        ActivateSubscriptionFromPaymentAction $activateSubscription,
        CompleteInvoicePaymentIntentAction $completeInvoicePaymentIntent,
    ): Response {
        $signature = $request->header('X-Razorpay-Signature', '');

        if (! $signature || ! $gateway->verifyWebhookSignature($request->getContent(), $signature)) {
            return response('Invalid signature', 400);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) === 'payment.captured') {
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;
            $signatureFromPayload = $payload['payload']['payment']['entity']['signature'] ?? $signature;

            $payment = SubscriptionPayment::where('razorpay_order_id', $orderId)->first();

            if ($payment && $payment->status !== 'paid') {
                $payment->update(['razorpay_payment_id' => $paymentId, 'raw_response' => $payload]);
                $activateSubscription->execute($payment);
            } elseif (! $payment) {
                // Not a subscription payment — the safety net for a customer invoice payment
                // the client-side verify call never confirmed (e.g. the browser closed early).
                $intent = InvoicePaymentIntent::where('razorpay_order_id', $orderId)->first();

                if ($intent && $intent->status !== 'paid') {
                    $intent->update(['raw_response' => $payload]);
                    $completeInvoicePaymentIntent->execute($intent, $paymentId, $signatureFromPayload);
                }
            }
        }

        return response('ok', 200);
    }
}
