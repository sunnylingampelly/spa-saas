<?php

namespace App\Http\Controllers\Web\SpaOwner;

use App\Domain\Billing\Mail\InvoiceMail;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Services\InvoicePdfService;
use App\Domain\Tenancy\Services\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceDeliveryController extends Controller
{
    public function download(Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        $this->authorize('view', $invoice);

        return $pdfService->render($invoice)->download("{$invoice->filenameSafeInvoiceNumber()}.pdf");
    }

    public function email(Invoice $invoice, TenantContext $tenantContext): RedirectResponse
    {
        $this->authorize('view', $invoice);

        $email = $invoice->customer?->email;

        if (! $email) {
            throw ValidationException::withMessages(['email' => 'This customer has no email address on file.']);
        }

        Mail::to($email)->send(new InvoiceMail($invoice, $tenantContext->getCurrentSpa()->name));

        return back()->with('success', "Invoice emailed to {$email}.");
    }
}
