<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.hr.payroll_title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #6f42c1; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #6f42c1; }
        .header p { margin: 5px 0 0; color: #666; }
        .stats { display: table; width: 100%; margin-bottom: 20px; }
        .stat-box { display: table-cell; width: 25%; text-align: center; padding: 10px; }
        .stat-box .value { font-size: 18px; font-weight: bold; }
        .stat-box .label { font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 10px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-success { color: #198754; }
        .text-danger { color: #dc3545; }
        .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 8px; color: #6f42c1; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - {{ __('reports.hr.payroll_title') }}</h1>
        <p>{{ __('common.generated') }}: {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="value">{{ number_format($stats['total_records']) }}</div>
            <div class="label">{{ __('reports.kpi.total_records') }}</div>
        </div>
        <div class="stat-box">
            <div class="value text-success">₵{{ number_format($stats['total_gross'], 2) }}</div>
            <div class="label">{{ __('reports.kpi.total_gross') }}</div>
        </div>
        <div class="stat-box">
            <div class="value text-danger">₵{{ number_format($stats['total_deductions'], 2) }}</div>
            <div class="label">{{ __('reports.kpi.total_deductions') }}</div>
        </div>
        <div class="stat-box">
            <div class="value">₵{{ number_format($stats['total_net'], 2) }}</div>
            <div class="label">{{ __('reports.kpi.total_net') }}</div>
        </div>
    </div>

    @if($byDepartment->count())
    <div class="section-title">{{ __('reports.charts.pay_by_dept') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('reports.columns.department') }}</th>
                <th class="text-right">{{ __('reports.hr.staff') }}</th>
                <th class="text-right">{{ __('reports.columns.gross') }}</th>
                <th class="text-right">{{ __('reports.columns.deductions') }}</th>
                <th class="text-right">{{ __('reports.columns.net_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byDepartment as $dept)
            <tr>
                <td>{{ $dept->name }}</td>
                <td class="text-right">{{ $dept->staff_count }}</td>
                <td class="text-right">₵{{ number_format($dept->total_gross, 2) }}</td>
                <td class="text-right text-danger">₵{{ number_format($dept->total_deductions, 2) }}</td>
                <td class="text-right">₵{{ number_format($dept->total_net, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="section-title">{{ __('reports.hr.payroll_title') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('reports.columns.employee') }}</th>
                <th>{{ __('reports.columns.department') }}</th>
                <th>{{ __('reports.columns.period') }}</th>
                <th class="text-right">{{ __('reports.columns.basic') }}</th>
                <th class="text-right">{{ __('reports.columns.allowances') }}</th>
                <th class="text-right">{{ __('reports.columns.deductions') }}</th>
                <th class="text-right">{{ __('reports.columns.net_pay') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $rec)
            <tr>
                <td>{{ $rec->employee?->user?->name ?? '—' }}</td>
                <td>{{ $rec->employee?->department?->name ?? '—' }}</td>
                <td>{{ $rec->pay_period }}</td>
                <td class="text-right">₵{{ number_format($rec->basic_salary, 2) }}</td>
                <td class="text-right">₵{{ number_format($rec->total_allowances, 2) }}</td>
                <td class="text-right text-danger">₵{{ number_format($rec->total_deductions, 2) }}</td>
                <td class="text-right">₵{{ number_format($rec->net_salary, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.print.confidential') }}</p>
    </div>
</body>
</html>
