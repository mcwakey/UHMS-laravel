<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.visits.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #0d6efd; }
        .header p { margin: 5px 0 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - {{ __('reports.visits.title') }}</h1>
        <p>{{ __('common.generated') }}: {{ now()->format('d M Y H:i') }} | {{ __('reports.total') }}: {{ $stats['total_visits'] ?? 0 }} {{ __('reports.kpi.total_visits') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.visits.visit_number') }}</th>
                <th>{{ __('reports.columns.patient') }}</th>
                <th>{{ __('reports.columns.type') }}</th>
                <th>{{ __('reports.columns.priority') }}</th>
                <th>{{ __('reports.columns.department') }}</th>
                <th>{{ __('reports.columns.doctor') }}</th>
                <th>{{ __('reports.columns.status') }}</th>
                <th>{{ __('reports.columns.date') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($visits as $visit)
            <tr>
                <td>{{ $visit->visit_number }}</td>
                <td>{{ $visit->patient->full_name }}</td>
                <td>{{ $visit->visit_type->label() }}</td>
                <td>{{ $visit->priority->label() }}</td>
                <td>—</td>
                <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                <td>{{ $visit->status->label() }}</td>
                <td>{{ $visit->visit_date->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.print.confidential') }}</p>
    </div>
</body>
</html>
