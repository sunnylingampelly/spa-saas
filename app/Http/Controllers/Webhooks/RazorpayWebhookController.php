<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Billing\Actions\CompleteInvoicePaymentIntentAction;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use App\Domain\Subscriptions\Actions\ActivateSubscriptionFromPaymentAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Subscriptions\Services\RazorpayGateway;
use App\Domain\Tenancy\Models\Spa;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Razorpay recommends treating the webhook as the source of truth rather than relying solely
 * on the client-side redirect/verify call — this is a redundant confirmation path for the same
 * "payment.captured" event the checkout flow already verifies synchronously.
 *
 * There are two entirely separate Razorpay accounts involved in this app (the platform's own,
 * and each spa's own), so there are two separate webhook endpoints — each verified against the
 * correct account's own webhook secret. A single shared endpoint can't work here: verifying a
 * signature requires already knowing which account's secret to check it against, so the account
 * has to be identified by the URL itself, not by inspecting the payload after the fact.
 */
class RazorpayWebhookController extends Controller
{
    /**
     * The platform's own Razorpay dashboard webhook config points here — subscription
     * (spa owner → platform) payments only.
     */
    public function handlePlatform(Request $request, ActivateSubscriptionFromPaymentAction $activateSubscription): Response
    {
        $gateway = RazorpayGateway::platform();
        $signature = $request->header('X-Razorpay-Signature', '');

        if (! $signature || ! $gateway->verifyWebhookSignature($request->getContent(), $signature)) {
            return response('Invalid signature', 400);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) === 'payment.captured') {
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;

            $payment = SubscriptionPayment::where('razorpay_order_id', $orderId)->first();

            if ($payment && $payment->status !== 'paid') {
                $payment->update(['razorpay_payment_id' => $paymentId, 'raw_response' => $payload]);
                $activateSubscription->execute($payment);
            }
        }

        return response('ok', 200);
    }

    /**
     * Each spa pastes their own unique URL (built from their razorpay_webhook_token) into
     * their own Razorpay dashboard's webhook config — customer invoice payments only.
     */
    public function handleForSpa(Request $request, Spa $spa, CompleteInvoicePaymentIntentAction $completeInvoicePaymentIntent): Response
    {
        $gateway = RazorpayGateway::forSpa($spa);
        $signature = $request->header('X-Razorpay-Signature', '');

        if (! $signature || ! $gateway->verifyWebhookSignature($request->getContent(), $signature)) {
            return response('Invalid signature', 400);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? null) === 'payment.captured') {
            $orderId = $payload['payload']['payment']['entity']['order_id'] ?? null;
            $paymentId = $payload['payload']['payment']['entity']['id'] ?? null;
            $signatureFromPayload = $payload['payload']['payment']['entity']['signature'] ?? $signature;

            $intent = InvoicePaymentIntent::where('razorpay_order_id', $orderId)
                ->where('spa_id', $spa->id)
                ->first();

            if ($intent && $intent->status !== 'paid') {
                $intent->update(['raw_response' => $payload]);
                $completeInvoicePaymentIntent->execute($intent, $paymentId, $signatureFromPayload);
            }
        }

        return response('ok', 200);
    }
}
