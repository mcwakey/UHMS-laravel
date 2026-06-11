<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.billing.income_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #0d6efd; }
        .header p { margin: 5px 0 0; color: #666; }
        .stats { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 10px; }
        .stat-box .value { font-size: 18px; font-weight: bold; }
        .stat-box .label { font-size: 10px; color: #666; }
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
        <h1>UHMS - {{ __('reports.billing.income_title') }}</h1>
        <p>{{ __('common.generated') }}: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value text-success">₵{{ number_format($stats['total_income'] ?? 0, 2) }}</div>
            <div class="label">{{ __('reports.kpi.total_income') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">₵{{ number_format($stats['consultation_fees'] ?? 0, 2) }}</div>
            <div class="label">{{ __('reports.kpi.consultation_fees') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">₵{{ number_format($stats['lab_revenue'] ?? 0, 2) }}</div>
            <div class="label">{{ __('reports.kpi.lab_revenue') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">₵{{ number_format($stats['insurance_revenue'] ?? 0, 2) }}</div>
            <div class="label">{{ __('reports.kpi.insurance_revenue') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.billing.receipt_no') }}</th>
                <th>{{ __('reports.columns.patient') }}</th>
                <th>{{ __('reports.columns.method') }}</th>
                <th class="text-right">{{ __('reports.columns.amount') }}</th>
                <th>{{ __('reports.billing.received_by') }}</th>
                <th>{{ __('reports.columns.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->payment_number }}</td>
                <td>{{ $payment->patient->full_name ?? '—' }}</td>
                <td>{{ $payment->payment_method->label() }}</td>
                <td class="text-right text-success">₵{{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->receivedBy->full_name ?? '—' }}</td>
                <td>{{ $payment->paid_at?->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.print.confidential') }}</p>
    </div>
</body>
</html>
