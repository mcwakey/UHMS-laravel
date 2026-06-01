<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 12px; margin-bottom: 16px; }
        .header td { vertical-align: top; }
        .logo { font-size: 22px; font-weight: bold; color: #0d6efd; }
        .logo small { display: block; font-size: 10px; color: #666; font-weight: normal; }
        .doc-title { font-size: 18px; font-weight: bold; text-align: right; }
        .doc-num { font-size: 14px; color: #0d6efd; font-weight: bold; text-align: right; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .badge-paid { background: #d1e7dd; color: #0f5132; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-partial { background: #cff4fc; color: #055160; }
        .badge-cancelled { background: #f8d7da; color: #842029; }
        .info-table { width: 100%; margin-bottom: 16px; }
        .info-table td { width: 33%; vertical-align: top; padding-right: 12px; }
        .info-table h5 { font-size: 12px; color: #0d6efd; margin-bottom: 6px; }
        .info-table p { margin-bottom: 3px; font-size: 11px; }
        .label { color: #888; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th, table.items td { padding: 6px 8px; border: 1px solid #dee2e6; text-align: left; font-size: 11px; }
        table.items th { background: #f8f9fa; font-weight: 600; }
        .text-end { text-align: right; }
        .totals { width: 280px; float: right; }
        .totals td { padding: 4px 8px; font-size: 12px; }
        .totals .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 14px; }
        .footer { clear: both; margin-top: 36px; padding-top: 12px; border-top: 1px solid #eee; text-align: center; color: #888; font-size: 10px; }
        .notes { margin-top: 14px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 11px; }
    </style>
</head>
<body>
    @php
        $badgeClass = match($invoice->status->value) {
            'paid' => 'badge-paid',
            'pending' => 'badge-pending',
            'partially_paid' => 'badge-partial',
            'cancelled', 'refunded' => 'badge-cancelled',
            default => 'badge-pending',
        };
    @endphp

    <table class="header">
        <tr>
            <td><div class="logo">UHMS<small>Ultimate Hospital Management System</small></div></td>
            <td>
                <div class="doc-title">INVOICE</div>
                <div class="doc-num">{{ $invoice->invoice_number }}</div>
                <div style="text-align:right; margin-top:4px;"><span class="badge {{ $badgeClass }}">{{ $invoice->status->label() }}</span></div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <h5>Invoice Details</h5>
                <p><span class="label">Date:</span> {{ $invoice->created_at->format('d M Y') }}</p>
                <p><span class="label">Due Date:</span> {{ $invoice->due_date?->format('d M Y') ?? '—' }}</p>
                <p><span class="label">Billing Type:</span> {{ $invoice->billing_type?->label() }}</p>
                @if($invoice->sponsor)
                <p><span class="label">Sponsor:</span> {{ $invoice->sponsor->name }}</p>
                @endif
            </td>
            <td>
                <h5>Patient</h5>
                <p style="font-weight:bold;">{{ $invoice->patient->full_name }}</p>
                <p>{{ $invoice->patient->patient_number }}</p>
                <p>{{ $invoice->patient->phone }}</p>
            </td>
            <td>
                <h5>Visit</h5>
                @if($invoice->visit)
                <p>{{ $invoice->visit->visit_number }}</p>
                <p>{{ $invoice->visit->department?->name }}</p>
                <p>{{ $invoice->visit->visit_date?->format('d M Y') }}</p>
                @else
                <p>—</p>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th>Description</th>
                <th class="text-end" style="width:12%;">Qty</th>
                <th class="text-end" style="width:18%;">Price</th>
                <th class="text-end" style="width:20%;">Patient Payable</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $item)
            @php $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0); @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td>
                    {{ $item->description }}
                    @if($item->serviceCatalog)<div style="font-size:9px; color:#666;">{{ $item->serviceCatalog->code }}</div>@endif
                </td>
                <td class="text-end">{{ $item->quantity }}</td>
                <td class="text-end">&#8373;{{ number_format($selectedPrice, 2) }}</td>
                <td class="text-end">&#8373;{{ number_format($item->patient_payable, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="text-end">&#8373;{{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->tax_amount > 0)
        <tr><td class="label">Tax</td><td class="text-end">&#8373;{{ number_format($invoice->tax_amount, 2) }}</td></tr>
        @endif
        @if($invoice->discount_amount > 0)
        <tr><td class="label">Discount</td><td class="text-end" style="color:red;">-&#8373;{{ number_format($invoice->discount_amount, 2) }}</td></tr>
        @endif
        @if(($invoice->adjustment_amount ?? 0) > 0)
        <tr><td class="label">Credit / Write-off</td><td class="text-end" style="color:#0d6efd;">-&#8373;{{ number_format($invoice->adjustment_amount, 2) }}</td></tr>
        @endif
        @if($invoice->nhis_amount > 0)
        <tr><td class="label">Insurance Covered</td><td class="text-end" style="color:#0d6efd;">&#8373;{{ number_format($invoice->nhis_amount, 2) }}</td></tr>
        @endif
        <tr class="total-row"><td>Total (GHS)</td><td class="text-end">&#8373;{{ number_format($invoice->total_amount, 2) }}</td></tr>
        <tr><td class="label">Amount Paid</td><td class="text-end" style="color:green;">&#8373;{{ number_format($invoice->amount_paid, 2) }}</td></tr>
        <tr><td><strong>Balance Due</strong></td><td class="text-end" style="color:red; font-weight:bold;">&#8373;{{ number_format($invoice->balance, 2) }}</td></tr>
    </table>

    @if($invoice->notes)
    <div class="notes" style="clear:both;"><strong>Notes:</strong> {{ $invoice->notes }}</div>
    @endif

    @if($invoice->creditNotes->where('status', 'issued')->count() > 0)
    <div style="clear:both; margin-top:16px;">
        <h5 style="font-size:12px; color:#0d6efd; margin-bottom:6px;">Credit Notes / Write-offs</h5>
        <table class="items">
            <thead><tr><th>Credit Note #</th><th>Type</th><th>Reason</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @foreach($invoice->creditNotes->where('status', 'issued') as $cn)
                <tr>
                    <td>{{ $cn->credit_note_number }}</td>
                    <td>{{ $cn->type?->label() }}</td>
                    <td>{{ $cn->reason }}</td>
                    <td class="text-end">&#8373;{{ number_format($cn->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} · UHMS · This is a computer-generated invoice.
    </div>
</body>
</html>
