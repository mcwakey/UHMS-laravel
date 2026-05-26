@extends('layouts.app')
@section('title', 'Consultation - ' . $visit->visit_number)

@push('styles')
<style>
    .consultation-sidebar .nav-link { padding: 0.4rem 0.75rem; border-radius: 0.4rem; color: #495057; font-size: 0.82rem; }
    .consultation-sidebar .nav-link.active { background-color: #e8f0fe; color: #1a73e8; font-weight: 600; }
    .consultation-sidebar .nav-link i { width: 18px; }
    .consultation-sidebar .badge { font-size: 0.6rem; }
    .ehr-item { border-left: 3px solid #dee2e6; padding-left: 1rem; margin-bottom: 0.75rem; }
    .ehr-item:hover { border-left-color: #0d6efd; }
    .severity-mild { border-left-color: #ffc107; }
    .severity-moderate { border-left-color: #fd7e14; }
    .severity-severe { border-left-color: #dc3545; }
    .ehr-item.is-primary { border-left-color: #ffc107 !important; }
    .vitals-static { background: linear-gradient(135deg, #f8f9ff, #eef2ff); border-left: 4px solid #6366f1 !important; }
    .triage-badge { font-size: 0.8rem; font-weight: 700; padding: 0.35rem 0.7rem; }
    .prev-visit-card { transition: border-color 0.15s; cursor: default; }
    .prev-visit-card:hover { border-color: #0d6efd !important; }
    .btn-xs { padding: 0.15rem 0.35rem; font-size: 0.72rem; line-height: 1.4; }
    .vitals-val { font-size: 1.05rem; font-weight: 700; }
    .vitals-label { font-size: 0.68rem; color: #6c757d; }
    .diagnosis-primary-badge { font-size: 0.6rem; vertical-align: middle; }
    .session-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 0.75rem; }
    .session-summary-item { border: 1px solid #e9ecef; border-radius: 0.5rem; padding: 0.7rem; background: #fff; }
    .session-route-row { border-left: 4px solid #dee2e6; }
    .session-route-row.is-current { border-left-color: #0d6efd; background: #f8fbff; }
    .session-route-row.is-completed { border-left-color: #198754; }
    .session-route-row.is-cancelled { border-left-color: #dc3545; opacity: 0.82; }
    .session-timeline { display: flex; flex-wrap: wrap; gap: 0.35rem; }
    .session-timeline .badge { font-size: 0.66rem; font-weight: 500; }
    /* ── Fixed-bottom sessions drawer (left/width matched to col-lg-10 by JS) ── */
    #sessionsDrawer { position: fixed; bottom: 0; left: 0; right: 0; z-index: 1040; background: #fff; border-top: 2px solid #0d6efd; box-shadow: 0 -4px 18px rgba(0,0,0,.12); max-height: 60vh; display: flex; flex-direction: column; transition: transform .25s ease; }
    #sessionsDrawer.is-collapsed { transform: translateY(calc(100% - 42px)); }
    #sessionsDrawerHandle { cursor: pointer; user-select: none; padding: .45rem 1rem; background: #0d6efd; color: #fff; display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
    #sessionsDrawerHandle .ti-chevron-up { transition: transform .25s; }
    #sessionsDrawer.is-collapsed #sessionsDrawerHandle .ti-chevron-up { transform: rotate(180deg); }
    #sessionsDrawerBody { overflow-y: auto; flex: 1; }
    body.has-sessions-drawer { padding-bottom: 46px; }
</style>
@endpush

@section('content')

{{-- ============================================================ --}}
{{-- PATIENT HEADER BAR --}}
{{-- ============================================================ --}}
@include('partials.patient-visit-header', ['visit' => $visit, 'showAlerts' => true])

@php
    $routeBadgeClasses = [
        \App\Models\VisitConsultationRoute::STATUS_ACTIVE => 'success',
        \App\Models\VisitConsultationRoute::STATUS_PENDING => 'warning',
        \App\Models\VisitConsultationRoute::STATUS_PAUSED => 'info',
        \App\Models\VisitConsultationRoute::STATUS_COMPLETED => 'secondary',
        \App\Models\VisitConsultationRoute::STATUS_CANCELLED => 'danger',
    ];
    $routeBadge = fn (?string $status) => $routeBadgeClasses[$status ?? ''] ?? 'light text-dark';
    $insuranceLabel = $visit->visitInsurance?->insuranceProvider?->name ?? 'Cash & Carry';
    $routeServiceNames = function ($route) {
        if (! $route) {
            return collect();
        }

        $names = $route->routeServices
            ->map(fn ($routeService) => $routeService->service?->name)
            ->filter()
            ->values();

        if ($names->isEmpty() && $route->service) {
            $names = collect([$route->service->name]);
        }

        return $names;
    };
    $selectedRouteServiceNames = $routeServiceNames($selectedRoute);
    $entryAuthor = function ($entry) {
        $user = $entry?->creator ?? $entry?->createdBy ?? $entry?->doctor ?? null;
        return $user?->full_name ? 'Dr. '.$user->full_name : 'Unknown user';
    };
    $entryMeta = function ($entry) use ($entryAuthor) {
        $bits = ['Entered by: '.$entryAuthor($entry)];
        if ($entry?->created_at) {
            $bits[] = 'Created: '.$entry->created_at->format('d M Y, h:i A');
        }
        if ($entry?->sourcePattern) {
            $bits[] = 'Source Pattern: '.$entry->sourcePattern->name;
        }
        return implode(' · ', $bits);
    };
    $canDeleteEntry = fn ($entry) => auth()->user() && $entryPermissions->canDelete(auth()->user(), $entry);
    $contributors = $selectedRoute?->contributors?->map(fn ($contributor) => $contributor->user?->full_name)->filter()->unique()->values() ?? collect();
@endphp

{{-- ============================================================ --}}
{{-- CURRENT SESSION HEADER --}}
{{-- ============================================================ --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-primary"></i>Current Session</h6>
            {{-- <small class="text-muted">Visit {{ $visit->visit_number }} · {{ $visit->patient->full_name }}</small> --}}
            <div class="fw-semibold ms-2">{{ $selectedRoute?->department?->name ?? 'No active session' }}</div>
        </div>
            {{-- <div class="session-summary-item">
                <div class="text-muted small">Department</div>
                <div class="fw-semibold">{{ $selectedRoute?->department?->name ?? 'No active session' }}</div>
            </div> --}}
            <div>
                <div class="text-muted small">Linked Services</div>
                <div class="fw-semibold">{{ $selectedRouteServiceNames->implode(', ') ?: '-' }}</div>
            </div>
            <div>
                <div class="text-muted small">Contributors</div>
                {{-- <div class="fw-semibold">{{ $selectedRoute?->doctor ? 'Dr. '.$selectedRoute->doctor->full_name : 'Unassigned' }}</div> --}}
                <div class="small text-muted">{{ $contributors->isNotEmpty() ? $contributors->implode(', ') : 'No contributors yet' }}</div>
            </div>

        <div class="d-flex flex-wrap gap-2">
            {{-- <span class="badge bg-{{ $visit->status->color() }}">{{ $visit->status->label() }}</span>
            @if($selectedRoute)
                <span class="badge bg-{{ $routeBadge($selectedRoute->status) }}">{{ $selectedRoute->status }}</span>
            @endif --}}
            @if($selectedRoute)
                @if(in_array($selectedRoute->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-player-play me-1"></i>Start Session</button>
                    </form>
                    @endcan
                @endif
                @if($selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Complete this consultation session?')">
                            <i class="ti ti-check me-1"></i>Complete Current Session
                        </button>
                    </form>
                    @endcan
                @endif
            @endif
        </div>
    </div>
    {{-- <div class="card-body"> --}}
        {{-- @if($routeSelectorRequired)
            <div class="alert alert-warning py-2 mb-3">
                <strong>Select a consultation session.</strong>
                This visit has multiple consultation routes and none is active yet.
            </div>
        @endif --}}
        {{-- <div class="session-summary-grid">
            <div class="session-summary-item">
                <div class="text-muted small">Doctor</div>
                <div class="fw-semibold">{{ $selectedRoute?->doctor ? 'Dr. ' . $selectedRoute->doctor->full_name : 'Unassigned' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Medical Record</div>
                <div class="fw-semibold">{{ $record ? 'MR-' . str_pad((string) $record->id, 5, '0', STR_PAD_LEFT) : '-' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Visit Type</div>
                <div class="fw-semibold">{{ $visit->visit_type?->label() ?? '-' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Insurance</div>
                <div class="fw-semibold">{{ $insuranceLabel }}</div>
            </div>
        </div> --}}
        {{-- <div class="d-flex flex-wrap gap-2 mt-3">
            @if($selectedRoute)
                @if(in_array($selectedRoute->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-player-play me-1"></i>Start Session</button>
                    </form>
                    @endcan
                @endif
                @if($selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                    @can('consultations.create')
                    <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $selectedRoute]) }}">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Complete this consultation session?')">
                            <i class="ti ti-check me-1"></i>Complete Current Session
                        </button>
                    </form>
                    @endcan
                @endif
            @endif
            @can('consultations.create')
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#sendSessionModal">
                <i class="ti ti-transfer me-1"></i>Send to Another Session
            </button>
            @endcan
        </div> --}}
    {{-- </div> --}}
</div>

{{-- ============================================================ --}}
{{-- VITALS — STATIC SECTION (always visible) --}}
{{-- ============================================================ --}}
<div class="card mb-3 vitals-static">
    <div class="card-body py-2">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 small"><i class="ti ti-heartbeat me-1 text-danger"></i>Latest Vitals</h6>
            @php
                $triageScore = $visit->triage_score;
                if (!$triageScore && $vitals->count() > 0) {
                    $lv = $vitals->first();
                    $triageScore = \App\Enums\TriageScore::compute([
                        'temperature'      => $lv->temperature,
                        'heart_rate'       => $lv->heart_rate,
                        'respiratory_rate' => $lv->respiratory_rate,
                        'spo2'             => $lv->spo2,
                    ]);
                }
            @endphp
            @if($triageScore)
                <span class="badge bg-{{ $triageScore->color() }} triage-badge">
                    <i class="ti {{ $triageScore->icon() }} me-1"></i>{{ $triageScore->label() }}
                </span>
            @else
                <span class="badge bg-secondary triage-badge"><i class="ti ti-help me-1"></i>Triage N/A</span>
            @endif
        </div>

        @if($vitals->count() > 0)
            @php $lv = $vitals->first(); @endphp
            <div class="row g-2">
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">Blood Pressure</div>
                    <div class="vitals-val">{{ $lv->blood_pressure ?? '—' }}</div>
                    <div class="vitals-label">mmHg</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">Heart Rate</div>
                    <div class="vitals-val">{{ $lv->heart_rate ?? '—' }}</div>
                    <div class="vitals-label">bpm</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">Temperature</div>
                    <div class="vitals-val">{{ $lv->temperature ?? '—' }}</div>
                    <div class="vitals-label">°C</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">SpO₂</div>
                    <div class="vitals-val">{{ $lv->spo2 ?? '—' }}</div>
                    <div class="vitals-label">%</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">Resp. Rate</div>
                    <div class="vitals-val">{{ $lv->respiratory_rate ?? '—' }}</div>
                    <div class="vitals-label">/min</div>
                </div>
                <div class="col-6 col-sm-4 col-md-2 text-center">
                    <div class="vitals-label">BMI</div>
                    <div class="vitals-val {{ $lv->bmi ? ($lv->bmi < 18.5 ? 'text-warning' : ($lv->bmi < 25 ? 'text-success' : ($lv->bmi < 30 ? 'text-warning' : 'text-danger'))) : '' }}">
                        {{ $lv->bmi ?? '—' }}
                    </div>
                    <div class="vitals-label">
                        @if($lv->bmi)
                            @if($lv->bmi < 18.5) Underweight
                            @elseif($lv->bmi < 25) Normal
                            @elseif($lv->bmi < 30) Overweight
                            @else Obese @endif
                        @else kg/m² @endif
                    </div>
                </div>
            </div>
            <div class="text-muted mt-1" style="font-size:0.7rem">
                <i class="ti ti-clock me-1"></i>{{ $lv->recorded_at->diffForHumans() }} by {{ $lv->recordedBy?->full_name ?? 'Unknown' }}
                @if($vitals->count() > 1)
                    &middot; <span class="text-primary">{{ $vitals->count() - 1 }} earlier reading(s)</span>
                @endif
            </div>
        @else
            <div class="text-muted small py-1">
                <i class="ti ti-heartbeat me-1"></i>No vitals recorded for this visit yet.
                @can('vitals.create')
                    <a href="{{ route('admin.vitals.create', ['visit_id' => $visit->id]) }}" class="ms-2">Record now</a>
                @endcan
            </div>
        @endif
    </div>
</div>

{{-- ============================================================ --}}
{{-- CONSULTATION GATING — Start Consultation banner --}}
{{-- ============================================================ --}}
@php
    $canEdit = $visit->status === \App\Enums\VisitStatus::CONSULTING
        && $selectedRoute
        && $selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE;
    $needsStart = $selectedRoute
        && in_array($selectedRoute->status, [
            \App\Models\VisitConsultationRoute::STATUS_PENDING,
            \App\Models\VisitConsultationRoute::STATUS_PAUSED,
        ], true);
@endphp
@if($needsStart)
<div class="card border-warning mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-1 text-warning"><i class="ti ti-player-play me-1"></i>Consultation Not Started</h6>
            <small class="text-muted">Click <strong>Start Consultation</strong> to begin entering clinical information.</small>
        </div>
        @can('consultations.create')
        <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
            @csrf
            <button type="submit" class="btn btn-warning"><i class="ti ti-player-play me-1"></i>Start Consultation</button>
        </form>
        @endcan
    </div>
</div>
@elseif($canEdit)
<div class="alert alert-success py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-pencil me-2"></i><strong>Consultation in progress</strong>&nbsp;— you may now enter clinical information.
</div>
@endif

{{-- ============================================================ --}}
{{-- MAIN 3-COLUMN LAYOUT --}}
{{-- ============================================================ --}}
<div class="row g-3">

    {{-- =================== LEFT SIDEBAR =================== --}}
    <div class="col-lg-2">
        <div class="card mb-3">
            <div class="card-body p-2">
                <nav class="consultation-sidebar">
                    <ul class="nav flex-column gap-1" id="consultationTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="tab-complaints" href="#complaints-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-message-report me-1"></i>Presenting Complaints
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-complaints">{{ $record?->complaints?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-hopc" href="#hopc-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-file-description me-1"></i>History Of Presenting Complaints
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-hopc">{{ $record?->historiesOfPresentingComplaint?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-examination" href="#examination-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-zoom-check me-1"></i>Examination
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-examination">{{ $record?->physicalExaminations?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-diagnoses" href="#diagnoses-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-report-medical me-1"></i>Diagnoses
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-diagnoses">{{ $record?->diagnoses?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-investigations" href="#investigations-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-test-pipe me-1"></i>Investigations
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-investigations">{{ $record?->investigations?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-treatments" href="#treatments-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-vaccine me-1"></i>Treatments
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-treatments">{{ $record?->treatments?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-prescriptions" href="#prescriptions-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-prescription me-1"></i>Prescriptions
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-prescriptions">{{ $record?->prescriptions?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-procedures" href="#procedures-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-activity-heartbeat me-1"></i>Procedures
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-procedures">{{ $procedureRequests->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-tasks" href="#tasks-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-checklist me-1"></i>Tasks
                                <span class="badge bg-secondary-subtle text-secondary ms-auto" id="badge-tasks">{{ $record?->tasks?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-summary" href="#summary-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-notes me-1"></i>Notes / Summary
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="tab-patterns" href="#patterns-section" data-bs-toggle="pill" role="tab">
                                <i class="ti ti-template me-1"></i>Patterns
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $patterns->count() }}</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-2">
                <h6 class="fw-bold mb-0 small">Quick Actions</h6>
            </div>
            <div class="card-body p-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-outline-info btn-sm">
                        <i class="ti ti-history me-1"></i>Preview
                    </a>
                    <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-eye me-1"></i>View Visit
                    </a>
                    @can('consultations.create')
                    <button type="button" class="btn btn-outline-purple btn-sm" data-bs-toggle="modal" data-bs-target="#savePatternModal">
                        <i class="ti ti-template me-1"></i>Save Pattern
                    </button>
                    @endcan
                    @if($visit->status->allowedTransitions())
                    <hr class="my-1">
                    <small class="text-muted fw-bold px-1">Transition Visit</small>
                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                        @if($nextStatus === \App\Enums\VisitStatus::ADMITTING)
                        {{-- Special admit button → go straight to admission form --}}
                        <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-warning btn-sm w-100"
                                    onclick="return confirm('Mark patient for admission and go to the admission form?')">
                                <i class="ti ti-bed me-1"></i>Admit Patient
                            </button>
                        </form>
                        @elseif($nextStatus === \App\Enums\VisitStatus::COMPLETED)
                        
                        <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-success btn-sm w-100"
                                    onclick="return confirm('Mark this consultation as completed?')">
                                <i class="ti ti-check me-1"></i>Complete Consultation
                            </button>
                        </form>
                        @elseif($nextStatus === \App\Enums\VisitStatus::CANCELLED)
                        
                        <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-danger btn-sm w-100"
                                    onclick="return confirm('Cancel this consultation? This action cannot be undone.')">
                                <i class="ti ti-trash me-1"></i>Cancel Consultation
                            </button>
                        </form>

                        {{-- @else
                        <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm w-100"
                                    onclick="return confirm('Move to {{ $nextStatus->label() }}?')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->label() }}
                            </button>
                        </form> --}}
                        @endif
                    @endforeach
                    @endif
                    @if($visit->status === \App\Enums\VisitStatus::CONSULTING)
                    <hr class="my-1">
                    <small class="text-muted fw-bold px-1">Session Routing</small>
                    <button type="button" class="btn btn-outline-indigo btn-sm w-100 mb-1" data-bs-toggle="modal" data-bs-target="#sendSessionModal">
                        <i class="ti ti-transfer me-1"></i>Transfer Consultation Session
                    </button>
                    {{-- <button type="button" class="btn btn-outline-purple btn-sm w-100" data-bs-toggle="modal" data-bs-target="#investigationModal">
                        <i class="ti ti-test-pipe me-1"></i>Send to Invest.
                    </button> --}}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-10">
<div class="row g-3">
    {{-- =================== MAIN CONTENT =================== --}}
    <div class="col-lg-8">
        <div class="tab-content" id="consultationTabContent">

            {{-- ========================= COMPLAINTS ========================= --}}
            <div class="tab-pane fade show active" id="complaints-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-message-report me-1"></i>Complaints</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addComplaintForm">
                            <i class="ti ti-plus me-1"></i>Add
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addComplaintForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="complaints" action="{{ route('admin.consultations.complaints.store', $visit) }}" method="POST" onsubmit="saveTabBeforeSubmit('complaints-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <input type="text" name="description" id="complaintDescInput" class="form-control" required placeholder="Type to search common complaints..." autocomplete="off" list="complaintSuggestions">
                                            <datalist id="complaintSuggestions"></datalist>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Duration</label>
                                            <input type="text" name="duration" class="form-control" placeholder="e.g., 3 days">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Severity</label>
                                            <select name="severity" class="form-select">
                                                <option value="">-- Select --</option>
                                                <option value="mild">Mild</option>
                                                <option value="moderate">Moderate</option>
                                                <option value="severe">Severe</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addComplaintForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="complaints-list">
                            @forelse($record?->complaints ?? [] as $complaint)
                            <div class="ehr-item severity-{{ $complaint->severity ?? 'mild' }}" id="complaint-{{ $complaint->id }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">{{ $complaint->description }}</p>
                                        <small class="text-muted">
                                            @if($complaint->duration) Duration: {{ $complaint->duration }} &middot; @endif
                                            @if($complaint->severity)
                                                Severity: <span class="badge bg-{{ $complaint->severity === 'severe' ? 'danger' : ($complaint->severity === 'moderate' ? 'warning' : 'info') }}">{{ ucfirst($complaint->severity) }}</span>
                                            @endif
                                        </small>
                                        <small class="text-muted d-block">{{ $entryMeta($complaint) }}</small>
                                    </div>
                                    @if($canDeleteEntry($complaint))
                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                            data-url="{{ route('admin.consultations.complaints.destroy', $complaint) }}"
                                            data-target="#complaint-{{ $complaint->id }}"
                                            data-badge="badge-complaints"
                                            data-confirm="Remove this complaint?">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="complaints-empty">
                                <i class="ti ti-message-report fs-1 d-block mb-2"></i>No complaints recorded yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= HISTORY OF PRESENTING COMPLAINT ========================= --}}
            <div class="tab-pane fade" id="hopc-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-file-description me-1"></i>History of Presenting Complaint</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addHopcForm">
                            <i class="ti ti-plus me-1"></i>Add
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addHopcForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="hopc" action="{{ route('admin.consultations.hopc.store', $visit) }}" method="POST" onsubmit="saveTabBeforeSubmit('hopc-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small">Link to Complaint <small class="text-muted">(optional)</small></label>
                                            <select name="complaint_id" class="form-select form-select-sm">
                                                <option value="">General narrative</option>
                                                @foreach($record?->complaints ?? [] as $complaint)
                                                    <option value="{{ $complaint->id }}">{{ Str::limit($complaint->description, 80) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Narrative <span class="text-danger">*</span></label>
                                            <textarea name="content" class="form-control" rows="4" placeholder="Detailed story behind the complaints..." required></textarea>
                                        </div>
                                        <div class="col-md-3"><input name="onset" class="form-control form-control-sm" placeholder="Onset"></div>
                                        <div class="col-md-3"><input name="duration" class="form-control form-control-sm" placeholder="Duration"></div>
                                        <div class="col-md-3"><input name="location" class="form-control form-control-sm" placeholder="Location"></div>
                                        <div class="col-md-3"><input name="severity" class="form-control form-control-sm" placeholder="Severity"></div>
                                        <div class="col-md-6"><input name="aggravating_factors" class="form-control form-control-sm" placeholder="Aggravating factors"></div>
                                        <div class="col-md-6"><input name="relieving_factors" class="form-control form-control-sm" placeholder="Relieving factors"></div>
                                        <div class="col-12"><input name="associated_symptoms" class="form-control form-control-sm" placeholder="Associated symptoms"></div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addHopcForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="hopc-list">
                            @forelse($record?->historiesOfPresentingComplaint ?? [] as $hopc)
                            <div class="ehr-item" id="hopc-{{ $hopc->id }}">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <p class="mb-1">{{ $hopc->content }}</p>
                                        @if($hopc->complaint)
                                            <small class="text-muted d-block">Complaint: {{ $hopc->complaint->description }}</small>
                                        @endif
                                        <small class="text-muted">{{ $entryMeta($hopc) }}</small>
                                    </div>
                                    @if($canDeleteEntry($hopc))
                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                            data-url="{{ route('admin.consultations.hopc.destroy', $hopc) }}"
                                            data-target="#hopc-{{ $hopc->id }}"
                                            data-badge="badge-hopc"
                                            data-confirm="Remove this history entry?">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="hopc-empty">
                                <i class="ti ti-file-description fs-1 d-block mb-2"></i>No history of presenting complaint recorded yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= EXAMINATION ========================= --}}
            <div class="tab-pane fade" id="examination-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-zoom-check me-1"></i>Examination / Physical Examination</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addExaminationForm">
                            <i class="ti ti-plus me-1"></i>Add
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addExaminationForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="examination" action="{{ route('admin.consultations.examinations.store', $visit) }}" method="POST" onsubmit="saveTabBeforeSubmit('examination-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small">Findings <span class="text-danger">*</span></label>
                                            <textarea name="findings" class="form-control" rows="3" required placeholder="Overall examination findings..."></textarea>
                                        </div>
                                        <div class="col-md-6"><textarea name="general_examination" class="form-control form-control-sm" rows="2" placeholder="General examination"></textarea></div>
                                        <div class="col-md-6"><textarea name="systemic_examination" class="form-control form-control-sm" rows="2" placeholder="Systemic examination"></textarea></div>
                                        <div class="col-md-6"><textarea name="cardiovascular" class="form-control form-control-sm" rows="2" placeholder="Cardiovascular"></textarea></div>
                                        <div class="col-md-6"><textarea name="respiratory" class="form-control form-control-sm" rows="2" placeholder="Respiratory"></textarea></div>
                                        <div class="col-md-6"><textarea name="gastrointestinal" class="form-control form-control-sm" rows="2" placeholder="Gastrointestinal"></textarea></div>
                                        <div class="col-md-6"><textarea name="central_nervous_system" class="form-control form-control-sm" rows="2" placeholder="Central nervous system"></textarea></div>
                                        <div class="col-md-6"><textarea name="specialty_examination" class="form-control form-control-sm" rows="2" placeholder="ENT / eye / dental / specialty"></textarea></div>
                                        <div class="col-md-6"><textarea name="local_examination" class="form-control form-control-sm" rows="2" placeholder="Local examination"></textarea></div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addExaminationForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="examination-list">
                            @forelse($record?->physicalExaminations ?? [] as $exam)
                            <div class="ehr-item" id="examination-{{ $exam->id }}">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <p class="mb-1">{{ $exam->findings }}</p>
                                        <small class="text-muted">{{ $entryMeta($exam) }}</small>
                                    </div>
                                    @if($canDeleteEntry($exam))
                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                            data-url="{{ route('admin.consultations.examinations.destroy', $exam) }}"
                                            data-target="#examination-{{ $exam->id }}"
                                            data-badge="badge-examination"
                                            data-confirm="Remove this examination entry?">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="examination-empty">
                                <i class="ti ti-zoom-check fs-1 d-block mb-2"></i>No examination findings recorded yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= DIAGNOSES ========================= --}}
            <div class="tab-pane fade" id="diagnoses-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>Diagnoses</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addDiagnosisForm">
                            <i class="ti ti-plus me-1"></i>Add
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addDiagnosisForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="diagnoses" action="{{ route('admin.consultations.diagnoses.store', $visit) }}" method="POST" onsubmit="saveTabBeforeSubmit('diagnoses-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small">ICD-10 <small class="text-muted">(optional search)</small></label>
                                            <input type="hidden" name="icd_code_id" id="icd_code_id">
                                            <select id="icd_code_select" class="form-select" style="width:100%">
                                                <option value="">Type to search ICD-10 codes...</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <input type="text" name="description" id="diagnosis_description" class="form-control" required placeholder="Type diagnosis or search ICD-10 above..." autocomplete="off" list="diagnosisSuggestions">
                                            <datalist id="diagnosisSuggestions"></datalist>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">ICD-10 Code (Manual)</label>
                                            <input type="text" name="icd_code" id="icd_code_manual" class="form-control" placeholder="e.g., J06.9">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Type</label>
                                            <select name="type" class="form-select">
                                                <option value="provisional">Provisional</option>
                                                <option value="final">Final</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Additional notes...">
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addDiagnosisForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="diagnoses-list">
                            @forelse($record?->diagnoses ?? [] as $diagnosis)
                            <div class="ehr-item {{ $diagnosis->is_primary ? 'is-primary' : '' }}" id="diagnosis-{{ $diagnosis->id }}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <p class="mb-1">
                                            {{ $diagnosis->description }}
                                            <span class="badge bg-{{ $diagnosis->type === 'final' ? 'success' : 'warning' }} ms-1 diagnosis-type-badge" id="type-badge-{{ $diagnosis->id }}">{{ ucfirst($diagnosis->type) }}</span>
                                            <span class="badge bg-warning text-dark ms-1 diagnosis-primary-badge primary-indicator {{ $diagnosis->is_primary ? '' : 'd-none' }}" id="primary-badge-{{ $diagnosis->id }}">
                                                <i class="ti ti-star-filled me-1"></i>Primary
                                            </span>
                                        </p>
                                        <small class="text-muted">
                                            @if($diagnosis->icdCodeEntry) ICD-10: <code>{{ $diagnosis->icdCodeEntry->code }}</code> &middot;
                                            @elseif($diagnosis->icd_code) ICD-10: <code>{{ $diagnosis->icd_code }}</code> &middot; @endif
                                            @if($diagnosis->notes) {{ $diagnosis->notes }} @endif
                                        </small>
                                        <small class="text-muted d-block">{{ $entryMeta($diagnosis) }}</small>
                                    </div>
                                    @if(auth()->user() && $entryPermissions->canEdit(auth()->user(), $diagnosis))
                                    <div class="d-flex gap-1 ms-2 flex-shrink-0">
                                        <button type="button" class="btn btn-xs btn-outline-secondary toggle-type-btn"
                                                title="Mark as {{ $diagnosis->type === 'provisional' ? 'Final' : 'Provisional' }}"
                                                data-id="{{ $diagnosis->id }}"
                                                data-current="{{ $diagnosis->type }}"
                                                data-url="{{ route('admin.consultations.diagnoses.update', $diagnosis) }}">
                                            <i class="ti ti-switch-2 me-1"></i><span class="toggle-type-label">{{ $diagnosis->type === 'provisional' ? 'Final' : 'Provisional' }}</span>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-warning set-primary-btn {{ $diagnosis->is_primary ? 'd-none' : '' }}"
                                                title="Set as Primary diagnosis"
                                                id="set-primary-{{ $diagnosis->id }}"
                                                data-id="{{ $diagnosis->id }}"
                                                data-url="{{ route('admin.consultations.diagnoses.primary', $diagnosis) }}">
                                            <i class="ti ti-star"></i>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                data-url="{{ route('admin.consultations.diagnoses.destroy', $diagnosis) }}"
                                                data-target="#diagnosis-{{ $diagnosis->id }}"
                                                data-badge="badge-diagnoses"
                                                data-confirm="Remove this diagnosis?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="diagnoses-empty">
                                <i class="ti ti-report-medical fs-1 d-block mb-2"></i>No diagnoses recorded yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= INVESTIGATIONS ========================= --}}
            <div class="tab-pane fade" id="investigations-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-test-pipe me-1"></i>Investigations</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addInvestigationForm">
                            <i class="ti ti-plus me-1"></i>Add
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addInvestigationForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="investigations" action="{{ route('admin.consultations.investigations.store', $visit) }}" method="POST" onsubmit="saveTabBeforeSubmit('investigations-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Department <span class="text-danger">*</span></label>
                                            @if($investigationDepts->isNotEmpty())
                                                <select id="investigationDeptSelect" class="form-select" required onchange="loadInvestigationServices(this.value)">
                                                    <option value="">-- Select Department --</option>
                                                    @foreach($investigationDepts as $dept)
                                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <select id="investigationDeptSelect" class="form-select d-none"></select>
                                                <input type="text" name="investigation_type" class="form-control" required placeholder="e.g., Blood Test, X-Ray...">
                                                <small class="text-muted">No investigation departments configured.</small>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Urgency</label>
                                            <select name="urgency" class="form-select">
                                                <option value="routine">Routine</option>
                                                <option value="urgent">Urgent</option>
                                                <option value="emergency">Emergency</option>
                                            </select>
                                        </div>
                                        @if($investigationDepts->isNotEmpty())
                                        <div class="col-12">
                                            <label class="form-label small">Select Services <span class="text-danger">*</span></label>
                                            <div id="investigationServicesContainer" class="border rounded p-2" style="min-height:50px;">
                                                <span class="text-muted small">Select a department first to load services</span>
                                            </div>
                                        </div>
                                        @endif
                                        <div class="col-12">
                                            <label class="form-label small">Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Clinical notes...">
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addInvestigationForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="investigations-list">
                            @php
                                $allItems = collect($labRequests ?? [])->flatMap(fn($r) => $r->items ?? collect())->filter();
                                $grouped  = collect($labRequests ?? [])->groupBy(fn($r) => $r->targetDepartment->name ?? 'Other');
                            @endphp
                            @if($grouped->isNotEmpty())
                                @foreach($grouped as $deptName => $reqs)
                                <div class="mb-3">
                                    <h6 class="small fw-bold border-bottom pb-1 mb-2 text-uppercase text-muted">
                                        <i class="ti ti-building-hospital me-1"></i>{{ $deptName }}
                                        <span class="badge bg-light text-dark ms-1">{{ collect($reqs)->sum(fn($r) => $r->items?->count() ?? 0) }}</span>
                                    </h6>
                                    @foreach($reqs as $req)
                                        @foreach($req->items ?? [] as $item)
                                        <div class="ehr-item d-flex justify-content-between align-items-start" id="lab-item-{{ $item->id }}">
                                            <div class="flex-grow-1">
                                                <p class="mb-1">
                                                    <strong>{{ $item->display_name ?? $item->name }}</strong>
                                                    <span class="badge bg-{{ $item->status_color }} ms-1">{{ ucfirst($item->status) }}</span>
                                                    @if($item->result?->is_verified)
                                                        <span class="badge bg-success ms-1"><i class="ti ti-check"></i> Verified</span>
                                                    @elseif($item->result)
                                                        <span class="badge bg-warning ms-1">Unverified</span>
                                                    @endif
                                                </p>
                                                <small class="text-muted">
                                                    Req #{{ $req->request_number }} &middot; {{ $req->created_at?->format('d M H:i') }}
                                                    @if($item->accepted_at) &middot; Accepted {{ $item->accepted_at->format('d M H:i') }} @endif
                                                </small>
                                            </div>
                                            <div class="d-flex gap-1">
                                                @if($item->result)
                                                <button type="button" class="btn btn-xs btn-outline-info viewResultBtn"
                                                        data-url="{{ route('admin.lab.results.view', $item) }}"
                                                        title="View Result"><i class="ti ti-eye"></i></button>
                                                @endif
                                                @if($item->result?->is_verified)
                                                <a data-no-inertia href="{{ route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print"><i class="ti ti-printer"></i></a>
                                                @endif
                                                @can('consultations.create')
                                                @if($item->isDeletable() && $canEdit)
                                                <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                        data-url="{{ route('admin.consultations.investigation-items.destroy', $item) }}"
                                                        data-method="DELETE"
                                                        data-target="#lab-item-{{ $item->id }}"
                                                        data-confirm="Remove this investigation item?"
                                                        title="Delete"><i class="ti ti-trash"></i></button>
                                                @endif
                                                @endcan
                                            </div>
                                        </div>
                                        @endforeach
                                    @endforeach
                                </div>
                                @endforeach
                            @else
                            <div class="text-center text-muted py-4" id="investigations-empty">
                                <i class="ti ti-test-pipe fs-1 d-block mb-2"></i>No investigations requested yet.
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= TREATMENTS ========================= --}}
            <div class="tab-pane fade" id="treatments-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-vaccine me-1"></i>Treatments</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addTreatmentForm">
                            <i class="ti ti-plus me-1"></i>Add
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addTreatmentForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="treatments" action="{{ route('admin.consultations.treatments.store', $visit) }}" method="POST" onsubmit="saveTabBeforeSubmit('treatments-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Type <span class="text-danger">*</span></label>
                                            <select name="type" class="form-select" required>
                                                <option value="">-- Select --</option>
                                                <option value="medication">Medication</option>
                                                <option value="procedure">Procedure</option>
                                                <option value="referral">Referral</option>
                                                <option value="advice">Advice</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <textarea name="description" class="form-control" rows="2" required placeholder="Treatment details..."></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addTreatmentForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="treatments-list">
                            @forelse($record?->treatments ?? [] as $treatment)
                            <div class="ehr-item" id="treatment-{{ $treatment->id }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">
                                            <span class="badge bg-{{ $treatment->type === 'medication' ? 'primary' : ($treatment->type === 'procedure' ? 'info' : ($treatment->type === 'referral' ? 'warning' : 'secondary')) }}">{{ ucfirst($treatment->type) }}</span>
                                            {{ $treatment->description }}
                                        </p>
                                        <small class="text-muted">{{ $entryMeta($treatment) }}</small>
                                    </div>
                                    @if($canDeleteEntry($treatment))
                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                            data-url="{{ route('admin.consultations.treatments.destroy', $treatment) }}"
                                            data-target="#treatment-{{ $treatment->id }}"
                                            data-badge="badge-treatments"
                                            data-confirm="Remove this treatment?">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="treatments-empty">
                                <i class="ti ti-vaccine fs-1 d-block mb-2"></i>No treatments recorded yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= PRESCRIPTIONS ========================= --}}
            <div class="tab-pane fade" id="prescriptions-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-prescription me-1"></i>Prescriptions</h6>
                        @can('prescriptions.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addPrescriptionForm">
                            <i class="ti ti-plus me-1"></i>New Rx
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('prescriptions.create')
                        <div class="collapse mb-3" id="addPrescriptionForm">
                            <div class="card card-body bg-light">
                                <form data-ajax-form="prescriptions" method="POST" action="{{ route('admin.consultations.prescriptions.store', $visit) }}" id="prescriptionForm" onsubmit="preparePrescriptionSubmit()">
                                    @csrf
                                    <div id="prescriptionFormErrors" class="alert alert-danger d-none small py-2 mb-2"></div>
                                    <div id="prescriptionItems">
                                        <div class="prescription-item border rounded p-2 mb-2">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Drug Name <span class="text-danger">*</span></label>
                                                    <select name="items[0][drug_id]" class="form-select form-select-sm drug-select" required>
                                                        <option value="">-- Search drug --</option>
                                                        @foreach($drugs as $drug)
                                                            <option value="{{ $drug->id }}"
                                                                data-name="{{ $drug->name }}"
                                                                data-strength="{{ $drug->strength ?? '' }}"
                                                                data-unit="{{ $drug->unit ?? '' }}">{{ $drug->name }}{{ $drug->generic_name ? ' ('.$drug->generic_name.')' : '' }}{{ $drug->strength ? ' - '.$drug->strength : '' }}{{ $drug->dosage_form ? ' ['.$drug->dosage_form.']' : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="items[0][drug_name]" class="drug-name-input">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Dosage <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[0][dosage]" class="form-control form-control-sm" required placeholder="500mg">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Freq <span class="text-danger">*</span></label>
                                                    <select name="items[0][frequency]" class="form-select form-select-sm" required>
                                                        <option value="OD">OD</option>
                                                        <option value="BD">BD</option>
                                                        <option value="TDS" selected>TDS</option>
                                                        <option value="QDS">QDS</option>
                                                        <option value="STAT">STAT</option>
                                                        <option value="PRN">PRN</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Duration <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[0][duration]" class="form-control form-control-sm" required placeholder="5 days">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Qty <span class="text-danger">*</span></label>
                                                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm" required min="1" value="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Route</label>
                                                    <select name="items[0][route]" class="form-select form-select-sm">
                                                        <option value="oral">Oral</option>
                                                        <option value="IV">IV</option>
                                                        <option value="IM">IM</option>
                                                        <option value="SC">SC</option>
                                                        <option value="topical">Topical</option>
                                                        <option value="inhaled">Inhaled</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-9">
                                                    <label class="form-label small">Instructions</label>
                                                    <input type="text" name="items[0][instructions]" class="form-control form-control-sm" placeholder="Special instructions...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">
                                            <i class="ti ti-plus me-1"></i>Add Medication
                                        </button>
                                        <div class="d-flex gap-2">
                                            <input type="text" name="notes" class="form-control form-control-sm" style="width:170px" placeholder="Rx Notes...">
                                            <button type="submit" class="btn btn-primary btn-sm" onclick="saveTabBeforeSubmit('prescriptions-section')">
                                                <i class="ti ti-check me-1"></i>Create Rx
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="prescriptions-list">
                            @forelse($record?->prescriptions ?? [] as $prescription)
                            <div class="border rounded p-3 mb-3" id="prescription-{{ $prescription->id }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="fw-bold">{{ $prescription->prescription_number }}</span>
                                        <span class="badge bg-{{ $prescription->status->color() }} ms-2">{{ $prescription->status->label() }}</span>
                                        <small class="text-muted d-block">{{ $entryMeta($prescription) }}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <small class="text-muted">{{ $prescription->created_at->format('d M Y, h:i A') }}</small>
                                        @if($canDeleteEntry($prescription) && in_array($prescription->status->value, ['pending', 'active']))
                                        <form method="POST" action="{{ route('admin.consultations.prescriptions.destroy', $prescription) }}"
                                              onsubmit="return confirm('Cancel &amp; delete this prescription?') &amp;&amp; saveTabBeforeSubmit('prescriptions-section')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete prescription">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0">
                                        <thead><tr class="text-muted small"><th>Drug</th><th>Dosage</th><th>Freq</th><th>Duration</th><th>Qty</th><th>Route</th></tr></thead>
                                        <tbody>
                                            @foreach($prescription->items as $item)
                                            <tr>
                                                <td class="fw-medium">{{ $item->drug_name }}</td>
                                                <td>{{ $item->dosage }}</td>
                                                <td>{{ $item->frequency }}</td>
                                                <td>{{ $item->duration }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $item->route }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($prescription->notes) <small class="text-muted">Notes: {{ $prescription->notes }}</small> @endif
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="prescriptions-empty">
                                <i class="ti ti-prescription fs-1 d-block mb-2"></i>No prescriptions created yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= PROCEDURES ========================= --}}
            <div class="tab-pane fade" id="procedures-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-activity-heartbeat me-1"></i>Theatre / Procedure Requests</h6>
                        @can('procedure.request')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addProcedureForm">
                            <i class="ti ti-plus me-1"></i>Request Procedure
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @can('procedure.request')
                        <div class="collapse mb-3" id="addProcedureForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.procedures.store', $visit) }}" onsubmit="return saveTabBeforeSubmit('procedures-section')">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Theatre / Procedure Department <span class="text-danger">*</span></label>
                                            <select name="department_id" id="procedureDeptSelect" class="form-select form-select-sm" required onchange="loadProcedureServices(this.value)">
                                                <option value="">-- Select department --</option>
                                                @foreach($procedureDepartments as $dept)
                                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Service <span class="text-danger">*</span></label>
                                            <select name="service_catalog_id" id="procedureServiceSelect" class="form-select form-select-sm" required disabled>
                                                <option value="">Select department first</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Priority <span class="text-danger">*</span></label>
                                            <select name="priority" class="form-select form-select-sm" required>
                                                <option value="routine">Routine</option>
                                                <option value="urgent">Urgent</option>
                                                <option value="emergency">Emergency</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small">Preferred date/time (optional)</label>
                                            <input type="datetime-local" name="preferred_datetime" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Indication / Reason <span class="text-danger">*</span></label>
                                            <textarea name="indication" class="form-control form-control-sm" rows="2" required placeholder="Clinical indication for the procedure..."></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Notes</label>
                                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Additional notes..."></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-2 d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Submit Request</button>
                                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addProcedureForm">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <div id="procedures-list">
                            @forelse($procedureRequests as $pr)
                            <div class="ehr-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1">
                                            <span class="badge" style="background-color: {{ $pr->status->color() }}; color:#fff;">{{ $pr->status->label() }}</span>
                                            <span class="fw-medium">{{ $pr->service?->name ?? 'Procedure' }}</span>
                                            <small class="text-muted">· {{ $pr->request_number }}</small>
                                        </p>
                                        <small class="text-muted">
                                            {{ ucfirst($pr->priority) }} ·
                                            {{ $pr->department?->name }} ·
                                            Requested {{ optional($pr->requested_at)->format('d M Y H:i') }}
                                            @if($pr->schedule)
                                                · Scheduled {{ optional($pr->schedule->scheduled_start)->format('d M Y H:i') }}
                                                @if($pr->schedule->theatreRoom) ({{ $pr->schedule->theatreRoom->name }}) @endif
                                            @endif
                                        </small>
                                        @if($pr->indication)
                                            <div><small><strong>Indication:</strong> {{ $pr->indication }}</small></div>
                                        @endif
                                        @if($pr->rejection_reason)
                                            <div><small class="text-danger"><strong>Rejected:</strong> {{ $pr->rejection_reason }}</small></div>
                                        @endif
                                        @if($pr->cancellation_reason)
                                            <div><small class="text-warning"><strong>Cancelled:</strong> {{ $pr->cancellation_reason }}</small></div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.theatre.show', $pr) }}">
                                            <i class="ti ti-eye me-1"></i>Open
                                        </a>
                                        @if($pr->status === \App\Enums\ProcedureStatus::COMPLETED)
                                            <a data-no-inertia class="btn btn-sm btn-outline-secondary" href="{{ route('admin.theatre.report', $pr) }}" target="_blank">Report</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4" id="procedures-empty">
                                <i class="ti ti-activity-heartbeat fs-1 d-block mb-2"></i>No procedure requests for this visit yet.
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= HISTORY ========================= --}}
            {{-- <div class="tab-pane fade" id="history-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-history me-1"></i>Medical History</h6>
                        <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-sm btn-outline-info">
                            <i class="ti ti-external-link me-1"></i>Full History
                        </a>
                    </div>
                    <div class="card-body">
                        @if(count($history['records']) > 0)
                            @foreach($history['records'] as $pastRecord)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold">{{ $pastRecord->visit?->visit_number ?? 'Unknown' }}</span>
                                    <small class="text-muted">{{ $pastRecord->created_at->format('d M Y') }}</small>
                                </div>
                                @if($pastRecord->complaints->count())
                                <div class="mb-2">
                                    <small class="text-muted fw-bold">Complaints:</small>
                                    @foreach($pastRecord->complaints as $c)
                                        <span class="badge bg-light text-dark">{{ Str::limit($c->description, 50) }}</span>
                                    @endforeach
                                </div>
                                @endif
                                @if($pastRecord->diagnoses->count())
                                <div>
                                    <small class="text-muted fw-bold">Diagnoses:</small>
                                    @foreach($pastRecord->diagnoses as $d)
                                        <span class="badge bg-info-subtle text-info">{{ Str::limit($d->description, 50) }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                            @if($history['total'] > 10)
                            <div class="text-center">
                                <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-outline-primary btn-sm">View All {{ $history['total'] }} Records</a>
                            </div>
                            @endif
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-history fs-1 d-block mb-2"></i>No previous medical history found.
                            </div>
                        @endif
                    </div>
                </div>
            </div> --}}

            {{-- ========================= PATTERNS ========================= --}}
            <div class="tab-pane fade" id="patterns-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-template me-1"></i>Medical Patterns</h6>
                        <a href="{{ route('admin.patterns.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-plus me-1"></i>Create Pattern
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="patternSearchInput" class="form-control" placeholder="Search patterns by complaint..." minlength="3">
                                <button type="button" class="btn btn-primary" id="patternSearchBtn">Search</button>
                            </div>
                        </div>
                        <div id="patternSearchResults" class="mb-3" style="display:none;"></div>
                        <h6 class="fw-bold small text-muted mb-2"><i class="ti ti-flame me-1"></i>Frequently Used</h6>
                        @if($patterns->count() > 0)
                            @foreach($patterns as $pattern)
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $pattern->name }}</h6>
                                        <div class="mb-1">
                                            @foreach($pattern->items->groupBy('type') as $type => $items)
                                                <span class="badge bg-{{ $items->first()->getTypeColor() }}-subtle text-{{ $items->first()->getTypeColor() }} me-1">
                                                    {{ $items->count() }} {{ $items->first()->getTypeLabel() }}{{ $items->count() > 1 ? 's' : '' }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">Used {{ $pattern->usage_count }} times</small>
                                    </div>
                                    @can('consultations.create')
                                    <button type="button" class="btn btn-sm btn-success apply-pattern-btn"
                                            data-pattern-id="{{ $pattern->id }}" data-pattern-name="{{ $pattern->name }}"
                                            data-pattern-types="{{ $pattern->items->pluck('type')->unique()->implode(',') }}">
                                        <i class="ti ti-check me-1"></i>Apply
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-template fs-1 d-block mb-2"></i>No patterns available yet.
                                <br><a href="{{ route('admin.patterns.create') }}">Create your first pattern</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ========================= TASKS ========================= --}}
            <div class="tab-pane fade" id="tasks-section" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-checklist me-1"></i>Tasks</h6>
                        @can('consultations.create')
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                            <i class="ti ti-plus me-1"></i>Add Task
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($record && $record->tasks && $record->tasks->count() > 0)
                            @foreach($record->tasks->sortBy(fn($t) => $t->completed_at ? 1 : 0) as $task)
                            <div class="d-flex align-items-start gap-2 mb-3 p-2 border rounded {{ $task->completed_at ? 'bg-light' : '' }}">
                                @if(auth()->user() && $entryPermissions->canEdit(auth()->user(), $task))
                                <form method="POST" action="{{ route('admin.consultations.tasks.toggle', $task) }}" onsubmit="saveTabBeforeSubmit('tasks-section')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm {{ $task->completed_at ? 'btn-success' : 'btn-outline-secondary' }} rounded-circle p-1" style="width:28px;height:28px;" title="{{ $task->completed_at ? 'Mark incomplete' : 'Mark complete' }}">
                                        <i class="ti ti-check fs-14"></i>
                                    </button>
                                </form>
                                @endif
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-medium {{ $task->completed_at ? 'text-decoration-line-through text-muted' : '' }}">{{ $task->title }}</span>
                                        @if($canDeleteEntry($task))
                                        <form method="POST" action="{{ route('admin.consultations.tasks.destroy', $task) }}" class="d-inline" onsubmit="return confirm('Delete this task?') && saveTabBeforeSubmit('tasks-section')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger"><i class="ti ti-x"></i></button>
                                        </form>
                                        @endif
                                    </div>
                                    @if($task->description) <small class="text-muted">{{ $task->description }}</small> @endif
                                    <div class="mt-1">
                                        <small class="text-muted">
                                            Created by: {{ $task->creator?->full_name ?? 'Unknown user' }}
                                            @if($task->assignedUser) Assigned: {{ $task->assignedUser->full_name }} @endif
                                            @if($task->due_date) &middot; Due: {{ $task->due_date->format('d M Y') }} @endif
                                            @if($task->completed_at) &middot; Done: {{ $task->completed_at->format('d M Y H:i') }} @endif
                                            @if($task->completedBy) &middot; Completed by: {{ $task->completedBy->full_name }} @endif
                                        </small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-checklist fs-1 d-block mb-2"></i>No tasks for this consultation yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ========================= NOTES / SUMMARY ========================= --}}
            <div class="tab-pane fade" id="summary-section" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>Notes / Consultation Summary</h6>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="row g-2 small">
                                <div class="col-md-4"><strong>Department Session:</strong> {{ $consultationSummary['department'] ?? '-' }}</div>
                                <div class="col-md-4"><strong>Main Doctor:</strong> {{ $consultationSummary['main_doctor'] ? 'Dr. '.$consultationSummary['main_doctor'] : 'Unassigned' }}</div>
                                <div class="col-md-4"><strong>Contributors:</strong> {{ collect($consultationSummary['contributors'] ?? [])->implode(', ') ?: '-' }}</div>
                                <div class="col-12"><strong>Services:</strong> {{ collect($consultationSummary['services'] ?? [])->implode(', ') ?: '-' }}</div>
                            </div>
                        </div>

                        @php
                            $summaryLabels = [
                                'complaints' => 'Complaints',
                                'history_of_presenting_complaint' => 'History of Presenting Complaint',
                                'examination' => 'Examination',
                                'diagnoses' => 'Diagnosis',
                                'investigations' => 'Investigations',
                                'treatments' => 'Treatments',
                                'prescriptions' => 'Prescriptions',
                                'procedures' => 'Procedures',
                                'tasks' => 'Tasks / Follow-up / Instructions',
                                'notes' => 'Notes',
                            ];
                        @endphp
                        @foreach($summaryLabels as $key => $label)
                            <div class="mb-3">
                                <h6 class="small fw-bold text-muted border-bottom pb-1">{{ $label }}</h6>
                                @forelse(($consultationSummary['sections'][$key] ?? []) as $entry)
                                    <div class="ehr-item">
                                        <div class="fw-medium">{{ $entry['content'] }}</div>
                                        <small class="text-muted">
                                            Entered by: {{ $entry['entered_by'] }}
                                            @if($entry['created_at']) · {{ $entry['created_at']->format('d M Y, h:i A') }} @endif
                                            @if($entry['source_pattern']) · Source Pattern: {{ $entry['source_pattern'] }} @endif
                                        </small>
                                        @if(!empty($entry['details']))
                                            <div class="small mt-1">
                                                @foreach($entry['details'] as $name => $value)
                                                    <span class="badge bg-light text-dark me-1">{{ $name }}: {{ $value }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-muted small mb-2">None recorded.</p>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- =================== RIGHT PANEL — PREVIOUS VISITS =================== --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header py-2">
                <h6 class="fw-bold mb-0 small"><i class="ti ti-clock-history me-1"></i>Previous Visits
                    @if($history['total'] > 0) <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $history['total'] }}</span> @endif
                </h6>
            </div>
            <div class="card-body p-2" style="max-height:600px;overflow-y:auto;">
                @if(count($history['records']) > 0)
                    @foreach($history['records'] as $index => $pastRecord)
                    <div class="prev-visit-card border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold small">{{ $pastRecord->visit?->visit_number ?? 'N/A' }}</div>
                                <small class="text-muted d-block">{{ $pastRecord->created_at->format('d M Y') }}</small>
                                @if($pastRecord->visit?->currentConsultationDoctor())
                                    <small class="text-muted d-block">Dr. {{ Str::limit($pastRecord->visit->currentConsultationDoctor()->full_name, 18) }}</small>
                                @endif
                                <small class="text-muted d-block">
                                    {{ $pastRecord->complaints->count() }} complaint(s) &middot; {{ $pastRecord->diagnoses->count() }} dx
                                </small>
                            </div>
                            <button type="button" class="btn btn-xs btn-outline-primary flex-shrink-0"
                                    onclick="window.location='{{ route('admin.consultations.history', $pastRecord->visit) }}'">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                    @if($history['total'] > 10)
                    <div class="text-center mt-1">
                        <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-sm btn-outline-secondary w-100">
                            + {{ $history['total'] - 10 }} more visits
                        </a>
                    </div>
                    @endif
                @else
                    <div class="text-center text-muted py-3">
                        <i class="ti ti-clock fs-3 d-block mb-1"></i>
                        <small>No previous visits</small>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- CONSULTATION SESSIONS — STICKY-BOTTOM DRAWER --}}
{{-- ============================================================ --}}
<div id="sessionsDrawer" class="is-collapsed">
    <div id="sessionsDrawerHandle" role="button" aria-expanded="false" aria-controls="sessionsDrawerBody" onclick="toggleSessionsDrawer()">
        <i class="ti ti-route fs-5"></i>
        <span class="fw-semibold small">Consultation Sessions for This Visit</span>
        <span class="badge bg-white text-primary rounded-pill ms-1">{{ $sessions->count() }}</span>
        <i class="ti ti-chevron-up ms-auto fs-5"></i>
    </div>
    <div id="sessionsDrawerBody">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Department</th>
                        <th>Linked Services</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Started</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $session)
                    @php
                        $rowClass = $selectedRoute && $selectedRoute->id === $session->id ? 'is-current' : '';
                        $rowClass .= $session->status === \App\Models\VisitConsultationRoute::STATUS_COMPLETED ? ' is-completed' : '';
                        $rowClass .= $session->status === \App\Models\VisitConsultationRoute::STATUS_CANCELLED ? ' is-cancelled' : '';
                        $sessionServiceNames = $routeServiceNames($session);
                    @endphp
                    <tr class="session-route-row {{ trim($rowClass) }}">
                        <td class="fw-medium">{{ $session->department?->name ?? '-' }}</td>
                        <td>
                            {{ $sessionServiceNames->implode(', ') ?: '-' }}
                            @if($selectedRoute && $selectedRoute->id === $session->id)
                                <span class="badge bg-primary ms-1">Current</span>
                            @endif
                        </td>
                        <td>{{ $session->doctor ? 'Dr. ' . $session->doctor->full_name : 'Unassigned' }}</td>
                        <td><span class="badge bg-{{ $routeBadge($session->status) }}">{{ $session->status }}</span></td>
                        <td>
                            <div class="small">{{ $session->started_at?->format('d M, h:i A') ?? '—' }}</div>
                            @if($session->completed_at)<div class="small text-muted">{{ $session->completed_at->format('d M, h:i A') }}</div>@endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $session]) }}" class="btn btn-xs btn-outline-primary">
                                    <i class="ti ti-eye"></i> Open
                                </a>
                                @can('consultations.create')
                                @if(in_array($session->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                                    <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $session]) }}">
                                        @csrf
                                        <button class="btn btn-xs btn-primary" type="submit">Start</button>
                                    </form>
                                @endif
                                @if($session->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                                    <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $session]) }}">
                                        @csrf
                                        <button class="btn btn-xs btn-success" type="submit" onclick="return confirm('Complete this session?')">Complete</button>
                                    </form>
                                @endif
                                @if(in_array($session->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                                    <form method="POST" action="{{ route('admin.consultations.routes.cancel', [$visit, $session]) }}">
                                        @csrf
                                        <button class="btn btn-xs btn-outline-danger" type="submit" onclick="return confirm('Cancel this queued session?')">Cancel</button>
                                    </form>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">No consultation sessions routed for this visit.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>{{-- end main row --}}

{{-- ============================================================ --}}
{{-- VISIT PREVIEW MODAL --}}
{{-- ============================================================ --}}
<div class="modal fade" id="visitPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-clock-history me-2"></i>Visit Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="visitPreviewContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- ADD TASK MODAL --}}
{{-- ============================================================ --}}
@can('consultations.create')
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.consultations.tasks.store', $visit) }}" onsubmit="saveTabBeforeSubmit('tasks-section')">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-checklist me-2"></i>Add Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g., Follow up on lab results">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">Unassigned</option>
                            @if(isset($doctors))
                                @foreach($doctors as $doc)
                                    <option value="{{ $doc->id }}">Dr. {{ $doc->full_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Add Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- ============================================================ --}}
{{-- SAVE AS PATTERN MODAL --}}
{{-- ============================================================ --}}
@can('consultations.create')
<div class="modal fade" id="savePatternModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patterns.from-record', $visit) }}">
                @csrf
                @if($selectedRoute)
                    <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute->id }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-template me-2"></i>Save as Pattern</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pattern Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Common Cold">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scope</label>
                        <select name="scope" class="form-select">
                            <option value="personal">Personal (Only me)</option>
                            <option value="system">System-Wide (All doctors)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Save Pattern</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

{{-- ============================================================ --}}
{{-- SEND TO ANOTHER CONSULTATION SESSION MODAL --}}
{{-- ============================================================ --}}
@if($visit->status === \App\Enums\VisitStatus::CONSULTING)
<div class="modal fade" id="sendSessionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.consultations.refer', $visit) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-transfer me-2"></i>Send to Another Consultation Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @php
                        $historyDeptIds = $visit->departmentHistory->pluck('department_id')->toArray();
                        $currentDeptId  = $visit->current_department_id;
                        $referralDepts  = \App\Models\Department::active()
                            ->where('id', '!=', $currentDeptId)
                            ->where('type', \App\Enums\DepartmentType::CONSULTATION->value)
                            ->orderBy('name')->get();
                        // Pre-load consultation services per referral department so the
                        // service picker can react to the department dropdown without
                        // an extra HTTP call.
                        $referralServicesByDept = \App\Models\ServiceCatalog::where('is_active', true)
                            ->where('category', \App\Enums\ServiceType::CONSULTATION->value)
                            ->whereIn('department_id', $referralDepts->pluck('id'))
                            ->orderBy('name')
                            ->get()
                            ->groupBy('department_id');
                    @endphp
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Target Consultation Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required id="sendSessionDeptSelect">
                            <option value="">— Select department —</option>
                            @foreach($referralDepts as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Services to add / bill <small class="text-muted">(optional)</small></label>
                        <select name="service_ids[]" class="form-select" id="sendSessionServiceSelect" disabled multiple size="4">
                            <option value="" disabled>Select department first</option>
                        </select>
                        <small class="text-muted">Services are linked under the target department session and billed once.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Doctor optional</label>
                        <select name="doctor_id" class="form-select" id="sendSessionDoctorSelect" disabled>
                            <option value="">Select department first</option>
                        </select>
                        <small class="text-muted">Doctors are loaded from specialties linked to the selected department.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason / Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Reason for this consultation session..."></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="activate_now" value="1" id="activateNewSessionNow">
                        <label class="form-check-label" for="activateNewSessionNow">Create and activate now</label>
                    </div>
                    @if($visit->departmentHistory->isNotEmpty())
                        <div class="alert alert-info py-2 small">
                            <strong>Department History:</strong><br>
                            @foreach($visit->departmentHistory as $hist)
                                <span class="badge bg-{{ $hist->typeColor() }}">{{ $hist->typeLabel() }}</span>
                                {{ $hist->department?->name }}
                                <span class="badge bg-{{ $hist->statusColor() }}">{{ $hist->statusLabel() }}</span><br>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" {{ $referralDepts->isEmpty() ? 'disabled' : '' }}>
                        <i class="ti ti-transfer me-1"></i>Create Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="investigationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-microscope me-2"></i>Send Investigation Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                {{-- Tab navigation --}}
                <ul class="nav nav-tabs mb-3" id="investModalTabs">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#investTabLabReq">
                            <i class="ti ti-flask me-1"></i>Lab / Imaging Request
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#investTabRoute">
                            <i class="ti ti-arrow-right me-1"></i>Route to Department
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    {{-- Tab 1: Lab Request --}}
                    <div class="tab-pane fade show active" id="investTabLabReq">
                        @can('lab.requests.create')
                        <form id="labRequestForm" method="POST" action="{{ route('admin.consultations.lab-request.store', $visit) }}">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Target Department <span class="text-danger">*</span></label>
                                    @if($investigationDepts->isNotEmpty())
                                    <select name="target_department_id" id="labReqDeptSelect" class="form-select" required
                                        onchange="loadLabReqItems(this.value)">
                                        <option value="">— Select department —</option>
                                        @foreach($investigationDepts as $dept)
                                        <option value="{{ $dept->id }}"
                                            data-result-type="{{ $dept->result_type?->value }}"
                                            data-uses-catalog="{{ $dept->result_type?->usesTestCatalog() ? 'true' : 'false' }}">
                                            {{ $dept->name }}
                                            <small>({{ $dept->result_type?->label() }})</small>
                                        </option>
                                        @endforeach
                                    </select>
                                    @else
                                    <div class="alert alert-warning py-2 mb-0">
                                        <small>No investigation departments configured. Please add departments with a result type set.</small>
                                    </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Urgency</label>
                                    <select name="urgency" class="form-select">
                                        <option value="routine">Routine</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="emergency">Emergency</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Clinical Notes</label>
                                    <input type="text" name="clinical_info" class="form-control" placeholder="Clinical indication / notes...">
                                </div>
                            </div>

                            {{-- Items container — shown after dept selected --}}
                            <div id="labReqItemsContainer" class="mt-3 d-none">
                                <label class="form-label fw-semibold" id="labReqItemsLabel">Items <span class="text-danger">*</span></label>
                                <div id="labReqItemsBody">
                                    <span class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Loading...</span>
                                </div>
                            </div>

                            <div id="labReqErrors" class="alert alert-danger py-2 d-none mt-3"></div>

                            <div class="mt-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="labReqSubmitBtn">
                                    <i class="ti ti-send me-1"></i>Send Request
                                </button>
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                        @else
                        <div class="alert alert-warning">You don't have permission to create lab requests.</div>
                        @endcan
                    </div>

                    {{-- Tab 2: Route to Department --}}
                    <div class="tab-pane fade" id="investTabRoute">
                        <form id="routeInvestigationForm" method="POST" action="{{ route('admin.consultations.investigation', $visit) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Investigation Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select" required>
                                    <option value="">— Select department —</option>
                                    @foreach($investigationDepts->isNotEmpty() ? $investigationDepts : \App\Models\Department::active()->orderBy('name')->get() as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Investigation notes..."></textarea>
                            </div>
                            <div id="investRouteErrors" class="alert alert-danger py-2 d-none"></div>
                            <button type="submit" class="btn btn-primary" id="investRouteSubmitBtn">
                                <i class="ti ti-arrow-right me-1"></i>Route Patient to Department
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- VISIT HISTORY JSON + JS CONFIG --}}
{{-- ============================================================ --}}
@php
$visitHistoryJson = $history['records']->map(function($r) {
    return [
        'visit_number'   => $r->visit?->visit_number ?? 'N/A',
        'date'           => $r->created_at->format('d M Y'),
        'doctor'         => $r->visit?->currentConsultationDoctor()?->full_name ?? null,
        'complaints'     => $r->complaints->map(function($c) { return $c->description; })->values()->all(),
        'diagnoses'      => $r->diagnoses->map(function($d) {
            return [
                'description' => $d->description,
                'type'        => $d->type,
                'is_primary'  => $d->is_primary ?? false,
                'icd_code'    => $d->icd_code,
            ];
        })->values()->all(),
        'investigations' => $r->investigations->map(function($i) {
            return [
                'type'        => $i->investigation_type,
                'description' => $i->description,
                'urgency'     => $i->urgency,
            ];
        })->values()->all(),
        'treatments'     => $r->treatments->map(function($t) {
            return [
                'type'        => $t->type,
                'description' => $t->description,
            ];
        })->values()->all(),
    ];
})->values();
@endphp

{{-- View Result Modal (used by investigations tab) --}}
<div class="modal fade" id="viewResultModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-clipboard-data me-1"></i>Investigation Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewResultBody">
                <div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
/* ================================================================
   PAGE GLOBALS
   The Inertia legacy bridge re-injects scripts from the @stack('scripts')
   region (the body's <script> tags inside v-html are NOT executed),
   so all page-scoped state must live here.
   ================================================================ */
window.visitHistoryData = @json($visitHistoryJson);
window.csrfToken      = '{{ csrf_token() }}';
window.tabStorageKey  = 'consult_tab_{{ $visit->id }}';
window.destroyUrls    = {
    complaint:     '{{ url("admin/consultations/complaints") }}',
    hopc:          '{{ url("admin/consultations/history-of-presenting-complaints") }}',
    examination:   '{{ url("admin/consultations/examinations") }}',
    diagnosis:     '{{ url("admin/consultations/diagnoses") }}',
    investigation: '{{ url("admin/consultations/investigations") }}',
    treatment:     '{{ url("admin/consultations/treatments") }}',
};
window.diagnosisBaseUrl  = '{{ url("admin/consultations/diagnoses") }}';
window.deptServicesBase  = '{{ url("admin/departments") }}';
window.procedureDeptServicesBase = '{{ url("admin/theatre/departments") }}';
window.prescriptionDestroyBase = '{{ url("admin/consultations/prescriptions") }}';
window.canEditConsultation = @json($canEdit);
window.currentConsultationRouteId = @json($selectedRoute?->id);

/* ── Sessions drawer: always-visible, anchored to col-lg-10 ──── */
function positionSessionsDrawer() {
    const col = document.querySelector('.col-lg-10');
    const drawer = document.getElementById('sessionsDrawer');
    if (!col || !drawer) return;
    const r = col.getBoundingClientRect();
    drawer.style.left  = r.left + 'px';
    drawer.style.right = 'auto';
    drawer.style.width = r.width + 'px';
}
function toggleSessionsDrawer() {
    const drawer = document.getElementById('sessionsDrawer');
    const handle = document.getElementById('sessionsDrawerHandle');
    if (!drawer) return;
    const collapsed = drawer.classList.toggle('is-collapsed');
    if (handle) handle.setAttribute('aria-expanded', String(!collapsed));
}
window.toggleSessionsDrawer = toggleSessionsDrawer;
document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('has-sessions-drawer');
    positionSessionsDrawer();
    if (window.ResizeObserver) {
        new ResizeObserver(positionSessionsDrawer).observe(document.documentElement);
    } else {
        window.addEventListener('resize', positionSessionsDrawer);
    }
});
/* ────────────────────────────────────────────────────────────── */

function openSendSessionModalFallback() {
    if (window.bootstrap && window.bootstrap.Modal) return false;
    const modal = document.getElementById('sendSessionModal');
    if (!modal) return false;
    modal.style.display = 'block';
    modal.removeAttribute('aria-hidden');
    modal.setAttribute('aria-modal', 'true');
    modal.classList.add('show');
    document.body.classList.add('modal-open');
    if (!document.querySelector('.uhms-session-modal-backdrop')) {
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show uhms-session-modal-backdrop';
        document.body.appendChild(backdrop);
    }
    return true;
}

function closeSendSessionModalFallback() {
    const modal = document.getElementById('sendSessionModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    modal.removeAttribute('aria-modal');
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.uhms-session-modal-backdrop').forEach(el => el.remove());
}

function initSendSessionPicker() {
    const endpointTemplate = @json(route('admin.departments.visit-options', ['department' => '__ID__']));
    const deptSel = document.getElementById('sendSessionDeptSelect');
    const svcSel = document.getElementById('sendSessionServiceSelect');
    const doctorSel = document.getElementById('sendSessionDoctorSelect');
    if (!deptSel || !svcSel || !doctorSel || deptSel.dataset.uhmsBound === '1') return;
    deptSel.dataset.uhmsBound = '1';

    function setOptions(select, placeholder, list, labelFn) {
        select.innerHTML = '';
        select.insertAdjacentHTML('beforeend', '<option value="">' + placeholder + '</option>');
        list.forEach(function (item) {
            select.insertAdjacentHTML('beforeend', '<option value="' + item.id + '">' + labelFn(item) + '</option>');
        });
    }

    deptSel.addEventListener('change', async function () {
        svcSel.innerHTML = '';
        doctorSel.innerHTML = '';
        if (!this.value) {
            svcSel.disabled = true;
            doctorSel.disabled = true;
            svcSel.innerHTML = '<option value="">Select department first</option>';
            doctorSel.innerHTML = '<option value="">Select department first</option>';
            return;
        }
        svcSel.disabled = true;
        doctorSel.disabled = true;
        svcSel.innerHTML = '<option value="">Loading services...</option>';
        doctorSel.innerHTML = '<option value="">Loading doctors...</option>';
        try {
            const res = await fetch(endpointTemplate.replace('__ID__', this.value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await res.json();
            const services = payload.services || [];
            const doctors = payload.doctors || [];
            svcSel.disabled = false;
            doctorSel.disabled = false;
            setOptions(svcSel, services.length ? 'Optional services to link/bill' : 'No consultation services available', services, function (s) { return s.name; });
            setOptions(doctorSel, doctors.length ? 'Optional doctor' : 'No doctor linked through specialty', doctors, function (d) { return d.name; });
        } catch (error) {
            svcSel.disabled = true;
            doctorSel.disabled = true;
            svcSel.innerHTML = '<option value="">Unable to load services</option>';
            doctorSel.innerHTML = '<option value="">Unable to load doctors</option>';
        }
    });
}

document.addEventListener('DOMContentLoaded', initSendSessionPicker);

document.addEventListener('DOMContentLoaded', () => {
    const routeId = window.currentConsultationRouteId;
    if (!routeId) return;
    document.querySelectorAll('form[action*="/consultations/{{ $visit->id }}"]').forEach(form => {
        if (form.querySelector('input[name="consultation_route_id"]')) return;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'consultation_route_id';
        input.value = routeId;
        form.appendChild(input);
    });
});

document.addEventListener('click', (e) => {
    const openBtn = e.target.closest('[data-bs-target="#sendSessionModal"]');
    if (openBtn && openSendSessionModalFallback()) {
        e.preventDefault();
        initSendSessionPicker();
        return;
    }

    if (e.target.closest('#sendSessionModal [data-bs-dismiss="modal"]') || e.target.matches('#sendSessionModal')) {
        if (!(window.bootstrap && window.bootstrap.Modal)) {
            e.preventDefault();
            closeSendSessionModalFallback();
        }
    }
});
@if(!$canEdit)
document.addEventListener('DOMContentLoaded', () => {
    // Disable all clinical entry forms until consultation is started
    document.querySelectorAll('[data-ajax-form]').forEach(form => {
        form.querySelectorAll('input, select, textarea, button').forEach(el => { el.disabled = true; });
        form.classList.add('opacity-50');
    });
    // Disable Add toggles (buttons that open clinical-entry collapses/modals)
    document.querySelectorAll('button[data-bs-target^="#add"], button[data-bs-target="#investigationModal"], button[data-bs-target="#sendSessionModal"], button[data-bs-target="#savePatternModal"]').forEach(b => {
        b.disabled = true; b.classList.add('disabled');
    });
});
@endif

/* View Investigation Result modal loader */
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.viewResultBtn');
    if (!btn) return;
    const modalEl = document.getElementById('viewResultModal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const body  = document.getElementById('viewResultBody');
    body.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border"></div></div>';
    modal.show();
    try {
        const r = await fetch(btn.dataset.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        body.innerHTML = await r.text();
    } catch (err) {
        body.innerHTML = '<div class="alert alert-danger">' + err.message + '</div>';
    }
});

var visitHistoryData = window.visitHistoryData;
var csrfToken = window.csrfToken;
var tabStorageKey = window.tabStorageKey;
var destroyUrls = window.destroyUrls;
var diagnosisBaseUrl = window.diagnosisBaseUrl;
var deptServicesBase = window.deptServicesBase;
var procedureDeptServicesBase = window.procedureDeptServicesBase;
var prescriptionDestroyBase = window.prescriptionDestroyBase;

/* ================================================================
   TAB PERSISTENCE
   ================================================================ */
(function () {
    var saved = window.location.hash || localStorage.getItem(tabStorageKey);
    if (saved) activateConsultationTab(saved);

    document.querySelectorAll('#consultationTabs .nav-link').forEach(function (link) {
        link.addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem(tabStorageKey, e.target.getAttribute('href'));
        });
    });
}());

function activateConsultationTab(tabSelector) {
    if (!tabSelector) return false;
    localStorage.setItem(tabStorageKey, tabSelector);
    var el = document.querySelector('#consultationTabs .nav-link[href="' + tabSelector + '"]');
    if (el) {
        new bootstrap.Tab(el).show();
        return true;
    }
    return false;
}

function saveTabBeforeSubmit(tabId) {
    activateConsultationTab('#' + tabId);
    return true;
}

/* ================================================================
   UTILITIES
   ================================================================ */
function escapeHtml(s) {
    if (s == null) return '';
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
}
function capFirst(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

function showToast(msg, type) {
    var t = document.createElement('div');
    t.className = 'alert alert-' + (type || 'success') + ' position-fixed bottom-0 end-0 m-3 shadow';
    t.style.cssText = 'z-index:9999;max-width:280px;font-size:.84rem;';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 3000);
}

/* ================================================================
   AJAX DELETE
   ================================================================ */
function bindDeleteButtons() {
    document.querySelectorAll('.ajax-delete:not([data-bound])').forEach(function (btn) {
        btn.setAttribute('data-bound', '1');
        btn.addEventListener('click', function () {
            if (!confirm(this.dataset.confirm || 'Remove this item?')) return;
            var url    = this.dataset.url;
            var target = this.dataset.target;
            var badge  = this.dataset.badge;
            var self   = this;
            self.disabled = true;

            var fd = new FormData();
            fd.append('_method', 'DELETE');
            fd.append('_token', csrfToken);

            fetch(url, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    var el = document.querySelector(target);
                    if (el) el.remove();
                    if (badge) {
                        var b = document.getElementById(badge);
                        if (b) b.textContent = Math.max(0, parseInt(b.textContent || 0) - 1);
                    }
                }
            })
            .catch(function () { alert('Delete failed. Please try again.'); self.disabled = false; });
        });
    });
}
bindDeleteButtons();

/* ================================================================
   AJAX FORM SUBMISSIONS (complaints, diagnoses, investigations, treatments)
   ================================================================ */
document.querySelectorAll('[data-ajax-form]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var section  = form.dataset.ajaxForm;
        localStorage.setItem(tabStorageKey, '#' + section + '-section');
        var btn      = form.querySelector('[type="submit"]');
        var origHtml = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) {
            if (!r.ok) return r.json().then(function (e) { throw e; });
            return r.json();
        })
        .then(function (data) {
            if (data.success) {
                onFormSuccess(section, data, form);
                showToast('Saved successfully.');
            }
        })
        .catch(function (err) {
            var msg = 'An error occurred.';
            if (err && err.errors) msg = Object.values(err.errors).flat().join('\n');
            else if (err && err.message) msg = err.message;
            // Inline error display for prescription form (better UX than alert)
            if (section === 'prescriptions') {
                var errBox = document.getElementById('prescriptionFormErrors');
                if (errBox) {
                    errBox.classList.remove('d-none');
                    errBox.innerHTML = '<i class="ti ti-alert-circle me-1"></i>' + escapeHtml(msg).replace(/\n/g, '<br>');
                    errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    return;
                }
            }
            alert(msg);
        })
        .finally(function () { if (btn) { btn.disabled = false; btn.innerHTML = origHtml; } });
    });
});

function onFormSuccess(section, data, form) {
    var listEl  = document.getElementById(section + '-list');
    var emptyEl = document.getElementById(section + '-empty');
    var badge   = document.getElementById('badge-' + section);
    var html    = '';
    var count   = 0;

    if (section === 'complaints' && data.complaint) {
        var c   = data.complaint;
        var sev = c.severity || 'mild';
        var sevBadge = c.severity
            ? '<span class="badge bg-' + (sev === 'severe' ? 'danger' : sev === 'moderate' ? 'warning' : 'info') + '">' + capFirst(sev) + '</span>'
            : '';
        html  = '<div class="ehr-item severity-' + escapeHtml(sev) + '" id="complaint-' + c.id + '">';
        html += '<div class="d-flex justify-content-between">';
        html += '<div><p class="mb-1">' + escapeHtml(c.description) + '</p>';
        html += '<small class="text-muted">' + (c.duration ? 'Duration: ' + escapeHtml(c.duration) + ' &middot; ' : '') + (c.severity ? 'Severity: ' + sevBadge : '') + '</small></div>';
        html += '<button type="button" class="btn btn-xs btn-outline-danger ajax-delete" data-url="' + destroyUrls.complaint + '/' + c.id + '" data-target="#complaint-' + c.id + '" data-badge="badge-complaints" data-confirm="Remove this complaint?"><i class="ti ti-trash"></i></button>';
        html += '</div></div>';
        count = 1;

    } else if (section === 'hopc' && data.hopc) {
        var h = data.hopc;
        html = '<div class="ehr-item" id="hopc-' + h.id + '">';
        html += '<div class="d-flex justify-content-between gap-2">';
        html += '<div><p class="mb-1">' + escapeHtml(h.content || '') + '</p>';
        html += '<small class="text-muted">Entered by: ' + escapeHtml(h.creator?.full_name || h.doctor?.full_name || 'Unknown user') + '</small></div>';
        html += '<button type="button" class="btn btn-xs btn-outline-danger ajax-delete" data-url="' + destroyUrls.hopc + '/' + h.id + '" data-target="#hopc-' + h.id + '" data-badge="badge-hopc" data-confirm="Remove this history entry?"><i class="ti ti-trash"></i></button>';
        html += '</div></div>';
        count = 1;

    } else if (section === 'examination' && data.examination) {
        var ex = data.examination;
        html = '<div class="ehr-item" id="examination-' + ex.id + '">';
        html += '<div class="d-flex justify-content-between gap-2">';
        html += '<div><p class="mb-1">' + escapeHtml(ex.findings || '') + '</p>';
        html += '<small class="text-muted">Entered by: ' + escapeHtml(ex.creator?.full_name || ex.doctor?.full_name || 'Unknown user') + '</small></div>';
        html += '<button type="button" class="btn btn-xs btn-outline-danger ajax-delete" data-url="' + destroyUrls.examination + '/' + ex.id + '" data-target="#examination-' + ex.id + '" data-badge="badge-examination" data-confirm="Remove this examination entry?"><i class="ti ti-trash"></i></button>';
        html += '</div></div>';
        count = 1;

    } else if (section === 'diagnoses' && data.diagnosis) {
        var d       = data.diagnosis;
        var typeBg  = d.type === 'final' ? 'success' : 'warning';
        var pClass  = d.is_primary ? '' : ' d-none';
        html  = '<div class="ehr-item' + (d.is_primary ? ' is-primary' : '') + '" id="diagnosis-' + d.id + '">';
        html += '<div class="d-flex justify-content-between align-items-start">';
        html += '<div class="flex-grow-1"><p class="mb-1">' + escapeHtml(d.description);
        html += ' <span class="badge bg-' + typeBg + ' ms-1 diagnosis-type-badge" id="type-badge-' + d.id + '">' + capFirst(d.type) + '</span>';
        html += ' <span class="badge bg-warning text-dark ms-1 diagnosis-primary-badge primary-indicator' + pClass + '" id="primary-badge-' + d.id + '"><i class="ti ti-star-filled me-1"></i>Primary</span>';
        html += '</p></div>';
        html += '<div class="d-flex gap-1 ms-2 flex-shrink-0">';
        html += '<button type="button" class="btn btn-xs btn-outline-secondary toggle-type-btn" title="Mark as ' + (d.type === 'provisional' ? 'Final' : 'Provisional') + '" data-id="' + d.id + '" data-current="' + escapeHtml(d.type) + '" data-url="' + diagnosisBaseUrl + '/' + d.id + '"><i class="ti ti-switch-2 me-1"></i><span class="toggle-type-label">' + (d.type === 'provisional' ? 'Final' : 'Provisional') + '</span></button>';
        if (!d.is_primary) {
            html += '<button type="button" class="btn btn-xs btn-outline-warning set-primary-btn" title="Set as Primary" id="set-primary-' + d.id + '" data-id="' + d.id + '" data-url="' + diagnosisBaseUrl + '/' + d.id + '/primary"><i class="ti ti-star"></i></button>';
        }
        html += '<button type="button" class="btn btn-xs btn-outline-danger ajax-delete" data-url="' + destroyUrls.diagnosis + '/' + d.id + '" data-target="#diagnosis-' + d.id + '" data-badge="badge-diagnoses" data-confirm="Remove this diagnosis?"><i class="ti ti-trash"></i></button>';
        html += '</div></div></div>';
        count = 1;

    } else if (section === 'investigations') {
        var invs = data.investigations || (data.investigation ? [data.investigation] : []);
        invs.forEach(function (inv) {
            var uc = inv.urgency === 'emergency' ? 'danger' : inv.urgency === 'urgent' ? 'warning' : 'secondary';
            html += '<div class="ehr-item" id="investigation-' + inv.id + '">';
            html += '<div class="d-flex justify-content-between">';
            html += '<div><p class="mb-1"><span class="badge bg-dark">' + escapeHtml(inv.investigation_type) + '</span>';
            if (inv.description && inv.description !== inv.investigation_type) html += ' ' + escapeHtml(inv.description);
            html += '</p><small class="text-muted">Urgency: <span class="badge bg-' + uc + '">' + capFirst(inv.urgency || 'routine') + '</span></small></div>';
            html += '<button type="button" class="btn btn-xs btn-outline-danger ajax-delete" data-url="' + destroyUrls.investigation + '/' + inv.id + '" data-target="#investigation-' + inv.id + '" data-badge="badge-investigations" data-confirm="Remove this investigation?"><i class="ti ti-trash"></i></button>';
            html += '</div></div>';
        });
        count = invs.length;

    } else if (section === 'treatments' && data.treatment) {
        var t  = data.treatment;
        var tc = t.type === 'medication' ? 'primary' : t.type === 'procedure' ? 'info' : t.type === 'referral' ? 'warning' : 'secondary';
        html  = '<div class="ehr-item" id="treatment-' + t.id + '">';
        html += '<div class="d-flex justify-content-between">';
        html += '<div><p class="mb-1"><span class="badge bg-' + tc + '">' + capFirst(t.type) + '</span> ' + escapeHtml(t.description) + '</p></div>';
        html += '<button type="button" class="btn btn-xs btn-outline-danger ajax-delete" data-url="' + destroyUrls.treatment + '/' + t.id + '" data-target="#treatment-' + t.id + '" data-badge="badge-treatments" data-confirm="Remove this treatment?"><i class="ti ti-trash"></i></button>';
        html += '</div></div>';
        count = 1;
    } else if (section === 'prescriptions' && data.prescription) {
        var rx = data.prescription;
        var statusColor = rx.status === 'pending' ? 'warning' : (rx.status === 'active' ? 'info' : (rx.status === 'dispensed' ? 'success' : 'secondary'));
        var statusLabel = capFirst(String(rx.status || 'pending').replace('_', ' '));
        html  = '<div class="border rounded p-3 mb-3" id="prescription-' + rx.id + '">';
        html += '<div class="d-flex justify-content-between align-items-center mb-2">';
        html += '<div><span class="fw-bold">' + escapeHtml(rx.prescription_number) + '</span>';
        html += ' <span class="badge bg-' + statusColor + ' ms-2">' + statusLabel + '</span></div>';
        html += '<small class="text-muted">just now</small></div>';
        html += '<div class="table-responsive"><table class="table table-sm table-borderless mb-0">';
        html += '<thead><tr class="text-muted small"><th>Drug</th><th>Dosage</th><th>Freq</th><th>Duration</th><th>Qty</th><th>Route</th></tr></thead><tbody>';
        (rx.items || []).forEach(function (it) {
            html += '<tr><td class="fw-medium">' + escapeHtml(it.drug_name) + '</td>';
            html += '<td>' + escapeHtml(it.dosage || '') + '</td>';
            html += '<td>' + escapeHtml(it.frequency || '') + '</td>';
            html += '<td>' + escapeHtml(it.duration || '') + '</td>';
            html += '<td>' + escapeHtml(String(it.quantity ?? '')) + '</td>';
            html += '<td>' + escapeHtml(it.route || '') + '</td></tr>';
        });
        html += '</tbody></table></div>';
        if (rx.notes) html += '<small class="text-muted">Notes: ' + escapeHtml(rx.notes) + '</small>';
        html += '</div>';
        // Hide form errors box on success
        var errBox = document.getElementById('prescriptionFormErrors');
        if (errBox) { errBox.classList.add('d-none'); errBox.innerHTML = ''; }
        count = 1;
    }

    if (html && listEl) {
        listEl.insertAdjacentHTML('beforeend', html);
        if (emptyEl) emptyEl.style.display = 'none';
        if (badge) badge.textContent = parseInt(badge.textContent || 0) + count;
        bindDeleteButtons();
        bindDiagnosisButtons();
    }

    // Reset form
    form.reset();
    var col = form.closest('.collapse');
    if (col) { var bs = bootstrap.Collapse.getInstance(col); if (bs) bs.hide(); }

    // Reset investigation dept/services
    var ds = document.getElementById('investigationDeptSelect');
    if (ds) {
        ds.value = '';
        var sc = document.getElementById('investigationServicesContainer');
        if (sc) sc.innerHTML = '<span class="text-muted small">Select a department first to load services</span>';
    }
}

/* ================================================================
   DIAGNOSIS — TYPE TOGGLE & SET PRIMARY
   ================================================================ */
function bindDiagnosisButtons() {
    document.querySelectorAll('.toggle-type-btn:not([data-bound])').forEach(function (btn) {
        btn.setAttribute('data-bound', '1');
        btn.addEventListener('click', function () {
            var id      = this.dataset.id;
            var current = this.dataset.current;
            var newType = current === 'provisional' ? 'final' : 'provisional';
            var self    = this;
            if (!confirm('Change type to ' + capFirst(newType) + '?')) return;
            self.disabled = true;

            var fd = new FormData();
            fd.append('_method', 'PATCH'); fd.append('_token', csrfToken); fd.append('type', newType);

            fetch(this.dataset.url, {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    var badge = document.getElementById('type-badge-' + id);
                    if (badge) {
                        badge.textContent = capFirst(newType);
                        badge.className = 'badge bg-' + (newType === 'final' ? 'success' : 'warning') + ' ms-1 diagnosis-type-badge';
                    }
                    self.dataset.current = newType;
                    self.title = 'Mark as ' + (newType === 'provisional' ? 'Final' : 'Provisional');
                    var label = self.querySelector('.toggle-type-label');
                    if (label) label.textContent = newType === 'provisional' ? 'Final' : 'Provisional';
                    showToast('Type set to ' + capFirst(newType) + '.');
                }
            })
            .catch(function () { alert('Failed to update type.'); })
            .finally(function () { self.disabled = false; });
        });
    });

    document.querySelectorAll('.set-primary-btn:not([data-bound])').forEach(function (btn) {
        btn.setAttribute('data-bound', '1');
        btn.addEventListener('click', function () {
            var id   = this.dataset.id;
            var self = this;
            self.disabled = true;

            var fd = new FormData();
            fd.append('_method', 'PATCH'); fd.append('_token', csrfToken);

            fetch(this.dataset.url, {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    // Clear all primary markers
                    document.querySelectorAll('.primary-indicator').forEach(function (b) { b.classList.add('d-none'); });
                    document.querySelectorAll('.set-primary-btn').forEach(function (b) { b.classList.remove('d-none'); });
                    document.querySelectorAll('#diagnoses-list .ehr-item').forEach(function (el) { el.classList.remove('is-primary'); });
                    // Apply to this diagnosis
                    var pb = document.getElementById('primary-badge-' + id);
                    if (pb) pb.classList.remove('d-none');
                    var sp = document.getElementById('set-primary-' + id);
                    if (sp) sp.classList.add('d-none');
                    var de = document.getElementById('diagnosis-' + id);
                    if (de) de.classList.add('is-primary');
                    showToast('Primary diagnosis updated.');
                }
            })
            .catch(function () { alert('Failed to set primary diagnosis.'); })
            .finally(function () { self.disabled = false; });
        });
    });
}
bindDiagnosisButtons();

/* ================================================================
   INVESTIGATION DEPARTMENT → SERVICES
   ================================================================ */
function loadInvestigationServices(deptId) {
    var container = document.getElementById('investigationServicesContainer');
    if (!container) return;
    if (!deptId) { container.innerHTML = '<span class="text-muted small">Select a department first to load services</span>'; return; }
    container.innerHTML = '<div class="py-2 text-center"><span class="spinner-border spinner-border-sm text-primary"></span> Loading...</div>';

    fetch(deptServicesBase + '/' + deptId + '/investigation-services', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (services) {
        if (!services.length) { container.innerHTML = '<span class="text-muted small">No active services found.</span>'; return; }
        var html = '<div class="row g-1">';
        services.forEach(function (s) {
            html += '<div class="col-md-6"><div class="form-check">';
            html += '<input type="checkbox" name="service_ids[]" value="' + s.id + '" class="form-check-input" id="svc' + s.id + '">';
            html += '<label class="form-check-label small" for="svc' + s.id + '">' + escapeHtml(s.name);
            if (s.price) html += ' <span class="text-muted small">GH₵' + parseFloat(s.price).toFixed(2) + '</span>';
            html += '</label></div></div>';
        });
        html += '</div>';
        container.innerHTML = html;
    })
    .catch(function () { container.innerHTML = '<span class="text-danger small">Failed to load services.</span>'; });
}

/* ================================================================
   PROCEDURE DEPARTMENT → SERVICES (Theatre / Procedure Request)
   ================================================================ */
function loadProcedureServices(deptId) {
    var svc = document.getElementById('procedureServiceSelect');
    if (!svc) return;
    if (!deptId) {
        svc.innerHTML = '<option value="">Select department first</option>';
        svc.disabled = true;
        return;
    }
    svc.innerHTML = '<option value="">Loading…</option>';
    svc.disabled = true;

    fetch(procedureDeptServicesBase + '/' + deptId + '/services', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (r) {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(function (items) {
        if (!items || items.length === 0) {
            svc.innerHTML = '<option value="">No procedure services found for this department</option>';
            svc.disabled = true;
            return;
        }
        svc.innerHTML = '<option value="">-- Select service --</option>';
        items.forEach(function (it) {
            var opt = document.createElement('option');
            opt.value = it.id;
            var price = it.price || it.selling_price;
            opt.textContent = it.name + (price ? (' — GH₵' + parseFloat(price).toFixed(2)) : '');
            svc.appendChild(opt);
        });
        svc.disabled = false;
    })
    .catch(function () {
        svc.innerHTML = '<option value="">Failed to load services</option>';
        svc.disabled = true;
    });
}

/* ================================================================
   LAB REQUEST — AJAX FORM SUBMISSION
   ================================================================ */
(function () {
    var form = document.getElementById('labRequestForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var errBox = document.getElementById('labReqErrors');
        var btn    = document.getElementById('labReqSubmitBtn');
        var orig   = btn ? btn.innerHTML : '';

        if (errBox) errBox.classList.add('d-none');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...'; }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) {
            if (!r.ok) return r.json().then(function (body) { throw body; });
            return r.json();
        })
        .then(function (data) {
            if (data.success) {
                // Close modal and show success
                var modal = bootstrap.Modal.getInstance(document.getElementById('investigationModal'));
                if (modal) modal.hide();
                showToast('Investigation request sent successfully.');
                // Reset form for next use
                form.reset();
                document.getElementById('labReqItemsContainer').classList.add('d-none');
            }
        })
        .catch(function (err) {
            var msg = 'Failed to send request.';
            if (err && err.errors) {
                msg = Object.values(err.errors).flat().join('<br>');
            } else if (err && err.message) {
                msg = err.message;
            }
            if (errBox) { errBox.innerHTML = msg; errBox.classList.remove('d-none'); }
            else alert(msg);
        })
        .finally(function () { if (btn) { btn.disabled = false; btn.innerHTML = orig; } });
    });
})();


function loadLabReqItems(deptId) {
    var container = document.getElementById('labReqItemsContainer');
    var body      = document.getElementById('labReqItemsBody');
    var label     = document.getElementById('labReqItemsLabel');
    if (!container || !body) return;

    if (!deptId) { container.classList.add('d-none'); return; }
    container.classList.remove('d-none');
    body.innerHTML = '<span class="text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Loading items...</span>';

    fetch(deptServicesBase + '/' + deptId + '/investigation-info', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (label) label.textContent = 'Items (' + data.label + ') *';

        if (data.uses_catalog) {
            // Show lab test checkboxes
            if (!data.lab_tests.length) { body.innerHTML = '<span class="text-warning small">No active lab tests configured.</span>'; return; }
            var html = '<div class="row g-1" style="max-height:250px;overflow-y:auto;">';
            data.lab_tests.forEach(function (t) {
                html += '<div class="col-md-6"><div class="form-check">';
                html += '<input type="checkbox" name="items[]" value="' + t.id + '" class="form-check-input" id="lt' + t.id + '">';
                html += '<label class="form-check-label small" for="lt' + t.id + '">' + escapeHtml(t.name);
                if (t.code) html += ' <span class="text-muted">(' + escapeHtml(t.code) + ')</span>';
                if (t.criteria && t.criteria.length) {
                    html += '<br><span class="text-muted small">' + t.criteria.map(function (c) {
                        return escapeHtml(c.name) + (c.normal_range ? ': ' + escapeHtml(c.normal_range) : '') + (c.unit ? ' ' + escapeHtml(c.unit) : '');
                    }).join(' &middot; ') + '</span>';
                }
                html += '</label></div></div>';
            });
            html += '</div>';
            body.innerHTML = html;
        } else {
            // Show free-text item rows
            body.innerHTML = '<div id="labReqFreeItems">' + freeTextItemRow(0) + '</div>' +
                '<button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addFreeTextItem()"><i class="ti ti-plus me-1"></i>Add Item</button>';
        }
    })
    .catch(function () { body.innerHTML = '<span class="text-danger small">Failed to load items.</span>'; });
}

var _freeItemIdx = 0;
function freeTextItemRow(idx) {
    return '<div class="input-group mb-1" id="freeItem' + idx + '">' +
        '<span class="input-group-text"><i class="ti ti-point"></i></span>' +
        '<input type="text" name="items[]" class="form-control" placeholder="e.g. Chest X-Ray, Abdominal Scan..." required>' +
        (idx > 0 ? '<button type="button" class="btn btn-outline-danger" onclick="document.getElementById(\'freeItem' + idx + '\').remove()"><i class="ti ti-x"></i></button>' : '') +
        '</div>';
}
function addFreeTextItem() {
    _freeItemIdx++;
    var ct = document.getElementById('labReqFreeItems');
    if (ct) ct.insertAdjacentHTML('beforeend', freeTextItemRow(_freeItemIdx));
}

/* ================================================================
   INVESTIGATION ROUTE — AJAX FORM SUBMISSION
   ================================================================ */
(function () {
    var form = document.getElementById('routeInvestigationForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var errBox = document.getElementById('investRouteErrors');
        var btn = document.getElementById('investRouteSubmitBtn');
        var original = btn ? btn.innerHTML : '';

        if (errBox) {
            errBox.classList.add('d-none');
            errBox.innerHTML = '';
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Routing...';
        }

        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) {
            if (!r.ok) return r.json().then(function (body) { throw body; });
            return r.json();
        })
        .then(function (data) {
            if (!data.success) return;

            localStorage.setItem(tabStorageKey, '#investigations-section');

            var modal = bootstrap.Modal.getInstance(document.getElementById('investigationModal'));
            if (modal) modal.hide();

            form.reset();
            showToast(data.message || 'Patient routed successfully.');
        })
        .catch(function (err) {
            var msg = 'Failed to route patient.';
            if (err && err.errors) {
                msg = Object.values(err.errors).flat().join('<br>');
            } else if (err && err.message) {
                msg = err.message;
            }

            if (errBox) {
                errBox.innerHTML = msg;
                errBox.classList.remove('d-none');
            } else {
                alert(msg);
            }
        })
        .finally(function () {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = original;
            }
        });
    });
})();

/* ================================================================
   PRESCRIPTIONS — DYNAMIC DRUG ROWS
   ================================================================ */
function initDrugSelect(select) {
    $(select).select2({
        theme: 'default',
        width: '100%',
        placeholder: '-- Search drug --',
        allowClear: true,
    });
}

function syncDrugName(row) {
    var drugSel = row.querySelector('.drug-select');
    var hidden = row.querySelector('.drug-name-input');
    if (!drugSel || !hidden) return;

    var selected = drugSel.options[drugSel.selectedIndex];
    hidden.value = selected && selected.value ? (selected.getAttribute('data-name') || selected.textContent.trim()) : '';
}

$('.drug-select').each(function () { initDrugSelect(this); });

/* ----------------------------------------------------------------
   AUTO-CALCULATE QUANTITY
   Formula: (dosage_mg / strength_mg) × doses_per_day × duration_days
   If strength can't be parsed, falls back to: doses_per_day × days
   ---------------------------------------------------------------- */
var freqMap = { OD:1, BD:2, TDS:3, QDS:4, STAT:1, PRN:1 };

function parseMg(str) {
    if (!str) return null;
    var m = String(str).match(/([\d.]+)\s*(mg|mcg|g|ml|iu|units?)?/i);
    if (!m) return null;
    var val = parseFloat(m[1]);
    var unit = (m[2] || 'mg').toLowerCase();
    if (unit === 'g') val *= 1000;
    if (unit === 'mcg') val /= 1000;
    return isNaN(val) ? null : val;
}

function parseDays(str) {
    if (!str) return null;
    str = String(str).toLowerCase().trim();
    // "5 days", "1 week", "2 weeks", "3 months", plain number
    var m = str.match(/^(\d+(?:\.\d+)?)\s*(day|days|week|weeks|month|months|wk|wks)?/);
    if (!m) return null;
    var n = parseFloat(m[1]);
    var u = m[2] || 'day';
    if (u.startsWith('week') || u === 'wk' || u === 'wks') n *= 7;
    if (u.startsWith('month')) n *= 30;
    return isNaN(n) ? null : Math.round(n);
}

function calcQty(row) {
    var drugSel   = row.querySelector('.drug-select');
    var dosageInp = row.querySelector('[name$="[dosage]"]');
    var freqSel   = row.querySelector('[name$="[frequency]"]');
    var durInp    = row.querySelector('[name$="[duration]"]');
    var qtyInp    = row.querySelector('[name$="[quantity]"]');
    if (!drugSel || !dosageInp || !freqSel || !durInp || !qtyInp) return;

    var selOpt    = drugSel.options[drugSel.selectedIndex];
    var strength  = selOpt ? selOpt.getAttribute('data-strength') : null;
    var dosage    = dosageInp.value.trim();
    var freq      = freqSel.value;
    var dur       = durInp.value.trim();

    var daysVal   = parseDays(dur);
    var freqVal   = freqMap[freq] || 1;

    if (!daysVal) return; // can't compute without duration

    var tabletsPerDose = 1;
    var dMg = parseMg(dosage);
    var sMg = parseMg(strength);
    if (dMg && sMg && sMg > 0) {
        tabletsPerDose = Math.ceil(dMg / sMg);
    }

    var total = tabletsPerDose * freqVal * daysVal;
    if (freq === 'STAT') total = tabletsPerDose; // one-off
    if (total > 0) {
        qtyInp.value = total;
        qtyInp.style.background = '#fffbe6'; // subtle highlight
        setTimeout(function(){ qtyInp.style.background = ''; }, 1200);
    }
}

function bindRxCalc(row) {
    ['change','input'].forEach(function(evt) {
        row.querySelector('[name$="[dosage]"]')?.addEventListener(evt, function(){ calcQty(row); });
        row.querySelector('[name$="[duration]"]')?.addEventListener(evt, function(){ calcQty(row); });
    });
    row.querySelector('[name$="[frequency]"]')?.addEventListener('change', function(){ calcQty(row); });
    // Select2 fires a jQuery event
    $(row).find('.drug-select').on('select2:select select2:clear change', function(){ syncDrugName(row); calcQty(row); });
}

/* Bind on the first (pre-rendered) row */
(function(){
    var firstRow = document.querySelector('#prescriptionItems .prescription-item');
    if (firstRow) bindRxCalc(firstRow);
})();

var rxIdx = 1;
document.getElementById('addItemBtn')?.addEventListener('click', function () {
    var cont = document.getElementById('prescriptionItems');
    var tpl  = cont.querySelector('.prescription-item').cloneNode(true);

    tpl.querySelectorAll('.select2-container').forEach(function (container) { container.remove(); });

    tpl.querySelectorAll('[name]').forEach(function (inp) {
        inp.name = inp.name.replace(/items\[\d+\]/, 'items[' + rxIdx + ']');
        if (inp.tagName === 'INPUT') inp.value = inp.type === 'number' ? '1' : '';
        if (inp.tagName === 'SELECT' && inp.classList.contains('drug-select')) {
            inp.value = '';
            inp.classList.remove('select2-hidden-accessible');
            inp.removeAttribute('data-select2-id');
            inp.removeAttribute('aria-hidden');
            inp.removeAttribute('tabindex');
        }
    });
    tpl.querySelectorAll('option[data-select2-id]').forEach(function (opt) { opt.removeAttribute('data-select2-id'); });
    tpl.style.position = 'relative';
    var rm = document.createElement('button');
    rm.type = 'button'; rm.className = 'btn btn-xs btn-outline-danger position-absolute top-0 end-0 m-1';
    rm.innerHTML = '<i class="ti ti-x"></i>'; rm.onclick = function () { tpl.remove(); reindexPrescriptionRows(); };
    tpl.appendChild(rm);
    cont.appendChild(tpl);
    /* Init Select2 on the new drug dropdown */
    $(tpl).find('.drug-select').each(function () { initDrugSelect(this); });
    /* Bind auto-calc on the new row */
    bindRxCalc(tpl);
    rxIdx++;
});

function reindexPrescriptionRows() {
    document.querySelectorAll('#prescriptionItems .prescription-item').forEach(function (row, index) {
        row.querySelectorAll('[name]').forEach(function (field) {
            field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
        });
        syncDrugName(row);
    });
    rxIdx = document.querySelectorAll('#prescriptionItems .prescription-item').length;
}

function preparePrescriptionSubmit() {
    document.querySelectorAll('#prescriptionItems .prescription-item').forEach(function (row) {
        syncDrugName(row);
        calcQty(row);
    });
    reindexPrescriptionRows();
    return true;
}

/* ================================================================
   PREVIOUS VISIT PREVIEW MODAL
   ================================================================ */
function previewVisit(index) {
    var vd = visitHistoryData[index];
    if (!vd) return;
    var html = '<p class="mb-3"><span class="fw-bold fs-6">' + escapeHtml(vd.visit_number) + '</span>'
        + ' <span class="text-muted">' + escapeHtml(vd.date) + '</span>'
        + (vd.doctor ? ' &middot; Dr. ' + escapeHtml(vd.doctor) : '') + '</p>';

    if (vd.complaints && vd.complaints.length) {
        html += '<h6 class="fw-bold small text-muted border-bottom pb-1 mb-2">Complaints</h6>';
        vd.complaints.forEach(function (c) { html += '<div class="ehr-item py-1">' + escapeHtml(c) + '</div>'; });
    }
    if (vd.diagnoses && vd.diagnoses.length) {
        html += '<h6 class="fw-bold small text-muted border-bottom pb-1 mb-2 mt-3">Diagnoses</h6>';
        vd.diagnoses.forEach(function (d) {
            html += '<div class="ehr-item py-1">' + escapeHtml(d.description);
            html += ' <span class="badge bg-' + (d.type === 'final' ? 'success' : 'warning') + '">' + capFirst(d.type) + '</span>';
            if (d.is_primary) html += ' <span class="badge bg-warning text-dark"><i class="ti ti-star-filled me-1"></i>Primary</span>';
            if (d.icd_code) html += ' <code class="ms-1">' + escapeHtml(d.icd_code) + '</code>';
            html += '</div>';
        });
    }
    if (vd.investigations && vd.investigations.length) {
        html += '<h6 class="fw-bold small text-muted border-bottom pb-1 mb-2 mt-3">Investigations</h6>';
        vd.investigations.forEach(function (i) {
            var ug = i.urgency === 'emergency' ? 'danger' : i.urgency === 'urgent' ? 'warning' : 'secondary';
            html += '<div class="ehr-item py-1"><span class="badge bg-dark">' + escapeHtml(i.type) + '</span>';
            if (i.description && i.description !== i.type) html += ' ' + escapeHtml(i.description);
            html += ' <span class="badge bg-' + ug + '">' + capFirst(i.urgency || 'routine') + '</span></div>';
        });
    }
    if (vd.treatments && vd.treatments.length) {
        html += '<h6 class="fw-bold small text-muted border-bottom pb-1 mb-2 mt-3">Treatments</h6>';
        vd.treatments.forEach(function (t) {
            var tc = t.type === 'medication' ? 'primary' : t.type === 'procedure' ? 'info' : t.type === 'referral' ? 'warning' : 'secondary';
            html += '<div class="ehr-item py-1"><span class="badge bg-' + tc + '">' + capFirst(t.type) + '</span> ' + escapeHtml(t.description) + '</div>';
        });
    }
    if (!vd.complaints.length && !vd.diagnoses.length && !vd.investigations.length && !vd.treatments.length) {
        html = '<div class="text-center text-muted py-3">No clinical data recorded for this visit.</div>';
    }

    document.getElementById('visitPreviewContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('visitPreviewModal')).show();
}

/* ================================================================
   PATTERN SEARCH & APPLY
   ================================================================ */
function bindApplyButtons() {
    document.querySelectorAll('.apply-pattern-btn:not([data-bound])').forEach(function (btn) {
        btn.setAttribute('data-bound', '1');
        btn.addEventListener('click', function () {
            var pid  = this.dataset.patternId;
            var pnm  = this.dataset.patternName;
            var available = (this.dataset.patternTypes || '').split(',').filter(Boolean);
            var self = this;
            if (!confirm('Apply pattern "' + pnm + '"?')) return;
            var sectionInput = prompt('Sections to apply (comma separated). Leave as-is to apply all shown sections.', available.join(','));
            if (sectionInput === null) return;
            var selectedSections = sectionInput.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            self.disabled = true; self.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            fetch('{{ url("admin/patterns") }}/' + pid + '/apply', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json',
                    'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    visit_id: {{ $visit->id }},
                    consultation_route_id: window.currentConsultationRouteId,
                    sections: selectedSections
                })
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    saveTabBeforeSubmit('patterns-section');
                    if (window.UhmsInertia) {
                        window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
                    } else {
                        window.location.reload();
                    }
                }
                else { alert('Failed to apply pattern.'); self.disabled = false; self.innerHTML = '<i class="ti ti-check me-1"></i>Apply'; }
            })
            .catch(function () { alert('Failed.'); self.disabled = false; self.innerHTML = '<i class="ti ti-check me-1"></i>Apply'; });
        });
    });
}
bindApplyButtons();

var psBtn = document.getElementById('patternSearchBtn');
var psInp = document.getElementById('patternSearchInput');
var psRes = document.getElementById('patternSearchResults');
if (psBtn) {
    psBtn.addEventListener('click', function () {
        var q = psInp.value.trim();
        if (q.length < 3) { psRes.innerHTML = '<div class="alert alert-warning py-2">Enter at least 3 characters.</div>'; psRes.style.display = 'block'; return; }
        psRes.innerHTML = '<div class="text-center py-2"><span class="spinner-border spinner-border-sm text-primary"></span></div>';
        psRes.style.display = 'block';
        fetch('{{ route("admin.patterns.suggest") }}?query=' + encodeURIComponent(q), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.patterns && d.patterns.length) {
                var h = '';
                d.patterns.forEach(function (p) {
                    h += '<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">';
                    h += '<div><strong>' + escapeHtml(p.name) + '</strong> <small class="text-muted">(' + p.items.length + ' items)</small></div>';
                    var patternTypes = (p.items || []).map(function (it) { return it.type; }).filter(function (value, index, arr) { return arr.indexOf(value) === index; }).join(',');
                    h += '<button type="button" class="btn btn-sm btn-success apply-pattern-btn" data-pattern-id="' + p.id + '" data-pattern-name="' + escapeHtml(p.name) + '" data-pattern-types="' + escapeHtml(patternTypes) + '"><i class="ti ti-check me-1"></i>Apply</button>';
                    h += '</div>';
                });
                psRes.innerHTML = h;
                bindApplyButtons();
            } else { psRes.innerHTML = '<div class="alert alert-info py-2 mb-0">No patterns found.</div>'; }
        })
        .catch(function () { psRes.innerHTML = '<div class="alert alert-danger py-2 mb-0">Search failed.</div>'; });
    });
    psInp.addEventListener('keypress', function (e) { if (e.key === 'Enter') { e.preventDefault(); psBtn.click(); } });
}

/* ================================================================
   COMPLAINT & DIAGNOSIS SUGGESTIONS (DB AUTOCOMPLETE)
   ================================================================ */
(function () {
    var suggestUrls = {
        complaint: '{{ route("admin.consultations.suggest.complaints") }}',
        diagnosis:  '{{ route("admin.consultations.suggest.diagnoses") }}',
    };
    var timers = {};

    function bindSuggest(inputId, datalistId, type) {
        var inp = document.getElementById(inputId);
        var dl  = document.getElementById(datalistId);
        if (!inp || !dl) return;
        inp.addEventListener('input', function () {
            var q = this.value.trim();
            clearTimeout(timers[type]);
            if (q.length < 2) { dl.innerHTML = ''; return; }
            timers[type] = setTimeout(function () {
                fetch(suggestUrls[type] + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (items) {
                    dl.innerHTML = items.map(function (s) {
                        return '<option value="' + escapeHtml(s) + '">';
                    }).join('');
                });
            }, 280);
        });
    }

    bindSuggest('complaintDescInput', 'complaintSuggestions', 'complaint');
    bindSuggest('diagnosis_description', 'diagnosisSuggestions', 'diagnosis');
}());

/* ================================================================
   ICD-10 AUTOCOMPLETE
   ================================================================ */
$(document).ready(function () {
    if ($('#icd_code_select').length && $.fn.select2) {
        $('#icd_code_select').select2({
            placeholder: 'Type to search ICD-10 codes...',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '{{ route("admin.icd-search") }}',
                dataType: 'json',
                delay: 300,
                data: function (p) { return { q: p.term }; },
                processResults: function (d) { return { results: d.results }; },
                cache: true
            },
            templateResult: function (i) {
                if (i.loading) return i.text;
                return $('<span>').html('<strong>' + escapeHtml(i.code) + '</strong> — ' + escapeHtml(i.description));
            },
            templateSelection: function (i) { return i.text || i.code; }
        }).on('select2:select', function (e) {
            var d = e.params.data;
            $('#icd_code_id').val(d.id);
            $('#icd_code_manual').val(d.code);
            var desc = $('#diagnosis_description');
            if (!desc.val().trim()) desc.val(d.description);
        }).on('select2:clear', function () {
            $('#icd_code_id').val('');
            $('#icd_code_manual').val('');
        });
    }
});

/* ================================================================
   EXPOSE TO GLOBAL SCOPE
   The Inertia legacy bridge wraps each <script> tag in its own
   function context, so top-level `function` and `var` declarations
   are NOT global. Inline event handlers (onclick / onchange /
   onsubmit) resolve identifiers against window. Map them here so
   inline handlers and other script blocks can reach them.
   ================================================================ */
(function () {
    var fns = ['saveTabBeforeSubmit','activateConsultationTab','loadInvestigationServices',
               'loadProcedureServices',
               'loadLabReqItems','addFreeTextItem','freeTextItemRow','preparePrescriptionSubmit',
               'previewVisit','escapeHtml','capFirst','showToast','reindexPrescriptionRows',
               'initDrugSelect','syncDrugName','calcQty','bindRxCalc'];
    fns.forEach(function (n) {
        try { if (typeof eval(n) === 'function') window[n] = eval(n); } catch (e) {}
    });
})();
</script>
@endpush
