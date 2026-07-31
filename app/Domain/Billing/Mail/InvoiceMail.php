<?php

namespace App\Domain\Billing\Mail;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Services\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Invoice $invoice, private readonly string $spaName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your invoice {$this->invoice->invoice_number} from {$this->spaName}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice',
            with: ['invoice' => $this->invoice, 'spaName' => $this->spaName],
        );
    }

    public function attachments(): array
    {
        /** @var InvoicePdfService $pdfService */
        $pdfService = app(InvoicePdfService::class);

        return [
            \Illuminate\Mail\Mailables\Attachment::fromData(
                fn () => $pdfService->render($this->invoice)->output(),
                "{$this->invoice->filenameSafeInvoiceNumber()}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
