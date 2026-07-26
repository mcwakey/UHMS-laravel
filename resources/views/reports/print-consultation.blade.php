<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.print_templates.consultation_note') }}</title>
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
        <h1>UHMS - {{ __('reports.print_templates.consultation_note') }}</h1>
        <p>{{ __('common.date') }}: {{ $record->created_at->format('d M Y H:i') }}</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.patient_name') }}:</td>
                <td>{{ $record->visit?->patient?->full_name ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.patient_id') }}:</td>
                <td>{{ $record->visit?->patient?->patient_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.date_of_birth') }}:</td>
                <td>{{ $record->visit?->patient?->date_of_birth?->format('d/m/Y') ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.gender') }}:</td>
                <td>{{ $record->visit?->patient?->gender?->label() ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.visit_no') }}:</td>
                <td>{{ $record->visit?->visit_number ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.dept') }}:</td>
                <td>{{ $record->visit?->department?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.doctor') }}:</td>
                <td colspan="3">{{ $record->doctor?->name ?? '—' }}</td>
            </tr>
        </table>
    </div>

    @if($record->complaints->count())
    <div class="section-title">{{ __('reports.print_templates.presenting_complaints') }}</div>
    <div class="content-block">
        @foreach($record->complaints as $complaint)
        <p>&bull; {{ $complaint->complaint }} @if($complaint->duration)({{ $complaint->duration }})@endif</p>
        @endforeach
    </div>
    @endif

    @if($record->physical_examination)
    <div class="section-title">{{ __('reports.print_templates.physical_examination') }}</div>
    <div class="content-block">
        <p>{{ $record->physical_examination }}</p>
    </div>
    @endif

    @if($record->visit?->vitals->count())
    <div class="section-title">{{ __('reports.print_templates.vitals') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('reports.print_templates.parameter') }}</th>
                <th>{{ __('reports.print_templates.value') }}</th>
                <th>{{ __('reports.print_templates.recorded_at') }}</th>
            </tr>
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
    <div class="section-title">{{ __('reports.print_templates.diagnoses') }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('reports.col_diagnosis') }}</th>
                <th>{{ __('reports.columns.type') }}</th>
                <th>{{ __('reports.columns.notes') }}</th>
            </tr>
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
    <div class="section-title">{{ __('reports.print_templates.clinical_notes') }}</div>
    <div class="content-block">
        <p>{{ $record->notes }}</p>
    </div>
    @endif

    {{-- Phase 14R.6.1 — Maternity Context.
         Active consultation → live projection.
         Completed consultation → the completion-time snapshot, with its
         version and capture metadata. Never current values under a historical
         label. Renders nothing while the summary flag is off. --}}
    @if (isset($maternitySummary) && $maternitySummary->shouldRender())
        <div class="section-title">
            @if ($maternitySummary->isSnapshot())
                {{ __('consultation_maternity_summary.summary.completion_snapshot_title') }}
                (v{{ $maternitySummary->snapshotVersion }})
            @elseif ($maternitySummary->isMissingSnapshot())
                {{ __('consultation_maternity_summary.summary.completion_snapshot_title') }}
            @else
                {{ __('consultation_maternity_summary.summary.current_record') }}
            @endif
        </div>
        <div class="content-block">
            <p style="font-size:10px;color:#666;">
                {{ __('consultation_maternity_summary.summary.source_of_truth') }} &middot;
                {{ __('consultation_maternity_summary.summary.encounter_source') }}
            </p>

            @if ($maternitySummary->isMissingSnapshot())
                <p>{{ __('consultation_maternity_summary.summary.no_snapshot_available') }}</p>
                <p style="font-size:10px;color:#666;">{{ __('consultation_maternity_summary.snapshot.none_fabricated') }}</p>
            @else
                @if ($maternitySummary->isSnapshot())
                    <p style="font-size:10px;color:#666;">
                        {{ __('consultation_maternity_summary.print.printed_snapshot_version') }}:
                        v{{ $maternitySummary->snapshotVersion }} &middot;
                        {{ __('consultation_maternity_summary.summary.captured_at') }}:
                        {{ $maternitySummary->capturedAt }} &middot;
                        {{ $maternitySummary->integrityVerified()
                            ? __('consultation_maternity_summary.snapshot.verified_short')
                            : ($maternitySummary->integrityMismatch()
                                ? __('consultation_maternity_summary.snapshot.mismatch_short')
                                : '—') }}
                    </p>
                    <p style="font-size:10px;color:#666;">{{ __('consultation_maternity_summary.print.historical_summary') }}</p>
                @endif

                @include('consultations.partials.maternity.summary-payload', [
                    'payload' => $maternitySummary->payload(),
                    'compact' => true,
                ])
            @endif
        </div>
    @endif


    <div class="signature">
        <p><strong>{{ __('reports.print_templates.consulting_physician') }}:</strong> {{ $record->doctor?->name ?? '—' }}</p>
        <div class="signature-line"></div>
        <p style="font-size: 10px; color: #666;">{{ __('reports.print.signature') }}</p>
    </div>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.sensitive.confidential_clinical') }}</p>
    </div>
</body>
</html>
