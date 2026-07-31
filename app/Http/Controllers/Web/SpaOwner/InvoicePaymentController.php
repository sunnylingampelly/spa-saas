<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Billing\Actions\RecordPaymentAction;
use App\Domain\Billing\Actions\RefundPaymentAction;
use App\Domain\Billing\DTOs\RecordPaymentData;
use App\Domain\Billing\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoicePaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice, RecordPaymentAction $action): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:cash,upi,card,wallet,gift_voucher,bank_transfer'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:255'],
        ]);

        $action->execute(
            $invoice,
            array_map(fn ($p) => new RecordPaymentData(
                method: $p['method'],
                amount: $p['amount'],
                referenceNumber: $p['reference_number'] ?? null,
            ), $data['payments']),
            $request->user()->id,
        );

        return back()->with('success', 'Payment recorded.');
    }

    public function refund(Request $request, Invoice $invoice, RefundPaymentAction $action): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'method' => ['required', 'in:cash,upi,card,wallet,gift_voucher,bank_transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action->execute($invoice, $data['method'], $data['amount'], $data['reason'] ?? null, $request->user()->id);

        return back()->with('success', 'Refund recorded.');
    }
}
