<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('reports.aging.title') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #333; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 14px; width: 100%; }
        .header td { vertical-align: top; }
        .logo { font-size: 18px; font-weight: bold; color: #0d6efd; }
        .logo small { display: block; font-size: 9px; color: #666; font-weight: normal; }
        .title { font-size: 16px; font-weight: bold; text-align: right; }
        .sub { font-size: 10px; color: #666; text-align: right; }
        .buckets { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .buckets td { padding: 8px; border: 1px solid #dee2e6; text-align: center; }
        .buckets .bk-label { font-size: 8px; color: #888; text-transform: uppercase; }
        .buckets .bk-total { font-size: 12px; font-weight: bold; margin-top: 3px; }
        table.rows { width: 100%; border-collapse: collapse; }
        table.rows th, table.rows td { padding: 5px 4px; border: 1px solid #dee2e6; text-align: left; font-size: 8px; }
        table.rows th { background: #f8f9fa; font-weight: 600; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .muted { color: #888; }
        .grand { font-weight: bold; background: #f1f5ff; }
        .footer { margin-top: 18px; text-align: center; color: #888; font-size: 9px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td><div class="logo">UHMS<small>{{ __('common.app_tagline') }}</small></div></td>
            <td>
                <div class="title">{{ __('reports.aging.title') }}</div>
                <div class="sub">{{ __('reports.aging.as_of') }} {{ $aging['as_of'] ?? $generatedAt->format('Y-m-d') }} | {{ __('common.generated') }} {{ $generatedAt->format('d M Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="buckets">
        <tr>
            @foreach($aging['buckets'] as $bucket)
            <td>
                <div class="bk-label">{{ $bucket['label'] }} ({{ $bucket['count'] }})</div>
                <div class="bk-total">&#8373;{{ number_format($bucket['total'], 2) }}</div>
            </td>
            @endforeach
        </tr>
    </table>

    <table class="rows">
        <thead>
            <tr>
                <th>{{ __('reports.columns.invoice_number') }}</th>
                <th>{{ __('reports.columns.patient') }}</th>
                <th>{{ __('reports.columns.payer') }}</th>
                <th>{{ __('reports.columns.due_aging') }}</th>
                <th>{{ __('reports.columns.status') }}</th>
                <th class="text-center">{{ __('reports.columns.days') }}</th>
                <th>{{ __('reports.columns.bucket') }}</th>
                <th class="text-end">{{ __('reports.columns.allocated') }}</th>
                <th class="text-end">{{ __('reports.columns.paid') }}</th>
                <th class="text-end">{{ __('reports.columns.adjustments') }}</th>
                <th class="text-end">{{ __('reports.columns.balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($aging['rows'] as $row)
            <tr>
                <td>{{ $row['invoice_number'] }}</td>
                <td>{{ $row['patient_name'] }}<br><span class="muted">{{ $row['patient_number'] }}</span></td>
                <td>{{ __('statuses.default.' . $row['payer_type']) }}<br><span class="muted">{{ $row['payer_name'] }}</span></td>
                <td>{{ $row['due_date'] ?? 'No due date' }}<br><span class="muted">From {{ $row['aging_start_date'] }}</span></td>
                <td>{{ __('statuses.default.' . $row['status']) }}</td>
                <td class="text-center">{{ $row['days_overdue'] }}</td>
                <td>{{ $aging['buckets'][$row['bucket']]['label'] ?? $row['bucket'] }}</td>
                <td class="text-end">&#8373;{{ number_format($row['allocated_amount'], 2) }}</td>
                <td class="text-end">&#8373;{{ number_format($row['paid_amount'], 2) }}</td>
                <td class="text-end">&#8373;{{ number_format($row['adjustment_amount'], 2) }}</td>
                <td class="text-end">&#8373;{{ number_format($row['balance'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="11" class="text-center" style="padding:14px;">{{ __('reports.aging.no_receivables') }}</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="grand">
                <td colspan="10" class="text-end">{{ __('reports.aging.grand_total') }} ({{ $aging['grand_count'] }} {{ __('reports.kpi.patient_receivables') }})</td>
                <td class="text-end">&#8373;{{ number_format($aging['grand_total'], 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">{{ __('reports.print.system_generated') }}</div>
</body>
</html>
