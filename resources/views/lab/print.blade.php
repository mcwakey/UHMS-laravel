@php
    $result  = $item->result;
    $request = $item->labRequest;
    $patient = $request->patient;
    $visit   = $request->visit;
    $serviceCriteria = $item->service?->investigationCriteria?->where('is_active', true) ?? collect();
    $serviceHeaders  = $item->service?->investigationHeaders?->where('is_active', true) ?? collect();
    $values  = $result?->values ?? collect();
    $valuesByCriteria = $values->keyBy('criteria_id');
    $hospital = config('app.name');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investigation Result — {{ $request->request_number }} — {{ $item->display_name }}</title>
    <style>
        @page { margin: 18mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
        h1, h2, h3 { margin: 0; }
        .header { border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 12px; }
        .hospital { font-size: 18px; font-weight: bold; }
        .meta { display: flex; justify-content: space-between; font-size: 11px; color: #555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f4f4f4; font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
        .label { color: #777; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        .value { font-weight: bold; font-size: 12px; }
        .signatures { margin-top: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .sig-box { border-top: 1px solid #333; padding-top: 4px; text-align: center; font-size: 11px; }
        .footer { margin-top: 16px; text-align: center; font-size: 10px; color: #777; }
        .no-print { margin: 12px 0; }
        @media print { .no-print { display: none; } }
        .section-title { font-weight: bold; font-size: 12px; margin: 12px 0 4px; border-bottom: 1px solid #999; padding-bottom: 2px; }
        .badge-verified { background: #198754; color: white; padding: 2px 6px; font-size: 10px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:6px 12px;">{{ __('lab.print_button') }}</button>
    <button onclick="window.close()" style="padding:6px 12px;">{{ __('lab.close_button') }}</button>
</div>

<div class="header">
    <div class="hospital">{{ $hospital }}</div>
    <div style="font-size:11px;color:#555;">{{ __('lab.result_report_title') }} &nbsp;<span class="badge-verified">{{ __('lab.verified_badge') }}</span></div>
</div>

<div class="meta">
    <div>{{ __('lab.request_hash') }}: <strong>{{ $request->request_number }}</strong></div>
    <div>{{ __('lab.date_printed') }}: {{ now()->format('d M Y H:i') }}</div>
</div>

<div class="grid">
    <div>
        <span class="label">{{ $patient ? __('lab.patient_label') : __('lab.recipient_label') }}</span><br>
        <span class="value">{{ $patient?->full_name ?? $request->external_party_name ?? '—' }}</span><br>
        <span style="font-size:11px;color:#555;">
            {{ $patient?->patient_number ?? ($request->external_party_name ? __('lab.walk_in') : '') }}
            @if($patient?->age) &middot; {{ $patient->age }}y @elseif($request->external_party_age) &middot; {{ $request->external_party_age }}y @endif
            @if($patient?->gender) &middot; {{ ucfirst($patient->gender->value ?? $patient->gender) }} @elseif($request->external_party_sex) &middot; {{ $request->external_party_sex }} @endif
        </span>
    </div>
    <div>
        <span class="label">{{ __('lab.visit_label') }}</span><br>
        <span class="value">{{ $visit?->visit_number ?? '—' }}</span><br>
        <span style="font-size:11px;color:#555;">
            {{ $visit?->visit_date?->format('d M Y') ?? '' }}
            @if($visit?->currentConsultationDoctor()) &middot; Dr. {{ $visit->currentConsultationDoctor()->full_name ?? $visit->currentConsultationDoctor()->name }} @endif
        </span>
    </div>
    <div>
        <span class="label">{{ __('lab.investigation_col') }}</span><br>
        <span class="value">{{ $item->display_name }}</span>
    </div>
    <div>
        <span class="label">{{ __('lab.department_label') }}</span><br>
        <span class="value">{{ $request->targetDepartment->name ?? '—' }}</span>
    </div>
</div>

@if($request->clinical_info)
<div style="margin-bottom:8px;">
    <span class="label">{{ __('lab.clinical_information') }}</span><br>
    <span>{{ $request->clinical_info }}</span>
</div>
@endif

<div class="section-title">{{ __('lab.result_col') }}</div>

@if($values->isNotEmpty())
    @foreach($serviceHeaders as $h)
        @php $hCriteria = $serviceCriteria->where('header_id', $h->id); @endphp
        @if($hCriteria->isNotEmpty())
        <div style="font-weight:bold;margin-top:8px;">{{ $h->name }}</div>
        <table>
            <thead><tr><th>{{ __('lab.parameter_th') }}</th><th>{{ __('lab.value_th') }}</th><th>{{ __('lab.unit_th') }}</th><th>{{ __('lab.reference_range_th') }}</th><th>{{ __('lab.flag_th') }}</th></tr></thead>
            <tbody>
            @foreach($hCriteria as $c)
                @php $v = $valuesByCriteria->get($c->id); @endphp
                <tr>
                    <td>{{ $c->name }}</td>
                    <td><strong>{{ $v?->value ?? '—' }}</strong></td>
                    <td>{{ $v?->unit ?? $c->unit }}</td>
                    <td>{{ $v?->reference_range ?? $c->reference_range }}</td>
                    <td>{{ $v?->flag ? ucfirst($v->flag) : '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    @endforeach

    @php $unsorted = $values->filter(fn ($v) => $serviceCriteria->firstWhere('id', $v->criteria_id)?->header_id === null || $serviceCriteria->firstWhere('id', $v->criteria_id) === null); @endphp
    @if($unsorted->isNotEmpty())
    <table>
        <thead><tr><th>{{ __('lab.parameter_th') }}</th><th>{{ __('lab.value_th') }}</th><th>{{ __('lab.unit_th') }}</th><th>{{ __('lab.reference_range_th') }}</th><th>{{ __('lab.flag_th') }}</th></tr></thead>
        <tbody>
        @foreach($unsorted as $v)
            <tr>
                <td>{{ $v->name }}</td>
                <td><strong>{{ $v->value }}</strong></td>
                <td>{{ $v->unit }}</td>
                <td>{{ $v->reference_range }}</td>
                <td>{{ $v->flag ? ucfirst($v->flag) : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
@elseif($result->result_text)
    <div style="white-space:pre-wrap;border:1px solid #ccc;padding:8px;">{{ $result->result_text }}</div>
@elseif($result->result_value)
    <div><strong>{{ $result->result_value }}</strong></div>
@endif

@if($result->remarks)
<div style="margin-top:8px;"><strong>{{ __('lab.remarks_label') }}:</strong> {{ $result->remarks }}</div>
@endif

<div class="signatures">
    <div>
        <div class="sig-box">
            {{ $result->performedBy?->name ?? '—' }}<br>
            <small>{{ __('lab.performed_by_sig') }} &middot; {{ $result->performed_at?->format('d M Y H:i') }}</small>
        </div>
    </div>
    <div>
        <div class="sig-box">
            {{ $result->verifiedBy?->name ?? '—' }}<br>
            <small>{{ __('lab.verified_by_sig') }} &middot; {{ $result->verified_at?->format('d M Y H:i') }}</small>
        </div>
    </div>
</div>

<div class="footer">
    {{ __('lab.footer_generated', ['hospital' => $hospital, 'date' => now()->format('d M Y H:i')]) }}
</div>

<script>window.addEventListener('load', () => { setTimeout(() => window.print(), 250); });</script>
</body>
</html>
