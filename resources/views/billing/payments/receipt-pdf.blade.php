<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt {{ $payment->payment_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { border-bottom: 2px solid #198754; padding-bottom: 10px; margin-bottom: 12px; }
        .header td { vertical-align: top; }
        .logo { font-size: 18px; font-weight: bold; color: #198754; }
        .logo small { display: block; font-size: 9px; color: #666; font-weight: normal; }
        .title { font-size: 15px; font-weight: bold; text-align: right; color: #198754; }
        .num { font-size: 12px; text-align: right; font-weight: 600; margin-top: 2px; }
        .reversed { display:inline-block; border: 2px solid #dc3545; color:#dc3545; padding: 2px 8px; font-size: 9px; font-weight: bold; transform: rotate(-3deg); }
        .info-table { width: 100%; margin-bottom: 12px; }
        .info-table td { width: 50%; vertical-align: top; }
        .info-table h5 { font-size: 10px; color: #198754; text-transform: uppercase; margin-bottom: 4px; }
        .info-table p { margin-bottom: 2px; font-size: 11px; }
        .label { color: #888; }
        .amount-banner { background: #d1e7dd; color: #0f5132; padding: 10px 14px; border-radius: 6px; margin: 12px 0; }
        .amount-banner table { width: 100%; }
        .amount-banner .amt { font-size: 20px; font-weight: 700; text-align: right; }
        table.inv { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.inv th, table.inv td { padding: 5px 7px; border-bottom: 1px solid #eee; text-align: left; font-size: 10px; }
        table.inv th { background: #f8f9fa; font-weight: 600; }
        .text-end { text-align: right; }
        .footer { margin-top: 18px; padding-top: 10px; border-top: 1px dashed #ccc; text-align: center; color: #888; font-size: 9px; line-height: 1.5; }
        .thanks { font-size: 11px; color: #198754; font-weight: 600; margin-bottom: 3px; }
    </style>
</head>
<body>
    @php
        $method = $payment->payment_method instanceof \App\Enums\PaymentMethod
            ? $payment->payment_method->label()
            : (\App\Enums\PaymentMethod::tryFrom((string) $payment->payment_method)?->label() ?? $payment->payment_method);
    @endphp
    <table class="header">
        <tr>
            <td><div class="logo">UHMS<small>Ultimate Hospital Management System</small></div></td>
            <td>
                <div class="title">PAYMENT RECEIPT</div>
                <div class="num">{{ $payment->payment_number }}</div>
                @if($payment->is_reversal)
                    <div style="text-align:right; margin-top:4px;"><span class="reversed">REVERSAL</span></div>
                @endif
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td>
                <h5>Received From</h5>
                <p style="font-weight:bold;">{{ $payment->patient->full_name }}</p>
                <p>{{ $payment->patient->patient_number }}</p>
                <p>{{ $payment->patient->phone }}</p>
            </td>
            <td>
                <h5>Payment Details</h5>
                <p><span class="label">Date:</span> {{ $payment->paid_at?->format('d M Y, h:i A') }}</p>
                <p><span class="label">Method:</span> {{ $method }}</p>
                @if($payment->reference_number)
                <p><span class="label">Reference:</span> {{ $payment->reference_number }}</p>
                @endif
                <p><span class="label">Cashier:</span> {{ $payment->receivedBy->name ?? '—' }}</p>
            </td>
        </tr>
    </table>

    <div class="amount-banner" @if($payment->is_reversal) style="background:#f8d7da; color:#842029;" @endif>
        <table>
            <tr>
                <td style="font-weight:600;">{{ $payment->is_reversal ? 'Amount Reversed' : 'Amount Received' }}</td>
                <td class="amt">&#8373;{{ number_format(abs($payment->amount), 2) }}</td>
            </tr>
        </table>
    </div>

    @if($payment->reversal_reason)
    <p style="font-size:10px; color:#842029; margin-bottom:10px;"><strong>Reason:</strong> {{ $payment->reversal_reason }}</p>
    @endif

    @if($payment->invoice)
    @php $inv = $payment->invoice; @endphp
    <h5 style="font-size:10px; text-transform:uppercase; color:#198754; margin-bottom:5px;">Applied To Invoice</h5>
    <table class="inv">
        <thead>
            <tr><th>Invoice #</th><th>Date</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th></tr>
        </thead>
        <tbody>
            <tr>
                <td style="font-weight:600;">{{ $inv->invoice_number }}</td>
                <td>{{ $inv->created_at->format('d M Y') }}</td>
                <td class="text-end">&#8373;{{ number_format($inv->total_amount, 2) }}</td>
                <td class="text-end" style="color:#198754;">&#8373;{{ number_format($inv->amount_paid, 2) }}</td>
                <td class="text-end" style="color:{{ $inv->balance > 0 ? '#dc3545' : '#198754' }}; font-weight:bold;">&#8373;{{ number_format($inv->balance, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        <div class="thanks">Thank you.</div>
        Generated on {{ now()->format('d M Y H:i') }} · UHMS · Computer-generated receipt.
    </div>
</body>
</html>
