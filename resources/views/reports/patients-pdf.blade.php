<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.patients.title') }}</title>
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
        <h1>UHMS - {{ __('reports.patients.title') }}</h1>
        <p>{{ __('common.generated') }}: {{ now()->format('d M Y H:i') }} | {{ __('reports.total') }}: {{ $stats['total_patients'] ?? 0 }} {{ __('reports.kpi.total_patients') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('reports.columns.patient_id') }}</th>
                <th>{{ __('reports.columns.name') }}</th>
                <th>{{ __('reports.columns.type') }}</th>
                <th>{{ __('reports.columns.dob') }}</th>
                <th>{{ __('reports.columns.phone') }}</th>
                <th>{{ __('reports.columns.visits') }}</th>
                <th>{{ __('reports.columns.registered') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($patients as $patient)
            <tr>
                <td>{{ $patient->patient_number }}</td>
                <td>{{ $patient->full_name }}</td>
                <td>{{ ucfirst($patient->gender?->value ?? '—') }}</td>
                <td>{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</td>
                <td><x-patient-protected-field field="phone" :value="$patient->phone" mode="export" /></td>
                <td>{{ $patient->visits_count }}</td>
                <td>{{ $patient->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.print.confidential') }}</p>
    </div>
</body>
</html>
