<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Domain\Subscriptions\Models\SubscriptionPayment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
}
