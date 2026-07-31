@component('mail::message')
# Invoice {{ $invoice->invoice_number }}

Thank you for visiting **{{ $spaName }}**. Your invoice is attached as a PDF.

| | |
|---|---|
| Invoice date | {{ $invoice->created_at->format('d-m-Y') }} |
| Total amount | ₹{{ number_format($invoice->total_amount, 2) }} |
| Balance due | ₹{{ number_format($invoice->balance_amount, 2) }} |

Thanks,<br>
{{ $spaName }}
@endcomponent
