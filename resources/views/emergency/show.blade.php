@extends('layouts.app')
@section('title', $case->emergency_number)

@php
    $erRoute = fn ($name, $parameters = []) => $workspaceRoutes->route($name, $parameters);
    $triageClass = $case->triage_badge_class;
    $activeInvoice = $case->visit?->latestInvoice;
    $temporaryPatient = $case->patient?->is_temporary ? $case->patient : null;
    $identityAction = old('_identity_action');
    $formContext = old('_form');
    $shouldOpenIdentityModal = $errors->any() && in_array($identityAction, ['existing', 'register'], true);
    $shouldOpenTriageModal = $errors->any() && $formContext === 'triage';
    $shouldOpenControlSheetModal = $errors->any() && $formContext === 'control_sheet';
    $identityModalTabSelector = $identityAction === 'register' ? '#register-identity-tab' : '#existing-identity-tab';
    $session = $case->activeEmergencySession;
    $currentTriage = $case->current_triage_category ?: 'UNTRIAGED';
    $contributors = $session?->contributors?->map(fn ($contributor) => $contributor->user?->name ?: $contributor->user?->full_name)->filter()->unique()->values() ?? collect();
    $latestVitals = $case->latestVitals;
    $criticalAlerts = collect([
        $case->patient?->allergies ? 'Allergies: '.$case->patient->allergies : null,
        in_array($currentTriage, ['RED', 'BLACK'], true) ? 'Critical triage: '.$currentTriage : null,
        $case->patient?->is_temporary ? 'Temporary identity not confirmed' : null,
        $activeInvoice && (float) $activeInvoice->balance > 0 ? 'Unpaid balance GH'.number_format((float) $activeInvoice->balance, 2) : null,
    ])->filter();
    $medicationSchedules = $case->medicationOrders->flatMap(fn ($order) => $order->schedules ?? collect());
    $medCounts = [
        'due_now' => $medicationSchedules->filter(fn ($schedule) => in_array($schedule->status, ['DUE', 'SCHEDULED'], true) && $schedule->scheduled_at && $schedule->scheduled_at->lte(now()))->count(),
        'overdue' => $medicationSchedules->filter(fn ($schedule) => ! in_array($schedule->status, ['GIVEN', 'MISSED', 'SKIPPED', 'REFUSED', 'HELD', 'CANCELLED', 'VOIDED'], true) && $schedule->scheduled_at && $schedule->scheduled_at->lt(now()->subMinutes(30)))->count(),
        'upcoming' => $medicationSchedules->filter(fn ($schedule) => in_array($schedule->status, ['SCHEDULED'], true) && $schedule->scheduled_at && $schedule->scheduled_at->gt(now()))->count(),
        'administered_today' => $case->medicationOrders->flatMap(fn ($order) => $order->administrations ?? collect())->filter(fn ($admin) => $admin->administered_at?->isToday())->count(),
    ];
    $frequencyMap = $frequencies->mapWithKeys(fn ($frequency) => [
        $frequency->code => [
            'times' => $frequency->times_per_day ?: ($frequency->interval_hours ? floor(24 / $frequency->interval_hours) : 0),
            'stat' => (bool) $frequency->is_stat,
            'prn' => (bool) $frequency->is_prn,
        ],
    ]);
    $pendingTasks = $case->clinicalTasks->filter(fn ($task) => ! in_array($task->status, ['COMPLETED', 'CANCELLED', 'MISSED', 'HELD', 'REFUSED', 'SKIPPED'], true));
    $dangerSignOptions = [
        'respiratory_distress' => 'Respiratory distress',
        'seizure' => 'Seizure',
        'shock' => 'Shock indicators',
        'uncontrolled_bleeding' => 'Uncontrolled bleeding',
        'trauma' => 'Major trauma',
        'bleeding' => 'Bleeding',
        'pregnancy' => 'Pregnancy',
        'dead_on_arrival' => 'Dead on arrival',
    ];
    $investigationServiceOptions = $investigationServices->map(fn ($service) => [
        'id' => $service->id,
        'department_id' => $service->department_id,
        'name' => $service->name,
        'code' => $service->code,
        'price' => $service->price,
    ])->values();
    $procedureServiceOptions = $procedureServices->map(fn ($service) => [
        'id' => $service->id,
        'department_id' => $service->department_id,
        'name' => $service->name,
        'code' => $service->code,
        'price' => $service->price,
    ])->values();
@endphp

@push('styles')
<style>
    .vitals-val { font-size: 1.1rem; font-weight: 700; }
    .vitals-label { font-size: .68rem; color: #6c757d; }
    /* Vitals small multiples — each vital gets its own auto-scaled mini chart. */
    .vitals-tile { border: 1px solid var(--bs-border-color, #e9ecef); border-radius: .5rem; padding: .5rem .65rem .35rem; height: 100%; }
    .vitals-spark-wrap { position: relative; height: 76px; margin-top: .15rem; }
    .er-section-title { font-size: .78rem; letter-spacing: .02em; text-transform: uppercase; color: #6c757d; font-weight: 600; }
    .er-scroll { max-height: 360px; overflow: auto; }
    /* Make select2 single selects match Bootstrap form-select sizing on this page. */
    .select2-container { width: 100% !important; }
    .select2-container--default .select2-selection--single {
        height: calc(1.5em + .75rem + 2px);
        border: 1px solid var(--bs-border-color, #ced4da);
        border-radius: .375rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: calc(1.5em + .75rem); padding-left: .75rem; color: #212529;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: calc(1.5em + .75rem); }

    /* ── Command bar ─────────────────────────────────────────── */
    .er-cmd { display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.25rem; background:var(--bs-card-bg,#fff);
        border:1px solid var(--bs-border-color); border-radius:14px;
        box-shadow:0 1px 2px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.045);
        border-left:4px solid var(--bs-danger); }
    .er-cmd__avatar { width:52px; height:52px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.05rem;
        color:#fff; background:linear-gradient(135deg,#c1121f,#dc3545); box-shadow:0 4px 12px rgba(220,53,69,.3); }
    .er-cmd__who { min-width:0; }
    .er-cmd__name { font-size:1.2rem; font-weight:700; line-height:1.2; margin:0;
        display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .er-cmd__meta { display:flex; gap:.9rem; flex-wrap:wrap; font-size:.78rem;
        color:var(--bs-secondary-color); margin-top:.3rem; }
    .er-cmd__meta span { display:inline-flex; align-items:center; gap:.25rem; }
    .er-cmd__divider { width:1px; align-self:stretch; background:var(--bs-border-color); margin:.15rem 0; }
    .er-cmd__fact { display:flex; flex-direction:column; gap:.15rem; }
    .er-cmd__lab { font-size:.63rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bs-tertiary-color); }
    .er-cmd__val { font-weight:600; font-size:.85rem; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .er-cmd__actions { margin-left:auto; display:flex; gap:.5rem; flex-wrap:wrap; }
    @media (max-width: 767px){ .er-cmd__actions { margin-left:0; width:100%; } .er-cmd__divider { display:none; } }

    /* ── Stat tiles ──────────────────────────────────────────── */
    .er-tiles { display:grid; grid-template-columns:repeat(6,1fr); gap:.6rem; }
    @media (max-width: 1199px){ .er-tiles { grid-template-columns:repeat(3,1fr); } }
    @media (max-width: 575px){ .er-tiles { grid-template-columns:repeat(2,1fr); } }
    .er-tile { text-align:left; width:100%; background:var(--bs-card-bg,#fff);
        border:1px solid var(--bs-border-color); border-radius:10px; padding:.7rem .8rem;
        position:relative; overflow:hidden; cursor:pointer; transition:transform .14s ease, border-color .14s ease; }
    .er-tile:hover { transform:translateY(-2px); border-color:var(--bs-primary); }
    .er-tile::before { content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--bs-border-color); }
    .er-tile--ok::before { background:var(--bs-success); }
    .er-tile--warn::before { background:var(--bs-warning); }
    .er-tile--crit::before { background:var(--bs-danger); }
    .er-tile--info::before { background:var(--bs-info); }
    .er-tile--primary::before { background:var(--bs-primary); }
    .er-tile__lab { font-size:.62rem; text-transform:uppercase; letter-spacing:.05em;
        color:var(--bs-tertiary-color); display:flex; align-items:center; gap:.3rem; }
    .er-tile__val { font-size:1.1rem; font-weight:700; margin-top:.25rem; line-height:1.15; }
    .er-tile__sub { font-size:.68rem; color:var(--bs-secondary-color); margin-top:.1rem; }
    .er-num { font-variant-numeric: tabular-nums; }
    .er-thread { display:flex; flex-direction:column; gap:.75rem; }
    .er-message { display:flex; gap:.65rem; align-items:flex-start; max-width:84%; }
    .er-message--mine { align-self:flex-end; flex-direction:row-reverse; }
    .er-message__avatar { width:34px; height:34px; border-radius:50%; flex:0 0 34px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.72rem; color:#fff; background:linear-gradient(135deg,#64748b,#94a3b8); }
    .er-message--mine .er-message__avatar { background:linear-gradient(135deg,#c1121f,#dc3545); }
    .er-message__bubble { border:1px solid var(--bs-border-color); border-radius:12px; border-top-left-radius:4px; padding:.65rem .8rem; background:#fff; min-width:220px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .er-message--mine .er-message__bubble { border-color:rgba(var(--bs-danger-rgb),.24); border-top-left-radius:12px; border-top-right-radius:4px; background:rgba(var(--bs-danger-rgb),.05); }
    .er-message__meta { display:flex; align-items:center; justify-content:space-between; gap:.75rem; color:var(--bs-secondary-color); font-size:.72rem; margin-bottom:.35rem; }
    .er-message__body { color:var(--bs-body-color); white-space:pre-wrap; overflow-wrap:anywhere; }
    @media (prefers-reduced-motion: reduce){ .er-tile:hover { transform:none; } }
    @media (max-width: 767px) {
        .er-message { max-width:100%; }
        .er-message__bubble { min-width:0; flex:1; }
    }
</style>
@endpush

@section('content')
@php
    $erPatient = $case->patient;
    $erInitials = $erPatient
        ? strtoupper(mb_substr($erPatient->first_name ?? $erPatient->full_name ?? 'E', 0, 1) . mb_substr($erPatient->last_name ?? '', 0, 1))
        : 'ER';
@endphp
{{-- ═══════════════════════════════════════════════════════════════════════
     COMMAND BAR — single source of truth for identity, triage & status
═══════════════════════════════════════════════════════════════════════ --}}
<div class="er-cmd mb-3">
    <div class="er-cmd__avatar">{{ $erInitials }}</div>
    <div class="er-cmd__who">
        <h1 class="er-cmd__name">
            {{ $case->patient->full_name ?? __('emergency.unknown_patient') }}
            <span class="badge {{ $triageClass }}">{{ $currentTriage }}</span>
            <x-status-badge :status="$case->emergency_status" domain="emergency" />
            <span class="badge badge-soft-secondary er-num">{{ $case->emergency_number }}</span>
            @if($temporaryPatient)<span class="badge bg-warning-subtle text-warning">{{ __('emergency.temporary') }}</span>@endif
        </h1>
        <div class="er-cmd__meta">
            @if($case->patient?->patient_number)<span class="er-num"><i class="ti ti-hash fs-12"></i> {{ $case->patient->patient_number }}</span>@endif
            @if($case->visit?->visit_number)<span class="er-num"><i class="ti ti-file-text fs-12"></i> {{ $case->visit->visit_number }}</span>@endif
            <span><i class="ti ti-ambulance fs-12"></i> {{ str_replace('_', ' ', $case->arrival_mode) }}</span>
        </div>
    </div>

    <div class="er-cmd__divider d-none d-md-block"></div>
    <div class="er-cmd__fact">
        <span class="er-cmd__lab">{{ __('emergency.min') }}</span>
        <span class="er-cmd__val er-num"><i class="ti ti-clock-hour-4"></i> {{ $case->waiting_minutes }}</span>
    </div>

    <div class="er-cmd__actions">
        @if($temporaryPatient)
            @can('patients.merge.confirm_identity')
                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#confirmEmergencyIdentityModal">
                    <i class="ti ti-id-badge-2 me-1"></i>{{ __('emergency.temporary') }}
                </button>
            @endcan
        @endif
        <a href="{{ $erRoute('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">{{ __('emergency.emergency_board_btn') }}</a>
        @if($case->visit)
            <a href="{{ $erRoute('admin.emergency.mar-chart', $case->visit) }}" class="btn btn-outline-danger btn-sm">{{ __('emergency.mar') }}</a>
            <a href="{{ $erRoute('admin.visits.preview', $case->visit) }}" class="btn btn-outline-primary btn-sm">{{ __('common.view') }}</a>
        @endif
    </div>

    @include('partials.visit-insurance-strip', ['visit' => $case->visit])
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <div class="fw-semibold mb-1">{{ __('emergency.correct_highlighted_details') }}</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $triageTileClass = match($currentTriage) {
        'RED', 'BLACK'     => 'crit',
        'ORANGE', 'YELLOW' => 'warn',
        'GREEN'            => 'ok',
        default            => 'info',
    };
    $erBayLabel   = $case->activeBayAssignment?->bed?->bed_number ?: ($case->bay->name ?? __('emergency.unassigned'));
    $erBayWard    = $case->activeBayAssignment?->ward?->name ?: ($case->bay?->ward?->name ?: __('emergency.no_emergency_bed_linked'));
    $erBayClass   = ($case->activeBayAssignment?->bed?->bed_number || $case->bay) ? 'primary' : 'warn';
    $erDoctorName = $session?->mainDoctor?->name ?? $case->assignedDoctor?->name ?? __('emergency.unassigned');
    $erNurseName  = $session?->primaryNurse?->name ?? $case->assignedNurse?->name ?? __('emergency.unassigned');
    $erMedsClass  = ($medCounts['overdue'] ?? 0) > 0 ? 'crit' : (($medCounts['due_now'] ?? 0) > 0 ? 'warn' : 'ok');
    $erTasksClass = $pendingTasks->count() > 0 ? 'warn' : 'ok';
@endphp
{{-- ── Stat tiles ─────────────────────────────────────────── --}}
<div class="er-tiles mb-3">
    <button type="button" class="er-tile er-tile--{{ $triageTileClass }}" onclick="erShowTab('erTabOverview')">
        <span class="er-tile__lab"><i class="ti ti-activity-heartbeat"></i>{{ __('emergency.triage_label') }}</span>
        <span class="er-tile__val"><span class="badge {{ $triageClass }}">{{ $currentTriage }}</span></span>
        <span class="er-tile__sub">{{ __('emergency.auto_prefix') }}: {{ $case->auto_triage_category ?: __('emergency.pending_calc') }}@if($case->triage_score) · {{ __('emergency.score') }} {{ $case->triage_score }}@endif</span>
    </button>

    <button type="button" class="er-tile er-tile--info" onclick="erShowTab('erTabOverview')">
        <span class="er-tile__lab"><i class="ti ti-clock-hour-4"></i>{{ __('emergency.min') }}</span>
        <span class="er-tile__val er-num">{{ $case->waiting_minutes }}</span>
        <span class="er-tile__sub">{{ str_replace('_', ' ', $case->arrival_mode) }}</span>
    </button>

    <button type="button" class="er-tile er-tile--{{ $erBayClass }}" onclick="erShowTab('erTabCoordination')">
        <span class="er-tile__lab"><i class="ti ti-bed"></i>{{ __('emergency.bay_bed') }}</span>
        <span class="er-tile__val">{{ $erBayLabel }}</span>
        <span class="er-tile__sub">{{ $erBayWard }}</span>
    </button>

    <button type="button" class="er-tile er-tile--info" onclick="erShowTab('erTabCoordination')">
        <span class="er-tile__lab"><i class="ti ti-users"></i>{{ __('emergency.emergency_team') }}</span>
        <span class="er-tile__val" style="font-size:.9rem">{{ __('emergency.doctor_prefix') }}: {{ $erDoctorName }}</span>
        <span class="er-tile__sub">{{ __('emergency.nurse_prefix_team') }}: {{ $erNurseName }}</span>
    </button>

    <button type="button" class="er-tile er-tile--{{ $erMedsClass }}" onclick="erShowTab('erTabMedication')">
        <span class="er-tile__lab"><i class="ti ti-pill"></i>{{ __('emergency.tab_medication') }}</span>
        <span class="er-tile__val er-num">{{ $medCounts['due_now'] }}</span>
        <span class="er-tile__sub er-num">{{ $medCounts['overdue'] }} {{ __('emergency.late_label') }} · {{ $medCounts['upcoming'] }} {{ __('emergency.next_label') }}</span>
    </button>

    <button type="button" class="er-tile er-tile--{{ $erTasksClass }}" onclick="erShowTab('erTabTasks')">
        <span class="er-tile__lab"><i class="ti ti-checklist"></i>{{ __('emergency.tab_tasks') }}</span>
        <span class="er-tile__val er-num">{{ $pendingTasks->count() }}</span>
        <span class="er-tile__sub">{{ __('emergency.pending_count_badge') }}</span>
    </button>
</div>

@if($criticalAlerts->isNotEmpty())
    <div class="alert alert-danger py-2">
        <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i>{{ __('emergency.critical_alerts') }}</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($criticalAlerts as $alert)
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ $alert }}</span>
            @endforeach
        </div>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════
     UNIFIED FULL-WIDTH WORKSPACE
═══════════════════════════════════════════════════════════════════════ --}}
<ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" id="erActionTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabOverview" type="button" role="tab"><i class="ti ti-layout-dashboard me-1"></i>{{ __('emergency.tab_overview') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabMedication" type="button" role="tab"><i class="ti ti-pill me-1"></i>{{ __('emergency.tab_medication') }}@if($case->medicationOrders->count())<span class="badge rounded-pill bg-danger-subtle text-danger ms-1">{{ $case->medicationOrders->count() }}</span>@endif</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabInvestigations" type="button" role="tab"><i class="ti ti-test-pipe me-1"></i>{{ __('emergency.tab_investigations') }}@if($case->labRequests->count())<span class="badge rounded-pill bg-secondary ms-1">{{ $case->labRequests->count() }}</span>@endif</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabProcedures" type="button" role="tab"><i class="ti ti-stethoscope me-1"></i>{{ __('emergency.tab_procedures') }}@if($case->procedureRequests->count())<span class="badge rounded-pill bg-secondary ms-1">{{ $case->procedureRequests->count() }}</span>@endif</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabConsumables" type="button" role="tab"><i class="ti ti-box me-1"></i>{{ __('emergency.tab_consumables') }}@if($case->consumableUsages->count())<span class="badge rounded-pill bg-secondary ms-1">{{ $case->consumableUsages->count() }}</span>@endif</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabTasks" type="button" role="tab"><i class="ti ti-checklist me-1"></i>{{ __('emergency.tab_tasks') }}@if($pendingTasks->count())<span class="badge rounded-pill bg-warning text-dark ms-1">{{ $pendingTasks->count() }}</span>@endif</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabBilling" type="button" role="tab"><i class="ti ti-receipt me-1"></i>{{ __('emergency.tab_billing') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabCoordination" type="button" role="tab"><i class="ti ti-map-pin me-1"></i>{{ __('emergency.coordination_title') }}</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link text-nowrap" data-bs-toggle="tab" data-bs-target="#erTabDisposition" type="button" role="tab"><i class="ti ti-logout me-1"></i>{{ __('emergency.disposition_title') }}</button>
    </li>
</ul>

<div class="tab-content">
    {{-- ── OVERVIEW ── --}}
    <div class="tab-pane fade show active" id="erTabOverview" role="tabpanel">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">{{ __('emergency.control_sheet') }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">{{ $case->arrival_mode }}</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#controlSheetModal">
                        <i class="ti ti-edit me-1"></i>{{ __('emergency.edit') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('emergency.patient_label') }}</div>
                        <div class="fw-semibold">{{ $case->patient->full_name ?? __('emergency.unknown_patient_cs') }}</div>
                        <div class="small text-muted">{{ $case->patient->patient_number ?? '' }}</div>
                        @if($case->patient?->is_temporary)
                            <span class="badge bg-warning-subtle text-warning">{{ __('emergency.temp_emergency_patient') }}</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('emergency.chief_complaint_label') }}</div>
                        <div>{{ $case->chief_complaint ?: __('emergency.not_recorded') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('emergency.initial_condition_label') }}</div>
                        <div>{{ $case->initial_condition ?: __('emergency.not_recorded') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('emergency.referral_source') }}</div>
                        <div>{{ $case->referral_facility ?: ($case->source ?: __('emergency.not_recorded')) }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('emergency.latest_vitals_label') }}</div>
                        @if($case->latestVitals)
                            <div class="small">
                                BP {{ $case->latestVitals->blood_pressure ?? '-' }},
                                HR {{ $case->latestVitals->heart_rate ?? '-' }},
                                Temp {{ $case->latestVitals->temperature ?? '-' }},
                                SpO2 {{ $case->latestVitals->spo2 ?? '-' }}
                            </div>
                            <small class="text-muted">{{ __('emergency.vitals_recorded_by') }} {{ $case->latestVitals->recordedBy->name ?? __('emergency.unknown_patient') }} at {{ $case->latestVitals->recorded_at?->format('d M H:i') }}</small>
                        @else
                            <div class="text-muted">{{ __('emergency.no_vitals') }}</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small">{{ __('emergency.billing_label') }}</div>
                        <div class="fw-semibold">{{ $activeInvoice?->invoice_number ?? __('emergency.no_invoice_yet') }}</div>
                        <small class="text-muted">{{ __('emergency.emergency_care_note') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">{{ __('emergency.vitals_trend') }}</h5>
                <span class="badge bg-light text-dark">{{ $case->vitals->count() }} {{ __('emergency.readings') }}</span>
            </div>
            <div class="card-body">
                @if($case->vitals->isNotEmpty())
                    {{-- One auto-scaled mini trend per vital: a wildly different value
                         range per vital (BP ~120 vs Temp ~37) makes a single shared
                         y-axis unreadable. --}}
                    <div class="row g-2">
                        <div class="col-6 col-md-4 col-xl">
                            <div class="vitals-tile">
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="vitals-val">{{ $latestVitals?->blood_pressure ?? '-' }}</span>
                                    <span class="vitals-label">BP</span>
                                </div>
                                <div class="vitals-spark-wrap"><canvas id="vitalsSparkBp"></canvas></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="vitals-tile">
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="vitals-val">{{ $latestVitals?->heart_rate ?? '-' }}</span>
                                    <span class="vitals-label">HR</span>
                                </div>
                                <div class="vitals-spark-wrap"><canvas id="vitalsSparkHr"></canvas></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="vitals-tile">
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="vitals-val">{{ $latestVitals?->respiratory_rate ?? '-' }}</span>
                                    <span class="vitals-label">RR</span>
                                </div>
                                <div class="vitals-spark-wrap"><canvas id="vitalsSparkRr"></canvas></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="vitals-tile">
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="vitals-val">{{ $latestVitals?->spo2 ?? '-' }}</span>
                                    <span class="vitals-label">SpO2</span>
                                </div>
                                <div class="vitals-spark-wrap"><canvas id="vitalsSparkSpo2"></canvas></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-xl">
                            <div class="vitals-tile">
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span class="vitals-val">{{ $latestVitals?->temperature ?? '-' }}</span>
                                    <span class="vitals-label">{{ __('emergency.vitals_temp') }}</span>
                                </div>
                                <div class="vitals-spark-wrap"><canvas id="vitalsSparkTemp"></canvas></div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive mt-3 er-scroll">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>{{ __('emergency.vitals_time') }}</th><th>BP</th><th>HR</th><th>RR</th><th>{{ __('emergency.vitals_temp') }}</th><th>SpO2</th><th>{{ __('emergency.vitals_recorded_by') }}</th></tr></thead>
                            <tbody>
                                @foreach($case->vitals->sortByDesc('recorded_at') as $vital)
                                    <tr>
                                        <td>{{ $vital->recorded_at?->format('d M H:i') }}</td>
                                        <td>{{ $vital->blood_pressure ?? '-' }}</td>
                                        <td>{{ $vital->heart_rate ?? '-' }}</td>
                                        <td>{{ $vital->respiratory_rate ?? '-' }}</td>
                                        <td>{{ $vital->temperature ?? '-' }}</td>
                                        <td>{{ $vital->spo2 ?? '-' }}</td>
                                        <td>{{ $vital->recordedBy->name ?? __('emergency.unknown_patient') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted py-3">{{ __('emergency.no_vitals_recorded') }}</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0">{{ __('emergency.clinical_notes') }}</h5>
                @can('emergency.notes.create')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyNoteModal">
                    <i class="ti ti-plus me-1"></i>{{ __('emergency.add_note_btn') }}
                </button>
                @endcan
            </div>
            <div class="card-body">
                <div class="er-thread">
                    @forelse($case->notes->sortByDesc('created_at') as $note)
                        @php
                            $noteMine = (int) ($note->created_by ?? $note->creator_id ?? 0) === (int) auth()->id();
                            $noteAuthor = $note->creator->name ?? __('emergency.unknown_user');
                            $noteInitials = collect(explode(' ', trim($noteAuthor)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'ER';
                        @endphp
                        <div class="er-message {{ $noteMine ? 'er-message--mine' : '' }}">
                            <div class="er-message__avatar">{{ $noteInitials }}</div>
                            <div class="er-message__bubble">
                                <div class="er-message__meta">
                                    <span>
                                        <span class="badge bg-light text-dark me-1">{{ str_replace('_', ' ', $note->note_type) }}</span>
                                        {{ $noteAuthor }}
                                    </span>
                                    <span>{{ $note->created_at?->format('d M H:i') }}</span>
                                </div>
                                <div class="er-message__body">{{ $note->content }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-muted py-3">{{ __('emergency.no_notes') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>{{-- ── /OVERVIEW ── --}}

                    {{-- Medication / MAR --}}
                    <div class="tab-pane fade" id="erTabMedication" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">{{ __('emergency.medication_mar') }}</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#addEmergencyMedicationModal">
                                    <i class="ti ti-plus me-1"></i>{{ __('emergency.order_medication_btn') }}
                                </button>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $erRoute('admin.emergency.mar-chart', $case->visit) }}"><i class="ti ti-external-link me-1"></i>{{ __('emergency.open_mar') }}</a>
                            </div>
                        </div>
                        <div class="row g-2 text-center mb-3">
                            <div class="col-3"><div class="fw-bold text-danger">{{ $medCounts['due_now'] }}</div><small class="text-muted">{{ __('emergency.due_label') }}</small></div>
                            <div class="col-3"><div class="fw-bold text-warning">{{ $medCounts['overdue'] }}</div><small class="text-muted">{{ __('emergency.late_label') }}</small></div>
                            <div class="col-3"><div class="fw-bold text-primary">{{ $medCounts['upcoming'] }}</div><small class="text-muted">{{ __('emergency.next_label') }}</small></div>
                            <div class="col-3"><div class="fw-bold text-success">{{ $medCounts['administered_today'] }}</div><small class="text-muted">{{ __('emergency.given_label') }}</small></div>
                        </div>
                        <div class="er-scroll">
                        @forelse($case->medicationOrders as $order)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">{{ $order->display_name }}</div>
                                    <x-status-badge :status="$order->status" domain="med_order" />
                                </div>
                                <small class="text-muted">{{ $order->dose }} {{ $order->route }} {{ $order->frequency?->code }} - Qty {{ $order->quantity_ordered ?? '-' }}</small>
                                @if($order->schedules->isNotEmpty())
                                    <div class="small mt-1">{{ __('emergency.next_dose') }}: {{ optional($order->schedules->whereIn('status', ['SCHEDULED', 'DUE'])->sortBy('scheduled_at')->first())->scheduled_at?->format('d M H:i') ?: __('emergency.no_pending_doses') }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="text-muted">{{ __('emergency.no_medication_orders') }}</div>
                        @endforelse
                        </div>
                    </div>

                    {{-- Investigations --}}
                    <div class="tab-pane fade" id="erTabInvestigations" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">{{ __('emergency.tab_investigations') }}</h6>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyInvestigationModal">
                                <i class="ti ti-plus me-1"></i>{{ __('emergency.request_investigation_btn') }}
                            </button>
                        </div>
                        <div class="er-scroll">
                        @forelse($case->labRequests as $request)
                            @php $requestItems = $request->items->map(fn ($item) => $item->display_name ?? $item->name ?? $item->labTest?->name)->filter()->implode(', '); @endphp
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">{{ $requestItems ?: $request->request_number }}</div>
                                    <x-status-badge :status="$request->status" domain="lab" />
                                </div>
                                <small class="text-muted">{{ $request->targetDepartment->name ?? __('emergency.dept_pending') }} - {{ $request->urgency ?? 'routine' }} - {{ __('emergency.requested_by_label') }} {{ $request->requestedBy->name ?? __('emergency.unknown_patient') }}</small>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('emergency.no_investigations') }}</div>
                        @endforelse
                        </div>
                    </div>

                    {{-- Procedures --}}
                    <div class="tab-pane fade" id="erTabProcedures" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">{{ __('emergency.tab_procedures') }}</h6>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyProcedureModal">
                                <i class="ti ti-plus me-1"></i>{{ __('emergency.request_procedure_btn') }}
                            </button>
                        </div>
                        <div class="er-scroll">
                        @forelse($case->procedureRequests as $request)
                            <div class="border rounded p-2 mb-2">
                                <div class="d-flex justify-content-between gap-2">
                                    <div class="fw-semibold">{{ $request->service?->name ?? $request->procedure_name ?? $request->request_number }}</div>
                                    <x-status-badge :status="$request->status" domain="theatre" />
                                </div>
                                <small class="text-muted">{{ $request->department->name ?? __('emergency.dept_pending') }} - {{ $request->priority ?? 'routine' }} - {{ __('emergency.requested_by_label') }} {{ $request->requestingDoctor->name ?? __('emergency.unknown_patient') }}</small>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('emergency.no_procedures') }}</div>
                        @endforelse
                        </div>
                    </div>

                    {{-- Consumables --}}
                    <div class="tab-pane fade" id="erTabConsumables" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">{{ __('emergency.consumables_label') }}</h6>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="badge bg-light text-dark">{{ __('emergency.emergency_stock_badge') }}</span>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#addEmergencyConsumableModal">
                                    <i class="ti ti-plus me-1"></i>{{ __('emergency.use_consumable_btn') }}
                                </button>
                            </div>
                        </div>
                        <div class="er-scroll">
                            @forelse($case->consumableUsages->sortByDesc('used_at') as $usage)
                                <div class="border rounded p-2 mb-2 small">
                                    <div class="d-flex justify-content-between gap-2">
                                        <span class="fw-semibold">{{ $usage->product->name ?? 'Consumable' }}</span>
                                        <span>{{ number_format((float) $usage->quantity_used, 2) }}</span>
                                    </div>
                                    <span class="text-muted">{{ $usage->used_at?->format('d M H:i') }} - {{ $usage->user->name ?? __('emergency.unknown_patient') }}</span>
                                </div>
                            @empty
                                <div class="text-muted">{{ __('emergency.no_consumables') }}</div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Tasks & Monitoring --}}
                    <div class="tab-pane fade" id="erTabTasks" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold mb-0">{{ __('emergency.tasks_monitoring') }}</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark">{{ $pendingTasks->count() }} {{ __('emergency.pending_count_badge') }}</span>
                                @can('emergency.case.update')
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyTaskModal">
                                    <i class="ti ti-plus me-1"></i>{{ __('emergency.add_task_btn') }}
                                </button>
                                @endcan
                            </div>
                        </div>
                        <div class="er-thread er-scroll">
                        @forelse($case->clinicalTasks->sortByDesc('scheduled_at') as $task)
                            @php $taskDone = in_array($task->status, ['COMPLETED', 'CANCELLED'], true); $isMedTask = $task->task_type === \App\Models\ClinicalTask::TYPE_MEDICATION_ADMINISTRATION; @endphp
                            @php
                                $taskMine = (int) ($task->assigned_to ?? 0) === (int) auth()->id();
                                $taskOwner = $task->assignedUser->name ?? $task->assigned_role ?? __('emergency.unassigned');
                                $taskInitials = collect(explode(' ', trim($taskOwner)))->filter()->take(2)->map(fn ($part) => strtoupper(substr($part, 0, 1)))->implode('') ?: 'ER';
                            @endphp
                            <div class="er-message {{ $taskMine ? 'er-message--mine' : '' }}">
                                <div class="er-message__avatar">{{ $taskInitials }}</div>
                                <div class="er-message__bubble {{ $taskDone ? 'bg-light' : '' }}">
                                    <div class="er-message__meta">
                                        <span>
                                            <span class="badge bg-light text-dark me-1">{{ $task->priority }}</span>
                                            {{ $taskOwner }}
                                        </span>
                                        <span>{{ $task->scheduled_at?->format('d M H:i') ?: __('emergency.no_schedule') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between gap-2">
                                        <div class="fw-semibold {{ $taskDone ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->title }}</div>
                                        <x-status-badge :status="$task->status" domain="default" />
                                    </div>
                                    @if($task->description)
                                        <div class="er-message__body small text-muted mt-1">{{ $task->description }}</div>
                                    @endif
                                    @unless($isMedTask)
                                    <form method="POST" action="{{ $erRoute('admin.emergency.tasks.complete', [$case, $task]) }}" class="mt-2">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm {{ $taskDone ? 'btn-outline-secondary' : 'btn-outline-success' }}" type="submit">
                                            <i class="ti {{ $taskDone ? 'ti-rotate' : 'ti-check' }} me-1"></i>{{ $taskDone ? __('emergency.reopen') : __('emergency.mark_complete') }}
                                        </button>
                                    </form>
                                    @endunless
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">{{ __('emergency.no_tasks') }}</div>
                        @endforelse
                        </div>
                    </div>

                    {{-- Billing --}}
                    <div class="tab-pane fade" id="erTabBilling" role="tabpanel">
                        @include('partials.visit-invoice-preview', [
                            'visit' => $case->visit,
                            'invoice' => $activeInvoice,
                            'title' => __('emergency.tab_billing'),
                            'emptyText' => __('emergency.no_invoice_items'),
                            'actions' => auth()->user()?->can('invoices.create')
                                ? new \Illuminate\Support\HtmlString('<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addEmergencyServiceModal"><i class="ti ti-plus me-1"></i>'.e(__('emergency.add_billable_service')).'</button>')
                                : null,
                        ])
                    </div>
    {{-- ── COORDINATION (triage · bay & team · timeline) ── --}}
    <div class="tab-pane fade" id="erTabCoordination" role="tabpanel">
        <div class="row g-3">
            <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">{{ __('emergency.triage_title') }}</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#triageModal">
                    <i class="ti ti-activity-heartbeat me-1"></i>{{ __('emergency.record_triage') }}
                </button>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge {{ $triageClass }}">{{ $currentTriage }}</span>
                    <span class="small text-muted">{{ __('emergency.score') }} {{ $case->triage_score ?? '-' }}</span>
                </div>
                <div class="small mb-2 d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <span>{{ __('emergency.auto_triage_label') }}: <span class="fw-semibold">{{ $case->auto_triage_category ?: __('emergency.pending_calc') }}</span></span>
                    <span>{{ __('emergency.avpu_label') }}: <span class="fw-semibold">{{ $case->avpu ?: __('emergency.not_recorded_label') }}</span></span>
                    <span>{{ __('emergency.pain_label') }}: <span class="fw-semibold">{{ $case->pain_score ?? __('emergency.not_recorded_label') }}</span></span>
                </div>
                @if($case->triage_reasons)
                    <div class="border rounded p-2 small">
                        <div class="fw-semibold text-danger mb-1">{{ __('emergency.automated_reasons') }}</div>
                        <ul class="mb-0 ps-3">
                            @foreach(array_slice($case->triage_reasons, 0, 3) as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="text-muted small">{{ __('emergency.no_triage_score') }}</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('emergency.bay_team_title') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $erRoute('admin.emergency.bay.assign', $case) }}" class="mb-3">
                    @csrf
                    <div class="er-section-title mb-1">{{ __('emergency.emergency_bay_section') }}</div>
                    <select class="form-select mb-2" name="emergency_bay_id" data-er-select2 data-placeholder="{{ __('emergency.bay') }}…" required>
                        @foreach($bays as $bay)
                            <option value="{{ $bay->id }}" @selected($case->emergency_bay_id === $bay->id)>{{ $bay->name }} - {{ $bay->status }}{{ $bay->bed?->bed_number ? ' - '.$bay->bed->bed_number : '' }}</option>
                        @endforeach
                    </select>
                    <div class="form-check small mb-2">
                        <input class="form-check-input" type="checkbox" name="override" value="1" id="overrideBay">
                        <label class="form-check-label" for="overrideBay">{{ __('emergency.override_bay_label') }}</label>
                    </div>
                    <button class="btn btn-sm btn-outline-primary w-100" type="submit"><i class="ti ti-bed me-1"></i>{{ __('emergency.assign_bay_btn') }}</button>
                </form>

                <form method="POST" action="{{ $erRoute('admin.emergency.bay.assign-ward-bed', $case) }}" class="mb-3 border-top pt-3">
                    @csrf
                    <div class="er-section-title mb-1">{{ __('emergency.ward_bed_section') }}</div>
                    <select class="form-select mb-2" name="ward_id" data-er-select2 data-placeholder="{{ __('emergency.no_ward_link') }}">
                        <option value="">{{ __('emergency.no_ward_link') }}</option>
                        @foreach($wards as $ward)
                            <option value="{{ $ward->id }}" @selected($case->activeBayAssignment?->ward_id === $ward->id)>{{ $ward->name }}</option>
                        @endforeach
                    </select>
                    <select class="form-select mb-2" name="bed_id" data-er-select2 data-placeholder="{{ __('emergency.no_bed_link') }}">
                        <option value="">{{ __('emergency.no_bed_link') }}</option>
                        @foreach($wards as $ward)
                            @foreach($ward->beds as $bed)
                                <option value="{{ $bed->id }}" @selected($case->activeBayAssignment?->bed_id === $bed->id)>{{ $ward->name }} - {{ $bed->bed_number }} ({{ $bed->status }})</option>
                            @endforeach
                        @endforeach
                    </select>
                    <div class="form-check small mb-2">
                        <input class="form-check-input" type="checkbox" name="override" value="1" id="overrideBed">
                        <label class="form-check-label" for="overrideBed">{{ __('emergency.override_bed_label') }}</label>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary w-100" type="submit" @disabled(! $case->emergency_bay_id)><i class="ti ti-link me-1"></i>{{ __('emergency.link_ward_bed_btn') }}</button>
                    @unless($case->emergency_bay_id)
                        <div class="small text-muted mt-1"><i class="ti ti-info-circle me-1"></i>{{ __('emergency.assign_bay_first_hint') }}</div>
                    @endunless
                </form>
                <div class="border-top pt-3 mb-3">
                    <div class="er-section-title mb-2">{{ __('emergency.assignment_history') }}</div>
                    @forelse($case->bayAssignments->sortByDesc('assigned_at') as $assignment)
                        <div class="d-flex align-items-start justify-content-between gap-2 small mb-2">
                            <div>
                                <div class="fw-semibold">{{ $assignment->ward?->name ?: 'Emergency' }} &middot; {{ $assignment->bed?->bed_number ?: ($assignment->emergencyBay?->name ?: 'Bay') }}</div>
                                <span class="text-muted">{{ $assignment->assigned_at?->format('d M H:i') }}</span>
                            </div>
                            <x-status-badge :status="$assignment->status" size="sm" />
                        </div>
                    @empty
                        <div class="small text-muted">{{ __('emergency.no_assignment_history') }}</div>
                    @endforelse
                </div>

                <form method="POST" action="{{ $erRoute('admin.emergency.cases.update', $case) }}" class="border-top pt-3">
                    @csrf
                    @method('PATCH')
                    <div class="er-section-title mb-2">{{ __('emergency.care_team_status') }}</div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">{{ __('emergency.doctor_label') }}</label>
                        <select class="form-select" name="assigned_doctor_id" data-er-select2 data-placeholder="{{ __('emergency.unassigned') }}">
                            <option value="">{{ __('emergency.unassigned') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($case->assigned_doctor_id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">{{ __('emergency.nurse_label') }}</label>
                        <select class="form-select" name="assigned_nurse_id" data-er-select2 data-placeholder="{{ __('emergency.unassigned') }}">
                            <option value="">{{ __('emergency.unassigned') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected($case->assigned_nurse_id === $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1">{{ __('emergency.emergency_status_label') }}</label>
                        <select class="form-select" name="emergency_status">
                            @foreach(['WAITING_TRIAGE','TRIAGED','UNDER_EMERGENCY_CARE','OBSERVATION','READY_FOR_DISPOSITION','CANCELLED'] as $status)
                                <option value="{{ $status }}" @selected($case->emergency_status === $status)>{{ str_replace('_', ' ', $status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-sm btn-primary w-100" type="submit"><i class="ti ti-device-floppy me-1"></i>{{ __('emergency.update_case_btn') }}</button>
                </form>
            </div>
        </div>
            </div>{{-- /col-lg-6 --}}
            <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('emergency.timeline_title') }}</h5></div>
            <div class="card-body">
                @forelse($case->logs->sortByDesc('created_at') as $log)
                    <div class="d-flex gap-3 pb-3 mb-3 border-bottom">
                        <div class="text-muted small" style="min-width: 110px;">{{ $log->created_at?->format('d M H:i') }}</div>
                        <div>
                            <div class="fw-semibold">{{ $log->title }}</div>
                            @if($log->description)<div class="small">{{ Str::limit($log->description, 15) }}</div>@endif
                            <small class="text-muted">{{ $log->action }} {{ __('emergency.timeline_action') }} {{ $log->performedBy->name ?? 'System' }}</small>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">{{ __('emergency.no_timeline') }}</div>
                @endforelse
            </div>
        </div>
            </div>{{-- /col-lg-6 --}}
        </div>{{-- /row --}}
    </div>{{-- ── /COORDINATION ── --}}

    {{-- ── DISPOSITION ── --}}
    <div class="tab-pane fade" id="erTabDisposition" role="tabpanel">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('emergency.disposition_title') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $erRoute('admin.emergency.disposition.store', $case) }}" class="row g-3">
            @csrf
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.disposition_label') }}</label>
                <select class="form-select" name="disposition" required>
                    @foreach(['ADMITTED','DISCHARGED','TRANSFERRED_TO_OPD','TRANSFERRED_TO_THEATRE','REFERRED_OUT','LEFT_AGAINST_MEDICAL_ADVICE','ABSCONDED','DIED','DEAD_ON_ARRIVAL'] as $disposition)
                        <option value="{{ $disposition }}" @selected($case->disposition === $disposition)>{{ str_replace('_', ' ', $disposition) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.disposition_time') }}</label>
                <input class="form-control" type="datetime-local" name="disposition_time" value="{{ now()->format('Y-m-d\TH:i') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('emergency.cause_of_death') }}</label>
                <input class="form-control" name="cause_of_death" placeholder="{{ __('emergency.cause_of_death_ph') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-danger w-100" type="submit">{{ __('emergency.record_disposition_btn') }}</button>
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('emergency.disposition_notes_label') }}</label>
                <textarea class="form-control" name="disposition_notes" rows="2">{{ $case->disposition_notes }}</textarea>
            </div>
                </form>
            </div>
        </div>
    </div>{{-- ── /DISPOSITION ── --}}
</div>{{-- /tab-content --}}

<div class="modal fade" id="addEmergencyMedicationModal" tabindex="-1" aria-labelledby="addEmergencyMedicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.medications.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyMedicationModalLabel"><i class="ti ti-pill me-1"></i>{{ __('emergency.order_medication_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.select_medication') }}</label>
                        <select class="form-select" name="product_id" id="medicationProductSelect" data-er-select2 data-placeholder="{{ __('emergency.select_medication') }}" required>
                            <option value="">{{ __('emergency.select_medication') }}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" data-emergency-stock="{{ $product->emergency_available_quantity ?? 0 }}" data-pharmacy-stock="{{ $product->pharmacy_available_quantity ?? 0 }}" @selected(old('product_id') == $product->id)>
                                    {{ $product->name }} - ER {{ number_format($product->emergency_available_quantity ?? 0, 0) }} / Pharmacy {{ number_format($product->pharmacy_available_quantity ?? 0, 0) }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('emergency.er_pharmacy_stock_note') }}</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('emergency.dose_placeholder') }}</label>
                        <input class="form-control" name="dose" placeholder="{{ __('emergency.dose_placeholder') }}" required value="{{ old('dose') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('emergency.route_placeholder') }}</label>
                        <input class="form-control" name="route" placeholder="{{ __('emergency.route_placeholder') }}" required value="{{ old('route') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('emergency.frequency_label') }}</label>
                        <select class="form-select" name="frequency_code" required>
                            @foreach($frequencies as $frequency)
                                <option value="{{ $frequency->code }}" @selected(old('frequency_code') === $frequency->code)>{{ $frequency->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('emergency.duration_placeholder') }}</label>
                        <input class="form-control" type="number" min="1" name="duration_value" value="{{ old('duration_value', 1) }}" placeholder="{{ __('emergency.duration_placeholder') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('emergency.duration_label') }}</label>
                        <select class="form-select" name="duration_unit">
                            <option value="days" @selected(old('duration_unit', 'days') === 'days')>{{ __('emergency.duration_days') }}</option>
                            <option value="weeks" @selected(old('duration_unit') === 'weeks')>{{ __('emergency.duration_weeks') }}</option>
                            <option value="months" @selected(old('duration_unit') === 'months')>{{ __('emergency.duration_months') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('emergency.qty_override_placeholder') }}</label>
                        <input class="form-control" type="number" min="0" name="quantity_ordered" placeholder="{{ __('emergency.qty_override_placeholder') }}" value="{{ old('quantity_ordered') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.start_at_label') }}</label>
                        <input class="form-control" type="datetime-local" name="start_at" value="{{ old('start_at') }}">
                    </div>
                    <div class="col-12"><div class="small text-muted" id="medicationQuantityHint">{{ __('emergency.er_pharmacy_stock_note') }}</div></div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.instructions_label') }}</label>
                        <textarea class="form-control" name="instructions" rows="3" placeholder="{{ __('emergency.instructions_placeholder') }}">{{ old('instructions') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-danger"><i class="ti ti-plus me-1"></i>{{ __('emergency.order_medication_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addEmergencyInvestigationModal" tabindex="-1" aria-labelledby="addEmergencyInvestigationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.investigations.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyInvestigationModalLabel"><i class="ti ti-microscope me-1"></i>{{ __('emergency.request_investigation_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.investigation_dept_ph') }}</label>
                        <select class="form-select" name="target_department_id" id="emergencyInvestigationDepartment" required>
                            <option value="">{{ __('emergency.investigation_dept_ph') }}</option>
                            @foreach($investigationDepartments as $department)
                                <option value="{{ $department->id }}" @selected(old('target_department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.tab_investigations') }}</label>
                        <select class="form-select" name="service_id[]" id="emergencyInvestigationService" multiple data-placeholder="{{ __('emergency.tab_investigations') }}" required disabled></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.urgency_label') }}</label>
                        <select class="form-select" name="urgency">
                            <option value="emergency" @selected(old('urgency', 'emergency') === 'emergency')>{{ __('emergency.urgency_emergency') }}</option>
                            <option value="urgent" @selected(old('urgency') === 'urgent')>{{ __('emergency.urgency_urgent') }}</option>
                            <option value="routine" @selected(old('urgency') === 'routine')>{{ __('emergency.urgency_routine') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.clinical_info_label') }}</label>
                        <textarea class="form-control" name="clinical_info" rows="3" placeholder="{{ __('emergency.clinical_info_label') }}">{{ old('clinical_info') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('emergency.request_investigation_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addEmergencyProcedureModal" tabindex="-1" aria-labelledby="addEmergencyProcedureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.procedures.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyProcedureModalLabel"><i class="ti ti-activity me-1"></i>{{ __('emergency.request_procedure_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.procedure_dept_ph') }}</label>
                        <select class="form-select" name="department_id" id="emergencyProcedureDepartment" required>
                            <option value="">{{ __('emergency.procedure_dept_ph') }}</option>
                            @foreach($procedureDepartments as $department)
                                <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.tab_procedures') }}</label>
                        <select class="form-select" name="service_catalog_id[]" id="emergencyProcedureService" multiple data-placeholder="{{ __('emergency.tab_procedures') }}" required disabled></select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admissions.priority_col') }}</label>
                        <select class="form-select" name="priority" required>
                            <option value="emergency" @selected(old('priority', 'emergency') === 'emergency')>{{ __('emergency.urgency_emergency') }}</option>
                            <option value="urgent" @selected(old('priority') === 'urgent')>{{ __('emergency.urgency_urgent') }}</option>
                            <option value="routine" @selected(old('priority') === 'routine')>{{ __('emergency.urgency_routine') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.indication_label') }}</label>
                        <textarea class="form-control" name="indication" rows="3" placeholder="{{ __('emergency.indication_label') }}" required>{{ old('indication') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('emergency.request_procedure_btn') }}</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="addEmergencyConsumableModal" tabindex="-1" aria-labelledby="addEmergencyConsumableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.consumables.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyConsumableModalLabel"><i class="ti ti-package me-1"></i>{{ __('emergency.use_consumable_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.select_consumable') }}</label>
                        <select class="form-select" name="product_id" id="emergencyConsumableSelect" data-er-select2 data-placeholder="{{ __('emergency.select_consumable') }}" required>
                            <option value="">{{ __('emergency.select_consumable') }}</option>
                            @foreach($consumableProducts as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} - ER {{ number_format($product->emergency_available_quantity ?? 0, 0) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admissions.qty_field') }}</label>
                        <input class="form-control" type="number" min="0.0001" step="0.01" name="quantity" value="{{ old('quantity', 1) }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ __('emergency.usage_notes_ph') }}</label>
                        <input class="form-control" name="notes" placeholder="{{ __('emergency.usage_notes_ph') }}" value="{{ old('notes') }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-danger"><i class="ti ti-plus me-1"></i>{{ __('emergency.use_consumable_btn') }}</button>
            </div>
        </form>
    </div>
</div>

@can('emergency.notes.create')
<div class="modal fade" id="addEmergencyNoteModal" tabindex="-1" aria-labelledby="addEmergencyNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.notes.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyNoteModalLabel"><i class="ti ti-notes me-1"></i>{{ __('emergency.add_note_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admissions.type_col') }}</label>
                        <select class="form-select" name="note_type" required>
                            @foreach(['DOCTOR_ASSESSMENT','NURSING_NOTE','RESUSCITATION_NOTE','OBSERVATION_NOTE','GENERAL_NOTE'] as $type)
                                <option value="{{ $type }}" @selected(old('note_type') === $type)>{{ str_replace('_', ' ', $type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.clinical_notes') }}</label>
                        <textarea class="form-control" name="content" rows="5" placeholder="{{ __('emergency.add_rapid_note') }}" required>{{ old('content') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('emergency.add_note_btn') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('emergency.case.update')
<div class="modal fade" id="addEmergencyTaskModal" tabindex="-1" aria-labelledby="addEmergencyTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.tasks.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyTaskModalLabel"><i class="ti ti-checklist me-1"></i>{{ __('emergency.add_task_btn') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ __('emergency.tab_tasks') }}</label>
                        <input class="form-control" name="title" placeholder="{{ __('emergency.task_placeholder') }}" maxlength="255" required value="{{ old('title') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admissions.priority_col') }}</label>
                        <select class="form-select" name="priority">
                            <option value="normal" @selected(old('priority', 'normal') === 'normal')>{{ __('emergency.priority_normal') }}</option>
                            <option value="high" @selected(old('priority') === 'high')>{{ __('emergency.priority_high') }}</option>
                            <option value="critical" @selected(old('priority') === 'critical')>{{ __('emergency.priority_critical') }}</option>
                            <option value="low" @selected(old('priority') === 'low')>{{ __('emergency.priority_low') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admissions.due_col') }}</label>
                        <input class="form-control" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admissions.assigned_to_col') }}</label>
                        <select class="form-select" name="assigned_to" data-er-select2 data-placeholder="{{ __('emergency.assign_to_ph') }}">
                            <option value="">{{ __('emergency.assign_to_ph') }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('emergency.add_task_btn') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('invoices.create')
<div class="modal fade" id="addEmergencyServiceModal" tabindex="-1" aria-labelledby="addEmergencyServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $erRoute('admin.emergency.services.store', $case) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addEmergencyServiceModalLabel"><i class="ti ti-receipt me-1"></i>{{ __('emergency.add_billable_service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">{{ __('admissions.service_field') }}</label>
                        <select class="form-select" name="service_catalog_id" data-er-select2 data-placeholder="{{ __('emergency.add_billable_service') }}" required>
                            <option value="">{{ __('emergency.add_billable_service') }}</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" @selected(old('service_catalog_id') == $service->id)>{{ $service->name }} - {{ $service->formatted_price }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admissions.qty_field') }}</label>
                        <input class="form-control" type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary" @disabled($services->isEmpty())><i class="ti ti-plus me-1"></i>{{ __('emergency.add_to_invoice_btn') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

<div class="modal fade" id="controlSheetModal" tabindex="-1" aria-labelledby="controlSheetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ $erRoute('admin.emergency.cases.update', $case) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="_form" value="control_sheet">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="controlSheetModalLabel">{{ __('emergency.control_sheet_modal_title') }}</h5>
                        <div class="small text-muted">{{ $case->emergency_number }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.arrival_mode_label') }}</label>
                            <select class="form-select" name="arrival_mode">
                                @foreach(['WALK_IN','AMBULANCE','POLICE','FAMILY_BROUGHT','REFERRAL','TRANSFER_FROM_OPD','TRANSFER_FROM_WARD','UNKNOWN'] as $mode)
                                    <option value="{{ $mode }}" @selected(old('arrival_mode', $case->arrival_mode) === $mode)>{{ str_replace('_', ' ', $mode) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.arrival_time_label') }}</label>
                            <input class="form-control" type="datetime-local" name="arrival_time" value="{{ old('arrival_time', $case->arrival_time?->format('Y-m-d\TH:i')) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.brought_by_label') }}</label>
                            <input class="form-control" name="brought_by" value="{{ old('brought_by', $case->brought_by) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.source_field_label') }}</label>
                            <input class="form-control" name="source" value="{{ old('source', $case->source) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.referral_facility_label') }}</label>
                            <input class="form-control" name="referral_facility" value="{{ old('referral_facility', $case->referral_facility) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.emergency_status_field') }}</label>
                            <select class="form-select" name="emergency_status">
                                @foreach(['WAITING_TRIAGE','TRIAGED','UNDER_EMERGENCY_CARE','OBSERVATION','READY_FOR_DISPOSITION','CANCELLED'] as $status)
                                    <option value="{{ $status }}" @selected(old('emergency_status', $case->emergency_status) === $status)>{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.chief_complaint_field') }}</label>
                            <textarea class="form-control" name="chief_complaint" rows="3">{{ old('chief_complaint', $case->chief_complaint) }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.initial_condition_field') }}</label>
                            <textarea class="form-control" name="initial_condition" rows="3">{{ old('initial_condition', $case->initial_condition) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('emergency.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('emergency.save_control_sheet') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="triageModal" tabindex="-1" aria-labelledby="triageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ $erRoute('admin.emergency.triage.store', $case) }}">
                @csrf
                <input type="hidden" name="_form" value="triage">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="triageModalLabel">{{ __('emergency.triage_modal_title') }}</h5>
                        <div class="small text-muted">{{ $case->emergency_number }} - {{ $case->patient->full_name ?? __('emergency.unknown_patient') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.auto_category_field') }}</label>
                            <input class="form-control" value="{{ $case->auto_triage_category ?: __('emergency.calculated_on_save') }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.final_category_field') }}</label>
                            <select class="form-select" name="final_triage_category">
                                <option value="">{{ __('emergency.use_auto_category') }}</option>
                                @foreach(['RED','ORANGE','YELLOW','GREEN','BLACK'] as $category)
                                    <option value="{{ $category }}" @selected(old('final_triage_category', $currentTriage) === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.avpu_field') }}</label>
                            <select class="form-select" name="avpu">
                                <option value="">{{ __('emergency.avpu_not_recorded') }}</option>
                                @foreach(['A' => __('emergency.avpu_alert'), 'V' => __('emergency.avpu_voice'), 'P' => __('emergency.avpu_pain'), 'U' => __('emergency.avpu_unresponsive')] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('avpu', $case->avpu) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('emergency.pain_score_field') }}</label>
                            <input class="form-control" type="number" min="0" max="10" name="pain_score" value="{{ old('pain_score', $case->pain_score) }}" placeholder="0-10">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.danger_signs_field') }}</label>
                            <div class="row g-2">
                                @foreach($dangerSignOptions as $value => $label)
                                    <div class="col-sm-6 col-lg-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="danger_signs[]" value="{{ $value }}" id="triage_danger_{{ $value }}" @checked(in_array($value, old('danger_signs', $case->danger_signs ?: []), true))>
                                            <label class="form-check-label" for="triage_danger_{{ $value }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('emergency.override_reason_field') }}</label>
                            <input class="form-control" name="triage_override_reason" value="{{ old('triage_override_reason', $case->triage_override_reason) }}" placeholder="{{ __('emergency.override_reason_ph') }}">
                        </div>
                        <div class="col-md-3"><input class="form-control" name="blood_pressure_systolic" value="{{ old('blood_pressure_systolic') }}" placeholder="BP Sys"></div>
                        <div class="col-md-3"><input class="form-control" name="blood_pressure_diastolic" value="{{ old('blood_pressure_diastolic') }}" placeholder="BP Dia"></div>
                        <div class="col-md-2"><input class="form-control" name="heart_rate" value="{{ old('heart_rate') }}" placeholder="HR"></div>
                        <div class="col-md-2"><input class="form-control" name="respiratory_rate" value="{{ old('respiratory_rate') }}" placeholder="RR"></div>
                        <div class="col-md-2"><input class="form-control" name="spo2" value="{{ old('spo2') }}" placeholder="SpO2"></div>
                        <div class="col-md-6"><input class="form-control" name="temperature" value="{{ old('temperature') }}" placeholder="Temperature"></div>
                        <div class="col-md-6"><input class="form-control" name="triage_score" value="{{ old('triage_score') }}" placeholder="Manual score"></div>
                        <div class="col-12">
                            <textarea class="form-control" name="triage_notes" rows="3" placeholder="Triage notes">{{ old('triage_notes', $case->triage_notes) }}</textarea>
                        </div>
                        @if($case->triage_reasons || $case->triage_warnings)
                            <div class="col-12">
                                <div class="border rounded p-3 small">
                                    @if($case->triage_reasons)
                                        <div class="fw-semibold text-danger mb-1">{{ __('emergency.automated_reasons') }}</div>
                                        <ul class="mb-2 ps-3">
                                            @foreach($case->triage_reasons as $reason)
                                                <li>{{ $reason }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if($case->triage_warnings)
                                        <div class="fw-semibold text-warning mb-1">{{ __('emergency.warnings_label') }}</div>
                                        <ul class="mb-0 ps-3">
                                            @foreach($case->triage_warnings as $warning)
                                                <li>{{ $warning }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('emergency.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('emergency.save_triage_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Jump to a workspace tab from the stat tiles.
function erShowTab(id) {
    var btn = document.querySelector('[data-bs-target="#' + id + '"]');
    if (btn && window.bootstrap) { bootstrap.Tab.getOrCreateInstance(btn).show(); }
    var pane = document.getElementById(id);
    if (pane) { setTimeout(function () { pane.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100); }
}
document.addEventListener('DOMContentLoaded', function () {
    var vitalsChartData = @json($vitalsChartData);
    if (window.Chart && vitalsChartData.labels && vitalsChartData.labels.length) {
        var rangeStatus = function (value, range) {
            if (value === null || value === undefined || !range) return 'normal';
            var numeric = Number(value);
            if (Number.isNaN(numeric)) return 'normal';
            if (numeric < range.min) return 'low';
            if (numeric > range.max) return 'high';
            return 'normal';
        };
        var abnormalPointColor = function (status, fallback) {
            if (status === 'low') return '#0d6efd';
            if (status === 'high') return '#dc3545';
            return fallback;
        };
        var vitalsSpark = function (id, series) {
            var el = document.getElementById(id);
            if (!el || !series.some(function (s) { return s.data && s.data.some(function (v) { return v !== null && v !== undefined; }); })) {
                return;
            }
            new Chart(el, {
                type: 'line',
                data: {
                    labels: vitalsChartData.labels,
                    datasets: series.map(function (s) {
                        return {
                            label: s.label, data: s.data, borderColor: s.color,
                            backgroundColor: s.color, borderWidth: 2,
                            tension: .3, spanGaps: true,
                            pointStyle: function(ctx) {
                                return rangeStatus(ctx.parsed.y, s.normalRange) === 'normal' ? 'circle' : 'triangle';
                            },
                            pointRotation: function(ctx) {
                                return rangeStatus(ctx.parsed.y, s.normalRange) === 'low' ? 180 : 0;
                            },
                            pointRadius: function(ctx) {
                                return rangeStatus(ctx.parsed.y, s.normalRange) === 'normal' ? 2.5 : 4.5;
                            },
                            pointHoverRadius: function(ctx) {
                                return rangeStatus(ctx.parsed.y, s.normalRange) === 'normal' ? 4 : 6;
                            },
                            pointBackgroundColor: function(ctx) {
                                return abnormalPointColor(rangeStatus(ctx.parsed.y, s.normalRange), s.color);
                            },
                            pointBorderColor: function(ctx) {
                                return abnormalPointColor(rangeStatus(ctx.parsed.y, s.normalRange), s.color);
                            },
                            pointBorderWidth: function(ctx) {
                                return rangeStatus(ctx.parsed.y, s.normalRange) === 'normal' ? 1 : 2;
                            }
                        };
                    })
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    interaction: { mode: 'nearest', intersect: false },
                    scales: {
                        x: { display: false },
                        y: {
                            beginAtZero: false, grace: '15%',
                            ticks: { maxTicksLimit: 3, font: { size: 9 }, color: '#adb5bd' },
                            grid: { display: false }, border: { display: false }
                        }
                    }
                }
            });
        };
        vitalsSpark('vitalsSparkBp', [
            { label: 'Systolic', data: vitalsChartData.systolic, color: '#6f42c1', normalRange: { min: 90, max: 120 } },
            { label: 'Diastolic', data: vitalsChartData.diastolic, color: '#20c997', normalRange: { min: 60, max: 80 } }
        ]);
        vitalsSpark('vitalsSparkHr', [{ label: 'Heart Rate', data: vitalsChartData.heart_rate, color: '#dc3545', normalRange: { min: 60, max: 100 } }]);
        vitalsSpark('vitalsSparkRr', [{ label: 'Respiratory Rate', data: vitalsChartData.respiratory_rate, color: '#0d6efd', normalRange: { min: 12, max: 20 } }]);
        vitalsSpark('vitalsSparkSpo2', [{ label: 'SpO2', data: vitalsChartData.spo2, color: '#198754', normalRange: { min: 95, max: 100 } }]);
        vitalsSpark('vitalsSparkTemp', [{ label: 'Temperature', data: vitalsChartData.temperature, color: '#fd7e14', normalRange: { min: 36.1, max: 37.2 } }]);
    }

    var modalToOpen = @json($shouldOpenTriageModal ? 'triageModal' : ($shouldOpenControlSheetModal ? 'controlSheetModal' : null));
    if (modalToOpen && window.bootstrap) {
        var targetModal = document.getElementById(modalToOpen);
        if (targetModal) {
            bootstrap.Modal.getOrCreateInstance(targetModal).show();
        }
    }

    document.querySelectorAll('[data-filter-target]').forEach(function (input) {
        var select = document.getElementById(input.dataset.filterTarget);
        if (!select) return;
        var options = Array.from(select.options).map(function (option) { return { option: option, text: option.text.toLowerCase() }; });
        input.addEventListener('input', function () {
            var needle = input.value.toLowerCase().trim();
            options.forEach(function (entry, index) {
                entry.option.hidden = index > 0 && needle && !entry.text.includes(needle);
            });
        });
    });

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function applySelect2(el, placeholder) {
        if (!hasSelect2() || !el) return;
        var $el = jQuery(el);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        var options = {
            width: '100%',
            placeholder: el.dataset.placeholder || placeholder || 'Select…',
            allowClear: !el.multiple,
        };
        var modal = el.closest('.modal');
        if (modal) {
            options.dropdownParent = jQuery(modal);
        }
        $el.select2(options);
    }

    // Generic searchable selects (drug picker, bay, ward, bed, billable service…).
    document.querySelectorAll('[data-er-select2]').forEach(function (el) { applySelect2(el); });

    // select2 initialised inside a hidden tab pane can render with the wrong
    // width; re-trigger it when its tab becomes visible.
    document.querySelectorAll('#erActionTabs button[data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function (e) {
            var pane = document.querySelector(e.target.getAttribute('data-bs-target'));
            if (pane && window.jQuery) {
                jQuery(pane).find('.select2-hidden-accessible').trigger('change.select2');
            }
        });
    });

    function formatEmergencyServiceLabel(service) {
        var parts = [service.name];
        if (service.code) parts.push('[' + service.code + ']');
        if (service.price !== null && service.price !== undefined && service.price !== '') parts.push('GH' + Number(service.price).toFixed(2));
        return parts.join(' - ');
    }

    function setupDepartmentServiceSelect(departmentSelectId, serviceSelectId, services, emptyDepartmentLabel, selectServiceLabel, emptyServiceLabel) {
        var departmentSelect = document.getElementById(departmentSelectId);
        var serviceSelect = document.getElementById(serviceSelectId);
        if (!departmentSelect || !serviceSelect) return;

        var isMulti = serviceSelect.multiple;
        function renderServices() {
            var departmentId = departmentSelect.value;
            if (hasSelect2() && jQuery(serviceSelect).hasClass('select2-hidden-accessible')) {
                jQuery(serviceSelect).select2('destroy');
            }
            serviceSelect.innerHTML = '';

            if (!departmentId) {
                serviceSelect.disabled = true;
                if (!isMulti) serviceSelect.append(new Option(emptyDepartmentLabel, ''));
                applySelect2(serviceSelect, emptyDepartmentLabel);
                return;
            }

            var matches = services.filter(function (service) {
                return String(service.department_id) === String(departmentId);
            });

            serviceSelect.disabled = matches.length === 0;
            if (!isMulti) serviceSelect.append(new Option(matches.length ? selectServiceLabel : emptyServiceLabel, ''));

            matches.forEach(function (service) {
                serviceSelect.append(new Option(formatEmergencyServiceLabel(service), service.id));
            });
            applySelect2(serviceSelect, matches.length ? selectServiceLabel : emptyServiceLabel);
        }

        departmentSelect.addEventListener('change', renderServices);
        renderServices();
    }

    setupDepartmentServiceSelect(
        'emergencyInvestigationDepartment',
        'emergencyInvestigationService',
        @json($investigationServiceOptions),
        'Select department first',
        'Investigation service',
        'No investigation services for this department'
    );
    setupDepartmentServiceSelect(
        'emergencyProcedureDepartment',
        'emergencyProcedureService',
        @json($procedureServiceOptions),
        'Select department first',
        'Procedure service',
        'No procedure services for this department'
    );

    var frequencyMap = @json($frequencyMap);
    var medicationForm = document.querySelector('form[action="{{ $erRoute('admin.emergency.medications.store', $case) }}"]');
    var hint = document.getElementById('medicationQuantityHint');
    function updateMedicationHint() {
        if (!medicationForm || !hint) return;
        var frequencyCode = medicationForm.querySelector('[name="frequency_code"]')?.value;
        var quantity = medicationForm.querySelector('[name="quantity_ordered"]')?.value;
        var durationValue = parseInt(medicationForm.querySelector('[name="duration_value"]')?.value || '1', 10);
        var durationUnit = medicationForm.querySelector('[name="duration_unit"]')?.value || 'days';
        if (quantity) {
            hint.textContent = 'Manual quantity override: ' + quantity + ' unit(s).';
            return;
        }
        var frequency = frequencyMap[frequencyCode] || {};
        var days = durationUnit === 'weeks' ? durationValue * 7 : (durationUnit === 'months' ? durationValue * 30 : durationValue);
        var calculated = frequency.stat ? 1 : Math.max(1, days * (frequency.times || 0));
        hint.textContent = frequency.prn ? 'PRN orders use the quantity override when supplied.' : 'Estimated quantity: ' + calculated + ' dose(s).';
    }
    if (medicationForm) {
        medicationForm.querySelectorAll('[name="frequency_code"], [name="duration_value"], [name="duration_unit"], [name="quantity_ordered"]').forEach(function (field) {
            field.addEventListener('input', updateMedicationHint);
            field.addEventListener('change', updateMedicationHint);
        });
        updateMedicationHint();
    }
});
</script>
@endpush

@if($temporaryPatient)
@can('patients.merge.confirm_identity')
<div class="modal fade" id="confirmEmergencyIdentityModal" tabindex="-1" aria-labelledby="confirmEmergencyIdentityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <div>
                    <h5 class="modal-title" id="confirmEmergencyIdentityModalLabel"><i class="ti ti-id-badge-2 me-1"></i>{{ __('emergency.confirm_identity_title') }}</h5>
                    <div class="small text-muted">{{ $temporaryPatient->patient_number }} - {{ $temporaryPatient->full_name }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ old('_identity_action') === 'register' ? '' : 'active' }}" id="existing-identity-tab" data-bs-toggle="tab" data-bs-target="#existing-identity-pane" type="button" role="tab" aria-controls="existing-identity-pane" aria-selected="{{ old('_identity_action') === 'register' ? 'false' : 'true' }}">
                            <i class="ti ti-users me-1"></i>{{ __('emergency.existing_patient_tab') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ old('_identity_action') === 'register' ? 'active' : '' }}" id="register-identity-tab" data-bs-toggle="tab" data-bs-target="#register-identity-pane" type="button" role="tab" aria-controls="register-identity-pane" aria-selected="{{ old('_identity_action') === 'register' ? 'true' : 'false' }}">
                            <i class="ti ti-user-plus me-1"></i>{{ __('emergency.register_patient_tab') }}
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ old('_identity_action') === 'register' ? '' : 'show active' }}" id="existing-identity-pane" role="tabpanel" aria-labelledby="existing-identity-tab" tabindex="0">
                        <form method="POST" action="{{ $erRoute('admin.emergency.cases.confirm-identity', $case) }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="_identity_action" value="existing">
                            <div class="col-md-7">
                                <label class="form-label">{{ __('emergency.confirmed_patient_folder') }} <span class="text-danger">*</span></label>
                                <select name="confirmed_patient_id" class="form-select @error('confirmed_patient_id') is-invalid @enderror" required>
                                    <option value="">{{ __('emergency.select_confirmed_patient') }}</option>
                                    @foreach($identityCandidates as $candidate)
                                        <option value="{{ $candidate->id }}" @selected(old('confirmed_patient_id') == $candidate->id)>{{ $candidate->patient_number }} - {{ $candidate->full_name }} - {{ $candidate->phone }}</option>
                                    @endforeach
                                </select>
                                @error('confirmed_patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">{{ __('emergency.confirmation_note_label') }}</label>
                                <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" placeholder="{{ __('emergency.confirm_id_note_ph') }}">
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input class="form-check-input @error('confirmed') is-invalid @enderror" type="checkbox" name="confirmed" value="1" id="existingIdentityConfirmed" required @checked(old('confirmed'))>
                                    <label class="form-check-label" for="existingIdentityConfirmed">{{ __('emergency.verify_merge_checkbox') }}</label>
                                    @error('confirmed')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button class="btn btn-warning" type="submit"><i class="ti ti-git-merge me-1"></i>{{ __('emergency.merge_btn') }}</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade {{ old('_identity_action') === 'register' ? 'show active' : '' }}" id="register-identity-pane" role="tabpanel" aria-labelledby="register-identity-tab" tabindex="0">
                        <form method="POST" action="{{ $erRoute('admin.emergency.cases.register-identity', $case) }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="_identity_action" value="register">
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.first_name_label') }} <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $temporaryPatient->first_name) }}" required>
                                @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.other_names_label') }}</label>
                                <input type="text" name="other_names" class="form-control @error('other_names') is-invalid @enderror" value="{{ old('other_names', $temporaryPatient->other_names) }}">
                                @error('other_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.last_name_label') }} <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name', $temporaryPatient->last_name) }}" required>
                                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.date_of_birth_label') }} <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth', $temporaryPatient->date_of_birth?->format('Y-m-d')) }}" required>
                                @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.gender_field_label') }} <span class="text-danger">*</span></label>
                                <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                                    <option value="">{{ __('emergency.select_gender') }}</option>
                                    @foreach(\App\Enums\Gender::cases() as $gender)
                                        <option value="{{ $gender->value }}" @selected(old('gender', $temporaryPatient->getRawOriginal('gender')) === $gender->value)>{{ $gender->label() }}</option>
                                    @endforeach
                                </select>
                                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.phone_number_label') }} <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $temporaryPatient->phone === '0000000000' ? '' : $temporaryPatient->phone) }}" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.secondary_phone_label') }}</label>
                                <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary', $temporaryPatient->phone_secondary) }}">
                                @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.email_address_label') }}</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $temporaryPatient->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.ghana_card_label') }}</label>
                                <input type="text" name="ghana_card_number" class="form-control @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number', $temporaryPatient->ghana_card_number) }}">
                                @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.blood_group_label') }}</label>
                                <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach(\App\Enums\BloodGroup::cases() as $bloodGroup)
                                        <option value="{{ $bloodGroup->value }}" @selected(old('blood_group', $temporaryPatient->getRawOriginal('blood_group')) === $bloodGroup->value)>{{ $bloodGroup->label() }}</option>
                                    @endforeach
                                </select>
                                @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.marital_status_label') }}</label>
                                <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                                    <option value="">{{ __('common.select') }}</option>
                                    @foreach(\App\Enums\MaritalStatus::cases() as $maritalStatus)
                                        <option value="{{ $maritalStatus->value }}" @selected(old('marital_status', $temporaryPatient->getRawOriginal('marital_status')) === $maritalStatus->value)>{{ $maritalStatus->label() }}</option>
                                    @endforeach
                                </select>
                                @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.region_label') }}</label>
                                <input type="text" name="region" class="form-control @error('region') is-invalid @enderror" value="{{ old('region', $temporaryPatient->region) }}">
                                @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.city_label') }}</label>
                                <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $temporaryPatient->city) }}">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.town_label') }}</label>
                                <input type="text" name="town" class="form-control @error('town') is-invalid @enderror" value="{{ old('town', $temporaryPatient->town) }}">
                                @error('town')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('emergency.digital_address_label') }}</label>
                                <input type="text" name="digital_address" class="form-control @error('digital_address') is-invalid @enderror" value="{{ old('digital_address', $temporaryPatient->digital_address) }}">
                                @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('emergency.address_label') }}</label>
                                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $temporaryPatient->address) }}</textarea>
                                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">{{ __('emergency.confirmation_note_label') }}</label>
                                <input type="text" name="reason" class="form-control @error('reason') is-invalid @enderror" value="{{ old('reason') }}" placeholder="{{ __('emergency.register_confirm_note_ph') }}">
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-8">
                                <div class="form-check">
                                    <input class="form-check-input @error('confirmed') is-invalid @enderror" type="checkbox" name="confirmed" value="1" id="registerIdentityConfirmed" required @checked(old('confirmed'))>
                                    <label class="form-check-label" for="registerIdentityConfirmed">{{ __('emergency.verify_register_checkbox') }}</label>
                                    @error('confirmed')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button class="btn btn-warning" type="submit"><i class="ti ti-user-plus me-1"></i>{{ __('emergency.register_and_confirm_btn') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var shouldOpen = @json($shouldOpenIdentityModal);
        if (!shouldOpen || !window.bootstrap) {
            return;
        }

        var modalEl = document.getElementById('confirmEmergencyIdentityModal');
        if (!modalEl) {
            return;
        }

        var tabSelector = @json($identityModalTabSelector);
        var tabEl = document.querySelector(tabSelector);
        if (tabEl && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tabEl).show();
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
</script>
@endpush
@endcan
@endif
@endsection
