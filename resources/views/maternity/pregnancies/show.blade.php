@extends('layouts.app')
@section('title', __('maternity.pregnancy_profile'))

@push('styles')
<style>
    /* ── Command bar ─────────────────────────────────────────── */
    .mat-cmd { display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.25rem; background:var(--bs-card-bg,#fff);
        border:1px solid var(--bs-border-color); border-radius:14px;
        box-shadow:0 1px 2px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.045); }
    .mat-cmd__avatar { width:52px; height:52px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.05rem;
        color:#fff; background:linear-gradient(135deg,#b5179e,#e0559f); box-shadow:0 4px 12px rgba(181,23,158,.28); }
    .mat-cmd__who { min-width:0; }
    .mat-cmd__name { font-size:1.2rem; font-weight:700; line-height:1.2; margin:0;
        display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .mat-cmd__meta { display:flex; gap:.9rem; flex-wrap:wrap; font-size:.78rem;
        color:var(--bs-secondary-color); margin-top:.3rem; }
    .mat-cmd__meta span { display:inline-flex; align-items:center; gap:.25rem; }
    .mat-cmd__divider { width:1px; align-self:stretch; background:var(--bs-border-color); margin:.15rem 0; }
    .mat-cmd__fact { display:flex; flex-direction:column; gap:.15rem; }
    .mat-cmd__lab { font-size:.63rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bs-tertiary-color); }
    .mat-cmd__val { font-weight:600; font-size:.85rem; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .mat-cmd__allergy { display:inline-flex; align-items:center; gap:.4rem; font-size:.75rem; font-weight:600;
        background:rgba(var(--bs-danger-rgb),.1); color:var(--bs-danger); padding:.35rem .6rem; border-radius:8px; }
    .mat-cmd__actions { margin-left:auto; display:flex; gap:.5rem; flex-wrap:wrap; }
    @media (max-width: 767px){ .mat-cmd__actions { margin-left:0; width:100%; } .mat-cmd__divider { display:none; } }

    /* ── Stat tiles ──────────────────────────────────────────── */
    .mat-tiles { display:grid; grid-template-columns:repeat(6,1fr); gap:.6rem; }
    @media (max-width: 1199px){ .mat-tiles { grid-template-columns:repeat(3,1fr); } }
    @media (max-width: 575px){ .mat-tiles { grid-template-columns:repeat(2,1fr); } }
    .mat-tile { text-align:left; width:100%; background:var(--bs-card-bg,#fff);
        border:1px solid var(--bs-border-color); border-radius:10px; padding:.7rem .8rem;
        position:relative; overflow:hidden; cursor:pointer; transition:transform .14s ease, border-color .14s ease; }
    .mat-tile:hover { transform:translateY(-2px); border-color:var(--bs-primary); }
    .mat-tile::before { content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--bs-border-color); }
    .mat-tile--ok::before { background:var(--bs-success); }
    .mat-tile--warn::before { background:var(--bs-warning); }
    .mat-tile--crit::before { background:var(--bs-danger); }
    .mat-tile--info::before { background:var(--bs-info); }
    .mat-tile--primary::before { background:var(--bs-primary); }
    .mat-tile__lab { font-size:.62rem; text-transform:uppercase; letter-spacing:.05em;
        color:var(--bs-tertiary-color); display:flex; align-items:center; gap:.3rem; }
    .mat-tile__val { font-size:1.1rem; font-weight:700; margin-top:.25rem; line-height:1.15; }
    .mat-tile__sub { font-size:.68rem; color:var(--bs-secondary-color); margin-top:.1rem; }
    .mat-num { font-variant-numeric: tabular-nums; }
    @media (prefers-reduced-motion: reduce){ .mat-tile:hover { transform:none; } }
</style>
@endpush

@section('content')
@php
    $matPatient  = $profile->patient;
    $matInitials = $matPatient
        ? strtoupper(mb_substr($matPatient->first_name ?? $matPatient->full_name ?? 'M', 0, 1) . mb_substr($matPatient->last_name ?? '', 0, 1))
        : 'MP';
    $anc       = $overview['anc'];
    $labor     = $overview['labor'];
    $newborn   = $overview['newborn'];
    $postnatal = $overview['postnatal'];

    $matStatusColor = $profile->profile_status?->color() ?? 'secondary';
    $matStatusTile  = match($matStatusColor) {
        'danger' => 'crit', 'warning' => 'warn', 'success' => 'ok', default => 'info',
    };
    $matAncTile     = $anc['missed_visit'] ? 'crit' : ($anc['pending_referral'] || $anc['high_risk'] ? 'warn' : 'ok');
    $matLaborTile   = $labor['active_episode'] ? 'primary' : 'info';
    $matNewbornTile = $newborn['pending_deliveries'] ? 'warn' : ($newborn['recorded_count'] > 0 ? 'ok' : 'info');
    $matGa          = $profile->gestational_age_weeks !== null
        ? $profile->gestational_age_weeks.'w '.$profile->gestational_age_days.'d'
        : __('common.not_available');
    $matVisit       = $profile->visit ?? $profile->admission?->visit;
    $matInvoice     = $matVisit?->latestInvoice;
    $matBalance     = (float) ($matInvoice?->balance ?? 0);
    $matBillingTile = $matInvoice ? ($matBalance > 0 ? 'crit' : 'ok') : 'info';
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════
     COMMAND BAR — single source of truth for identity, status & key dates
═══════════════════════════════════════════════════════════════════════ --}}
<div class="mat-cmd mb-3">
    <div class="mat-cmd__avatar">{{ $matInitials }}</div>
    <div class="mat-cmd__who">
        <h1 class="mat-cmd__name">
            {{ $profile->patient?->full_name ?? __('common.not_available') }}
            <span class="badge bg-{{ $matStatusColor }}">{{ $profile->profile_status?->label() }}</span>
        </h1>
        <div class="mat-cmd__meta">
            @if($profile->patient?->patient_number)<span class="mat-num"><i class="ti ti-hash fs-12"></i> {{ $profile->patient->patient_number }}</span>@endif
            <span><i class="ti ti-baby-carriage fs-12"></i> G{{ $profile->gravida ?? '—' }} P{{ $profile->para ?? '—' }}</span>
            @php $matBlood = trim(($profile->blood_group ?? '').' '.($profile->rhesus_status ?? '')); @endphp
            @if($matBlood)<span><i class="ti ti-droplet fs-12 text-danger"></i> {{ $matBlood }}</span>@endif
        </div>
    </div>

    <div class="mat-cmd__divider d-none d-md-block"></div>
    <div class="mat-cmd__fact">
        <span class="mat-cmd__lab">{{ __('maternity.gestational_age') }}</span>
        <span class="mat-cmd__val mat-num">{{ $matGa }}</span>
    </div>

    <div class="mat-cmd__divider d-none d-md-block"></div>
    <div class="mat-cmd__fact">
        <span class="mat-cmd__lab">{{ __('maternity.edd') }}</span>
        <span class="mat-cmd__val mat-num">{{ $profile->estimated_due_date?->format('d M Y') ?? __('common.not_available') }}</span>
    </div>

    @if($profile->allergies_snapshot)
        <span class="mat-cmd__allergy" title="{{ __('maternity.allergies_snapshot') }}">
            <i class="ti ti-alert-triangle"></i> {{ Str::limit($profile->allergies_snapshot, 60) }}
        </span>
    @endif

    <div class="mat-cmd__actions">
        <a href="{{ route('admin.maternity.pregnancies.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.pregnancy.update')
        <a href="{{ route('admin.maternity.pregnancies.edit', $profile) }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-edit me-1"></i>{{ __('common.edit') }}</a>
        @endcan
    </div>

    @include('partials.visit-insurance-strip', ['visit' => $matVisit])
</div>

@if(! empty($overview['warnings']))
<div class="alert alert-warning">
    <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.profile_warnings') }}</div>
    <ul class="mb-0">
        @foreach($overview['warnings'] as $warning)
        <li>{{ $warning }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- ── Stat tiles ─────────────────────────────────────────── --}}
<div class="mat-tiles mb-3">
    <button type="button" class="mat-tile mat-tile--primary" onclick="matShowTab('matTabOverview')">
        <span class="mat-tile__lab"><i class="ti ti-calendar-heart"></i>{{ __('maternity.gestational_age') }}</span>
        <span class="mat-tile__val mat-num">{{ $matGa }}</span>
        <span class="mat-tile__sub">{{ __('maternity.edd') }}: {{ $profile->estimated_due_date?->format('d M Y') ?? __('common.not_available') }}</span>
    </button>

    <button type="button" class="mat-tile mat-tile--{{ $matStatusTile }}" onclick="matShowTab('matTabOverview')">
        <span class="mat-tile__lab"><i class="ti ti-shield-heart"></i>{{ __('common.status') }}</span>
        <span class="mat-tile__val">{{ $profile->profile_status?->label() ?? __('common.not_available') }}</span>
        <span class="mat-tile__sub mat-num">G{{ $profile->gravida ?? '—' }} P{{ $profile->para ?? '—' }} · A{{ $profile->abortions ?? '—' }}</span>
    </button>

    <button type="button" class="mat-tile mat-tile--{{ $matAncTile }}" onclick="matShowTab('matTabAntenatal')">
        <span class="mat-tile__lab"><i class="ti ti-stethoscope"></i>{{ __('maternity.anc_visit_count') }}</span>
        <span class="mat-tile__val mat-num">{{ $anc['visit_count'] }}</span>
        <span class="mat-tile__sub">@if($anc['missed_visit']){{ __('maternity.missed_anc_visit') }}@else{{ __('maternity.next_visit_date') }}: {{ $anc['next_visit_date']?->format('d M') ?? __('common.none') }}@endif</span>
    </button>

    <button type="button" class="mat-tile mat-tile--{{ $matLaborTile }}" onclick="matShowTab('matTabLabor')">
        <span class="mat-tile__lab"><i class="ti ti-baby-carriage"></i>{{ __('maternity.labor_and_delivery') }}</span>
        <span class="mat-tile__val" style="font-size:.95rem">{{ $labor['active_episode']?->status?->label() ?? __('common.none') }}</span>
        <span class="mat-tile__sub">{{ $labor['latest_episode']?->labor_stage?->label() ?? __('maternity.labor_history').': '.$labor['episode_count'] }}</span>
    </button>

    <button type="button" class="mat-tile mat-tile--{{ $matNewbornTile }}" onclick="matShowTab('matTabNewborns')">
        <span class="mat-tile__lab"><i class="ti ti-baby-bottle"></i>{{ __('maternity.newborn_records') }}</span>
        <span class="mat-tile__val mat-num">{{ $newborn['recorded_count'] }}</span>
        <span class="mat-tile__sub mat-num">{{ $newborn['live_births'] }} {{ __('maternity.live_births') }} · {{ $newborn['stillbirths'] }} {{ __('maternity.stillbirths') }}</span>
    </button>

    <button type="button" class="mat-tile mat-tile--info" onclick="matShowTab('matTabPostnatal')">
        <span class="mat-tile__lab"><i class="ti ti-heart-handshake"></i>{{ __('maternity.postnatal_care') }}</span>
        <span class="mat-tile__val mat-num">{{ $postnatal['active_count'] ?? 0 }}</span>
        <span class="mat-tile__sub">{{ __('maternity.active_postnatal_cases') }}</span>
    </button>

    <button type="button" class="mat-tile mat-tile--{{ $matBillingTile }}" onclick="matShowTab('matTabBilling')">
        <span class="mat-tile__lab"><i class="ti ti-receipt"></i>{{ __('emergency.tab_billing') }}</span>
        <span class="mat-tile__val mat-num">@if($matInvoice)GH&#8373; {{ number_format($matBalance, 2) }}@else{{ __('common.none') }}@endif</span>
        <span class="mat-tile__sub">{{ $matInvoice?->invoice_number ?? __('emergency.no_invoice_yet') }}</span>
    </button>
</div>

{{-- ── Tabs ───────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" id="matTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabOverview" type="button" role="tab"><i class="ti ti-layout-dashboard me-1"></i>{{ __('maternity.overview') }}</button></li>
    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabAntenatal" type="button" role="tab"><i class="ti ti-stethoscope me-1"></i>{{ __('maternity.antenatal_care') }} <span class="badge bg-secondary ms-1 mat-num">{{ $anc['visit_count'] }}</span></button></li>
    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabLabor" type="button" role="tab"><i class="ti ti-baby-carriage me-1"></i>{{ __('maternity.labor_and_delivery') }} <span class="badge bg-secondary ms-1 mat-num">{{ $labor['episode_count'] }}</span></button></li>
    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabNewborns" type="button" role="tab"><i class="ti ti-baby-bottle me-1"></i>{{ __('maternity.newborn_records') }} <span class="badge bg-secondary ms-1 mat-num">{{ $newborn['recorded_count'] }}</span></button></li>
    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabPostnatal" type="button" role="tab"><i class="ti ti-heart-handshake me-1"></i>{{ __('maternity.postnatal_care') }}</button></li>
    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabCases" type="button" role="tab"><i class="ti ti-folders me-1"></i>{{ __('maternity.maternity_cases') }} <span class="badge bg-secondary ms-1 mat-num">{{ $profile->maternityCases->count() }}</span></button></li>
    <li class="nav-item"><button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#matTabBilling" type="button" role="tab"><i class="ti ti-receipt me-1"></i>{{ __('emergency.tab_billing') }} @if($matInvoice?->items?->count())<span class="badge bg-secondary ms-1 mat-num">{{ $matInvoice->items->count() }}</span>@endif</button></li>
</ul>

<div class="tab-content">
    {{-- ── OVERVIEW ── --}}
    <div class="tab-pane fade show active" id="matTabOverview" role="tabpanel">
        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="ti ti-notes me-1"></i>{{ __('maternity.profile_summary') }}</h5>
                        <span class="badge bg-{{ $profile->profile_status?->color() ?? 'secondary' }}">{{ $profile->profile_status?->label() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $profile->patient?->full_name }}</strong><div class="text-muted small">{{ $profile->patient?->patient_number }}</div></div>
                            <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.gravida') }}</small><strong>{{ $profile->gravida ?? __('common.not_available') }}</strong></div>
                            <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.para') }}</small><strong>{{ $profile->para ?? __('common.not_available') }}</strong></div>
                            <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.abortions') }}</small><strong>{{ $profile->abortions ?? __('common.not_available') }}</strong></div>
                            <div class="col-md-2"><small class="text-muted d-block">{{ __('maternity.living_children') }}</small><strong>{{ $profile->living_children ?? __('common.not_available') }}</strong></div>
                            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.lmp') }}</small><strong>{{ $profile->last_menstrual_period?->format('d M Y') ?? __('common.not_available') }}</strong></div>
                            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.edd') }}</small><strong>{{ $profile->estimated_due_date?->format('d M Y') ?? __('common.not_available') }}</strong></div>
                            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.gestational_age') }}</small><strong>{{ $profile->gestational_age_weeks !== null ? $profile->gestational_age_weeks.'w '.$profile->gestational_age_days.'d' : __('common.not_available') }}</strong></div>
                            <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_group') }}</small><strong>{{ trim(($profile->blood_group ?? '').' '.($profile->rhesus_status ?? '')) ?: __('common.not_available') }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.linked_visit') }}</small><strong>{{ $profile->visit?->visit_number ?? __('common.none') }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.linked_admission') }}</small><strong>{{ $profile->admission?->admission_number ?? $overview['admission']?->admission_number ?? __('common.none') }}</strong></div>
                            <div class="col-md-4"><small class="text-muted d-block">{{ __('maternity.department') }}</small><strong>{{ $profile->department?->name ?? __('common.none') }}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-alert-circle me-1"></i>{{ __('maternity.risk_snapshot') }}</h5></div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @forelse($overview['risk_summary']['flags'] as $flag)
                            <span class="badge bg-danger-subtle text-danger">{{ __('maternity.'.$flag) }}</span>
                            @empty
                            <span class="text-muted">{{ __('maternity.no_risk_flags') }}</span>
                            @endforelse
                        </div>
                        @if($overview['risk_summary']['known_risks']->isNotEmpty())
                        <ul class="mb-0">
                            @foreach($overview['risk_summary']['known_risks'] as $risk)
                            <li>{{ $risk }}</li>
                            @endforeach
                        </ul>
                        @endif
                        @if($profile->allergies_snapshot)
                        <hr>
                        <div><small class="text-muted d-block">{{ __('maternity.allergies_snapshot') }}</small>{{ $profile->allergies_snapshot }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-bolt me-1"></i>{{ __('dashboards.quick_actions') }}</h5></div>
                    <div class="card-body d-grid gap-2">
                        @can('maternity.case.create')
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#openMaternityCaseModal">
                            <i class="ti ti-folder-plus me-1"></i>{{ __('maternity.open_maternity_case') }}
                        </button>
                        @endcan
                        @can('maternity.pregnancy.risk.manage')
                        @if($profile->profile_status?->value !== 'high_risk')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#markHighRiskModal">
                            <i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.mark_high_risk') }}
                        </button>
                        @endif
                        @endcan
                        @can('maternity.pregnancy.close')
                        @if($overview['active_profile'])
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#closePregnancyProfileModal">
                            <i class="ti ti-circle-check me-1"></i>{{ __('maternity.close_profile') }}
                        </button>
                        @endif
                        @endcan
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-clock me-1"></i>{{ __('maternity.future_workflows') }}</h5></div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            @foreach($overview['future_panels'] as $panel)
                            <div class="list-group-item px-0">{{ $panel }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ANTENATAL ── --}}
    <div class="tab-pane fade" id="matTabAntenatal" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('maternity.antenatal_care') }}</h5>
                <div class="d-flex gap-2">
                    @can('maternity.anc.record')
                    <a href="{{ route('admin.maternity.pregnancies.antenatal.create', $profile) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.record_anc_visit') }}</a>
                    @endcan
                    @can('maternity.anc.view')
                    <a href="{{ route('admin.maternity.pregnancies.antenatal.index', $profile) }}" class="btn btn-sm btn-outline-primary">{{ __('maternity.anc_history') }}</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.anc_visit_count') }}</small><strong>{{ $anc['visit_count'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_anc_visit') }}</small><strong>{{ $anc['latest_visit']?->visit_date?->format('d M Y') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.next_visit_date') }}</small><strong>{{ $anc['next_visit_date']?->format('d M Y') ?? __('common.none') }}</strong>@if($anc['missed_visit']) <span class="badge bg-danger ms-1">{{ __('maternity.missed_anc_visit') }}</span>@endif</div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.high_risk_anc') }}</small><span class="badge bg-{{ $anc['high_risk'] ? 'danger' : 'success' }}">{{ $anc['high_risk'] ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_pressure') }}</small><strong>{{ $anc['latest_visit']?->blood_pressure_systolic && $anc['latest_visit']?->blood_pressure_diastolic ? $anc['latest_visit']->blood_pressure_systolic.'/'.$anc['latest_visit']->blood_pressure_diastolic : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fetal_heart_rate') }}</small><strong>{{ $anc['latest_visit']?->fetal_heart_rate ?? __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fundal_height') }}</small><strong>{{ $anc['latest_visit']?->fundal_height_cm ? $anc['latest_visit']->fundal_height_cm.' cm' : __('common.not_available') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.referral') }}</small><span class="badge bg-{{ $anc['pending_referral'] ? 'warning' : 'secondary' }}">{{ $anc['pending_referral'] ? __('maternity.referrals_pending') : __('common.none') }}</span></div>
                </div>
                @if($anc['danger_signs']->isNotEmpty() || $anc['risk_flags']->isNotEmpty())
                <hr>
                <div class="mb-2">@foreach($anc['danger_signs'] as $sign)<span class="badge bg-danger me-1">{{ __('maternity.anc_danger_signs.'.$sign) }}</span>@endforeach</div>
                <div>@foreach($anc['risk_flags'] as $flag)<span class="badge bg-warning text-dark me-1">{{ __('maternity.anc_risk_flags.'.$flag) }}</span>@endforeach</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── LABOR & DELIVERY ── --}}
    <div class="tab-pane fade" id="matTabLabor" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-baby-carriage me-1"></i>{{ __('maternity.labor_and_delivery') }}</h5>
                <div class="d-flex gap-2">
                    @can('maternity.labor.start')
                    <a href="{{ route('admin.maternity.pregnancies.labor.create', $profile) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.start_labor_episode') }}</a>
                    @endcan
                    @can('maternity.labor.view')
                    <a href="{{ route('admin.maternity.labor.index') }}" class="btn btn-sm btn-outline-primary">{{ __('maternity.labor_episodes') }}</a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.active_labor_episode') }}</small>
                        @if($labor['active_episode'])
                        <a href="{{ route('admin.maternity.labor.show', $labor['active_episode']) }}">{{ $labor['active_episode']->status?->label() }}</a>
                        @else
                        <strong>{{ __('common.none') }}</strong>
                        @endif
                    </div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.labor_history') }}</small><strong>{{ $labor['episode_count'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_labor_stage') }}</small><strong>{{ $labor['latest_episode']?->labor_stage?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.delivery_record_status') }}</small><strong>{{ $labor['latest_delivery_record']?->status?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_observation') }}</small><strong>{{ $labor['latest_observation']?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.fetal_heart_rate') }}</small><strong>{{ $labor['latest_observation']?->fetal_heart_rate ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.blood_pressure') }}</small><strong>{{ $labor['latest_observation']?->blood_pressure_systolic && $labor['latest_observation']?->blood_pressure_diastolic ? $labor['latest_observation']->blood_pressure_systolic.'/'.$labor['latest_observation']->blood_pressure_diastolic : __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_records_pending') }}</small><span class="badge bg-{{ $labor['newborn_records_pending'] ? 'warning' : 'secondary' }}">{{ $labor['newborn_records_pending'] ? __('common.yes') : __('common.no') }}</span></div>
                </div>
                @if($labor['warnings'])
                <hr>
                @foreach($labor['warnings'] as $warning)<span class="badge bg-warning text-dark me-1">{{ $warning }}</span>@endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- ── NEWBORNS ── --}}
    <div class="tab-pane fade" id="matTabNewborns" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-baby-bottle me-1"></i>{{ __('maternity.newborn_records') }}</h5></div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.recorded_newborn_count') }}</small><strong>{{ $newborn['recorded_count'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.newborn_records_pending') }}</small><span class="badge bg-{{ $newborn['pending_deliveries'] ? 'warning' : 'success' }}">{{ $newborn['pending_deliveries'] ? __('common.yes') : __('common.no') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.live_births') }}</small><strong>{{ $newborn['live_births'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.stillbirths') }}</small><strong>{{ $newborn['stillbirths'] }}</strong></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('maternity.birth_order') }}</th><th>{{ __('maternity.sex') }}</th><th>{{ __('maternity.birth_weight') }}</th><th>{{ __('maternity.newborn_outcome') }}</th><th>{{ __('maternity.status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($newborn['records'] as $record)
                            <tr>
                                <td>{{ $record->birth_order }}</td><td>{{ $record->sex?->label() ?? __('common.none') }}</td><td>{{ $record->birth_weight_kg ? $record->birth_weight_kg.' kg' : __('common.none') }}</td><td>{{ $record->outcome?->label() ?? __('common.none') }}</td><td><span class="badge bg-{{ $record->status?->color() ?? 'secondary' }}">{{ $record->status?->label() }}</span></td><td class="text-end"><a href="{{ route('admin.maternity.newborns.show', $record) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="6"><x-empty-state icon="ti-baby-bottle" :title="__('maternity.no_newborn_records_yet')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── POSTNATAL ── --}}
    <div class="tab-pane fade" id="matTabPostnatal" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-heart-handshake me-1"></i>{{ __('maternity.postnatal_care') }}</h5></div>
            <div class="card-body">
                @if($postnatal['latest_case'])
                <div class="row g-3 mb-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.active_postnatal_cases') }}</small><strong>{{ $postnatal['active_count'] }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.status') }}</small><a href="{{ route('admin.maternity.postnatal.show', $postnatal['latest_case']) }}">{{ $postnatal['latest_case']->status?->label() }}</a></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_mother_observation') }}</small><strong>{{ $postnatal['latest_mother_observation']?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.latest_newborn_observation') }}</small><strong>{{ $postnatal['latest_newborn_observation']?->observed_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('maternity.delivery_at') }}</th><th>{{ __('maternity.status') }}</th><th>{{ __('maternity.risk_level') }}</th><th>{{ __('maternity.follow_up_date') }}</th><th></th></tr></thead>
                        <tbody>
                            @foreach($postnatal['cases'] as $case)
                            <tr>
                                <td>{{ $case->deliveryRecord?->delivery_at?->format('d M Y H:i') ?? __('common.none') }}</td>
                                <td><span class="badge bg-{{ $case->status?->color() ?? 'secondary' }}">{{ $case->status?->label() }}</span></td>
                                <td><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() }}</span></td>
                                <td>{{ $case->follow_up_date?->format('d M Y') ?? __('common.none') }}</td>
                                <td class="text-end"><a href="{{ route('admin.maternity.postnatal.show', $case) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <x-empty-state icon="ti-heart-handshake" :title="__('maternity.no_postnatal_case_yet')" />
                @endif
            </div>
        </div>
    </div>

    {{-- ── CASES ── --}}
    <div class="tab-pane fade" id="matTabCases" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-folders me-1"></i>{{ __('maternity.maternity_cases') }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('maternity.case_type') }}</th><th>{{ __('maternity.risk_level') }}</th><th>{{ __('maternity.case_status') }}</th><th>{{ __('common.created_by') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($profile->maternityCases as $case)
                            <tr>
                                <td>{{ $case->case_type?->label() ?? __('common.not_available') }}</td>
                                <td><span class="badge badge-soft-{{ $case->risk_level?->color() ?? 'secondary' }}">{{ $case->risk_level?->label() ?? __('common.not_available') }}</span></td>
                                <td><span class="badge bg-{{ $case->status?->color() ?? 'secondary' }}">{{ $case->status?->label() }}</span></td>
                                <td>{{ $case->openedBy?->name ?? __('common.not_available') }}</td>
                                <td class="text-end"><a href="{{ route('admin.maternity.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="5"><x-empty-state icon="ti-folder-open" :title="__('maternity.no_maternity_cases')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="tab-pane fade" id="matTabBilling" role="tabpanel">
        @include('partials.visit-invoice-preview', [
            'visit' => $matVisit,
            'invoice' => $matInvoice,
            'title' => __('emergency.tab_billing'),
            'emptyText' => __('emergency.no_invoice_items'),
        ])
    </div>
</div>

@can('maternity.case.create')
<div class="modal fade" id="openMaternityCaseModal" tabindex="-1" aria-labelledby="openMaternityCaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.maternity.cases.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="pregnancy_profile_id" value="{{ $profile->id }}">
            <input type="hidden" name="patient_id" value="{{ $profile->patient_id }}">
            <input type="hidden" name="visit_id" value="{{ $profile->visit_id }}">
            <input type="hidden" name="admission_id" value="{{ $profile->admission_id }}">
            <input type="hidden" name="department_id" value="{{ $profile->department_id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="openMaternityCaseModalLabel"><i class="ti ti-folder-plus me-1"></i>{{ __('maternity.open_maternity_case') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('maternity.case_type') }}</label>
                        <select name="case_type" class="form-select">
                            <option value="pregnancy_profile">{{ __('maternity.case_types.pregnancy_profile') }}</option>
                            <option value="antenatal">{{ __('maternity.case_types.antenatal') }}</option>
                            <option value="maternity_admission">{{ __('maternity.case_types.maternity_admission') }}</option>
                            <option value="labor_observation">{{ __('maternity.case_types.labor_observation') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('maternity.risk_level') }}</label>
                        <select name="risk_level" class="form-select">
                            <option value="low">{{ __('maternity.risk_levels.low') }}</option>
                            <option value="moderate">{{ __('maternity.risk_levels.moderate') }}</option>
                            <option value="high" @selected($profile->profile_status?->value === 'high_risk')>{{ __('maternity.risk_levels.high') }}</option>
                            <option value="emergency">{{ __('maternity.risk_levels.emergency') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('maternity.clinical_summary') }}</label>
                        <textarea name="clinical_summary" rows="4" class="form-control">{{ old('clinical_summary') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-folder-plus me-1"></i>{{ __('maternity.open_case') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('maternity.pregnancy.risk.manage')
@if($profile->profile_status?->value !== 'high_risk')
<div class="modal fade" id="markHighRiskModal" tabindex="-1" aria-labelledby="markHighRiskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.maternity.pregnancies.status', $profile) }}" class="modal-content">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="high_risk">
            <div class="modal-header">
                <h5 class="modal-title" id="markHighRiskModalLabel"><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.mark_high_risk') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('maternity.high_risk_reason') }}</label>
                <textarea name="reason" rows="4" class="form-control" placeholder="{{ __('maternity.high_risk_reason') }}">{{ old('reason') }}</textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-warning">{{ __('maternity.mark_high_risk') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@can('maternity.pregnancy.close')
@if($overview['active_profile'])
<div class="modal fade" id="closePregnancyProfileModal" tabindex="-1" aria-labelledby="closePregnancyProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ route('admin.maternity.pregnancies.status', $profile) }}" class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title" id="closePregnancyProfileModalLabel"><i class="ti ti-circle-check me-1"></i>{{ __('maternity.close_pregnancy_profile') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('common.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="delivered">{{ __('maternity.profile_statuses.delivered') }}</option>
                        <option value="transferred">{{ __('maternity.profile_statuses.transferred') }}</option>
                        <option value="closed">{{ __('maternity.profile_statuses.closed') }}</option>
                    </select>
                </div>
                <label class="form-label">{{ __('common.reason') }}</label>
                <textarea name="reason" rows="4" class="form-control" placeholder="{{ __('common.reason') }}">{{ old('reason') }}</textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-outline-danger">{{ __('maternity.close_profile') }}</button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan
@endsection

@push('scripts')
<script>
function matShowTab(id) {
    var btn = document.querySelector('[data-bs-target="#' + id + '"]');
    if (btn && window.bootstrap) { bootstrap.Tab.getOrCreateInstance(btn).show(); }
    var pane = document.getElementById(id);
    if (pane) { setTimeout(function () { pane.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100); }
}
(function () {
    var hash = window.location.hash;
    if (hash && window.bootstrap) {
        var tab = document.querySelector('[data-bs-target="' + hash + '"]');
        if (tab) { bootstrap.Tab.getOrCreateInstance(tab).show(); }
    }
})();
</script>
@endpush
