<?php

namespace App\Http\Controllers\Web\Public;

use App\Domain\Billing\Actions\CompleteInvoicePaymentIntentAction;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoicePaymentIntent;
use App\Domain\Subscriptions\Services\RazorpayGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fully public — reached via an unguessable Invoice::public_token, never behind auth or
 * spa.context. TenantScope is a confirmed no-op for unauthenticated requests, so the token
 * itself (bound via {invoice:public_token}) is the only access-control boundary here.
 */
class InvoicePaymentController extends Controller
{
    public function show(Invoice $invoice): Response
    {
        return Inertia::render('Public/PayInvoice', [
            'invoice' => $invoice->load(['items.service']),
            'spaName' => $invoice->spa->name,
            'razorpayEnabled' => app(RazorpayGateway::class)->isConfigured(),
            'razorpayKeyId' => config('services.razorpay.key_id'),
        ]);
    }

    public function createOrder(Invoice $invoice, RazorpayGateway $gateway): JsonResponse
    {
        abort_unless($gateway->isConfigured(), 503, 'Online payments are not configured yet.');

        if (in_array($invoice->status, ['cancelled', 'refunded'], true)) {
            abort(422, 'This invoice can no longer accept payments.');
        }

        if ((float) $invoice->balance_amount <= 0) {
            abort(422, 'This invoice is already paid in full.');
        }

        $amountInPaise = (int) round((float) $invoice->balance_amount * 100);

        $order = $gateway->createOrder($amountInPaise, "invoice-{$invoice->id}-".now()->timestamp);

        $intent = InvoicePaymentIntent::create([
            'spa_id' => $invoice->spa_id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
            'amount' => $invoice->balance_amount,
            'razorpay_order_id' => $order['id'],
        ]);

        return response()->json([
            'order_id' => $order['id'],
            'amount' => $amountInPaise,
            'key_id' => config('services.razorpay.key_id'),
            'intent_id' => $intent->id,
            'invoice_number' => $invoice->invoice_number,
            'spa_name' => $invoice->spa->name,
        ]);
    }

    public function verify(Request $request, Invoice $invoice, RazorpayGateway $gateway, CompleteInvoicePaymentIntentAction $action): JsonResponse
    {
        $data = $request->validate([
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $intent = InvoicePaymentIntent::where('razorpay_order_id', $data['razorpay_order_id'])
            ->where('invoice_id', $invoice->id)
            ->firstOrFail();

        if (! $gateway->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            throw ValidationException::withMessages(['razorpay_signature' => 'Payment verification failed.']);
        }

        $action->execute($intent, $data['razorpay_payment_id'], $data['razorpay_signature']);

        return response()->json(['success' => true]);
    }
}
