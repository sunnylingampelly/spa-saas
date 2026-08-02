<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Subscriptions\Actions\ExportSubscriptionPaymentsAction;
use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();

        $payments = SubscriptionPayment::withoutGlobalScopes()
            ->with('subscription.spa:id,name')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Payments', [
            'payments' => $payments,
            'filters' => ['status' => $status],
        ]);
    }

    public function export(Request $request, ExportSubscriptionPaymentsAction $action): StreamedResponse
    {
        return $action->execute($request->string('status')->toString() ?: null);
    }
}
