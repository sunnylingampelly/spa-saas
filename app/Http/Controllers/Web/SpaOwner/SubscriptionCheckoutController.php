<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Subscriptions\Actions\ActivateSubscriptionFromPaymentAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Domain\Subscriptions\Services\RazorpayGateway;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubscriptionCheckoutController extends Controller
{
    public function razorpayOrder(Request $request, TenantContext $tenantContext)
    {
        $gateway = RazorpayGateway::platform();

        // Plain abort()/HttpException rendering isn't reliably JSON on a web route (see
        // bootstrap/app.php's shouldRenderJsonWhen, scoped to api/* paths) — this endpoint
        // is called via axios expecting a JSON body either way, so return JSON explicitly.
        if (! $gateway->isConfigured()) {
            return response()->json(['message' => 'Online payments are not configured yet.'], 503);
        }

        $planCode = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('subscriptions.plans')))],
        ])['plan'];

        $spa = $tenantContext->getCurrentSpa();
        $subscription = $spa->subscription;
        $plan = config("subscriptions.plans.{$planCode}");
        $amountInPaise = $plan['price'] * 100;

        if ($blockingReason = $subscription->blockingReasonForPurchase($planCode)) {
            return response()->json(['message' => $blockingReason], 422);
        }

        // A double-click (or a checkout window left open and retried) must never open a
        // second live Razorpay order for the same purchase — that's two real charge
        // attempts for one thing. Reuse whatever's still pending and fresh instead of
        // asking Razorpay for a new one.
        $existingPending = SubscriptionPayment::where('subscription_id', $subscription->id)
            ->where('plan_code', $planCode)
            ->where('method', 'razorpay')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->first();

        if ($existingPending) {
            return response()->json([
                'order_id' => $existingPending->razorpay_order_id,
                'amount' => $amountInPaise,
                'key_id' => config('services.razorpay.key_id'),
                'payment_id' => $existingPending->id,
                'spa_name' => $spa->name,
                'plan_label' => $plan['label'],
            ]);
        }

        $order = $gateway->createOrder($amountInPaise, "spa-{$spa->id}-{$planCode}-".now()->timestamp);

        $payment = SubscriptionPayment::create([
            'spa_id' => $spa->id,
            'subscription_id' => $subscription->id,
            'plan_code' => $planCode,
            'method' => 'razorpay',
            'status' => 'pending',
            'amount' => $plan['price'],
            'razorpay_order_id' => $order['id'],
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $amountInPaise,
            'key_id' => config('services.razorpay.key_id'),
            'payment_id' => $payment->id,
            'spa_name' => $spa->name,
            'plan_label' => $plan['label'],
        ]);
    }

    public function razorpayVerify(
        Request $request,
        TenantContext $tenantContext,
        ActivateSubscriptionFromPaymentAction $activateSubscription,
    ): RedirectResponse {
        $gateway = RazorpayGateway::platform();

        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = SubscriptionPayment::where('razorpay_order_id', $data['razorpay_order_id'])->firstOrFail();

        // Belt-and-suspenders: BelongsToTenant's global scope already filters this query to the
        // current spa, but a payment-activation endpoint should never trust that implicitly.
        abort_unless($payment->spa_id === $tenantContext->getCurrentSpaId(), 403);

        if (! $gateway->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            throw ValidationException::withMessages(['razorpay_signature' => 'Payment verification failed.']);
        }

        $payment->update([
            'razorpay_payment_id' => $data['razorpay_payment_id'],
            'razorpay_signature' => $data['razorpay_signature'],
        ]);

        $activateSubscription->execute($payment);

        return redirect()->route('subscription.show')->with('success', 'Payment successful — your subscription is now active.');
    }

    public function manualSubmit(Request $request, TenantContext $tenantContext): RedirectResponse
    {
        $data = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('subscriptions.plans')))],
            'proof_note' => ['nullable', 'string', 'max:500'],
        ]);

        $spa = $tenantContext->getCurrentSpa();
        $subscription = $spa->subscription;
        $plan = config("subscriptions.plans.{$data['plan']}");

        if ($blockingReason = $subscription->blockingReasonForPurchase($data['plan'])) {
            throw ValidationException::withMessages(['plan' => $blockingReason]);
        }

        $alreadyPending = SubscriptionPayment::where('subscription_id', $spa->subscription->id)
            ->where('plan_code', $data['plan'])
            ->where('method', 'manual')
            ->where('status', 'pending')
            ->exists();

        if ($alreadyPending) {
            return back()->with('success', 'Your payment is already awaiting confirmation — no need to submit again.');
        }

        SubscriptionPayment::create([
            'spa_id' => $spa->id,
            'subscription_id' => $spa->subscription->id,
            'plan_code' => $data['plan'],
            'method' => 'manual',
            'status' => 'pending',
            'amount' => $plan['price'],
            'proof_note' => $data['proof_note'] ?? null,
        ]);

        return back()->with('success', 'Thanks — we\'ll confirm your payment shortly and activate your plan.');
    }
}
