<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Tenancy\Services\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

class InvoicePdfService
{
    public function __construct(
        private readonly InvoiceQrCodeService $qrCodeService,
        private readonly TenantContext $tenantContext,
    ) {}

    public function render(Invoice $invoice): PdfDocument
    {
        $invoice->loadMissing('items', 'customer');

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'spa' => $this->tenantContext->getCurrentSpa(),
            'qrCodeSvg' => $this->qrCodeService->svgFor($invoice),
        ])->setPaper('a4');
    }
}
