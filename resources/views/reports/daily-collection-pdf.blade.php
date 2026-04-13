<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Collection Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #0d6efd; }
        .header p { margin: 5px 0 0; color: #666; }
        .stats { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 33%; text-align: center; padding: 10px; }
        .stat-box .value { font-size: 18px; font-weight: bold; }
        .stat-box .label { font-size: 10px; color: #666; }
        .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 8px; color: #0d6efd; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-success { color: #198754; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - Daily Collection Report</h1>
        <p>Date: {{ $filters['date'] ?? now()->toDateString() }} &bull; Generated: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value text-success">₵{{ number_format($stats['total_collected'], 2) }}</div>
            <div class="label">Total Collected</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_transactions']) }}</div>
            <div class="label">Transactions</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $stats['methods'] }}</div>
            <div class="label">Payment Methods</div>
        </div>
    </div>

    @if($byMethod->count())
    <div class="section-title">Collection by Payment Method</div>
    <table>
        <thead>
            <tr><th>Method</th><th class="text-right">Transactions</th><th class="text-right">Amount</th></tr>
        </thead>
        <tbody>
            @foreach($byMethod as $method)
            <tr>
                <td>{{ $method->payment_method }}</td>
                <td class="text-right">{{ number_format($method->count) }}</td>
                <td class="text-right text-success">₵{{ number_format($method->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="section-title">Payment Details</div>
    <table>
        <thead>
            <tr>
                <th>Receipt #</th>
                <th>Time</th>
                <th>Patient</th>
                <th>Method</th>
                <th>Received By</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->receipt_number ?? '—' }}</td>
                <td>{{ $payment->created_at->format('H:i') }}</td>
                <td>{{ $payment->invoice?->patient?->full_name ?? '—' }}</td>
                <td>{{ $payment->payment_method?->label() ?? $payment->payment_method }}</td>
                <td>{{ $payment->receivedBy?->name ?? '—' }}</td>
                <td class="text-right text-success">₵{{ number_format($payment->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>University Hospital Management System (UHMS) &bull; Confidential</p>
    </div>
</body>
</html>
