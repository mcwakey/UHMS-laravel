@extends('layouts.app')
@section('title', __('admissions.admission_details') . ' — ' . $admission->admission_number)

@push('styles')
<style>
    .vitals-val { font-size: 1.1rem; font-weight: 700; }
    .vitals-label { font-size: 0.68rem; color: #6c757d; }
    .task-done { text-decoration: line-through; opacity: 0.6; }
    .ehr-item { border-left: 3px solid var(--bs-border-color); padding-left: 0.9rem; margin-bottom: 0.6rem; }
    .ehr-item.is-primary { border-left-color: #ffc107; }
    .adm-num { font-variant-numeric: tabular-nums; }

    /* ── Command bar ─────────────────────────────────────────── */
    .adm-cmd { display:flex; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.25rem; background:var(--bs-card-bg,#fff);
        border:1px solid var(--bs-border-color); border-radius:14px;
        box-shadow:0 1px 2px rgba(0,0,0,.04), 0 4px 16px rgba(0,0,0,.045); }
    .adm-cmd__avatar { width:52px; height:52px; border-radius:50%; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-weight:700; font-size:1.05rem;
        color:#fff; background:linear-gradient(135deg,#2E37A4,#4C56C5); box-shadow:0 4px 12px rgba(46,55,164,.28); overflow:hidden; }
    .adm-cmd__avatar img { width:100%; height:100%; object-fit:cover; }
    .adm-cmd__who { min-width:0; }
    .adm-cmd__name { font-size:1.2rem; font-weight:700; line-height:1.2; margin:0;
        display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .adm-cmd__meta { display:flex; gap:.9rem; flex-wrap:wrap; font-size:.78rem;
        color:var(--bs-secondary-color); margin-top:.3rem; }
    .adm-cmd__meta span { display:inline-flex; align-items:center; gap:.25rem; }
    .adm-cmd__divider { width:1px; align-self:stretch; background:var(--bs-border-color); margin:.15rem 0; }
    .adm-cmd__fact { display:flex; flex-direction:column; gap:.15rem; }
    .adm-cmd__lab { font-size:.63rem; text-transform:uppercase; letter-spacing:.06em; color:var(--bs-tertiary-color); }
    .adm-cmd__val { font-weight:600; font-size:.85rem; display:inline-flex; align-items:center; gap:.35rem; white-space:nowrap; }
    .adm-cmd__allergy { display:inline-flex; align-items:center; gap:.4rem; font-size:.75rem; font-weight:600;
        background:rgba(var(--bs-danger-rgb),.1); color:var(--bs-danger); padding:.35rem .6rem; border-radius:8px; }
    .adm-cmd__actions { margin-left:auto; display:flex; gap:.5rem; flex-wrap:wrap; }
    .adm-cmd__insurance { flex:0 0 100%; border-top:1px solid var(--bs-border-color); padding-top:.7rem;
        display:flex; align-items:center; gap:.65rem; color:var(--bs-secondary-color); font-size:.82rem; min-width:0; }
    .adm-cmd__insurance-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center;
        justify-content:center; flex-shrink:0; }
    .adm-cmd__insurance-main { min-width:0; display:flex; align-items:center; gap:.45rem; flex-wrap:wrap; }
    .adm-cmd__insurance-sub { color:var(--bs-secondary-color); }
    @media (max-width: 767px){ .adm-cmd__actions { margin-left:0; width:100%; } .adm-cmd__divider { display:none; } }

    /* ── Stat tiles ──────────────────────────────────────────── */
    .adm-tiles { display:grid; grid-template-columns:repeat(6,1fr); gap:.6rem; }
    @media (max-width: 1199px){ .adm-tiles { grid-template-columns:repeat(3,1fr); } }
    @media (max-width: 575px){ .adm-tiles { grid-template-columns:repeat(2,1fr); } }
    .adm-tile { text-align:left; width:100%; background:var(--bs-card-bg,#fff);
        border:1px solid var(--bs-border-color); border-radius:10px; padding:.7rem .8rem;
        position:relative; overflow:hidden; cursor:pointer; transition:transform .14s ease, border-color .14s ease; }
    .adm-tile:hover { transform:translateY(-2px); border-color:var(--bs-primary); }
    .adm-tile::before { content:""; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--bs-border-color); }
    .adm-tile--ok::before { background:var(--bs-success); }
    .adm-tile--warn::before { background:var(--bs-warning); }
    .adm-tile--crit::before { background:var(--bs-danger); }
    .adm-tile--info::before { background:var(--bs-info); }
    .adm-tile--primary::before { background:var(--bs-primary); }
    .adm-tile__lab { font-size:.62rem; text-transform:uppercase; letter-spacing:.05em;
        color:var(--bs-tertiary-color); display:flex; align-items:center; gap:.3rem; }
    .adm-tile__val { font-size:1.15rem; font-weight:700; margin-top:.25rem; line-height:1.1; }
    .adm-tile__sub { font-size:.68rem; color:var(--bs-secondary-color); margin-top:.1rem; }

    .nursing-thread { display:flex; flex-direction:column; gap:.75rem; }
    .nursing-message { display:flex; gap:.65rem; align-items:flex-start; max-width:84%; }
    .nursing-message--mine { align-self:flex-end; flex-direction:row-reverse; }
    .nursing-message__avatar { width:34px; height:34px; border-radius:50%; flex:0 0 34px; display:flex;
        align-items:center; justify-content:center; font-weight:700; font-size:.72rem;
        color:#fff; background:linear-gradient(135deg,#64748b,#94a3b8); }
    .nursing-message--mine .nursing-message__avatar { background:linear-gradient(135deg,#2E37A4,#4C56C5); }
    .nursing-message__bubble { border:1px solid var(--bs-border-color); border-radius:12px; border-top-left-radius:4px;
        padding:.65rem .8rem; background:#fff; min-width:220px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
    .nursing-message--mine .nursing-message__bubble { border-color:rgba(var(--bs-primary-rgb),.22);
        border-top-left-radius:12px; border-top-right-radius:4px; background:rgba(var(--bs-primary-rgb),.06); }
    .nursing-message__meta { display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        color:var(--bs-secondary-color); font-size:.72rem; margin-bottom:.35rem; }
    .nursing-message__body { color:var(--bs-body-color); white-space:pre-wrap; overflow-wrap:anywhere; }
    .nursing-message__actions { margin-top:.55rem; display:flex; justify-content:flex-end; gap:.35rem; }
    .vitals-trend-card { border:1px solid var(--bs-border-color); border-radius:8px; padding:.75rem;
        min-height:132px; background:var(--bs-card-bg,#fff); }
    .vitals-spark-wrap { height:72px; margin-top:.35rem; }
    .vitals-spark-wrap canvas { width:100% !important; height:100% !important; }
    .adm-invoice-table th,
    .adm-invoice-table td { padding:.85rem 1rem; }
    .adm-invoice-table thead th { font-size:.72rem; text-transform:uppercase; color:var(--bs-heading-color); }
    .adm-invoice-group-row th { background:#10c7b7 !important; color:#fff; border-color:#10c7b7;
        font-size:.74rem; text-transform:uppercase; letter-spacing:0; }
    .adm-invoice-group-badge { background:#e9ffff; color:#0f172a; font-size:.62rem; }
    .adm-invoice-preview .card-header { min-height:52px; }

    @media (prefers-reduced-motion: reduce){ .adm-tile:hover { transform:none; } }
    @media (max-width: 767px) {
        .nursing-message { max-width:100%; }
        .nursing-message__bubble { min-width:0; flex:1; }
    }
</style>
@endpush

@section('content')
@php
    $medicalRecord = $admission->visit->medicalRecord;
    $patient = $admission->patient;
    $initials = strtoupper(substr($patient->first_name,0,1).substr($patient->last_name,0,1));
    $visitInsurance = $admission->visit?->visitInsurance;
    $insuranceProvider = $visitInsurance?->insuranceProvider;
    $insuranceTier = $visitInsurance?->insuranceTier;
    $hasRealInsurance = $visitInsurance
        && $visitInsurance->is_active
        && $insuranceProvider
        && ! $insuranceProvider->is_default;
    $admissionNursingTasks = $admission->nursingTasks ?? collect();
    $openAdmissionNursingTasks = $admissionNursingTasks->filter(fn ($task) => $task->status?->isOpen());
    $completedAdmissionNursingTasks = $admissionNursingTasks->filter(fn ($task) => $task->status?->value === 'completed');
@endphp

{{-- ═══════════════════════════════════════════════════════════════════════
     COMMAND BAR — single source of truth for identity, status & location
═══════════════════════════════════════════════════════════════════════ --}}
<div class="adm-cmd mb-3">
    <div class="adm-cmd__avatar">
        @if(!empty($patient->avatar))
            <img src="{{ asset('storage/'.$patient->avatar) }}" alt="{{ $patient->full_name }}">
        @else
            {{ $initials }}
        @endif
    </div>
    <div class="adm-cmd__who">
        <h2 class="adm-cmd__name">
            {{ $patient->full_name }}
            <span class="badge badge-soft-{{ $admission->status->color() }} align-middle">{{ $admission->status->label() }}</span>
            <span class="badge badge-soft-secondary adm-num align-middle">{{ $patient->patient_number }}</span>
        </h2>
        <div class="adm-cmd__meta">
            <span><i class="ti ti-user fs-12"></i> {{ $patient->age }} {{ __('admissions.years_short') }} · {{ $patient->gender->label() }}</span>
            @if($patient->blood_group)
                <span><i class="ti ti-droplet fs-12 text-danger"></i> {{ $patient->blood_group?->value }}</span>
            @endif
            <span class="adm-num"><i class="ti ti-hash fs-12"></i> <x-patient-protected-field field="phone" :value="$patient->phone" /></span>
        </div>
    </div>

    <div class="adm-cmd__divider d-none d-md-block"></div>
    <div class="adm-cmd__fact">
        <span class="adm-cmd__lab">{{ __('admissions.location_card') }}</span>
        <span class="adm-cmd__val"><i class="ti ti-bed"></i> {{ $admission->bed->ward->name }} · {{ $admission->bed->bed_number }}</span>
        <span class="text-muted small">{{ $admission->bed->bed_type->label() }} · <i class="ti ti-clock"></i> {{ $admission->length_of_stay }} {{ __('admissions.days') }}</span>
    </div>

    <div class="adm-cmd__divider d-none d-md-block"></div>
    <div class="adm-cmd__fact">
        <span class="adm-cmd__lab">{{ __('admissions.admitted_on_label') }}</span>
        <!-- <span class="fs-12">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span> -->
        <span class="adm-cmd__val adm-num">{{ $admission->admission_date->format('d M Y') }}</span>
        <span class="text-muted small"><i class="ti ti-user"></i> {{ $admission->admittedBy->name ?? '—' }}</span>
    </div>

    <!-- <div class="adm-cmd__divider d-none d-md-block"></div>
    <div class="adm-cmd__fact">
        <span class="adm-cmd__lab">{{ __('admissions.admitted_by_label') }}</span>
        <span class="fs-12">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span>
    </div> -->

    @if($admission->expected_discharge_date)
    <div class="adm-cmd__divider d-none d-md-block"></div>
    <div class="adm-cmd__fact">
        <span class="adm-cmd__lab">{{ __('admissions.expected_discharge_label') }}</span>
        <!-- <span class="fs-12">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span> -->
        <span class="adm-cmd__val adm-num">{{ $admission->expected_discharge_date->format('d M Y') }}</span>
    </div>
    @endif

     @if($admission->actual_discharge_date)
    <div class="adm-cmd__divider d-none d-md-block"></div>
    <div class="adm-cmd__fact">
        <span class="adm-cmd__lab">{{ __('admissions.discharged_on_label') }}</span>
        <!-- <span class="fs-12">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span> -->
        <span class="adm-cmd__val adm-num">{{ $admission->actual_discharge_date->format('d M Y') }}</span>
        <span class="text-muted small"><i class="ti ti-user"></i> {{ $admission->dischargedBy->name ?? '—' }}</span>
    </div>

    <!-- <div class="adm-cmd__divider d-none d-md-block"></div>
    <div class="adm-cmd__fact">
        <span class="adm-cmd__lab">{{ __('admissions.discharged_by_label') }}</span>
        <span class="fs-12">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span>
    </div> -->

    @endif

    @if($patient->allergies)
        <span class="adm-cmd__allergy" title="{{ __('admissions.allergies') }}">
            <i class="ti ti-alert-triangle"></i> {{ Str::limit($patient->allergies, 60) }}
        </span>
    @endif

    <div class="adm-cmd__actions">
        <div class="text-md-end mt-2 mt-md-0">
        @if(($workspaceContext['workspaceKey'] ?? null) === 'inpatient' && app(\App\Services\Admissions\AdmissionExtensionService::class)->canExtend($admission))
            @can('admissions.readmit')
            <a href="{{ route('inpatient.readmissions.create', $admission) }}" class="btn btn-primary btn-md fs-13">
                <i class="ti ti-refresh me-1"></i>{{ __('inpatient.actions.readmit') }}
            </a>
            @endcan
        @endif
        @if($admission->status->value === 'admitted')
            @can('ward.discharge')
            <a href="{{ $workspaceRoutes->route('admin.admissions.discharge', $admission) }}" class="btn btn-warning btn-md fs-13">
                <i class="ti ti-logout me-1"></i>{{ __('admissions.discharge_patient') }}
            </a>
            @endcan
        @endif
        <a href="{{ $workspaceRoutes->route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>{{ __('admissions.back') }}
        </a>
        <div class="text-muted small mt-1">
            <span class="text-muted ms-2 small">{{ $admission->admission_number }}</span>
            <i class="ti ti-user-md me-1"></i> | Dr. {{ $admission->attendingDoctor->name ?? 'N/A' }}
        </div>

        <!-- <div class="adm-cmd__divider d-none d-md-block"></div> -->
        <!-- <div class="d-block d-md-inline-block text-muted fs-12">
            <span class="adm-cmd__lab"></span>
            <span class="adm-cmd__lab">{{ $admission->admission_number }}</span>
        </div> -->
    </div>
    </div>

    <div class="adm-cmd__insurance">
        @if($hasRealInsurance)
            <div class="adm-cmd__insurance-icon bg-success bg-opacity-10 text-success">
                <i class="ti ti-shield-check"></i>
            </div>
            <div class="adm-cmd__insurance-main">
                <span class="fw-semibold text-body text-truncate">{{ $insuranceProvider->name }}</span>
                @if($visitInsurance->is_expired)
                    <span class="badge bg-danger fs-11">Expired</span>
                @elseif($visitInsurance->is_valid)
                    <span class="badge bg-success fs-11">Active</span>
                @else
                    <span class="badge bg-secondary fs-11">Inactive</span>
                @endif
                @if($insuranceTier?->name)
                    <span class="adm-cmd__insurance-sub">{{ $insuranceTier->name }}</span>
                @endif
                @if($visitInsurance->membership_number)
                    <span class="adm-cmd__insurance-sub adm-num">#<x-patient-protected-field field="membership_number" :value="$visitInsurance->membership_number" /></span>
                @endif
            </div>
        @else
            <div class="adm-cmd__insurance-icon bg-secondary bg-opacity-10 text-secondary">
                <i class="ti ti-cash"></i>
            </div>
            <div class="adm-cmd__insurance-main">
                <span class="fw-semibold text-body">Cash &amp; Carry</span>
                <span class="adm-cmd__insurance-sub">No active insurance for this visit</span>
            </div>
        @endif
    </div>
</div>

@cannot('ward.manage')
{{-- @include('admissions.partials.simplified-activity-view') --}}
<!-- @if(false) -->
{{-- ═══════════════════════════════════════════════════════════════════════
     SIMPLIFIED WARD ACTIVITY VIEW — for Nurses and limited-access roles
═══════════════════════════════════════════════════════════════════════ --}}
<!-- <div class="row g-3">
    {{-- Medication administration --}}
    @can('admission.medication_board.view')
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>{{ __('admissions.medication_admin') }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    @can('admission.mar_chart.view')
                    <a href="{{ $workspaceRoutes->route('admin.admissions.mar-chart', $admission) }}" class="btn btn-sm btn-primary">{{ __('admissions.mar_chart_btn') }}</a>
                    @endcan
                    <a href="{{ $workspaceRoutes->route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">{{ __('admissions.dose_board_btn') }}</a>
                </div>
            </div>
            <div class="card-body">
                @include('admissions.partials.medication-counts', ['counts' => $medicationBoard['counts'] ?? [], 'layout' => 'inline'])
            </div>
        </div>
    </div>
    @endcan

    @can('admission.nursing.view')
    <div class="col-12">
        @include('admissions.partials.nursing-care-tab')
    </div>
    @endcan

    @can('admission.discharge.readiness.view')
    <div class="col-12">
        @include('admissions.partials.discharge-readiness-tab')
    </div>
    @endcan

    {{-- Vitals recording --}}
    @can('vitals.create')
    <div class="col-12">
        @include('admissions.partials.vitals-form')
    </div>
    @endcan

    {{-- Consultation preview --}}
    @if($medicalRecord)
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.consultation_records') }}</h5>
                <small class="text-muted">Dr. {{ $medicalRecord->doctor->name ?? 'N/A' }}</small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @if($medicalRecord->complaints->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-message-circle me-1"></i>{{ __('admissions.complaints') }}</h6>
                        @foreach($medicalRecord->complaints as $c)
                        <div class="ehr-item"><div class="fw-medium">{{ $c->description }}</div>@if($c->duration)<small class="text-muted">{{ $c->duration }}</small>@endif</div>
                        @endforeach
                    </div>
                    @endif
                    @if($medicalRecord->diagnoses->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.diagnoses') }}</h6>
                        @foreach($medicalRecord->diagnoses as $d)
                        <div class="ehr-item {{ $d->is_primary?'is-primary':'' }}">
                            @if($d->is_primary)<span class="badge bg-warning text-dark me-1" style="font-size:0.6rem">{{ __('admissions.primary_badge') }}</span>@endif
                            <span class="fw-medium">{{ $d->description }}</span>
                            @if($d->icdCodeEntry)<span class="badge bg-light text-dark ms-1 border">{{ $d->icdCodeEntry->code }}</span>@endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if($medicalRecord->treatments->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-first-aid-kit me-1"></i>{{ __('admissions.treatments') }}</h6>
                        @foreach($medicalRecord->treatments as $t)
                        <div class="ehr-item"><div class="fw-medium">{{ $t->description }}</div>@if($t->notes)<small class="text-muted">{{ $t->notes }}</small>@endif</div>
                        @endforeach
                    </div>
                    @endif
                    @if($medicalRecord->prescriptions->count() > 0)
                    <div class="col-md-6">
                        <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-pill me-1"></i>{{ __('admissions.active_prescriptions') }}</h6>
                        @foreach($medicalRecord->prescriptions as $rx)
                        @if($rx->items->count() > 0)
                        <div class="ehr-item mb-2">
                            <ul class="mb-0 ps-3 fs-12">
                                @foreach($rx->items as $item)
                                <li><strong>{{ $item->drug->name ?? '?' }}</strong> — {{ $item->dosage }}@if($item->frequency), {{ $item->frequency }}@endif @if($item->duration), {{ $item->duration }}@endif</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Doctor-ordered clinical tasks --}}
    @if($medicalRecord->tasks->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.doctor_clinical_tasks') }}</h5>
                <span class="badge bg-warning text-dark">{{ $medicalRecord->tasks->whereNotIn('status',['completed','cancelled'])->count() }} {{ __('admissions.pending_label') }}</span>
            </div>
            <div class="card-body p-0">
                @include('admissions.partials.consultation-tasks-table', ['tasks' => $medicalRecord->tasks])
            </div>
        </div>
    </div>
    @endif
    @endif
</div> -->
<!-- @endif -->
{{-- ═══════════════════════════════════════════════════════════════════════
     FULL MANAGEMENT VIEW — Doctors, Admin, Ward Manager, etc.
     Command bar (above) + stat tiles + full-width tabbed workspace
═══════════════════════════════════════════════════════════════════════ --}}
@php
    $tileBalance    = $dischargeReadiness['invoice_balance'] ?? null;
    $medsDue        = $medicationBoard['counts']['due_now'] ?? 0;
    $medsOverdue    = $medicationBoard['counts']['overdue'] ?? 0;
    $openNursing    = ($careOverview['open_tasks'] ?? collect())->count();
    $overdueNursing = ($careOverview['overdue_tasks'] ?? collect())->count();
    $latestVitals   = $careOverview['latest_vitals'] ?? null;
    $vitalsOverdue  = $careOverview['vitals_overdue'] ?? false;
    $drStatus       = $dischargeReadiness['overall_status'] ?? null;

    $medsClass  = $medsOverdue > 0 ? 'crit' : ($medsDue > 0 ? 'warn' : 'ok');
    $nurseClass = $overdueNursing > 0 ? 'crit' : ($openNursing > 0 ? 'warn' : 'ok');
    $balClass   = ($tileBalance ?? 0) > 0 ? 'crit' : 'ok';
@endphp

{{-- ── Stat tiles ─────────────────────────────────────────── --}}
<div class="adm-tiles mb-3">
    <button type="button" class="adm-tile adm-tile--{{ $balClass }}" onclick="showTab('tab-billing')">
        <span class="adm-tile__lab"><i class="ti ti-cash"></i>{{ __('admissions.billing_balance') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
        <span class="adm-tile__val adm-num">@if($tileBalance !== null)GH&#8373; {{ number_format($tileBalance,2) }}@else—@endif</span>
        <span class="adm-tile__sub">{{ ($tileBalance ?? 0) > 0 ? __('admissions.not_cleared') : __('admissions.cleared') }}</span>
    </div>
    </button>

    @can('admission.medication_board.view')
    <button type="button" class="adm-tile adm-tile--{{ $medsClass }}" onclick="showTab('tab-medications')">
        <span class="adm-tile__lab"><i class="ti ti-pill"></i>{{ __('admissions.medications_due') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
        <span class="adm-tile__val adm-num">{{ $medsDue }}</span>
        <span class="adm-tile__sub adm-num">{{ $medsOverdue }} {{ __('admissions.overdue_badge') }}</span>
    </div>
    </button>
    @endcan

    @can('admission.nursing.view')
    <button type="button" class="adm-tile adm-tile--{{ $nurseClass }}" onclick="showTab('tab-nursing')">
        <span class="adm-tile__lab"><i class="ti ti-clipboard-check"></i>{{ __('admissions.open_nursing_tasks') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
        <span class="adm-tile__val adm-num">{{ $openNursing }}</span>
        <span class="adm-tile__sub adm-num">{{ $overdueNursing }} {{ __('admissions.overdue_label') }}</span>
    </div>
    </button>
    @endcan

    <!-- <button type="button" class="adm-tile adm-tile--primary" onclick="showTab('tab-overview')">
        <span class="adm-tile__lab"><i class="ti ti-calendar-stats"></i>{{ __('admissions.stay_col') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
            <span class="adm-tile__val adm-num">{{ $admission->length_of_stay }}<span class="fs-13 text-muted"> {{ __('admissions.days') }}</span></span>
            <span class="adm-tile__sub">{{ $admission->bed->bed_type->label() }} · GH&#8373; {{ number_format($admission->bed->daily_rate,2) }}</span>
        </div>
    </button> -->

    <button type="button" class="adm-tile adm-tile--{{ $vitalsOverdue ? 'crit' : 'info' }}" onclick="showTab('tab-vitals')">
        <span class="adm-tile__lab"><i class="ti ti-heartbeat"></i>{{ __('admissions.last_vitals') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
        <span class="adm-tile__val">{{ $latestVitals?->recorded_at?->diffForHumans() ?? __('admissions.not_recorded') }}</span>
        <span class="adm-tile__sub adm-num">{{ $admission->visit->vitals->count() }} {{ __('admissions.tab_vitals') }}</span>
    </div>
    </button>

    @can('admission.discharge.readiness.view')
    <button type="button" class="adm-tile adm-tile--{{ $drStatus?->color() === 'success' ? 'ok' : ($drStatus?->color() === 'danger' ? 'crit' : ($drStatus?->color() === 'info' ? 'info' : 'warn')) }}" onclick="showTab('tab-discharge')">
        <span class="adm-tile__lab"><i class="ti ti-shield-check"></i>{{ __('admissions.tab_discharge') }}</span>
        <div class="d-flex align-items-center justify-content-between gap-2">
        <span class="adm-tile__val">{{ $drStatus?->label() ?? '—' }}</span>
        <span class="adm-tile__sub">{{ __('admissions.discharge_readiness') }}</span>
    </div>
    </button>
    @endcan
</div>

{{-- ── Tabs ───────────────────────────────────────────────── --}}
<ul class="nav nav-tabs mb-3" id="admTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button"><i class="ti ti-layout-dashboard me-1"></i>{{ __('admissions.tab_overview') }}</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-vitals" type="button"><i class="ti ti-heartbeat me-1"></i>{{ __('admissions.tab_vitals') }} <span class="badge bg-secondary ms-1">{{ $admission->visit->vitals->count() }}</span></button></li>
    <!-- <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-consult" type="button"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.tab_consultation') }}</button></li> -->
    @can('admission.nursing.view')
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-nursing" type="button">
            <i class="ti ti-report-medical me-1"></i>{{ __('admissions.tab_nursing') }}
            @if(($careOverview['overdue_tasks'] ?? collect())->isNotEmpty())<span class="badge bg-danger ms-1">{{ $careOverview['overdue_tasks']->count() }}</span>
            @elseif(($careOverview['open_tasks'] ?? collect())->isNotEmpty())<span class="badge bg-warning text-dark ms-1">{{ $careOverview['open_tasks']->count() }}</span>@endif
        </button>
    </li>
    @endcan
    @can('admission.medication_board.view')
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-medications" type="button"><i class="ti ti-pill me-1"></i>{{ __('admissions.tab_mar') }} @if(($medicationBoard['counts']['overdue'] ?? 0) > 0)<span class="badge bg-danger ms-1">{{ $medicationBoard['counts']['overdue'] }}</span>@elseif(($medicationBoard['counts']['due_now'] ?? 0) > 0)<span class="badge bg-info ms-1">{{ $medicationBoard['counts']['due_now'] }}</span>@endif</button></li>
    @endcan
    @can('admission.nursing.view')
    <!-- <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tasks" type="button">
            <i class="ti ti-clipboard-list me-1"></i>{{ __('admissions.tab_tasks') }}
            @php $pendingCount = $openAdmissionNursingTasks->count(); @endphp
            @if($pendingCount > 0)<span class="badge bg-warning text-dark ms-1">{{ $pendingCount }}</span>
            @else<span class="badge bg-secondary ms-1">{{ $admissionNursingTasks->count() }}</span>@endif
        </button>
    </li> -->
    @endcan
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-rounds" type="button"><i class="ti ti-notes me-1"></i>{{ __('admissions.tab_ward_rounds') }} <span class="badge bg-secondary ms-1">{{ $admission->wardRounds->count() }}</span></button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-billing" type="button"><i class="ti ti-file-invoice me-1"></i>{{ __('admissions.tab_billing') }} <span class="badge bg-secondary ms-1">{{ $admission->visit->visitServices->count() }}</span></button></li>
    @can('admission.discharge.readiness.view')
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-discharge" type="button">
            <i class="ti ti-shield-check me-1"></i>{{ __('admissions.tab_discharge') }}
            <span class="badge bg-{{ $dischargeReadiness['overall_status']->color() }} ms-1">{{ $dischargeReadiness['overall_status']->label() }}</span>
        </button>
    </li>
    @endcan
</ul>

<div class="tab-content">
    {{-- ── TAB: OVERVIEW (absorbs the old persistent left column, shown once) ── --}}
    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
        <div class="row g-3">
            <div class="col-xl-4">
                <!-- @include('partials.patient-card', [
                    'patient' => $admission->patient,
                    'visit'   => $admission->visit,
                    'compact' => true,
                ]) -->

                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-clipboard me-1"></i>{{ __('admissions.admission_details_card') }}</h5>
                        <span class="badge badge-soft-secondary adm-num">{{ $admission->length_of_stay }} {{ __('admissions.days') }}</span>
                    </div>
                    <div class="card-body">
                        <!-- <div class="table-responsive"><table class="table table-sm table-borderless mb-2"> -->
                            <!-- <tr><td class="text-muted" style="width:45%">{{ __('admissions.admission_no') }}</td><td class="fw-medium adm-num">{{ $admission->admission_number }}</td></tr> -->
                            <!-- <tr><td class="text-muted">{{ __('admissions.visit_no_label') }}</td><td><a href="{{ $workspaceRoutes->route('admin.visits.show', $admission->visit) }}" class="text-decoration-none">{{ $admission->visit->visit_number }}</a></td></tr> -->
                            <!-- <tr><td class="text-muted">{{ __('admissions.admitted_on_label') }}</td><td class="adm-num">{{ $admission->admission_date->format('d M Y, H:i') }}</td></tr> -->
                            <!-- <tr><td class="text-muted">{{ __('admissions.admitted_by_label') }}</td><td>{{ $admission->admittedBy->name ?? '—' }}</td></tr> -->
                            @if($admission->expected_discharge_date)
                            <!-- <tr><td class="text-muted">{{ __('admissions.expected_discharge_label') }}</td><td class="adm-num">{{ $admission->expected_discharge_date->format('d M Y') }}</td></tr> -->
                            @endif
                            @if($admission->actual_discharge_date)
                            <!-- <tr><td class="text-muted">{{ __('admissions.discharged_on_label') }}</td><td class="adm-num">{{ $admission->actual_discharge_date->format('d M Y, H:i') }}</td></tr> -->
                            <!-- <tr><td class="text-muted">{{ __('admissions.discharged_by_label') }}</td><td>{{ $admission->dischargedBy->name ?? '—' }}</td></tr> -->
                            @endif
                        <!-- </table></div> -->

                        {{-- Bed & location — single source of truth for ward/bed/rate --}}
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="min-w-0">
                                <div class="text-muted fs-12 mb-1"><i class="ti ti-bed me-1"></i>{{ __('admissions.location_card') }}</div>
                                <div class="fw-semibold">{{ $admission->bed->ward->name }} / {{ $admission->bed->bed_number }}</div>
                                <small class="text-muted adm-num">{{ $admission->bed->bed_type->label() }} · GH&#8373; {{ number_format($admission->bed->daily_rate,2) }}</small>
                            </div>
                            <span class="badge badge-soft-{{ $admission->bed->status->color() }} flex-shrink-0">{{ $admission->bed->status->label() }}</span>
                        </div>

                        @if($admission->bed->status_reason)
                            <div class="alert alert-light border py-2 fs-12 mb-0 mt-2">{{ $admission->bed->status_reason }}</div>
                        @endif
                        @if(in_array($admission->bed->status, [\App\Enums\BedStatus::CLEANING, \App\Enums\BedStatus::BLOCKED, \App\Enums\BedStatus::MAINTENANCE, \App\Enums\BedStatus::ISOLATION], true))
                            <div class="alert alert-warning py-2 fs-12 mb-0 mt-2">
                                <i class="ti ti-alert-triangle me-1"></i>{{ __('admissions.current_bed_warning') }}
                            </div>
                        @endif

                        @if($admission->admitting_diagnosis)
                        <div class="alert alert-light border py-2 mb-0 mt-2">
                            <div class="text-muted fs-12 mb-1"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.admitting_diagnosis_card') }}</div>
                            <div class="fs-13">{{ $admission->admitting_diagnosis }}</div>
                        </div>
                        @endif

                        @if($admission->status->value === 'admitted')
                            <div class="alert alert-info py-2 fs-12 mb-0 mt-2">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ config('admissions.bed_release_after_discharge', 'available') === 'cleaning'
                                    ? __('admissions.discharge_release_preview_cleaning')
                                    : __('admissions.discharge_release_preview_available') }}
                            </div>
                        @endif

                        @can('beds.transfer')
                            @if($admission->status->value === 'admitted')
                            <details class="mt-2">
                                <summary class="fs-13 fw-semibold text-primary" style="cursor:pointer"><i class="ti ti-arrows-transfer-up me-1"></i>{{ __('admissions.transfer_patient') }}</summary>
                                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.transfer-bed', $admission) }}" class="border rounded p-2 mt-2">
                                    @csrf
                                    <label class="form-label small fw-semibold">{{ __('admissions.transfer_to_bed') }}</label>
                                    <select name="bed_id" class="form-select form-select-sm mb-2" required>
                                        <option value="">{{ __('admissions.select_available_bed') }}</option>
                                        @foreach($availableTransferBeds as $bed)
                                            @if($bed->id !== $admission->bed_id)
                                                <option value="{{ $bed->id }}">{{ $bed->ward->name }} — {{ $bed->bed_number }} ({{ $bed->bed_type->label() }})</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <textarea name="reason" rows="2" class="form-control form-control-sm mb-2" placeholder="{{ __('admissions.transfer_reason_ph') }}" required></textarea>
                                    <button class="btn btn-sm btn-outline-primary w-100"><i class="ti ti-arrows-transfer-up me-1"></i>{{ __('admissions.transfer_patient') }}</button>
                                </form>
                            </details>
                            @endif
                        @endcan

                        @if($admission->locationHistories->isNotEmpty())
                        <details class="mt-2">
                            <summary class="fs-13 fw-semibold text-muted" style="cursor:pointer"><i class="ti ti-route me-1"></i>{{ __('admissions.location_history') }} ({{ $admission->locationHistories->count() }})</summary>
                            <div class="list-group list-group-flush mt-2">
                                @foreach($admission->locationHistories->take(6) as $history)
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <div class="fw-semibold fs-13">{{ $history->event_type->label() }}</div>
                                                <small class="text-muted">
                                                    @if($history->fromBed)
                                                        {{ $history->fromWard?->name }} / {{ $history->fromBed?->bed_number }} &rarr;
                                                    @endif
                                                    {{ $history->toBed ? ($history->toWard?->name . ' / ' . $history->toBed?->bed_number) : __('admissions.no_current_bed') }}
                                                </small>
                                                @if($history->reason)
                                                    <div class="fs-12 text-muted mt-1">{{ $history->reason }}</div>
                                                @endif
                                            </div>
                                            <small class="text-muted text-end">{{ $history->moved_at?->diffForHumans() }}<br>{{ $history->movedBy?->name }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                        @endif
                    </div>
                </div>

                @if($admission->status->value === 'discharged')
                <div class="card mb-3 border-success">
                    <div class="card-header bg-success bg-opacity-10">
                        <h5 class="card-title mb-0 text-success"><i class="ti ti-logout me-1"></i>{{ __('admissions.discharge_summary_card') }}</h5>
                    </div>
                    <div class="card-body">
                        @if($admission->discharge_summary)<p>{{ $admission->discharge_summary }}</p>@endif
                        @if($admission->discharge_instructions)<hr><h6>{{ __('admissions.instructions_label') }}</h6><p class="mb-0">{{ $admission->discharge_instructions }}</p>@endif
                    </div>
                </div>
                @endif
            </div>

            <div class="col-xl-8">
                {{-- Clinical snapshot — quick reference; full record in Consultation tab --}}
                @if($medicalRecord && ($medicalRecord->diagnoses->count() || $medicalRecord->complaints->count() || $medicalRecord->prescriptions->count()))
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-clipboard-heart me-1"></i>{{ __('admissions.clinical_snapshot') }}</h5>
                        <!-- <a href="#" onclick="event.preventDefault();showTab('tab-consult')" class="fs-12 text-decoration-none">{{ __('admissions.open_full_consultation') }} <i class="ti ti-arrow-right fs-11"></i></a> -->
                        <div class="alert alert-info">
                            <i class="ti ti-user-md me-1"></i>
                            {{ __('admissions.consultation_by') }} <strong>{{ $medicalRecord->doctor->name ?? 'N/A' }}</strong>
                            &mdash;
                            <a href="{{ $workspaceRoutes->route('admin.consultations.show', $admission->visit) }}" target="_blank" class="link-primary ms-1">
                                <i class="ti ti-external-link me-1"></i>{{ __('admissions.open_full_consultation') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            @if($medicalRecord->diagnoses->count() > 0)
                            <div class="col-md-6">
                                <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.diagnoses') }}</h6>
                                @foreach($medicalRecord->diagnoses as $d)
                                <div class="ehr-item {{ $d->is_primary?'is-primary':'' }}">
                                    @if($d->is_primary)<span class="badge bg-warning text-dark me-1" style="font-size:0.6rem">{{ __('admissions.primary_badge') }}</span>@endif
                                    <span class="fw-medium">{{ $d->description }}</span>
                                    @if($d->icdCodeEntry)<span class="badge bg-light text-dark ms-1 border">{{ $d->icdCodeEntry->code }}</span>@endif
                                </div>
                                @endforeach
                            </div>
                            @endif
                            @if($medicalRecord->complaints->count() > 0)
                            <div class="col-md-6">
                                <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-message-circle me-1"></i>{{ __('admissions.complaints') }}</h6>
                                @foreach($medicalRecord->complaints as $c)
                                <div class="ehr-item"><div class="fw-medium">{{ $c->description }}</div>@if($c->duration)<small class="text-muted">{{ $c->duration }}</small>@endif</div>
                                @endforeach
                            </div>
                            @endif
                            @if($medicalRecord->prescriptions->count() > 0)
                            <div class="col-12">
                                <h6 class="fw-bold fs-13 mb-2"><i class="ti ti-pill me-1"></i>{{ __('admissions.active_prescriptions') }}</h6>
                                @foreach($medicalRecord->prescriptions as $rx)
                                @if($rx->items->count() > 0)
                                <div class="ehr-item mb-2">
                                    <ul class="mb-0 ps-3 fs-12">
                                        @foreach($rx->items as $item)
                                        <li><strong>{{ $item->drug->name ?? '?' }}</strong> — {{ $item->dosage }}@if($item->frequency), {{ $item->frequency }}@endif @if($item->duration), {{ $item->duration }}@endif</li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Latest vitals snapshot --}}
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0"><i class="ti ti-heartbeat me-1"></i>{{ __('admissions.last_vitals') }}</h5>
                        <a href="#" onclick="event.preventDefault();showTab('tab-vitals')" class="fs-12 text-decoration-none">{{ __('admissions.tab_vitals') }} <i class="ti ti-arrow-right fs-11"></i></a>
                    </div>
                    <div class="card-body">
                        @php $lv = $admission->visit->vitals->sortByDesc('recorded_at')->first(); @endphp
                        @if($lv)
                        <div class="row g-2 text-center">
                            <div class="col-4 col-md-2"><div class="vitals-val">{{ $lv->blood_pressure ?? '—' }}</div><div class="vitals-label">{{ __('admissions.bp_col') }}</div></div>
                            <div class="col-4 col-md-2"><div class="vitals-val adm-num">{{ $lv->heart_rate ?? '—' }}</div><div class="vitals-label">{{ __('admissions.hr_col') }}</div></div>
                            <div class="col-4 col-md-2"><div class="vitals-val adm-num">{{ $lv->temperature ? $lv->temperature.'°' : '—' }}</div><div class="vitals-label">{{ __('admissions.temp_col') }}</div></div>
                            <div class="col-4 col-md-2"><div class="vitals-val adm-num">{{ $lv->spo2 ? $lv->spo2.'%' : '—' }}</div><div class="vitals-label">{{ __('admissions.spo2_col') }}</div></div>
                            <div class="col-4 col-md-2"><div class="vitals-val adm-num">{{ $lv->respiratory_rate ?? '—' }}</div><div class="vitals-label">{{ __('admissions.rr_col') }}</div></div>
                            <div class="col-4 col-md-2"><div class="vitals-val adm-num">{{ $lv->blood_sugar ?? '—' }}</div><div class="vitals-label">{{ __('admissions.sugar_col') }}</div></div>
                        </div>
                        <div class="text-muted fs-12 mt-2 text-center">{{ $lv->recorded_at->format('d M Y, H:i') }} · {{ $lv->recordedBy->name ?? '—' }}</div>
                        @else
                        <div class="text-center py-3 text-muted"><i class="ti ti-activity-off fs-1 d-block mb-2"></i>{{ __('admissions.no_vitals_yet') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('admission.nursing.view')
    <div class="tab-pane fade" id="tab-nursing" role="tabpanel">
        @include('admissions.partials.nursing-care-tab')
    </div>
    @endcan

    @can('admission.discharge.readiness.view')
    <div class="tab-pane fade" id="tab-discharge" role="tabpanel">
        @include('admissions.partials.discharge-readiness-tab')
    </div>
    @endcan

    @can('admission.medication_board.view')
    <div class="tab-pane fade" id="tab-medications" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-pill me-1"></i>{{ __('admissions.medication_admin_record') }}</h5>
                <div class="d-flex gap-2 flex-wrap">
                    @can('admission.mar_chart.view')
                    <a href="{{ $workspaceRoutes->route('admin.admissions.mar-chart', $admission) }}" class="btn btn-sm btn-primary">{{ __('admissions.mar_chart_btn') }}</a>
                    @endcan
                    <a href="{{ $workspaceRoutes->route('admin.admissions.medications.show', $admission) }}" class="btn btn-sm btn-outline-primary">{{ __('admissions.dose_board_btn') }}</a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    @include('admissions.partials.medication-counts', ['counts' => $medicationBoard['counts'] ?? [], 'layout' => 'grid'])
                </div>
                @if(($medicationBoard['orders'] ?? collect())->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="bg-light"><tr><th>{{ __('admissions.medication_col') }}</th><th>{{ __('admissions.progress_col') }}</th><th>{{ __('admissions.next_due_col') }}</th><th>{{ __('admissions.status_col') }}</th></tr></thead>
                            <tbody>
                                @foreach($medicationBoard['orders'] as $entry)
                                @php $order = $entry['order']; $progress = $entry['progress']; @endphp
                                <tr>
                                    <td><strong>{{ $order->display_name }}</strong><br><small class="text-muted">{{ $order->dose }} {{ $order->frequency_code ? '· '.$order->frequency_code : '' }}</small></td>
                                    <td>{{ $progress['given_doses'] }}/{{ $progress['total_doses'] }} {{ __('admissions.given_doses') }}</td>
                                    <td>{{ $progress['next_due_at'] ? $progress['next_due_at']->format('d M H:i') : '—' }}</td>
                                    <td><span class="badge badge-soft-secondary">{{ str_replace('_',' ', $order->status) }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">{{ __('admissions.no_medication_orders') }}</div>
                @endif
            </div>
        </div>
    </div>
    @endcan

    <!-- TAB: WARD ROUNDS -->
    <div class="tab-pane fade" id="tab-rounds" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>{{ __('admissions.ward_rounds_history') }} ({{ $admission->wardRounds->count() }})</h5>
                @if($admission->status->value === 'admitted')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#recordWardRoundModal">
                    <i class="ti ti-plus me-1"></i>{{ __('admissions.record_ward_round') }}
                </button>
                @endif
            </div>
            <div class="card-body">
                <div class="nursing-thread">
                    @include('admissions.partials.ward-rounds-thread', ['rounds' => $admission->wardRounds])
                </div>
                {{-- Legacy ward round table replaced by message thread.
                @if($admission->wardRounds->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light"><tr><th style="width:15%">{{ __('admissions.date_time_col') }}</th><th style="width:15%">{{ __('admissions.recorded_by_col') }}</th><th>{{ __('admissions.notes_col') }}</th><th>{{ __('admissions.instructions_col') }}</th></tr></thead>
                        <tbody>
                            @foreach($admission->wardRounds as $round)
                            <tr>
                                <td><div class="fw-medium">{{ $round->round_date->format('d M Y') }}</div><small class="text-muted">{{ $round->round_date->format('H:i') }}</small></td>
                                <td>{{ $round->recordedBy->name ?? '—' }}</td>
                                <td>{{ $round->notes }}</td>
                                <td>{{ $round->instructions ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>{{ __('admissions.no_ward_rounds_yet') }}</div>
                @endif
                --}}
            </div>
        </div>
    </div>

    @if($admission->status->value === 'admitted')
    <div class="modal fade" id="recordWardRoundModal" tabindex="-1" aria-labelledby="recordWardRoundModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.rounds.store', $admission) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="recordWardRoundModalLabel"><i class="ti ti-notes me-1"></i>{{ __('admissions.record_ward_round') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('admissions.round_notes_label') }} <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" required placeholder="{{ __('admissions.round_notes_ph') }}">{{ old('notes') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('admissions.instructions_field') }}</label>
                        <textarea name="instructions" class="form-control" rows="3" placeholder="{{ __('admissions.instructions_ph') }}">{{ old('instructions') }}</textarea>
                    </div>
                    <div>
                        <label class="form-label">{{ __('admissions.round_datetime_label') }}</label>
                        <input type="datetime-local" name="round_date" class="form-control" value="{{ old('round_date', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('admissions.save_round_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- TAB: PERIODIC VITALS -->
    <div class="tab-pane fade" id="tab-vitals" role="tabpanel">
        @include('admissions.partials.vitals-trend')
        @if(false)
        <div class="mb-3">
            @include('admissions.partials.vitals-form', ['icon' => 'ti-activity'])
        </div>
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-history me-1"></i>{{ __('admissions.vitals_history') }} ({{ $admission->visit->vitals->count() }})</h5></div>
            <div class="card-body p-0">
                @if($admission->visit->vitals->count() > 0)
                @php $vitalsChronological = $admission->visit->vitals->sortBy('recorded_at'); @endphp
                {{-- Chart --}}
                <div class="p-3 border-bottom">
                    <canvas id="vitalsChart" height="110"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light">
                            <tr><th>{{ __('admissions.date_time_col') }}</th><th>{{ __('admissions.bp_col') }}</th><th>{{ __('admissions.hr_col') }}</th><th>{{ __('admissions.temp_col') }}</th><th>{{ __('admissions.spo2_col') }}</th><th>{{ __('admissions.rr_col') }}</th><th>{{ __('admissions.sugar_col') }}</th><th>{{ __('admissions.by_col') }}</th></tr>
                        </thead>
                        <tbody>
                            @foreach($admission->visit->vitals->sortByDesc('recorded_at') as $vital)
                            <tr>
                                <td><div class="fw-medium">{{ $vital->recorded_at->format('d M Y') }}</div><small class="text-muted">{{ $vital->recorded_at->format('H:i') }}</small></td>
                                <td>{{ $vital->blood_pressure ?? '—' }}</td>
                                <td>{{ $vital->heart_rate ?? '—' }}</td>
                                <td>{{ $vital->temperature ? $vital->temperature.'°C' : '—' }}</td>
                                <td>{{ $vital->spo2 ? $vital->spo2.'%' : '—' }}</td>
                                <td>{{ $vital->respiratory_rate ?? '—' }}</td>
                                <td>{{ $vital->blood_sugar ?? '—' }}</td>
                                <td>{{ $vital->recordedBy->name ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-4 text-muted"><i class="ti ti-activity-off fs-1 d-block mb-2"></i>{{ __('admissions.no_vitals_yet') }}</div>
                @endif
            </div>
        </div>
        @endif
    </div>

    <!-- TAB: CONSULTATION RECORDS (read-only) -->
    <div class="tab-pane fade" id="tab-consult" role="tabpanel">
        <div class="alert alert-info py-2 mb-3">
            <i class="ti ti-user-md me-1"></i>
            {{ __('admissions.consultation_by') }} <strong>{{ $medicalRecord->doctor->name ?? 'N/A' }}</strong>
            &mdash;
            <a href="{{ $workspaceRoutes->route('admin.consultations.show', $admission->visit) }}" target="_blank" class="link-primary ms-1">
                <i class="ti ti-external-link me-1"></i>{{ __('admissions.open_full_consultation') }}
            </a>
        </div>
        @if($medicalRecord)
            <div class="row" id="summary-section">
                <div class="card">
                    <div class="card-header">
                        <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>{{ __('admissions.notes_summary_card') }}</h6>
                    </div>
                    <div class="card-body" id="consultation-summary-body">
                        @include('consultations.partials.summary-sections', ['consultationSummary' => $consultationSummary])
                    </div>
                </div>
            </div>

            @if($medicalRecord->complaints->count()===0 && $medicalRecord->diagnoses->count()===0 && $medicalRecord->treatments->count()===0 && $medicalRecord->prescriptions->count()===0)
                <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>{{ __('admissions.no_consultation_data') }}</div>
            @endif
        @endif
    </div>

    <!-- TAB: TASKS -->
    @can('admission.nursing.view')
    <div class="tab-pane fade" id="tab-tasks" role="tabpanel">
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-clipboard-list me-1"></i>{{ __('admissions.nursing_tasks') }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark">{{ $openAdmissionNursingTasks->count() }} {{ __('admissions.pending_label') }}</span>
                    <span class="badge bg-success">{{ $completedAdmissionNursingTasks->count() }} {{ __('admissions.done_label') }}</span>
                    @can('admission.nursing.tasks.create')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNursingTaskModal">
                        <i class="ti ti-plus me-1"></i>{{ __('admissions.add_nursing_task') }}
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="nursing-thread">
                    @include('admissions.partials.nursing-tasks-thread', ['tasks' => $admissionNursingTasks])
                </div>
            </div>
        </div>

        @if($medicalRecord && $medicalRecord->tasks->count() > 0)
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-stethoscope me-1"></i>{{ __('admissions.doctor_clinical_tasks') }}</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-warning text-dark">{{ $medicalRecord->tasks->whereNotIn('status',['completed','cancelled'])->count() }} {{ __('admissions.pending_label') }}</span>
                    <span class="badge bg-success">{{ $medicalRecord->tasks->where('status','completed')->count() }} {{ __('admissions.done_label') }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                @include('admissions.partials.consultation-tasks-table', ['tasks' => $medicalRecord->tasks, 'detailed' => true])
            </div>
        </div>
        @endif
    </div>
    @endcan

    <!-- TAB: BILLING -->
    <div class="tab-pane fade" id="tab-billing" role="tabpanel">
        @include('admissions.partials.billing-tab')
        @if(false)
        @if($admission->status->value === 'admitted')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-plus me-1"></i>{{ __('admissions.add_service_charge') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.services.store', $admission) }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('admissions.service_field') }} <span class="text-danger">*</span></label>
                            <select name="service_catalog_id" class="form-select form-select-sm select2" required>
                                <option value="">{{ __('admissions.select_service') }}</option>
                                @foreach($services as $svc)
                                <option value="{{ $svc->id }}" {{ old('service_catalog_id')==$svc->id?'selected':'' }}>
                                    {{ $svc->name }}@if($svc->base_price) (GH&#8373; {{ number_format($svc->base_price,2) }})@endif
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('admissions.qty_field') }}</label>
                            <input type="number" name="quantity" class="form-control form-control-sm" value="{{ old('quantity',1) }}" min="1" max="99">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('admissions.notes_optional_field') }}</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('admissions.optional_ph') }}" value="{{ old('notes') }}">
                        </div>
                        <div class="col-md-1 text-end">
                            <button aria-label="Add" title="Add" type="submit" class="btn btn-primary btn-sm w-100"><i class="ti ti-plus"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif
@php
            $invoice = $admission->visit->latestInvoice;
            $invoiceItems = $invoice ? $invoice->items : collect();
            $svcItems = $admission->visit->visitServices;
            $totalCharged = $svcItems->sum('total_price') + $invoiceItems->sum('total_price');
            $totalPatient = $svcItems->sum('patient_payable') + ($invoice ? ($invoice->total_amount - $invoice->nhis_amount) : 0);
            $totalInsurance = $svcItems->sum('insurance_covered') + ($invoice ? $invoice->nhis_amount : 0);
        @endphp

        {{-- Admission Invoice Items (Admission Fee, Bed Fee, Consumable) --}}
        @if($invoiceItems->count() > 0)
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-bed me-1"></i>{{ __('admissions.admission_charges') }}
                    @if($invoice)<a href="{{ $workspaceRoutes->route('admin.billing.invoices.show', $invoice) }}" class="btn btn-outline-primary btn-xs ms-2 fs-11"><i class="ti ti-file-invoice me-1"></i>{{ $invoice->invoice_number }}</a>@endif
                </h5>
                <span class="fw-bold text-primary">GH&#8373; {{ number_format($invoiceItems->sum('total_price'),2) }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light"><tr><th>{{ __('admissions.description_col') }}</th><th class="text-center">{{ __('admissions.qty_col') }}</th><th class="text-end">{{ __('admissions.unit_price_col') }}</th><th class="text-end">{{ __('admissions.total_col') }}</th><th class="text-end">{{ __('admissions.insurance_col') }}</th><th class="text-end">{{ __('admissions.patient_pays_col') }}</th></tr></thead>
                        <tbody>
                            @foreach($invoiceItems as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">GH&#8373; {{ number_format($item->unit_price,2) }}</td>
                                <td class="text-end fw-medium">GH&#8373; {{ number_format($item->total_price,2) }}</td>
                                <td class="text-end text-info">{{ $item->nhis_approved_amount > 0 ? 'GH&#8373; '.number_format($item->nhis_approved_amount,2) : '—' }}</td>
                                <td class="text-end text-success fw-medium">GH&#8373; {{ number_format($item->total_price - $item->nhis_approved_amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Additional Service Charges --}}
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-receipt me-1"></i>{{ __('admissions.additional_service_charges') }} ({{ $svcItems->count() }})</h5>
                <span class="fw-bold text-success">GH&#8373; {{ number_format($svcItems->sum('total_price'),2) }}</span>
            </div>
            <div class="card-body p-0">
                @if($svcItems->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light"><tr><th>{{ __('admissions.service_col') }}</th><th class="text-center">{{ __('admissions.qty_col') }}</th><th class="text-end">{{ __('admissions.unit_price_col') }}</th><th class="text-end">{{ __('admissions.total_col') }}</th><th class="text-end">{{ __('admissions.insurance_col') }}</th><th class="text-end">{{ __('admissions.patient_pays_col') }}</th></tr></thead>
                        <tbody>
                            @foreach($svcItems as $svc)
                            <tr>
                                <td>{{ $svc->serviceCatalog->name ?? '—' }}</td>
                                <td class="text-center">{{ $svc->quantity }}</td>
                                <td class="text-end">GH&#8373; {{ number_format($svc->unit_price,2) }}</td>
                                <td class="text-end fw-medium">GH&#8373; {{ number_format($svc->total_price,2) }}</td>
                                <td class="text-end text-info">{{ $svc->insurance_covered>0?'GH&#8373; '.number_format($svc->insurance_covered,2):'—' }}</td>
                                <td class="text-end text-success fw-medium">GH&#8373; {{ number_format($svc->patient_payable,2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr><td colspan="3" class="text-end">{{ __('admissions.subtotal_label') }}:</td><td class="text-end">GH&#8373; {{ number_format($svcItems->sum('total_price'),2) }}</td><td class="text-end text-info">GH&#8373; {{ number_format($svcItems->sum('insurance_covered'),2) }}</td><td class="text-end text-success">GH&#8373; {{ number_format($svcItems->sum('patient_payable'),2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="text-center py-3 text-muted"><i class="ti ti-receipt-off fs-1 d-block mb-2"></i>{{ __('admissions.no_additional_charges') }}</div>
                @endif
            </div>
        </div>
        @endif
    </div>

</div>{{-- /tab-content --}}
@endcannot
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function(){
    var hash = window.location.hash;
    if(hash){
        var tab = document.querySelector('[data-bs-target="'+hash+'"]');
        if(tab) new bootstrap.Tab(tab).show();
    }
})();
function showTab(id){
    var tab = document.querySelector('[data-bs-target="#'+id+'"]');
    if(tab) new bootstrap.Tab(tab).show();
    var el = document.getElementById(id);
    if(el) setTimeout(function(){ el.scrollIntoView({behavior:'smooth', block:'start'}); }, 100);
}

@if($admission->visit->vitals->count() > 0)
@php
$admissionVitalsSparkData = $admission->visit->vitals->sortBy('recorded_at')->values()->map(function($v) {
    return [
        'label' => $v->recorded_at->format('d M H:i'),
        'systolic' => $v->blood_pressure_systolic,
        'diastolic' => $v->blood_pressure_diastolic,
        'heart_rate' => $v->heart_rate,
        'respiratory_rate' => $v->respiratory_rate,
        'temperature' => $v->temperature,
        'spo2' => $v->spo2,
    ];
})->values()->toArray();
@endphp
(function(){
    var vitals = @json($admissionVitalsSparkData);
    if (!window.Chart || !vitals.length) return;

    var labels = vitals.map(function(v){ return v.label; });
    var series = {
        systolic: vitals.map(function(v){ return v.systolic; }),
        diastolic: vitals.map(function(v){ return v.diastolic; }),
        heartRate: vitals.map(function(v){ return v.heart_rate; }),
        rr: vitals.map(function(v){ return v.respiratory_rate; }),
        temp: vitals.map(function(v){ return v.temperature; }),
        spo2: vitals.map(function(v){ return v.spo2; })
    };

    function hasData(datasets) {
        return datasets.some(function(dataset) {
            return dataset.data.some(function(value) { return value !== null && value !== undefined; });
        });
    }

    function rangeStatus(value, range) {
        if (value === null || value === undefined || !range) return 'normal';
        var numeric = Number(value);
        if (Number.isNaN(numeric)) return 'normal';
        if (numeric < range.min) return 'low';
        if (numeric > range.max) return 'high';
        return 'normal';
    }

    function abnormalPointColor(status, fallback) {
        if (status === 'low') return '#0d6efd';
        if (status === 'high') return '#dc3545';
        return fallback;
    }

    function spark(id, datasets) {
        var el = document.getElementById(id);
        if (!el || !hasData(datasets)) return;
        new Chart(el, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets.map(function(dataset) {
                    return {
                        label: dataset.label,
                        data: dataset.data,
                        borderColor: dataset.color,
                        backgroundColor: dataset.color,
                        borderWidth: 2,
                        tension: .32,
                        spanGaps: true,
                        fill: false,
                        pointStyle: function(ctx) {
                            return rangeStatus(ctx.parsed.y, dataset.normalRange) === 'normal' ? 'circle' : 'triangle';
                        },
                        pointRotation: function(ctx) {
                            return rangeStatus(ctx.parsed.y, dataset.normalRange) === 'low' ? 180 : 0;
                        },
                        pointRadius: function(ctx) {
                            return rangeStatus(ctx.parsed.y, dataset.normalRange) === 'normal' ? 2.5 : 4.5;
                        },
                        pointHoverRadius: function(ctx) {
                            return rangeStatus(ctx.parsed.y, dataset.normalRange) === 'normal' ? 4 : 6;
                        },
                        pointBackgroundColor: function(ctx) {
                            return abnormalPointColor(rangeStatus(ctx.parsed.y, dataset.normalRange), dataset.color);
                        },
                        pointBorderColor: function(ctx) {
                            return abnormalPointColor(rangeStatus(ctx.parsed.y, dataset.normalRange), dataset.color);
                        },
                        pointBorderWidth: function(ctx) {
                            return rangeStatus(ctx.parsed.y, dataset.normalRange) === 'normal' ? 1 : 2;
                        }
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'nearest', intersect: false },
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { display: false },
                    y: {
                        beginAtZero: false,
                        grace: '14%',
                        ticks: { maxTicksLimit: 3, font: { size: 9 }, color: '#9ca3af' },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    spark('vitalsSparkBp', [
        { label: 'Systolic', data: series.systolic, color: '#6f42c1', normalRange: { min: 90, max: 120 } },
        { label: 'Diastolic', data: series.diastolic, color: '#20c997', normalRange: { min: 60, max: 80 } }
    ]);
    spark('vitalsSparkHr', [{ label: 'Heart Rate', data: series.heartRate, color: '#dc3545', normalRange: { min: 60, max: 100 } }]);
    spark('vitalsSparkRr', [{ label: 'Respiratory Rate', data: series.rr, color: '#0d6efd', normalRange: { min: 12, max: 20 } }]);
    spark('vitalsSparkSpo2', [{ label: 'SpO2', data: series.spo2, color: '#198754', normalRange: { min: 95, max: 100 } }]);
    spark('vitalsSparkTemp', [{ label: 'Temperature', data: series.temp, color: '#fd7e14', normalRange: { min: 36.1, max: 37.2 } }]);
})();
@endif

@if(false && $admission->visit->vitals->count() > 1)
@php
$vitalsChartData = $admission->visit->vitals->sortBy('recorded_at')->values()->map(function($v) {
    return [
        'label'    => $v->recorded_at->format('d M H:i'),
        'systolic' => $v->blood_pressure_systolic,
        'diastolic'=> $v->blood_pressure_diastolic,
        'hr'       => $v->heart_rate,
        'temp'     => $v->temperature,
        'spo2'     => $v->spo2,
    ];
})->values()->toArray();
@endphp
(function(){
    var vitals = @json($vitalsChartData);

    var labels    = vitals.map(function(v){ return v.label; });
    var systolic  = vitals.map(function(v){ return v.systolic; });
    var diastolic = vitals.map(function(v){ return v.diastolic; });
    var hr        = vitals.map(function(v){ return v.hr; });
    var temp      = vitals.map(function(v){ return v.temp ? v.temp * 10 : null; }); // scale for visibility
    var spo2      = vitals.map(function(v){ return v.spo2; });

    new Chart(document.getElementById('vitalsChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'BP Systolic', data: systolic, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'BP Diastolic', data: diastolic, borderColor: '#fd7e14', backgroundColor: 'rgba(253,126,20,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'Heart Rate', data: hr, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'SpO₂ (%)', data: spo2, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.08)', tension: 0.4, pointRadius: 4, fill: false },
                { label: 'Temp (×10 °C)', data: temp, borderColor: '#6f42c1', backgroundColor: 'rgba(111,66,193,0.08)', tension: 0.4, pointRadius: 4, borderDash: [5,5], fill: false },
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            if(ctx.dataset.label === 'Temp (×10 °C)' && ctx.raw != null)
                                return 'Temp: ' + (ctx.raw / 10).toFixed(1) + ' °C';
                            return ctx.dataset.label + ': ' + (ctx.raw ?? '—');
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.05)' } },
                x: { grid: { display: false }, ticks: { font: { size: 10 } } }
            }
        }
    });
})();
@endif
</script>
@endpush
