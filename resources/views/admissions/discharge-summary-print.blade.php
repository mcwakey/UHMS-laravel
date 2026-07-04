<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('admissions.structured_discharge_summary') }} - {{ $admission->admission_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 2rem; }
        h1 { font-size: 1.5rem; margin-bottom: .25rem; }
        h2 { font-size: 1rem; border-bottom: 1px solid #d1d5db; padding-bottom: .25rem; margin-top: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        td { padding: .25rem 0; vertical-align: top; }
        .muted { color: #6b7280; }
        @media print { button { display: none; } body { margin: 1rem; } }
    </style>
</head>
<body>
    <button onclick="window.print()">{{ __('admissions.print_summary') }}</button>
    <h1>{{ __('admissions.structured_discharge_summary') }}</h1>
    <div class="muted">{{ $admission->admission_number }} · {{ $admission->patient->full_name }} · {{ $admission->patient->patient_number }}</div>

    <table>
        <tr><td class="muted">{{ __('admissions.ward_bed') }}</td><td>{{ $admission->bed?->ward?->name }} / {{ $admission->bed?->bed_number }}</td></tr>
        <tr><td class="muted">{{ __('admissions.admitted_on') }}</td><td>{{ $admission->admission_date?->format('d M Y H:i') }}</td></tr>
        <tr><td class="muted">{{ __('admissions.summary_status') }}</td><td>{{ $summary->summary_status?->label() }}</td></tr>
        <tr><td class="muted">{{ __('admissions.prepared_by') }}</td><td>{{ $summary->preparedBy->name ?? '—' }}</td></tr>
        <tr><td class="muted">{{ __('admissions.approved_by') }}</td><td>{{ $summary->approvedBy->name ?? '—' }} {{ $summary->approved_at ? '(' . $summary->approved_at->format('d M Y H:i') . ')' : '' }}</td></tr>
    </table>

    @foreach([
        'primary_diagnosis' => __('admissions.primary_diagnosis'),
        'admission_reason' => __('admissions.admission_reason'),
        'hospital_course' => __('admissions.hospital_course'),
        'investigations_summary' => __('admissions.investigations_summary'),
        'procedures_summary' => __('admissions.procedures_summary'),
        'treatment_given' => __('admissions.treatment_given'),
        'discharge_condition' => __('admissions.discharge_condition'),
        'discharge_medications' => __('admissions.discharge_medications'),
        'follow_up_instructions' => __('admissions.follow_up_instructions'),
        'warning_signs' => __('admissions.warning_signs'),
        'final_outcome' => __('admissions.final_outcome'),
    ] as $field => $label)
        @if($summary->{$field})
            <h2>{{ $label }}</h2>
            <p>{{ $summary->{$field} }}</p>
        @endif
    @endforeach

    @if($summary->secondary_diagnoses)
        <h2>{{ __('admissions.secondary_diagnoses') }}</h2>
        <ul>
            @foreach($summary->secondary_diagnoses as $diagnosis)
                <li>{{ $diagnosis }}</li>
            @endforeach
        </ul>
    @endif

    @if($summary->follow_up_date)
        <h2>{{ __('admissions.follow_up_date') }}</h2>
        <p>{{ $summary->follow_up_date->format('d M Y') }}</p>
    @endif
</body>
</html>
