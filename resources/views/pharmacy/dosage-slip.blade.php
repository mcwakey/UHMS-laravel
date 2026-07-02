<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dosage Slip</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f5f5f5;
            color: #000;
            font-family: Arial, DejaVu Sans, sans-serif;
            font-size: 12px;
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
        .brand { font-size: 17px; font-weight: 800; }
        .title { font-size: 13px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #333; }
        .rule { border-top: 1px dashed #000; margin: 7px 0; }
        .drug { font-size: 15px; font-weight: 800; overflow-wrap: anywhere; }
        .kv { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
        .kv .k { font-weight: 700; white-space: nowrap; }
        .kv .v { text-align: right; overflow-wrap: anywhere; }
        .instructions { margin-top: 7px; padding-top: 6px; border-top: 1px dashed #000; }
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
    $prescription = $item->prescription;
    $patient = $prescription?->patient;
@endphp
<div class="print-toolbar">
    <button type="button" class="secondary" onclick="window.close()">Close</button>
    <button type="button" onclick="window.print()">Print</button>
</div>

<div class="receipt">
    <div class="center">
        <!-- <div class="footer">UHMS</div> -->
        <div class="title">Dosage Slip</div>
        <div class="muted">{{ $prescription?->prescription_number }} | {{ now()->format('d M Y H:i') }}</div>
    </div>

    <div class="rule"></div>

    <!-- <div class="kv"><span class="k">{{ __('common.patient') }}</span><span class="v">{{ $patient?->full_name ?? '-' }}</span></div>
    <div class="kv"><span class="k">Folder</span><span class="v">{{ $patient?->patient_number ?? '-' }}</span></div> -->

    <!-- <div class="rule"></div> -->

    <div class="drug">{{ $item->drug_name ?: ($item->drug?->name ?? '-') }}</div>
    @if($item->drug)
        <div class="muted">{{ trim(($item->drug->dosage_form ?? '').' '.($item->drug->strength ?? '')) }}</div>
    @endif

    <div class="rule"></div>

    <div class="kv"><span class="k">{{ __('pharmacy.dosage') }}</span><span class="v">{{ $item->dosage ?: '-' }} x {{ $item->frequency ?: '-' }} - {{ $item->duration ?: '-' }} days</span></div>
    <!-- <div class="kv"><span class="k">{{ __('pharmacy.frequency') }}</span><span class="v"></span></div>
    <div class="kv"><span class="k">{{ __('pharmacy.duration') }}</span><span class="v"></span></div> -->
    <div class="kv"><span class="k">{{ __('pharmacy.route') }}</span><span class="v">{{ $item->route ?: '-' }}</span>
    <span class="k">{{ __('common.qty') }}</span><span class="v">{{ $item->quantity ?? '-' }}</span></div>

    @if($item->instructions)
        <div class="instructions">
            <strong>{{ __('common.notes') }}:</strong> {{ $item->instructions }}
        </div>
    @endif

    <div class="footer">Follow the prescriber's instructions.</div>
</div>
</body>
</html>
