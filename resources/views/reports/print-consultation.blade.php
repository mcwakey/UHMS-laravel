<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consultation Note</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0d6efd; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #0d6efd; }
        .header p { margin: 5px 0 0; color: #666; }
        .patient-info { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; }
        .patient-info table { border: none; margin: 0; width: 100%; }
        .patient-info td { border: none; padding: 3px 10px 3px 0; font-size: 12px; }
        .patient-info .lbl { font-weight: bold; color: #555; width: 120px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 8px; color: #0d6efd; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .content-block { margin-bottom: 10px; padding-left: 10px; }
        .content-block p { margin: 3px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .signature { margin-top: 40px; }
        .signature-line { border-top: 1px solid #333; width: 200px; display: inline-block; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - Consultation Note</h1>
        <p>Date: {{ $record->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td class="lbl">Patient Name:</td>
                <td>{{ $record->visit?->patient?->full_name ?? '—' }}</td>
                <td class="lbl">Patient ID:</td>
                <td>{{ $record->visit?->patient?->patient_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Date of Birth:</td>
                <td>{{ $record->visit?->patient?->date_of_birth?->format('d/m/Y') ?? '—' }}</td>
                <td class="lbl">Gender:</td>
                <td>{{ $record->visit?->patient?->gender?->label() ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Visit #:</td>
                <td>{{ $record->visit?->visit_number ?? '—' }}</td>
                <td class="lbl">Department:</td>
                <td>{{ $record->visit?->department?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">Doctor:</td>
                <td colspan="3">{{ $record->doctor?->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    @if($record->complaints->count())
    <div class="section-title">Presenting Complaints</div>
    <div class="content-block">
        @foreach($record->complaints as $complaint)
        <p>&bull; {{ $complaint->complaint }} @if($complaint->duration)({{ $complaint->duration }})@endif</p>
        @endforeach
    </div>
    @endif

    @if($record->physical_examination)
    <div class="section-title">Physical Examination</div>
    <div class="content-block">
        <p>{{ $record->physical_examination }}</p>
    </div>
    @endif

    @if($record->visit?->vitals->count())
    <div class="section-title">Vitals</div>
    <table>
        <thead>
            <tr><th>Parameter</th><th>Value</th><th>Recorded At</th></tr>
        </thead>
        <tbody>
            @foreach($record->visit->vitals as $vital)
            <tr>
                <td>
                    @if($vital->temperature) Temperature @endif
                    @if($vital->blood_pressure_systolic) Blood Pressure @endif
                    @if($vital->pulse) Pulse @endif
                    @if($vital->weight) Weight @endif
                </td>
                <td>
                    @if($vital->temperature) {{ $vital->temperature }}°C @endif
                    @if($vital->blood_pressure_systolic) {{ $vital->blood_pressure_systolic }}/{{ $vital->blood_pressure_diastolic }} mmHg @endif
                    @if($vital->pulse) {{ $vital->pulse }} bpm @endif
                    @if($vital->weight) {{ $vital->weight }} kg @endif
                </td>
                <td>{{ $vital->created_at->format('d/m/Y H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($record->diagnoses->count())
    <div class="section-title">Diagnoses</div>
    <table>
        <thead>
            <tr><th>#</th><th>Diagnosis</th><th>Type</th><th>Notes</th></tr>
        </thead>
        <tbody>
            @foreach($record->diagnoses as $i => $diag)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $diag->diagnosis ?? $diag->description ?? '—' }}</td>
                <td>{{ $diag->type ?? '—' }}</td>
                <td>{{ $diag->notes ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($record->notes)
    <div class="section-title">Clinical Notes</div>
    <div class="content-block">
        <p>{{ $record->notes }}</p>
    </div>
    @endif

    <div class="signature">
        <p><strong>Consulting Physician:</strong> {{ $record->doctor?->name ?? '—' }}</p>
        <div class="signature-line"></div>
        <p style="font-size: 10px; color: #666;">Signature</p>
    </div>

    <div class="footer">
        <p>University Hospital Management System (UHMS) &bull; Confidential Medical Document</p>
    </div>
</body>
</html>
