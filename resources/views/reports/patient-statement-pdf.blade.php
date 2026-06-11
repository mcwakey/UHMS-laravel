<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.statement.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #0d6efd; }
        .header p { margin: 5px 0 0; color: #666; }
        .patient-info { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; }
        .patient-info table { border: none; margin: 0; }
        .patient-info td { border: none; padding: 3px 15px 3px 0; font-size: 12px; }
        .patient-info .label { font-weight: bold; color: #555; }
        .stats { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 10px; }
        .stat-box .value { font-size: 16px; font-weight: bold; }
        .stat-box .label { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .totals td { font-weight: bold; background: #f8f9fa; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - {{ __('reports.statement.title') }}</h1>
        <p>{{ __('common.generated') }}: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td class="label">{{ __('reports.statement.patient_name') }}:</td>
                <td>{{ $patient->full_name }}</td>
                <td class="label">{{ __('reports.statement.patient_id') }}:</td>
                <td>{{ $patient->patient_number }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('common.phone') }}:</td>
                <td>{{ $patient->phone ?? '—' }}</td>
                <td class="label">{{ __('common.date_of_birth') }}:</td>
                <td>{{ $patient->date_of_birth?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value text-danger">₵{{ number_format($summary['total_charges'], 2) }}</div>
            <div class="label">{{ __('reports.statement.total_charges') }}</div>
        </div>
        <div class="stat-box">
            <div class="value text-success">₵{{ number_format($summary['total_payments'], 2) }}</div>
            <div class="label">{{ __('reports.statement.total_payments') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">₵{{ number_format($summary['balance_due'], 2) }}</div>
            <div class="label">{{ __('reports.statement.balance_due') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ $summary['invoice_count'] }} / {{ $summary['payment_count'] }}</div>
            <div class="label">{{ __('reports.statement.transaction_ledger') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.columns.date') }}</th>
                <th>{{ __('reports.statement.col_description') }}</th>
                <th class="text-right">{{ __('reports.statement.col_charges') }}</th>
                <th class="text-right">{{ __('reports.statement.col_payments') }}</th>
                <th class="text-right">{{ __('reports.statement.col_balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledger as $entry)
            <tr>
                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</td>
                <td>{{ $entry['description'] }}</td>
                <td class="text-right text-danger">{{ $entry['type'] === 'charge' ? '₵' . number_format($entry['amount'], 2) : '' }}</td>
                <td class="text-right text-success">{{ $entry['type'] === 'payment' ? '₵' . number_format($entry['amount'], 2) : '' }}</td>
                <td class="text-right">₵{{ number_format($entry['balance'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals">
                <td colspan="2">{{ __('reports.columns.totals') }}</td>
                <td class="text-right text-danger">₵{{ number_format($summary['total_charges'], 2) }}</td>
                <td class="text-right text-success">₵{{ number_format($summary['total_payments'], 2) }}</td>
                <td class="text-right">₵{{ number_format($summary['balance_due'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.print.confidential') }}</p>
    </div>
</body>
</html>
