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
    public function razorpayOrder(Request $request, TenantContext $tenantContext, RazorpayGateway $gateway)
    {
        abort_unless($gateway->isConfigured(), 503, 'Online payments are not configured yet.');

        $planCode = $request->validate([
            'plan' => ['required', Rule::in(array_keys(config('subscriptions.plans')))],
        ])['plan'];

        $spa = $tenantContext->getCurrentSpa();
        $plan = config("subscriptions.plans.{$planCode}");
        $amountInPaise = $plan['price'] * 100;

        $order = $gateway->createOrder($amountInPaise, "spa-{$spa->id}-{$planCode}-".now()->timestamp);

        $payment = SubscriptionPayment::create([
            'spa_id' => $spa->id,
            'subscription_id' => $spa->subscription->id,
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
        RazorpayGateway $gateway,
        ActivateSubscriptionFromPaymentAction $activateSubscription,
    ): RedirectResponse {
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
        $plan = config("subscriptions.plans.{$data['plan']}");

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
