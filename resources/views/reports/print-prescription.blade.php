<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('reports.print_templates.prescription') }}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f5f5f5;
            color: #000;
            font-family: Arial, DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.25;
        }
        .print-toolbar {
            width: 80mm;
            margin: 10px auto;
            display: flex;
            justify-content: flex-end;
            gap: 6px;
        }
        .print-toolbar button {
            border: 0;
            border-radius: 5px;
            padding: 7px 11px;
            cursor: pointer;
            background: #111827;
            color: #fff;
            font-size: 12px;
        }
        .print-toolbar .secondary {
            background: #fff;
            color: #111827;
            border: 1px solid #cbd5e1;
        }
        .receipt {
            width: 80mm;
            margin: 0 auto;
            padding: 3mm;
            background: #fff;
        }
        .center { text-align: center; }
        .brand { font-size: 18px; font-weight: 800; letter-spacing: .5px; }
        .title { font-size: 13px; font-weight: 800; margin-top: 2px; text-transform: uppercase; }
        .muted { color: #333; }
        .rule { border-top: 1px dashed #000; margin: 7px 0; }
        .kv { display: flex; justify-content: space-between; gap: 8px; margin: 2px 0; }
        .kv .k { font-weight: 700; white-space: nowrap; }
        .kv .v { text-align: right; overflow-wrap: anywhere; }
        .section-title { font-weight: 800; text-transform: uppercase; margin: 8px 0 4px; }
        .rx-item { padding: 6px 0; border-top: 1px dashed #000; }
        .rx-item:first-child { border-top: 0; }
        .drug-name { font-weight: 800; font-size: 12px; overflow-wrap: anywhere; }
        .sig { margin-top: 16px; }
        .sig-line { border-top: 1px solid #000; margin-top: 22px; padding-top: 3px; text-align: center; }
        .footer { margin-top: 8px; font-size: 9px; text-align: center; }
        @media print {
            body { background: #fff; }
            .print-toolbar { display: none; }
            .receipt { width: 100%; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
@php
    $patient = $prescription->patient ?? $prescription->visit?->patient;
    $doctor = $prescription->doctor;
@endphp
<div class="print-toolbar">
    <button type="button" class="secondary" onclick="window.close()">Close</button>
    <button type="button" onclick="window.print()">Print</button>
</div>

<div class="receipt">
    <div class="center">
        <div class="brand">UHMS</div>
        <div class="title">{{ __('reports.print_templates.prescription') }}</div>
        <div class="muted">{{ $prescription->prescription_number }}</div>
        <div class="muted">{{ $prescription->created_at->format('d M Y H:i') }}</div>
    </div>

    <div class="rule"></div>

    <div class="kv"><span class="k">{{ __('common.patient') }}</span><span class="v">{{ $patient?->full_name ?? '-' }}</span></div>
    <div class="kv"><span class="k">{{ __('reports.print_templates.gender') }}</span><span class="v">{{ $patient?->gender?->label() ?? '-' }}</span></div>
    <div class="kv"><span class="k">Folder</span><span class="v">{{ $patient?->patient_number ?? '-' }}</span></div>
    <div class="kv"><span class="k">{{ __('reports.print_templates.visit_no') }}</span><span class="v">{{ $prescription->visit?->visit_number ?? '-' }}</span></div>
    <div class="kv"><span class="k">{{ __('reports.print_templates.prescriber') }}</span><span class="v">{{ $doctor?->full_name ?? $doctor?->name ?? '-' }}</span></div>

    <div class="rule"></div>
    <div class="section-title">Medication</div>

    @foreach($prescription->items as $i => $item)
        <div class="rx-item">
            <div class="drug-name">{{ $i + 1 }}. {{ $item->drug_name ?: ($item->drug?->name ?? '-') }}</div>
            @if($item->drug)
                <div class="muted">{{ trim(($item->drug->dosage_form ?? '').' '.($item->drug->strength ?? '')) }}</div>
            @endif
            <div class="kv"><span class="k">{{ __('pharmacy.dosage') }}</span><span class="v">{{ $item->dosage ?? '-' }} x {{ $item->frequency ?? '-' }} - {{ $item->duration ?? '-' }} days</span></div>
            <!-- <div class="kv"><span class="k">{{ __('pharmacy.frequency') }}</span><span class="v"></span></div>
            <div class="kv"><span class="k">{{ __('pharmacy.duration') }}</span><span class="v"></span></div> -->
            <div class="kv"><span class="k">{{ __('pharmacy.route') }}</span><span class="v">{{ $item->route ?? '-' }}</span>
            <span class="k">{{ __('common.qty') }}</span><span class="v">{{ $item->quantity ?? '-' }}</span></div>
            @if($item->instructions)
                <div><strong>{{ __('common.notes') }}:</strong> {{ $item->instructions }}</div>
            @endif
        </div>
    @endforeach

    @if($prescription->notes)
        <div class="rule"></div>
        <div><strong>{{ __('reports.columns.notes') }}:</strong> {{ $prescription->notes }}</div>
    @endif

    <div class="sig">
        <div class="sig-line">{{ __('reports.print_templates.prescribing_physician') }}</div>
    </div>

    <div class="footer">
        {{ __('reports.print.system_generated') }}<br>
        {{ __('reports.sensitive.confidential_clinical') }}
    </div>
</div>
</body>
</html>
