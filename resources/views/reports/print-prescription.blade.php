<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.print_templates.prescription') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #198754; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; color: #198754; }
        .header .rx { font-size: 36px; color: #198754; font-weight: bold; }
        .header p { margin: 5px 0 0; color: #666; }
        .patient-info { margin-bottom: 15px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; }
        .patient-info table { border: none; margin: 0; width: 100%; }
        .patient-info td { border: none; padding: 3px 10px 3px 0; font-size: 12px; }
        .patient-info .lbl { font-weight: bold; color: #555; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; font-size: 11px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .drug-name { font-weight: bold; font-size: 12px; }
        .instructions { font-size: 10px; color: #555; font-style: italic; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .signature { margin-top: 40px; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="rx">℞</div>
        <h1>UHMS - {{ __('reports.print_templates.prescription') }}</h1>
        <p>{{ __('common.date') }}: {{ $prescription->created_at->format('d M Y') }}</p>
    </div>

    <div class="patient-info">
        <table>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.patient_name') }}:</td>
                <td>{{ $prescription->visit?->patient?->full_name ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.patient_id') }}:</td>
                <td>{{ $prescription->visit?->patient?->patient_number ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.date_of_birth') }}:</td>
                <td>{{ $prescription->visit?->patient?->date_of_birth?->format('d/m/Y') ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.gender') }}:</td>
                <td>{{ $prescription->visit?->patient?->gender?->label() ?? '—' }}</td>
            </tr>
            <tr>
                <td class="lbl">{{ __('reports.print_templates.prescriber') }}:</td>
                <td>{{ $prescription->prescriber?->name ?? '—' }}</td>
                <td class="lbl">{{ __('reports.print_templates.visit_no') }}:</td>
                <td>{{ $prescription->visit?->visit_number ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('reports.print_templates.medication') }}</th>
                <th>{{ __('reports.print_templates.dosage') }}</th>
                <th>{{ __('reports.print_templates.frequency') }}</th>
                <th>{{ __('reports.print_templates.duration') }}</th>
                <th>{{ __('reports.col_quantity') }}</th>
                <th>{{ __('reports.print_templates.route') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($prescription->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <span class="drug-name">{{ $item->drug?->name ?? '—' }}</span>
                    @if($item->instructions)
                    <br><span class="instructions">{{ $item->instructions }}</span>
                    @endif
                </td>
                <td>{{ $item->dosage ?? '—' }}</td>
                <td>{{ $item->frequency ?? '—' }}</td>
                <td>{{ $item->duration ?? '—' }}</td>
                <td>{{ $item->quantity ?? '—' }}</td>
                <td>{{ $item->route ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($prescription->notes)
    <p><strong>{{ __('reports.columns.notes') }}:</strong> {{ $prescription->notes }}</p>
    @endif

    <div class="signature">
        <p><strong>{{ __('reports.print_templates.prescribing_physician') }}:</strong> {{ $prescription->prescriber?->name ?? '—' }}</p>
        <div class="signature-line"></div>
        <p style="font-size: 10px; color: #666;">{{ __('reports.print_templates.signature_stamp') }}</p>
    </div>

    <div class="footer">
        <p>{{ __('reports.print.system_generated') }} &bull; {{ __('reports.sensitive.confidential_clinical') }}</p>
    </div>
</body>
</html>
