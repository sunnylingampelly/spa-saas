<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #f8fafc; font-size: 11px; text-transform: uppercase; color: #64748b; }
        .text-right { text-align: right; }
        .totals { width: 280px; margin-left: auto; margin-top: 12px; }
        .totals td { border: none; padding: 3px 8px; }
        .totals .grand { font-weight: bold; font-size: 14px; border-top: 1px solid #1e293b; }
        .footer { margin-top: 30px; font-size: 10px; color: #64748b; }
        .qr { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $spa->name }}</h1>
            <p class="muted">
                {{ $spa->address_line_1 }} {{ $spa->address_line_2 }}<br>
                {{ $spa->city }}, {{ $spa->state }} {{ $spa->pincode }}<br>
                @if($spa->gst_number) GSTIN: {{ $spa->gst_number }} @endif
            </p>
        </div>
        <div class="text-right">
            <h1>TAX INVOICE</h1>
            <p class="muted">
                Invoice No: {{ $invoice->invoice_number }}<br>
                FY: {{ $invoice->financial_year }}<br>
                Date: {{ $invoice->created_at->format('d-m-Y') }}
            </p>
        </div>
    </div>

    <p><strong>Billed to:</strong> {{ $invoice->billedToName() }}
        @if($invoice->customer?->phone) ({{ $invoice->customer->phone }}) @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>SAC</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Rate</th>
                <th class="text-right">GST%</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->hsn_sac_code ?? '-' }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->gst_rate, 2) }}%</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">₹{{ number_format($invoice->subtotal, 2) }}</td></tr>
        @if($invoice->discount_amount > 0)
            <tr><td>Discount</td><td class="text-right">-₹{{ number_format($invoice->discount_amount, 2) }}</td></tr>
        @endif
        <tr><td>Taxable amount</td><td class="text-right">₹{{ number_format($invoice->taxable_amount, 2) }}</td></tr>
        @if($invoice->cgst_amount > 0)
            <tr><td>CGST</td><td class="text-right">₹{{ number_format($invoice->cgst_amount, 2) }}</td></tr>
            <tr><td>SGST</td><td class="text-right">₹{{ number_format($invoice->sgst_amount, 2) }}</td></tr>
        @endif
        @if($invoice->igst_amount > 0)
            <tr><td>IGST</td><td class="text-right">₹{{ number_format($invoice->igst_amount, 2) }}</td></tr>
        @endif
        @if($invoice->tip_amount > 0)
            <tr><td>Tip</td><td class="text-right">₹{{ number_format($invoice->tip_amount, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="text-right">₹{{ number_format($invoice->total_amount, 2) }}</td></tr>
        <tr><td>Paid</td><td class="text-right">₹{{ number_format($invoice->paid_amount, 2) }}</td></tr>
        <tr><td>Balance due</td><td class="text-right">₹{{ number_format($invoice->balance_amount, 2) }}</td></tr>
    </table>

    <div class="qr">
        {!! $qrCodeSvg !!}
        <p class="muted">Scan to verify this bill's invoice number, date and total.</p>
    </div>

    @if($spa->invoice_footer_note)
        <p class="footer">{{ $spa->invoice_footer_note }}</p>
    @endif
</body>
</html>
