<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use Illuminate\Validation\ValidationException;

class CancelInvoiceAction
{
    public function execute(Invoice $invoice, ?string $reason): Invoice
    {
        if ((float) $invoice->paid_amount > 0) {
            throw ValidationException::withMessages([
                'status' => 'This invoice has payments recorded against it — refund them before cancelling.',
            ]);
        }

        $invoice->update([
            'status' => 'cancelled',
            'cancelled_reason' => $reason,
            'balance_amount' => 0,
        ]);

        return $invoice;
    }
}
