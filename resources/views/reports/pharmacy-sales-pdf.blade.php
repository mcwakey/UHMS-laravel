<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.pharmacy.sales_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #198754; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #198754; }
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
        <h1>UHMS - {{ __('reports.pharmacy.sales_title') }}</h1>
        <p>{{ __('common.generated') }}: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value text-success">₵{{ number_format($stats['total_revenue'], 2) }}</div>
            <div class="label">{{ __('reports.kpi.total_revenue') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_dispensed']) }}</div>
            <div class="label">{{ __('reports.kpi.total_dispensed') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_items']) }}</div>
            <div class="label">{{ __('reports.kpi.items_dispensed') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">{{ number_format($stats['unique_patients']) }}</div>
            <div class="label">{{ __('reports.kpi.unique_patients') }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.columns.date') }}</th>
                <th>{{ __('reports.columns.patient') }}</th>
                <th>{{ __('reports.columns.drug') }}</th>
                <th>{{ __('reports.columns.batch') }}</th>
                <th class="text-right">{{ __('reports.columns.qty') }}</th>
                <th class="text-right">{{ __('reports.columns.unit_price') }}</th>
                <th class="text-right">{{ __('reports.columns.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $rec)
            <tr>
                <td>{{ $rec->created_at->format('d/m/Y') }}</td>
                <td>{{ $rec->prescription?->visit?->patient?->full_name ?? '—' }}</td>
                <td>{{ $rec->prescriptionItem?->drug?->name ?? '—' }}</td>
                <td>—</td>
                <td class="text-right">{{ $rec->quantity_dispensed }}</td>
                <td class="text-right">₵{{ number_format($rec->unit_price ?? 0, 2) }}</td>
                <td class="text-right text-success">₵{{ number_format(($rec->unit_price ?? 0) * $rec->quantity_dispensed, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.print.confidential') }}</p>
    </div>
</body>
</html>
