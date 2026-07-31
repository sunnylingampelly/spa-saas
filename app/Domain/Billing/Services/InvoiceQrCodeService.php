<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Encodes a plain verification summary (invoice number, date, total) that the
 * customer can scan to cross-check their printed/emailed bill. This is NOT a
 * government e-invoice IRN QR — real GST e-invoicing requires IRP/GSP
 * integration, which is out of scope here.
 */
class InvoiceQrCodeService
{
    public function svgFor(Invoice $invoice): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);

        $payload = sprintf(
            'Invoice: %s | Date: %s | Total: INR %s',
            $invoice->invoice_number,
            $invoice->created_at->format('d-m-Y'),
            number_format((float) $invoice->total_amount, 2)
        );

        return (new Writer($renderer))->writeString($payload);
    }
}
