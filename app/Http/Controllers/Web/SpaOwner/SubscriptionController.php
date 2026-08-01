<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Subscriptions\Services\PayoutQrCodeService;
use App\Domain\Subscriptions\Services\RazorpayGateway;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function show(TenantContext $tenantContext, PayoutQrCodeService $qrCodeService): Response
    {
        $gateway = RazorpayGateway::platform();
        $spa = $tenantContext->getCurrentSpa();
        $subscription = $spa->subscription;
        $plans = config('subscriptions.plans');

        $pendingManualPlanCodes = $subscription->payments()
            ->where('method', 'manual')
            ->where('status', 'pending')
            ->pluck('plan_code');

        return Inertia::render('Subscription/Show', [
            'subscription' => $subscription,
            'payments' => $subscription->payments()->latest()->limit(10)->get(),
            'plans' => $plans,
            'payout' => config('subscriptions.payout'),
            'payoutQrSvgs' => collect($plans)->mapWithKeys(fn ($plan, $code) => [
                $code => $qrCodeService->svgForAmount($plan['price'], "SpaOrbit {$plan['label']} - {$spa->name}"),
            ]),
            'razorpayEnabled' => $gateway->isConfigured(),
            'razorpayKeyId' => config('services.razorpay.key_id'),
            'pendingManualPlanCodes' => $pendingManualPlanCodes,
        ]);
    }
}
