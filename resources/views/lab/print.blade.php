@php
    $patient = $request->patient;
    $visit = $request->visit;
    $org = \App\Models\Setting::getGroup('organization');
    $orgName = $org['name'] ?? config('app.name', 'UHMS');
    $orgLogo = ! empty($org['logo']) ? asset('storage/' . $org['logo']) : null;
    $orgAddress = collect([$org['address'] ?? null, $org['city'] ?? null, $org['region'] ?? null])->filter()->implode(', ');
    $orgContact = collect([$org['phone'] ?? null, $org['email'] ?? null])->filter()->implode(' | ');
    $doctor = $visit?->currentConsultationDoctor();
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('lab.result_report_title') }} - {{ $request->request_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 14mm 14mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #17243a; font-family: "Segoe UI", Arial, sans-serif; font-size: 10.5px; line-height: 1.35; background: #eef2f7; }
        .print-toolbar { max-width: 210mm; margin: 12px auto; display: flex; gap: 8px; }
        .print-toolbar button { border: 0; border-radius: 6px; padding: 8px 14px; cursor: pointer; }
        .print-primary { background: #155eef; color: #fff; }
        .print-secondary { background: #fff; color: #344054; }
        .report-page { width: 210mm; min-height: 297mm; margin: 0 auto 12px; padding: 12mm 14mm 14mm; background: #fff; }
        .report-header { display: grid; grid-template-columns: 1fr auto; gap: 18px; align-items: center; padding-bottom: 10px; border-bottom: 3px solid #155eef; }
        .brand { display: flex; gap: 12px; align-items: center; }
        .brand img { max-height: 48px; max-width: 125px; }
        .brand-name { font-size: 19px; font-weight: 800; color: #102a56; }
        .brand-meta { color: #667085; font-size: 9px; }
        .document-title { text-align: right; }
        .document-title strong { display: block; color: #155eef; font-size: 16px; text-transform: uppercase; letter-spacing: .08em; }
        .document-title span { color: #667085; }
        .patient-band { display: grid; grid-template-columns: 1.4fr .8fr .8fr .9fr; gap: 8px; margin: 12px 0; padding: 10px 12px; border: 1px solid #d8e1ee; border-radius: 8px; background: #f8fbff; }
        .meta-label { color: #667085; font-size: 8px; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
        .meta-value { margin-top: 2px; font-weight: 700; }
        .clinical-note { margin: 0 0 10px; padding: 8px 10px; border-left: 3px solid #7aa7e8; background: #f8fafc; }
        .result-report-section { margin: 0 0 12px; border: 1px solid #d8e1ee; border-radius: 8px; overflow: hidden; break-inside: avoid; page-break-inside: avoid; }
        .result-page-break { break-before: page; page-break-before: always; }
        .result-report-heading { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 9px 11px; border-bottom: 1px solid #dde5ef; background: #f5f8fc; }
        .result-report-heading h2 { margin: 0; color: #102a56; font-size: 13px; }
        .result-report-kicker { color: #667085; font-size: 8px; text-transform: uppercase; letter-spacing: .08em; }
        .result-status { border-radius: 999px; padding: 3px 7px; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        .result-status-normal { background: #dcfce7; color: #166534; }
        .result-status-alert { background: #fee2e2; color: #991b1b; }
        .result-group-title { margin: 8px 10px 4px; color: #475467; font-size: 9px; text-transform: uppercase; letter-spacing: .06em; }
        .result-table { width: calc(100% - 20px); margin: 0 10px 9px; border-collapse: collapse; }
        .result-table th, .result-table td { border-bottom: 1px solid #e4e9f0; padding: 5px 6px; text-align: left; }
        .result-table th { color: #667085; background: #f8fafc; font-size: 8px; text-transform: uppercase; letter-spacing: .04em; }
        .result-table .result-value { font-weight: 800; }
        .result-flag-normal { color: #166534; }
        .result-flag-alert { color: #b42318; background: #fff8f6; }
        .result-narrative, .result-summary-value { margin: 9px 10px; padding: 9px; background: #f8fafc; white-space: pre-wrap; }
        .result-summary-value { font-size: 15px; font-weight: 800; }
        .result-remarks, .result-attachment { margin: 0 10px 8px; padding: 6px 8px; border-left: 2px solid #7aa7e8; background: #f8fafc; }
        .signoff { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-top: 20px; }
        .signature { padding-top: 5px; border-top: 1px solid #98a2b3; text-align: center; }
        .signature strong { display: block; }
        .report-footer { margin-top: 12px; padding-top: 7px; border-top: 1px solid #d0d5dd; color: #667085; font-size: 8px; display: flex; justify-content: space-between; }
        @media print {
            body { background: #fff; }
            .print-toolbar { display: none; }
            .report-page { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
<div class="print-toolbar">
    <button type="button" class="print-primary" onclick="window.print()">{{ __('lab.print_button') }}</button>
    <button type="button" class="print-secondary" onclick="window.close()">{{ __('lab.close_button') }}</button>
</div>

<main class="report-page">
    <header class="report-header">
        <div class="brand">
            @if($orgLogo)<img src="{{ $orgLogo }}" alt="{{ $orgName }}">@endif
            <div>
                <div class="brand-name">{{ $orgName }}</div>
                @if($orgAddress)<div class="brand-meta">{{ $orgAddress }}</div>@endif
                @if($orgContact)<div class="brand-meta">{{ $orgContact }}</div>@endif
            </div>
        </div>
        <div class="document-title">
            <strong>{{ __('lab.result_report_title') }}</strong>
            <span>{{ $request->request_number }} | {{ $items->count() }} {{ Str::plural('result', $items->count()) }}</span>
        </div>
    </header>

    <section class="patient-band">
        <div><div class="meta-label">{{ $patient ? __('lab.patient_label') : __('lab.recipient_label') }}</div><div class="meta-value">{{ $patient?->full_name ?? $request->external_party_name ?? '—' }}</div></div>
        <div><div class="meta-label">{{ __('common.patient_no') }}</div><div class="meta-value">{{ $patient?->patient_number ?? __('lab.walk_in') }}</div></div>
        <div><div class="meta-label">{{ __('common.gender') }} / {{ __('common.age') }}</div><div class="meta-value">{{ $patient?->gender?->label() ?? $request->external_party_sex ?? '—' }} / {{ $patient?->age ?? $request->external_party_age ?? '—' }}</div></div>
        <div><div class="meta-label">{{ __('lab.visit_label') }}</div><div class="meta-value">{{ $visit?->visit_number ?? '—' }}</div></div>
        <div><div class="meta-label">{{ __('lab.department_label') }}</div><div class="meta-value">{{ $request->targetDepartment?->name ?? '—' }}</div></div>
        <div><div class="meta-label">{{ __('lab.requested_by_label') }}</div><div class="meta-value">{{ $request->requestedBy?->name ?? '—' }}</div></div>
        <div><div class="meta-label">{{ __('common.doctor') }}</div><div class="meta-value">{{ $doctor?->full_name ?? $doctor?->name ?? '—' }}</div></div>
        <div><div class="meta-label">{{ __('common.date') }}</div><div class="meta-value">{{ $request->created_at?->format('d M Y H:i') }}</div></div>
    </section>

    @if($request->clinical_info)
        <div class="clinical-note"><strong>{{ __('lab.clinical_information') }}:</strong> {{ $request->clinical_info }}</div>
    @endif

    @foreach($items as $printItem)
        <div class="{{ $separatePages && ! $loop->first ? 'result-page-break' : '' }}">
            @include('lab.partials.report-result', ['item' => $printItem])
        </div>
    @endforeach

    <section class="signoff">
        <div class="signature">
            <strong>{{ $items->last()->result->performedBy?->name ?? '—' }}</strong>
            {{ __('lab.performed_by_sig') }} | {{ $items->last()->result->performed_at?->format('d M Y H:i') }}
        </div>
        <div class="signature">
            <strong>{{ $items->last()->result->verifiedBy?->name ?? '—' }}</strong>
            {{ __('lab.verified_by_sig') }} | {{ $items->last()->result->verified_at?->format('d M Y H:i') }}
        </div>
    </section>

    <footer class="report-footer">
        <span>{{ __('lab.footer_generated', ['hospital' => $orgName, 'date' => now()->format('d M Y H:i')]) }}</span>
        <span>{{ $request->request_number }}</span>
    </footer>
</main>

<script>window.addEventListener('load', () => setTimeout(() => window.print(), 250));</script>
</body>
</html>
