<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Subscriptions\Actions\ConfirmManualPaymentAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendingPaymentController extends Controller
{
    public function index(): Response
    {
        $payments = SubscriptionPayment::withoutGlobalScopes()
            ->where('method', 'manual')
            ->where('status', 'pending')
            ->with('subscription.spa:id,name')
            ->latest()
            ->get();

        return Inertia::render('SuperAdmin/PendingPayments', [
            'payments' => $payments,
        ]);
    }

    public function confirm(Request $request, SubscriptionPayment $payment, ConfirmManualPaymentAction $confirmManualPayment): RedirectResponse
    {
        $confirmManualPayment->execute($payment, $request->user()->id);

        return back()->with('success', 'Payment confirmed and subscription activated.');
    }
}
