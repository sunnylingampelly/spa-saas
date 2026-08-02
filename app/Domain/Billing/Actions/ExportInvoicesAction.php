<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Shared\Services\SpreadsheetExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportInvoicesAction
{
    private const HEADINGS = [
        'invoice_number', 'date', 'customer', 'subtotal', 'discount_amount', 'taxable_amount',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'tip_amount', 'total_amount', 'paid_amount',
        'balance_amount', 'status',
    ];

    public function __construct(private readonly SpreadsheetExportService $exportService) {}

    public function execute(?string $status): StreamedResponse
    {
        $rows = Invoice::query()
            ->with('customer:id,name')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice) => [
                $invoice->invoice_number,
                optional($invoice->created_at)->toDateString(),
                $invoice->customer?->name ?? $invoice->guest_name,
                $invoice->subtotal,
                $invoice->discount_amount,
                $invoice->taxable_amount,
                $invoice->cgst_amount,
                $invoice->sgst_amount,
                $invoice->igst_amount,
                $invoice->tip_amount,
                $invoice->total_amount,
                $invoice->paid_amount,
                $invoice->balance_amount,
                $invoice->status,
            ]);

        return $this->exportService->download('invoices.xlsx', self::HEADINGS, $rows);
    }
}
