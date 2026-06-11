<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.print_templates.lab_report') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #dc3545; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #dc3545; }
        .header p { margin: 5px 0 0; color: #666; }
        .patient-info { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; }
        .patient-info table { border: none; margin: 0; width: 100%; }
        .patient-info td { border: none; padding: 3px 10px 3px 0; font-size: 12px; }
        .patient-info .lbl { font-weight: bold; color: #555; width: 130px; }
        .section-title { font-size: 14px; font-weight: bold; margin: 15px 0 8px; color: #dc3545; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .abnormal { color: #dc3545; font-weight: bold; }
        .normal { color: #198754; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .signature-block { margin-top: 30px; display: table; width: 100%; }
        .sig-col { display: table-cell; width: 50%; vertical-align: top; }
        .signature-line { border-top: 1px solid #333; width: 180px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>UHMS - {{ __('reports.print_templates.lab_report') }}</h1>
        <p>{{ __('col_request') ?? 'Request' }} #: {{ $labRequest->request_number }} &bull; {{ __('common.date') }}: {{ $labRequest->created_at->format('d M Y') }}</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.patient_name') }}:</td>
                <td>{{ $labRequest->patient?->full_name ?? $labRequest->external_party_name ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.patient_id') }}:</td>
                <td>{{ $labRequest->patient?->patient_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ $labRequest->patient ? __('reports.print_templates.date_of_birth') : __('reports.print_templates.age') }}:</td>
                <td>{{ $labRequest->patient?->date_of_birth?->format('d/m/Y') ?? ($labRequest->external_party_age ? $labRequest->external_party_age.' yrs' : '—') }}</td>
                <td class="lbl">{{ __('reports.print_templates.gender') }}:</td>
                <td>{{ $labRequest->patient?->gender?->label() ?? ($labRequest->external_party_sex ?: '—') }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.requested_by') }}:</td>
                <td>{{ $labRequest->requestedBy?->name ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.dept') }}:</td>
                <td>{{ $labRequest->department?->name ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.priority') }}:</td>
                <td>{{ $labRequest->priority?->label() ?? ucfirst($labRequest->priority ?? 'Normal') }}</td>
                <td class="lbl">{{ __('reports.print_templates.sample_collected') }}:</td>
                <td>{{ $labRequest->sample_collected_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        </table>
    </div>

    @if($labRequest->clinical_notes)
    <div class="section-title">{{ __('reports.print_templates.clinical_info') }}</div>
    <p style="padding-left: 10px;">{{ $labRequest->clinical_notes }}</p>
    @endif

    <div class="section-title">{{ __('reports.print_templates.test_results') }}</div>
    <table>
        <thead>
            <tr>
                <th>{{ __('reports.print_templates.test') }}</th>
                <th>{{ __('reports.print_templates.result') }}</th>
                <th>{{ __('reports.print_templates.unit') }}</th>
                <th>{{ __('reports.print_templates.reference_range') }}</th>
                <th>{{ __('reports.columns.status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($labRequest->items as $item)
            <tr>
                <td>{{ $item->labTest?->name ?? '—' }}</td>
                <td class="{{ ($item->is_abnormal ?? false) ? 'abnormal' : 'normal' }}">{{ $item->result ?? 'Pending' }}</td>
                <td>{{ $item->labTest?->unit ?? '—' }}</td>
                <td>{{ $item->labTest?->reference_range ?? '—' }}</td>
                <td>{{ ucfirst($item->status ?? 'pending') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($labRequest->notes)
    <div class="section-title">{{ __('reports.print_templates.comments') }}</div>
    <p style="padding-left: 10px;">{{ $labRequest->notes }}</p>
    @endif

    <div class="signature-block">
        <div class="sig-col">
            <p><strong>{{ __('reports.print_templates.performed_by') }}:</strong> {{ $labRequest->performedBy?->name ?? '—' }}</p>
            <div class="signature-line"></div>
            <p style="font-size: 10px; color: #666;">{{ __('reports.print_templates.lab_technician') }}</p>
        </div>
        <div class="sig-col">
            <p><strong>{{ __('reports.print_templates.verified_by') }}:</strong> {{ $labRequest->verifiedBy?->name ?? '—' }}</p>
            <div class="signature-line"></div>
            <p style="font-size: 10px; color: #666;">{{ __('reports.print_templates.pathologist') }}</p>
        </div>
    </div>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.sensitive.confidential_clinical') }}</p>
    </div>
</body>
</html>
