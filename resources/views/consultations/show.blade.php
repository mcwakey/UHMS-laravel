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
    .owner-group-header { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 0.45rem; padding: 0.45rem 0.65rem; margin-bottom: 0.55rem; }
    .owner-group .ehr-item { margin-left: 0.45rem; }
    .entry-actions { min-width: max-content; }
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
    $ownerOf = fn ($entry) => $entry?->creator ?? $entry?->createdBy ?? $entry?->doctor ?? $entry?->requestedBy ?? $entry?->requestingDoctor ?? null;
    $ownerKey = fn ($entry) => ($ownerOf($entry)?->id) ? 'user-'.$ownerOf($entry)->id : 'unknown';
    $ownerName = fn ($entry) => $ownerOf($entry)?->full_name ?? 'Unknown user';
    $ownerDisplayName = fn ($entry) => $ownerName($entry) === 'Unknown user' ? 'Unknown user' : 'Dr. '.$ownerName($entry);
    $isMainOwner = fn ($entry) => $selectedRoute?->doctor_id && $ownerOf($entry)?->id && (int) $selectedRoute->doctor_id === (int) $ownerOf($entry)->id;
    $ownerRoleLabel = fn ($entry) => $isMainOwner($entry) ? 'Main Doctor' : 'Contributor';
    $ownerRoleClass = fn ($entry) => $isMainOwner($entry) ? 'primary' : 'secondary';
    $ownerGroups = fn ($entries) => collect($entries ?? [])->groupBy(fn ($entry) => $ownerKey($entry));
    $entryFooter = function ($entry) {
        $bits = [];
        if ($entry?->created_at) {
            $bits[] = 'Created: '.$entry->created_at->format('d M Y, h:i A');
        }
        if (($entry?->updater?->full_name ?? null) && $entry?->updated_at && $entry?->created_at && $entry->updated_at->gt($entry->created_at)) {
            $bits[] = 'Edited by: '.$entry->updater->full_name.' '.$entry->updated_at->format('d M Y, h:i A');
        }
        if ($entry?->sourcePattern) {
            $bits[] = 'Source Pattern: '.$entry->sourcePattern->name;
        }
        return implode(' · ', $bits);
    };
    $canDeleteEntry = fn ($entry) => auth()->user() && $entryPermissions->canDelete(auth()->user(), $entry);
    $canEditEntry = fn ($entry) => auth()->user() && $entryPermissions->canEdit(auth()->user(), $entry);
    $contributors = $selectedRoute?->contributors?->map(fn ($contributor) => $contributor->user?->full_name)->filter()->unique()->values() ?? collect();
    $isEmergencyRoute = $selectedRoute?->isEmergencySession() ?? false;
    $emergencyCase = $selectedRoute?->emergencyCase;
    $emergencySession = $selectedRoute?->emergencySession;
    $emergencyReadOnly = $isEmergencyRoute && in_array($emergencyCase?->emergency_status, [
        \App\Models\EmergencyCase::STATUS_DISPOSED,
        \App\Models\EmergencyCase::STATUS_CANCELLED,
    ], true);
    $selectedRouteLabel = $isEmergencyRoute
        ? 'Emergency Department Session'
        : ($selectedRoute?->department?->name ?? 'No active session');
@endphp

{{-- ============================================================ --}}
{{-- CURRENT SESSION HEADER --}}
{{-- ============================================================ --}}
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <div>
            <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-primary"></i>Current Session</h6>
            {{-- <small class="text-muted">Visit {{ $visit->visit_number }} · {{ $visit->patient->full_name }}</small> --}}
            <div class="fw-semibold ms-2">
                {{ $selectedRouteLabel }}
                @if($isEmergencyRoute)
                    <span class="badge bg-danger ms-1">Emergency</span>
                @endif
            </div>
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
            {{-- <x-status-badge :status="$visit->status" />
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

@if($isEmergencyRoute && $emergencyCase)
<div class="card mb-3 border-danger-subtle">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-0"><i class="ti ti-urgent me-1 text-danger"></i>Emergency Department Session</h6>
            <small class="text-muted">{{ $emergencyCase->emergency_number }} · {{ $emergencyCase->arrival_time?->format('d M Y, h:i A') ?? $emergencyCase->created_at?->format('d M Y, h:i A') }}</small>
        </div>
        <div class="d-flex flex-wrap gap-1">
            <span class="badge bg-danger">{{ str_replace('_', ' ', $emergencyCase->emergency_status) }}</span>
            @if($emergencyCase->current_triage_category)
                <span class="badge {{ $emergencyCase->triage_badge_class }}">{{ $emergencyCase->current_triage_category }}</span>
            @endif
            @if($emergencyReadOnly)
                <span class="badge bg-secondary">Read only</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        @if($emergencyReadOnly)
            <div class="alert alert-secondary py-2 small mb-3">
                This emergency session has been disposed or cancelled. Clinical details are shown as a completed session record.
            </div>
        @endif

        <div class="session-summary-grid mb-3">
            <div class="session-summary-item">
                <div class="text-muted small">Chief Complaint</div>
                <div class="fw-semibold">{{ $emergencyCase->chief_complaint ?: '-' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Main Doctor</div>
                <div class="fw-semibold">{{ $emergencySession?->mainDoctor?->full_name ?? $emergencyCase->assignedDoctor?->full_name ?? 'Unassigned' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Primary Nurse</div>
                <div class="fw-semibold">{{ $emergencySession?->primaryNurse?->full_name ?? $emergencyCase->assignedNurse?->full_name ?? 'Unassigned' }}</div>
            </div>
            <div class="session-summary-item">
                <div class="text-muted small">Medical Record</div>
                <div class="fw-semibold">{{ $emergencySession?->medical_record_id ? 'MR-'.str_pad((string) $emergencySession->medical_record_id, 5, '0', STR_PAD_LEFT) : '-' }}</div>
            </div>
        </div>

        <div class="row g-3">
            {{-- <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-heartbeat me-1 text-danger"></i>Triage & Vitals</div>
                    @php $latestEmergencyVitals = $emergencyCase->latestVitals; @endphp
                    <div class="small text-muted mb-1">Triaged by {{ $emergencyCase->triagedBy?->full_name ?? '-' }}</div>
                    <div class="small mb-2">{{ $emergencyCase->triage_notes ?: 'No triage notes recorded.' }}</div>
                    @if($latestEmergencyVitals)
                        <div class="d-flex flex-wrap gap-1 small">
                            @if($latestEmergencyVitals->blood_pressure)<span class="badge bg-light text-dark">BP {{ $latestEmergencyVitals->blood_pressure }}</span>@endif
                            @if($latestEmergencyVitals->heart_rate)<span class="badge bg-light text-dark">HR {{ $latestEmergencyVitals->heart_rate }}</span>@endif
                            @if($latestEmergencyVitals->respiratory_rate)<span class="badge bg-light text-dark">RR {{ $latestEmergencyVitals->respiratory_rate }}</span>@endif
                            @if($latestEmergencyVitals->temperature)<span class="badge bg-light text-dark">Temp {{ $latestEmergencyVitals->temperature }}</span>@endif
                            @if($latestEmergencyVitals->spo2)<span class="badge bg-light text-dark">SpO2 {{ $latestEmergencyVitals->spo2 }}%</span>@endif
                        </div>
                    @endif
                </div>
            </div> --}}
            <div class="col-lg-12">
                <div class="border rounded p-2 h-100">
                    <div class="d-flex fw-semibold small mb-2"><i class="ti ti-notes me-1 text-primary"></i>Emergency Notes</div>
                    <div class="row g-2">
                        @forelse($emergencyCase->notes->take(3) as $note)
                        <div class="col-lg-6">
                            <div class="small border-bottom pb-1 mb-1 h-100">
                                <span class="badge bg-light text-dark">{{ str_replace('_', ' ', $note->note_type) }}</span>
                                {{ Str::limit($note->content, 90) }}
                                <div class="text-muted">{{ $note->creator?->full_name ?? 'Unknown user' }} · {{ $note->created_at?->format('d M, h:i A') }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12 small text-muted">No emergency notes recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-pill me-1 text-success"></i>Medication / MAR</div>
                    @forelse($emergencyCase->medicationOrders->take(4) as $order)
                        <div class="small border-bottom pb-1 mb-1">
                            <div class="fw-semibold">{{ $order->display_name }}</div>
                            <div class="text-muted">{{ trim(($order->dose ?: '').' '.($order->dose_unit ?: '').' '.($order->route ?: '')) ?: '-' }} · <x-status-badge :status="$order->status" domain="med_order" size="sm" /></div>
                        </div>
                    @empty
                        <div class="small text-muted">No emergency medications ordered.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-microscope me-1 text-info"></i>Investigations</div>
                    @forelse($emergencyCase->labRequests->take(4) as $request)
                        <div class="small">{{ $request->items->map(fn($item) => $item->display_name ?? $item->name)->filter()->implode(', ') ?: $request->request_number }} <x-status-badge :status="$request->status" domain="lab" size="sm" /></div>
                    @empty
                        <div class="small text-muted">No investigations requested.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-activity me-1 text-warning"></i>Procedures</div>
                    @forelse($emergencyCase->procedureRequests->take(4) as $procedure)
                        <div class="small">{{ $procedure->service?->name ?? $procedure->procedure?->name ?? 'Procedure request' }} <span class="text-muted">{{ $procedure->status?->label() ?? $procedure->status }}</span></div>
                    @empty
                        <div class="small text-muted">No procedures requested.</div>
                    @endforelse
                </div>
            </div>
            {{-- <div class="col-lg-4">
                <div class="border rounded p-2 h-100">
                    <div class="fw-semibold small mb-2"><i class="ti ti-package me-1 text-secondary"></i>Consumables</div>
                    @forelse($emergencyCase->consumableUsages->take(4) as $usage)
                        <div class="small">{{ $usage->product?->name ?? 'Consumable' }} x {{ (float) $usage->quantity_used }} @if($usage->invoice_item_id)<span class="badge bg-success-subtle text-success">Billed</span>@endif</div>
                    @empty
                        <div class="small text-muted">No consumables used.</div>
                    @endforelse
                </div>
            </div> --}}
        </div>

        <div class="mt-3">
            <a href="{{ route('admin.emergency.cases.show', $emergencyCase) }}" class="btn btn-sm btn-outline-danger">
                <i class="ti ti-external-link me-1"></i>Open Emergency Control Sheet
            </a>
        </div>
    </div>
</div>
@endif

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
    $canCorrectLocked = auth()->user()?->can('consultation.entries.correct_completed') || auth()->user()?->can('visits.reopen_locked_session');
    $isSelectedRouteLocked = $selectedRoute && $selectedRoute->locked_at;
    $canEdit = in_array($visit->status, [\App\Enums\VisitStatus::CONSULTING, \App\Enums\VisitStatus::EMERGENCY], true)
        && $selectedRoute
        && $selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE
        && (! $isSelectedRouteLocked || $canCorrectLocked);
    $needsStart = $selectedRoute
        && (
            in_array($selectedRoute->status, [
                \App\Models\VisitConsultationRoute::STATUS_PENDING,
                \App\Models\VisitConsultationRoute::STATUS_PAUSED,
            ], true)
            || (
                $selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE
                && in_array($visit->status, [
                    \App\Enums\VisitStatus::WAITING_CONSULTATION,
                    \App\Enums\VisitStatus::ACTIVE,
                ], true)
            )
        );
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
@if($isSelectedRouteLocked && ! $canCorrectLocked)
<div class="alert alert-secondary py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-lock me-2"></i><strong>Session locked</strong>&nbsp;- this outpatient session is read-only.
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
                                <i class="ti ti-file-description me-1"></i>HOPC
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
                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#followUpAppointmentModal" @disabled(! $selectedRoute) title="{{ $selectedRoute ? 'Set next appointment' : 'Select a consultation session first' }}">
                        <i class="ti ti-calendar-plus me-1"></i>{{ $followUpAppointment ? 'Update Next Appointment' : 'Next Appointment' }}
                        @if($followUpAppointment)
                            <span class="badge bg-primary-subtle text-primary ms-1">Set</span>
                        @endif
                    </button>
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
                                            <label class="form-label small">Complaint <span class="text-danger">*</span></label>
                                            <input type="hidden" name="complaint_catalogue_id" id="complaintCatalogueIdInput">
                                            <input type="text" name="description" id="complaintDescInput" class="form-control" required placeholder="Search catalogue or type a custom complaint..." autocomplete="off" list="complaintSuggestions">
                                            <datalist id="complaintSuggestions"></datalist>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Duration</label>
                                            <input type="text" name="duration" class="form-control" placeholder="e.g., 3">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Duration Unit</label>
                                            <select name="duration_unit" class="form-select">
                                                <option value="">-- Select --</option>
                                                <option value="minutes">Minutes</option>
                                                <option value="hours">Hours</option>
                                                <option value="days">Days</option>
                                                <option value="weeks">Weeks</option>
                                                <option value="months">Months</option>
                                                <option value="years">Years</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Severity</label>
                                            <select name="severity" class="form-select">
                                                <option value="">-- Select --</option>
                                                <option value="mild">Mild</option>
                                                <option value="moderate">Moderate</option>
                                                <option value="severe">Severe</option>
                                                <option value="critical">Critical</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Notes</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="Clinical context or related notes"></textarea>
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
                            @forelse($ownerGroups($record?->complaints ?? []) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('entry', $group->count()) }}</small>
                                </div>
                                @foreach($group as $complaint)
                                <div class="ehr-item severity-{{ $complaint->severity ?? 'mild' }}" id="complaint-{{ $complaint->id }}" data-owner-key="{{ $ownerKey($complaint) }}">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="mb-1">{{ $complaint->description }}</p>
                                            <small class="text-muted">
                                                @if($complaint->complaintCatalogue) Catalogue: {{ $complaint->complaintCatalogue->name }} &middot; @endif
                                                @if($complaint->duration) Duration: {{ trim($complaint->duration.' '.($complaint->duration_unit ?? '')) }} &middot; @endif
                                                @if($complaint->severity)
                                                    Severity: <span class="badge bg-{{ in_array($complaint->severity, ['severe', 'critical'], true) ? 'danger' : ($complaint->severity === 'moderate' ? 'warning' : 'info') }}">{{ ucfirst($complaint->severity) }}</span>
                                                @endif
                                            </small>
                                            @if($complaint->notes)<small class="text-muted d-block">Notes: {{ $complaint->notes }}</small>@endif
                                            @if($entryFooter($complaint))<small class="text-muted d-block">{{ $entryFooter($complaint) }}</small>@endif
                                        </div>
                                        <div class="entry-actions d-flex gap-1">
                                            @if($canEditEntry($complaint))
                                            @php
                                                $editEntryPayload = [
                                                    'complaint_catalogue_id' => $complaint->complaint_catalogue_id,
                                                    'description' => $complaint->description,
                                                    'duration' => $complaint->duration,
                                                    'duration_unit' => $complaint->duration_unit,
                                                    'severity' => $complaint->severity,
                                                    'notes' => $complaint->notes,
                                                ];
                                            @endphp
                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                    data-entry-type="complaint"
                                                    data-url="{{ route('admin.consultations.complaints.update', $complaint) }}"
                                                    data-entry='@json($editEntryPayload)'>
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteEntry($complaint))
                                            <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                    data-url="{{ route('admin.consultations.complaints.destroy', $complaint) }}"
                                                    data-target="#complaint-{{ $complaint->id }}"
                                                    data-badge="badge-complaints"
                                                    data-confirm="Remove this complaint?" aria-label="Delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
                            @forelse($ownerGroups($record?->historiesOfPresentingComplaint ?? []) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('entry', $group->count()) }}</small>
                                </div>
                                @foreach($group as $hopc)
                                <div class="ehr-item" id="hopc-{{ $hopc->id }}" data-owner-key="{{ $ownerKey($hopc) }}">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <p class="mb-1">{{ $hopc->content }}</p>
                                            @if($hopc->complaint)
                                                <small class="text-muted d-block">Complaint: {{ $hopc->complaint->description }}</small>
                                            @endif
                                            @if($entryFooter($hopc))<small class="text-muted">{{ $entryFooter($hopc) }}</small>@endif
                                        </div>
                                        <div class="entry-actions d-flex gap-1">
                                            @if($canEditEntry($hopc))
                                            @php
                                                $editEntryPayload = ['content' => $hopc->content, 'complaint_id' => $hopc->complaint_id, 'onset' => $hopc->onset, 'duration' => $hopc->duration, 'location' => $hopc->location, 'severity' => $hopc->severity, 'aggravating_factors' => $hopc->aggravating_factors, 'relieving_factors' => $hopc->relieving_factors, 'associated_symptoms' => $hopc->associated_symptoms];
                                            @endphp
                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                    data-entry-type="hopc"
                                                    data-url="{{ route('admin.consultations.hopc.update', $hopc) }}"
                                                    data-entry='@json($editEntryPayload)'>
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteEntry($hopc))
                                            <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                    data-url="{{ route('admin.consultations.hopc.destroy', $hopc) }}"
                                                    data-target="#hopc-{{ $hopc->id }}"
                                                    data-badge="badge-hopc"
                                                    data-confirm="Remove this history entry?" aria-label="Delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
                            @forelse($ownerGroups($record?->physicalExaminations ?? []) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('entry', $group->count()) }}</small>
                                </div>
                                @foreach($group as $exam)
                                <div class="ehr-item" id="examination-{{ $exam->id }}" data-owner-key="{{ $ownerKey($exam) }}">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <p class="mb-1">{{ $exam->findings }}</p>
                                            @if($entryFooter($exam))<small class="text-muted">{{ $entryFooter($exam) }}</small>@endif
                                        </div>
                                        <div class="entry-actions d-flex gap-1">
                                            @if($canEditEntry($exam))
                                            @php
                                                $editEntryPayload = ['findings' => $exam->findings, 'general_examination' => $exam->general_examination, 'systemic_examination' => $exam->systemic_examination, 'cardiovascular' => $exam->cardiovascular, 'respiratory' => $exam->respiratory, 'gastrointestinal' => $exam->gastrointestinal, 'central_nervous_system' => $exam->central_nervous_system, 'specialty_examination' => $exam->specialty_examination, 'local_examination' => $exam->local_examination, 'notes' => $exam->notes];
                                            @endphp
                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                    data-entry-type="examination"
                                                    data-url="{{ route('admin.consultations.examinations.update', $exam) }}"
                                                    data-entry='@json($editEntryPayload)'>
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteEntry($exam))
                                            <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                    data-url="{{ route('admin.consultations.examinations.destroy', $exam) }}"
                                                    data-target="#examination-{{ $exam->id }}"
                                                    data-badge="badge-examination"
                                                    data-confirm="Remove this examination entry?" aria-label="Delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
                            @forelse($ownerGroups($record?->diagnoses ?? []) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('entry', $group->count()) }}</small>
                                </div>
                                @foreach($group as $diagnosis)
                                <div class="ehr-item {{ $diagnosis->is_primary ? 'is-primary' : '' }}" id="diagnosis-{{ $diagnosis->id }}" data-owner-key="{{ $ownerKey($diagnosis) }}">
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
                                            @if($entryFooter($diagnosis))<small class="text-muted d-block">{{ $entryFooter($diagnosis) }}</small>@endif
                                        </div>
                                        @if($canEditEntry($diagnosis))
                                        <div class="d-flex gap-1 ms-2 flex-shrink-0 entry-actions">
                                            @php
                                                $editEntryPayload = ['description' => $diagnosis->description, 'icd_code' => $diagnosis->icd_code, 'icd_code_id' => $diagnosis->icd_code_id, 'type' => $diagnosis->type, 'notes' => $diagnosis->notes];
                                            @endphp
                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                    data-entry-type="diagnosis"
                                                    data-url="{{ route('admin.consultations.diagnoses.update', $diagnosis) }}"
                                                    data-entry='@json($editEntryPayload)'>
                                                <i class="ti ti-edit"></i>
                                            </button>
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
                                            @if($canDeleteEntry($diagnosis))
                                            <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                    data-url="{{ route('admin.consultations.diagnoses.destroy', $diagnosis) }}"
                                                    data-target="#diagnosis-{{ $diagnosis->id }}"
                                                    data-badge="badge-diagnoses"
                                                    data-confirm="Remove this diagnosis?" aria-label="Delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
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
                                                <select name="department_id" id="investigationDeptSelect" class="form-select" required onchange="loadInvestigationServices(this.value)">
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
                                $grouped = collect($labRequests ?? [])->groupBy(function($r) {
                                    if ($r->targetDepartment?->name) return $r->targetDepartment->name;
                                    if ($r->department?->name) return $r->department->name;
                                    $firstItem = $r->items->first();
                                    return $firstItem?->service?->department?->name ?? 'Other';
                                });
                            @endphp
                            @if($grouped->isNotEmpty())
                                @foreach($grouped as $deptName => $reqs)
                                <div class="mb-3">
                                    <h6 class="small fw-bold border-bottom pb-1 mb-2 text-uppercase text-muted">
                                        <i class="ti ti-building-hospital me-1"></i>{{ $deptName }}
                                        <span class="badge bg-light text-dark ms-1">{{ collect($reqs)->count() }} {{ Str::plural('request', collect($reqs)->count()) }}</span>
                                    </h6>
                                    @foreach(collect($reqs)->groupBy(fn($r) => $ownerKey($r)) as $ownerReqs)
                                        @php
                                            $firstReq = $ownerReqs->first();
                                        @endphp
                                        <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstReq) }}">
                                            <div class="owner-group-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-semibold">{{ $ownerDisplayName($firstReq) }}</span>
                                                    <span class="badge bg-{{ $ownerRoleClass($firstReq) }}-subtle text-{{ $ownerRoleClass($firstReq) }} ms-1">{{ $ownerRoleLabel($firstReq) }}</span>
                                                </div>
                                                <small class="text-muted">{{ $ownerReqs->sum(fn($r) => $r->items?->count() ?? 0) }} {{ Str::plural('item', $ownerReqs->sum(fn($r) => $r->items?->count() ?? 0)) }}</small>
                                            </div>
                                            @foreach($ownerReqs as $req)
                                                @php
                                                    $hasProcessedItem = collect($req->items ?? [])->contains(fn($item) => $item->isAccepted() || $item->result);
                                                    $requestEditable = $canEdit && auth()->user() && ($req->status === 'pending') && ! $hasProcessedItem && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || (int) $req->requested_by === (int) auth()->id() || auth()->user()->can('consultation.entries.edit_any'));
                                                @endphp
                                                <div class="border rounded p-2 mb-2" id="lab-request-{{ $req->id }}">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <div>
                                                            <span class="fw-semibold">{{ $req->request_number }}</span>
                                                            <span class="badge bg-{{ $req->urgency_color }} ms-1">{{ ucfirst($req->urgency ?? 'routine') }}</span>
                                                            <span class="badge bg-{{ $req->status_color }} ms-1">{{ $req->status_label }}</span>
                                                            <small class="text-muted d-block">Requested {{ $req->created_at?->format('d M Y, h:i A') }}</small>
                                                            @if($req->clinical_info)
                                                                <small class="text-muted d-block">Notes: {{ $req->clinical_info }}</small>
                                                            @endif
                                                        </div>
                                                        <div class="entry-actions d-flex gap-1 align-items-center">
                                                            @if($requestEditable)
                                                            @php
                                                                $editEntryPayload = ['urgency' => $req->urgency, 'clinical_info' => $req->clinical_info];
                                                            @endphp
                                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                                    data-entry-type="lab-request"
                                                                    data-url="{{ route('admin.consultations.lab-request.update', $req) }}"
                                                                    data-entry='@json($editEntryPayload)'>
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            @elseif($hasProcessedItem || $req->status !== 'pending')
                                                                <small class="text-muted"><i class="ti ti-lock me-1"></i>Locked: request already processed</small>
                                                            @endif
                                                        </div>
                                                    </div>
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
                                                                @if($item->accepted_at) Accepted {{ $item->accepted_at->format('d M H:i') }} @endif
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
                                                </div>
                                            @endforeach
                                        </div>
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
                            @forelse($ownerGroups($record?->treatments ?? []) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('entry', $group->count()) }}</small>
                                </div>
                                @foreach($group as $treatment)
                                <div class="ehr-item" id="treatment-{{ $treatment->id }}" data-owner-key="{{ $ownerKey($treatment) }}">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <p class="mb-1">
                                                <span class="badge bg-{{ $treatment->type === 'medication' ? 'primary' : ($treatment->type === 'procedure' ? 'info' : ($treatment->type === 'referral' ? 'warning' : 'secondary')) }}">{{ ucfirst($treatment->type) }}</span>
                                                {{ $treatment->description }}
                                            </p>
                                            @if($entryFooter($treatment))<small class="text-muted">{{ $entryFooter($treatment) }}</small>@endif
                                        </div>
                                        <div class="entry-actions d-flex gap-1">
                                            @if($canEditEntry($treatment))
                                            @php
                                                $editEntryPayload = ['type' => $treatment->type, 'description' => $treatment->description];
                                            @endphp
                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                    data-entry-type="treatment"
                                                    data-url="{{ route('admin.consultations.treatments.update', $treatment) }}"
                                                    data-entry='@json($editEntryPayload)'>
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            @endif
                                            @if($canDeleteEntry($treatment))
                                            <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                    data-url="{{ route('admin.consultations.treatments.destroy', $treatment) }}"
                                                    data-target="#treatment-{{ $treatment->id }}"
                                                    data-badge="badge-treatments"
                                                    data-confirm="Remove this treatment?" aria-label="Delete" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
                            @forelse($ownerGroups($record?->prescriptions ?? []) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('prescription', $group->count()) }}</small>
                                </div>
                                @foreach($group as $prescription)
                                <div class="border rounded p-3 mb-3" id="prescription-{{ $prescription->id }}" data-owner-key="{{ $ownerKey($prescription) }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <span class="fw-bold">{{ $prescription->prescription_number }}</span>
                                            <x-status-badge :status="$prescription->status" class="ms-2" />
                                            @if($entryFooter($prescription))<small class="text-muted d-block">{{ $entryFooter($prescription) }}</small>@endif
                                        </div>
                                        <div class="d-flex align-items-center gap-1 entry-actions">
                                            @if($canEditEntry($prescription) && in_array($prescription->status->value, ['pending', 'active']))
                                            @php
                                                $editEntryPayload = ['notes' => $prescription->notes];
                                            @endphp
                                            <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                    data-entry-type="prescription"
                                                    data-url="{{ route('admin.consultations.prescriptions.update', $prescription) }}"
                                                    data-entry='@json($editEntryPayload)'>
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            @elseif(! in_array($prescription->status->value, ['pending', 'active']))
                                                <small class="text-muted"><i class="ti ti-lock me-1"></i>Locked</small>
                                            @endif
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
                                @endforeach
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
                                <form data-ajax-form="procedures" method="POST" action="{{ route('admin.consultations.procedures.store', $visit) }}" onsubmit="return saveTabBeforeSubmit('procedures-section')">
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
                            @php
                                $procedureDeptGroups = collect($procedureRequests ?? [])->groupBy(fn($pr) => $pr->department?->name ?? 'Other');
                            @endphp
                            @forelse($procedureDeptGroups as $deptName => $deptProcedures)
                            <div class="mb-3">
                                <h6 class="small fw-bold border-bottom pb-1 mb-2 text-uppercase text-muted">
                                    <i class="ti ti-building-hospital me-1"></i>{{ $deptName }}
                                    <span class="badge bg-light text-dark ms-1">{{ $deptProcedures->count() }} {{ Str::plural('request', $deptProcedures->count()) }}</span>
                                </h6>
                                @foreach($deptProcedures->groupBy(fn($pr) => $ownerKey($pr)) as $ownerProcedures)
                                    @php
                                        $firstPr = $ownerProcedures->first();
                                    @endphp
                                    <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstPr) }}">
                                        <div class="owner-group-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-semibold">{{ $ownerDisplayName($firstPr) }}</span>
                                                <span class="badge bg-{{ $ownerRoleClass($firstPr) }}-subtle text-{{ $ownerRoleClass($firstPr) }} ms-1">{{ $ownerRoleLabel($firstPr) }}</span>
                                            </div>
                                            <small class="text-muted">{{ $ownerProcedures->count() }} {{ Str::plural('request', $ownerProcedures->count()) }}</small>
                                        </div>
                                        @foreach($ownerProcedures as $pr)
                                        @php
                                            $procedureEditable = $canEdit && auth()->user() && $pr->status === \App\Enums\ProcedureStatus::REQUESTED && (auth()->user()->hasAnyRole(['Super Admin', 'Admin']) || (int) $pr->requested_by === (int) auth()->id() || auth()->user()->can('consultation.entries.edit_any'));
                                        @endphp
                                        <div class="ehr-item" id="procedure-{{ $pr->id }}" data-owner-key="{{ $ownerKey($pr) }}">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <p class="mb-1">
                                                        <span class="badge" style="background-color: {{ $pr->status->color() }}; color:#fff;">{{ $pr->status->label() }}</span>
                                                        <span class="fw-medium">{{ $pr->service?->name ?? 'Procedure' }}</span>
                                                        <small class="text-muted">· {{ $pr->request_number }}</small>
                                                    </p>
                                                    <small class="text-muted">
                                                        {{ ucfirst($pr->priority) }} ·
                                                        Requested {{ optional($pr->requested_at)->format('d M Y H:i') }}
                                                        @if($pr->preferred_datetime) · Preferred {{ optional($pr->preferred_datetime)->format('d M Y H:i') }} @endif
                                                        @if($pr->schedule)
                                                            · Scheduled {{ optional($pr->schedule->scheduled_start)->format('d M Y H:i') }}
                                                            @if($pr->schedule->theatreRoom) ({{ $pr->schedule->theatreRoom->name }}) @endif
                                                        @endif
                                                    </small>
                                                    @if($pr->indication)
                                                        <div><small><strong>Indication:</strong> {{ $pr->indication }}</small></div>
                                                    @endif
                                                    @if($pr->notes)
                                                        <div><small class="text-muted"><strong>Notes:</strong> {{ $pr->notes }}</small></div>
                                                    @endif
                                                    @if($pr->rejection_reason)
                                                        <div><small class="text-danger"><strong>Rejected:</strong> {{ $pr->rejection_reason }}</small></div>
                                                    @endif
                                                    @if($pr->cancellation_reason)
                                                        <div><small class="text-warning"><strong>Cancelled:</strong> {{ $pr->cancellation_reason }}</small></div>
                                                    @endif
                                                </div>
                                                <div class="text-end entry-actions d-flex gap-1 align-items-start">
                                                    @if($procedureEditable)
                                                    @php
                                                        $editEntryPayload = ['priority' => $pr->priority, 'indication' => $pr->indication, 'notes' => $pr->notes, 'preferred_datetime' => optional($pr->preferred_datetime)->format('Y-m-d\TH:i')];
                                                    @endphp
                                                    <button aria-label="Edit" title="Edit" type="button" class="btn btn-sm btn-outline-primary edit-entry-btn"
                                                            data-entry-type="procedure"
                                                            data-url="{{ route('admin.consultations.procedures.update', $pr) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @elseif($pr->status !== \App\Enums\ProcedureStatus::REQUESTED)
                                                        <small class="text-muted mt-1"><i class="ti ti-lock me-1"></i>Locked: request already processed</small>
                                                    @endif
                                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.theatre.show', $pr) }}">
                                                        <i class="ti ti-eye me-1"></i>Open
                                                    </a>
                                                    @if($pr->status === \App\Enums\ProcedureStatus::COMPLETED)
                                                        <a data-no-inertia class="btn btn-sm btn-outline-secondary" href="{{ route('admin.theatre.report', $pr) }}" target="_blank">Report</a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                @endforeach
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
                        <div id="tasks-list">
                        @if($record && $record->tasks && $record->tasks->count() > 0)
                            @foreach($ownerGroups($record->tasks->sortBy(fn($t) => $t->completed_at ? 1 : 0)) as $group)
                            @php
                                $firstEntry = $group->first();
                            @endphp
                            <div class="owner-group mb-3" data-owner-key="{{ $ownerKey($firstEntry) }}">
                                <div class="owner-group-header d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $ownerDisplayName($firstEntry) }}</span>
                                        <span class="badge bg-{{ $ownerRoleClass($firstEntry) }}-subtle text-{{ $ownerRoleClass($firstEntry) }} ms-1">{{ $ownerRoleLabel($firstEntry) }}</span>
                                    </div>
                                    <small class="text-muted">{{ $group->count() }} {{ Str::plural('task', $group->count()) }}</small>
                                </div>
                                @foreach($group as $task)
                                <div class="d-flex align-items-start gap-2 mb-3 p-2 border rounded {{ $task->completed_at ? 'bg-light' : '' }}" id="task-{{ $task->id }}" data-owner-key="{{ $ownerKey($task) }}">
                                    @if($canEditEntry($task))
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
                                            <div class="entry-actions d-flex gap-1">
                                                @if($canEditEntry($task))
                                                @php
                                                    $editEntryPayload = ['title' => $task->title, 'description' => $task->description, 'priority' => $task->priority, 'status' => $task->status, 'assigned_to' => $task->assigned_to, 'due_date' => optional($task->due_date)->format('Y-m-d')];
                                                @endphp
                                                <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                        data-entry-type="task"
                                                        data-url="{{ route('admin.consultations.tasks.update', $task) }}"
                                                        data-entry='@json($editEntryPayload)'>
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                @endif
                                                @if($canDeleteEntry($task))
                                                <form method="POST" action="{{ route('admin.consultations.tasks.destroy', $task) }}" class="d-inline" onsubmit="return confirm('Delete this task?') && saveTabBeforeSubmit('tasks-section')">
                                                    @csrf @method('DELETE')
                                                    <button aria-label="Close" title="Close" type="submit" class="btn btn-xs btn-outline-danger"><i class="ti ti-x"></i></button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                        @if($task->description) <small class="text-muted">{{ $task->description }}</small> @endif
                                        <div class="mt-1">
                                            <small class="text-muted">
                                                Created: {{ $task->created_at?->format('d M Y, h:i A') }}
                                                @if($task->assignedUser) &middot; Assigned: {{ $task->assignedUser->full_name }} @endif
                                                @if($task->due_date) &middot; Due: {{ $task->due_date->format('d M Y') }} @endif
                                                @if($task->completed_at) &middot; Done: {{ $task->completed_at->format('d M Y H:i') }} @endif
                                                @if($task->completedBy) &middot; Completed by: {{ $task->completedBy->full_name }} @endif
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
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
            </div>

            {{-- ========================= NEXT APPOINTMENT / FOLLOW-UP MODAL ========================= --}}
            <div class="modal fade" id="followUpAppointmentModal" tabindex="-1" aria-labelledby="followUpAppointmentModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="followUpAppointmentModalLabel"><i class="ti ti-calendar-plus me-1"></i>Next Appointment / Follow-up</h5>
                                @if($followUpAppointment)
                                    <small class="text-muted">Current status: {{ $followUpAppointment->status?->label() ?? ucfirst((string) $followUpAppointment->status) }}</small>
                                @endif
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                        @if(! $selectedRoute)
                            <x-empty-state icon="ti-route-off" title="No active consultation session" message="Select a consultation session before setting a follow-up appointment." />
                        @else
                            @php
                                $followUpDepartmentId = (string) old('department_id', $followUpAppointment?->department_id ?? $selectedRoute?->department_id);
                                $followUpServiceId = (string) old('service_id', $followUpAppointment?->services?->first()?->id);
                                $followUpDoctorId = (string) old('doctor_id', $followUpAppointment?->doctor_id ?? $selectedRoute?->doctor_id);
                                $followUpPriority = (string) old('priority', $followUpAppointment?->priority ?? 'normal');
                                $canManageFollowUp = $followUpAppointment
                                    ? auth()->user()?->can('consultation.followup.update')
                                    : auth()->user()?->can('consultation.followup.create');
                                $followUpAction = $followUpAppointment
                                    ? route('admin.consultations.routes.follow-up.update', [$visit, $selectedRoute, $followUpAppointment])
                                    : route('admin.consultations.routes.follow-up.store', [$visit, $selectedRoute]);
                            @endphp

                            @if($followUpAppointment)
                                <div class="alert alert-light border d-flex flex-wrap gap-3 align-items-center mb-3">
                                    <div>
                                        <div class="text-muted small">Current follow-up</div>
                                        <div class="fw-semibold">
                                            {{ $followUpAppointment->appointment_date?->format('d M Y') }}
                                            @if($followUpAppointment->start_time)
                                                &middot; {{ \Carbon\Carbon::parse($followUpAppointment->start_time)->format('h:i A') }}
                                            @endif
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Department</div>
                                        <div class="fw-semibold">{{ $followUpAppointment->department?->name ?? '-' }}</div>
                                    </div>
                                    <div>
                                        <div class="text-muted small">Doctor</div>
                                        <div class="fw-semibold">{{ $followUpAppointment->doctor?->full_name ?? 'Unassigned' }}</div>
                                    </div>
                                    @can('consultation.followup.cancel')
                                        <div class="ms-auto">
                                            <x-confirm-form
                                                :action="route('admin.consultations.routes.follow-up.cancel', [$visit, $selectedRoute, $followUpAppointment])"
                                                method="POST"
                                                button-label="Cancel Follow-up"
                                                button-class="btn btn-outline-danger btn-sm"
                                                icon="ti-x"
                                                confirm-title="Cancel this follow-up appointment?"
                                                confirm-text="A cancellation reason is required and will be recorded in the patient timeline."
                                                confirm-button="Yes, cancel"
                                                :require-reason="true"
                                                reason-name="reason"
                                                reason-placeholder="Reason for cancelling this follow-up"
                                            />
                                        </div>
                                    @endcan
                                </div>
                            @endif

                            @if($canManageFollowUp)
                                <form method="POST" action="{{ $followUpAction }}">
                                    @csrf
                                    @if($followUpAppointment)
                                        @method('PUT')
                                    @endif

                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Next Appointment Date <span class="text-danger">*</span></label>
                                            <input type="date" name="appointment_date" class="form-control @error('appointment_date') is-invalid @enderror" required value="{{ old('appointment_date', $followUpAppointment?->appointment_date?->toDateString()) }}">
                                            @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Next Appointment Time</label>
                                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $followUpAppointment?->start_time ? substr((string) $followUpAppointment->start_time, 0, 5) : '') }}">
                                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Priority</label>
                                            <select name="priority" class="form-select @error('priority') is-invalid @enderror">
                                                @foreach(\App\Enums\Priority::cases() as $priority)
                                                    <option value="{{ $priority->value }}" @selected($followUpPriority === $priority->value)>{{ $priority->label() }}</option>
                                                @endforeach
                                            </select>
                                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Department <span class="text-danger">*</span></label>
                                            <select name="department_id" id="followUpDepartmentSelect" class="form-select @error('department_id') is-invalid @enderror" required>
                                                @foreach($consultationDepartments as $department)
                                                    <option value="{{ $department->id }}" @selected($followUpDepartmentId === (string) $department->id)>{{ $department->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Service</label>
                                            <select name="service_id" id="followUpServiceSelect" class="form-select @error('service_id') is-invalid @enderror">
                                                <option value="">No specific service</option>
                                                @foreach($consultationServices as $service)
                                                    <option value="{{ $service->id }}" data-department-id="{{ $service->department_id }}" @selected($followUpServiceId === (string) $service->id)>
                                                        {{ $service->name }}{{ $service->department?->name ? ' - '.$service->department->name : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Doctor</label>
                                            <select name="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror">
                                                <option value="">Unassigned</option>
                                                @foreach($doctors as $doc)
                                                    <option value="{{ $doc->id }}" @selected($followUpDoctorId === (string) $doc->id)>Dr. {{ $doc->full_name }}</option>
                                                @endforeach
                                            </select>
                                            @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Reason / Follow-up Note <span class="text-danger">*</span></label>
                                            <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2" required placeholder="Reason for review, e.g. Review lab results and blood pressure control">{{ old('reason', $followUpAppointment?->reason) }}</textarea>
                                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Clinical Instruction / Note</label>
                                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Patient instructions, preparation, warning signs, or documents to bring">{{ old('notes', $followUpAppointment?->notes) }}</textarea>
                                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-12">
                                            <div class="form-check">
                                                <input type="checkbox" name="notify_patient" value="1" id="notifyPatientFollowUp" class="form-check-input" @checked(old('notify_patient'))>
                                                <label class="form-check-label" for="notifyPatientFollowUp">Notify patient when reminder channels are configured</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-calendar-check me-1"></i>{{ $followUpAppointment ? 'Update Follow-up' : 'Set Follow-up' }}
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-secondary mb-0">
                                    <i class="ti ti-lock me-1"></i>You do not have permission to {{ $followUpAppointment ? 'update' : 'create' }} consultation follow-up appointments.
                                </div>
                            @endif
                        @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ========================= NOTES / SUMMARY ========================= --}}
            <div class="tab-pane fade" id="summary-section" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>Notes / Consultation Summary</h6>
                    </div>
                    <div class="card-body" id="consultation-summary-body">
                        @include('consultations.partials.summary-sections', ['consultationSummary' => $consultationSummary])
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- =================== RIGHT PANEL — PREVIOUS VISITS =================== --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 small"><i class="ti ti-user-forward me-1"></i>Next Patient in Line</h6>
                @if($nextPatientInLine)
                    <span class="badge bg-{{ $nextPatientInLine['priority_color'] ?? 'secondary' }}">{{ $nextPatientInLine['priority'] ?? 'Normal' }}</span>
                @endif
            </div>
            <div class="card-body p-3">
                @if($nextPatientInLine)
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                        <div class="fw-semibold">{{ $nextPatientInLine['patient_name'] }}</div>
                        <span class="badge bg-soft-primary text-primary flex-shrink-0">Queue #{{ $nextPatientInLine['queue_number'] }}</span>
                    </div>
                    <div class="small text-muted mb-2">
                        {{ $nextPatientInLine['patient_number'] ?: 'No patient number' }}
                        @if($nextPatientInLine['visit_number'])
                            &middot; {{ $nextPatientInLine['visit_number'] }}
                        @endif
                    </div>
                    <div class="small mb-2">
                        @if($nextPatientInLine['age'])
                            <span class="badge bg-light text-dark border">Age {{ $nextPatientInLine['age'] }}</span>
                        @endif
                        @if($nextPatientInLine['gender'])
                            <span class="badge bg-light text-dark border">{{ ucfirst($nextPatientInLine['gender']) }}</span>
                        @endif
                    </div>
                    <div class="small text-muted">
                        <div><i class="ti ti-clock me-1"></i>Waiting: {{ $nextPatientInLine['waiting_minutes'] ?? 0 }} minutes</div>
                        <div><i class="ti ti-building-hospital me-1"></i>{{ $nextPatientInLine['department'] ?: 'Consultation department' }}</div>
                        @if(! empty($nextPatientInLine['services']))
                            <div><i class="ti ti-stethoscope me-1"></i>{{ implode(', ', $nextPatientInLine['services']) }}</div>
                        @endif
                        @if($nextPatientInLine['doctor'])
                            <div><i class="ti ti-user-heart me-1"></i>Assigned: Dr. {{ $nextPatientInLine['doctor'] }}</div>
                        @endif
                    </div>
                    <div class="mt-2">
                        <span class="badge bg-{{ $nextPatientInLine['payment_allowed'] ? 'success' : 'warning text-dark' }}">
                            <i class="ti ti-credit-card me-1"></i>{{ $nextPatientInLine['payment_message'] }}
                        </span>
                    </div>
                    @can('consultations.create')
                        <div class="d-grid gap-2 mt-3">
                            <form method="POST" action="{{ route('admin.consultations.routes.next-patient.open', [$visit, $selectedRoute]) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm w-100" @disabled(! $nextPatientInLine['payment_allowed']) title="{{ $nextPatientInLine['payment_allowed'] ? 'Open next patient' : $nextPatientInLine['payment_message'] }}">
                                    <i class="ti ti-arrow-right me-1"></i>Open Next Patient
                                </button>
                            </form>
                            <x-confirm-form
                                :action="route('admin.consultations.routes.next-patient.complete-open', [$visit, $selectedRoute])"
                                method="POST"
                                button-label="Complete & Open Next"
                                button-class="btn btn-success btn-sm w-100"
                                icon="ti-check"
                                confirm-title="Complete this consultation and open next patient?"
                                confirm-text="The current consultation session will be completed before the next patient is opened."
                                confirm-button="Complete and open"
                                :disabled="! $nextPatientInLine['payment_allowed']"
                                :disabled-reason="$nextPatientInLine['payment_message']"
                            />
                        </div>
                    @endcan
                @else
                    <x-empty-state icon="ti-users-off" title="No patient waiting" message="No patient is currently waiting in this consultation queue." />
                @endif
            </div>
        </div>

        {{-- todo: next appointment card should also show up here if set, with option to cancel or reschedule if user has permission --}}
        <div class="card">
            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#followUpAppointmentModal" @disabled(! $selectedRoute) title="{{ $selectedRoute ? 'Set next appointment' : 'Select a consultation session first' }}">
                <i class="ti ti-calendar-plus me-1"></i>{{ $followUpAppointment ? 'Update Next Appointment' : 'Next Appointment' }}
                @if($followUpAppointment)
                    <span class="badge bg-primary-subtle text-primary ms-1">Set</span>
                @endif
            </button>
        </div>

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
                                    onclick="window.location='{{ route('admin.consultations.history', $pastRecord->visit) }}'" aria-label="View" title="View">
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
    <div id="sessionsDrawerHandle" role="button" aria-expanded="false" aria-controls="sessionsDrawerBody" data-sessions-drawer-toggle>
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
                        $sessionLabel = $session->isEmergencySession() ? 'Emergency Department Session' : ($session->department?->name ?? '-');
                    @endphp
                    <tr class="session-route-row {{ trim($rowClass) }}">
                        <td class="fw-medium">
                            {{ $sessionLabel }}
                            @if($session->isEmergencySession())
                                <span class="badge bg-danger ms-1">Emergency</span>
                            @endif
                        </td>
                        <td>
                            {{ $sessionServiceNames->implode(', ') ?: '-' }}
                            @if($selectedRoute && $selectedRoute->id === $session->id)
                                <span class="badge bg-primary ms-1">Current</span>
                            @endif
                        </td>
                        <td>{{ $session->doctor ? 'Dr. ' . $session->doctor->full_name : 'Unassigned' }}</td>
                        <td><x-status-badge :status="$session->status" domain="consultation_session" /></td>
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
                        <td colspan="6"><x-empty-state message="No consultation sessions routed for this visit." /></td>
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
            <form data-ajax-form="tasks" method="POST" action="{{ route('admin.consultations.tasks.store', $visit) }}" onsubmit="saveTabBeforeSubmit('tasks-section')">
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
{{-- EDIT CONSULTATION ENTRY MODAL --}}
{{-- ============================================================ --}}
@can('consultations.create')
<div class="modal fade" id="editEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editEntryForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-edit me-2"></i><span id="editEntryTitle">Edit Entry</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="editEntryErrors" class="alert alert-danger d-none small py-2"></div>
                    <div id="editEntryFields"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Update</button>
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
                        $referralServicesPayloadByDept = $referralServicesByDept
                            ->map(fn ($services) => $services->map(fn ($service) => [
                                'id' => $service->id,
                                'name' => $service->name,
                                'category' => $service->category,
                            ])->values())
                            ->all();
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
   The Inertia legacy bridge re-injects scripts from the pushed
   scripts region (body script tags inside v-html are NOT executed),
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
window.procedureRequestBase = '{{ url("admin/consultations/procedures") }}';
window.labRequestBase = '{{ url("admin/consultations/lab-requests") }}';
window.taskBase = '{{ url("admin/consultations/tasks") }}';
window.canEditConsultation = @json($canEdit);
window.currentConsultationRouteId = @json($selectedRoute?->id);
window.currentUser = @json(auth()->user() ? ['id' => auth()->id(), 'full_name' => auth()->user()->full_name, 'roles' => auth()->user()->getRoleNames()->values()] : null);
window.summaryFragmentUrl = '{{ route('admin.consultations.summary-fragment', $visit) }}';
window.taskAssignableUsers = @json($doctors->map(fn($doctor) => ['id' => $doctor->id, 'name' => 'Dr. '.$doctor->full_name])->values());
window.sendSessionServicesByDept = @json($referralServicesPayloadByDept ?? []);

if (window.uhmsConsultationPageAbortController) {
    window.uhmsConsultationPageAbortController.abort();
}
if (window.uhmsConsultationDrawerResizeObserver) {
    window.uhmsConsultationDrawerResizeObserver.disconnect();
    window.uhmsConsultationDrawerResizeObserver = null;
}
window.uhmsConsultationPageAbortController = new AbortController();
var consultationPageSignal = window.uhmsConsultationPageAbortController.signal;
var consultationPageBindCycle = Date.now().toString(36) + Math.random().toString(36).slice(2);

function addConsultationListener(target, event, handler, options) {
    if (!target) return;
    var opts = Object.assign({}, options || {}, { signal: consultationPageSignal });
    target.addEventListener(event, handler, opts);
}

function hasConsultationBinding(target, key) {
    if (!target) return false;
    if (target.__uhmsConsultationBindings && target.__uhmsConsultationBindings[key] === consultationPageBindCycle) return true;
    return !!(target.dataset && target.dataset[key] === consultationPageBindCycle);
}

function markConsultationBinding(target, key) {
    if (!target) return;
    target.__uhmsConsultationBindings = target.__uhmsConsultationBindings || {};
    target.__uhmsConsultationBindings[key] = consultationPageBindCycle;
    if (target.dataset) target.dataset[key] = consultationPageBindCycle;
}

function addConsultationListenerOnce(target, key, event, handler, options) {
    if (!target || hasConsultationBinding(target, key)) return;
    markConsultationBinding(target, key);
    addConsultationListener(target, event, handler, options);
}

function runWhenConsultationReady(callback) {
    if (document.readyState === 'loading') {
        addConsultationListener(document, 'DOMContentLoaded', callback);
    } else {
        callback();
    }
}

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

function bindSessionsDrawer() {
    const drawer = document.getElementById('sessionsDrawer');
    const handle = document.getElementById('sessionsDrawerHandle');
    if (!drawer || !handle) return;

    document.body.classList.add('has-sessions-drawer');
    positionSessionsDrawer();
    addConsultationListenerOnce(handle, 'uhmsBound', 'click', toggleSessionsDrawer);
    if (window.ResizeObserver && !window.uhmsConsultationDrawerResizeObserver) {
        window.uhmsConsultationDrawerResizeObserver = new ResizeObserver(positionSessionsDrawer);
        window.uhmsConsultationDrawerResizeObserver.observe(document.documentElement);
    } else if (!window.ResizeObserver) {
        addConsultationListenerOnce(window, 'uhmsResizeBound', 'resize', positionSessionsDrawer);
    }
}
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
    if (!deptSel || !svcSel || !doctorSel || hasConsultationBinding(deptSel, 'uhmsBound')) return;
    markConsultationBinding(deptSel, 'uhmsBound');

    function setOptions(select, placeholder, list, labelFn, placeholderDisabled = false) {
        select.innerHTML = '';
        select.insertAdjacentHTML('beforeend', '<option value=""' + (placeholderDisabled ? ' disabled' : '') + '>' + placeholder + '</option>');
        list.forEach(function (item) {
            select.insertAdjacentHTML('beforeend', '<option value="' + item.id + '">' + labelFn(item) + '</option>');
        });
    }

    function preloadedServicesFor(departmentId) {
        const grouped = window.sendSessionServicesByDept || {};
        return grouped[String(departmentId)] || grouped[departmentId] || [];
    }

    function showServices(services) {
        svcSel.disabled = services.length === 0;
        setOptions(
            svcSel,
            services.length ? 'Optional services to link/bill' : 'No consultation services available',
            services,
            function (s) { return s.name; },
            true
        );
    }

    addConsultationListener(deptSel, 'change', async function () {
        svcSel.innerHTML = '';
        doctorSel.innerHTML = '';
        if (!this.value) {
            svcSel.disabled = true;
            doctorSel.disabled = true;
            svcSel.innerHTML = '<option value="">Select department first</option>';
            doctorSel.innerHTML = '<option value="">Select department first</option>';
            return;
        }
        const fallbackServices = preloadedServicesFor(this.value);
        svcSel.disabled = true;
        doctorSel.disabled = true;
        svcSel.innerHTML = '<option value="">Loading services...</option>';
        doctorSel.innerHTML = '<option value="">Loading doctors...</option>';
        if (fallbackServices.length) {
            showServices(fallbackServices);
        }
        try {
            const res = await fetch(endpointTemplate.replace('__ID__', this.value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) {
                throw new Error('Options request failed');
            }
            const payload = await res.json();
            const services = (payload.services || []).filter(function (service) {
                return service.category === 'consultation';
            });
            const doctors = payload.doctors || [];
            doctorSel.disabled = false;
            showServices(services.length ? services : fallbackServices);
            setOptions(doctorSel, doctors.length ? 'Optional doctor' : 'No doctor linked through specialty', doctors, function (d) { return d.name; });
        } catch (error) {
            showServices(fallbackServices);
            doctorSel.disabled = true;
            svcSel.innerHTML = '<option value="">Unable to load services</option>';
            if (fallbackServices.length) {
                showServices(fallbackServices);
            }
            doctorSel.innerHTML = '<option value="">Unable to load doctors</option>';
        }
    });
}

function bindSendSessionModalEvents() {
    const modal = document.getElementById('sendSessionModal');
    addConsultationListenerOnce(modal, 'uhmsBound', 'shown.bs.modal', initSendSessionPicker);
}

function initConsultationSessionUi() {
    bindSessionsDrawer();
    bindSendSessionModalEvents();
    initSendSessionPicker();
}

runWhenConsultationReady(initConsultationSessionUi);

function ensureCurrentRouteInput(form) {
    const routeId = window.currentConsultationRouteId;
    if (!routeId || !form || form.querySelector('input[name="consultation_route_id"]')) return;

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'consultation_route_id';
    input.value = routeId;
    form.appendChild(input);
}

function bindCurrentRouteInputs() {
    document.querySelectorAll('form[action*="/consultations/{{ $visit->id }}"], form[data-ajax-form]').forEach(ensureCurrentRouteInput);
}

runWhenConsultationReady(bindCurrentRouteInputs);

addConsultationListener(document, 'click', (e) => {
    const openBtn = e.target.closest('[data-bs-target="#sendSessionModal"]');
    if (openBtn) {
        initSendSessionPicker();
        if (openSendSessionModalFallback()) {
            e.preventDefault();
            return;
        }
    }

    if (e.target.closest('#sendSessionModal [data-bs-dismiss="modal"]') || e.target.matches('#sendSessionModal')) {
        if (!(window.bootstrap && window.bootstrap.Modal)) {
            e.preventDefault();
            closeSendSessionModalFallback();
        }
    }
});
@if(!$canEdit)
runWhenConsultationReady(() => {
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
addConsultationListener(document, 'click', async (e) => {
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
var procedureRequestBase = window.procedureRequestBase;
var labRequestBase = window.labRequestBase;
var taskBase = window.taskBase;
var summaryFragmentUrl = window.summaryFragmentUrl;

/* ================================================================
   TAB PERSISTENCE
   ================================================================ */
(function () {
    var saved = window.location.hash || localStorage.getItem(tabStorageKey);
    if (saved) activateConsultationTab(saved);

    document.querySelectorAll('#consultationTabs .nav-link').forEach(function (link) {
        addConsultationListenerOnce(link, 'uhmsTabBound', 'shown.bs.tab', function (e) {
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

function currentPageUrl() {
    var url = new URL(window.location.href);
    if (window.currentConsultationRouteId) {
        url.searchParams.set('consultation_route_id', window.currentConsultationRouteId);
    }
    url.searchParams.set('_refresh', Date.now());
    return url.toString();
}

function parseConsultationRefreshDocument(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var inertiaPage = doc.querySelector('script[data-page="app"][type="application/json"]');

    if (inertiaPage && inertiaPage.textContent) {
        try {
            var payload = JSON.parse(inertiaPage.textContent);
            if (payload && payload.props && payload.props.html) {
                return new DOMParser().parseFromString(payload.props.html, 'text/html');
            }
        } catch (e) {}
    }

    return doc;
}

function refreshConsultationSection(section) {
    var target = document.getElementById(section + '-list');
    if (!target) return Promise.resolve();

    return fetch(currentPageUrl(), {
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(function (r) { return r.text(); })
    .then(function (html) {
        var doc = parseConsultationRefreshDocument(html);
        var fresh = doc.getElementById(section + '-list');
        if (fresh) target.innerHTML = fresh.innerHTML;

        var badge = document.getElementById('badge-' + section);
        var freshBadge = doc.getElementById('badge-' + section);
        if (badge && freshBadge) badge.textContent = freshBadge.textContent;

        bindDeleteButtons();
        bindDiagnosisButtons();
        bindEditEntryButtons();
    });
}

function refreshConsultationSummary() {
    var target = document.getElementById('consultation-summary-body');
    if (!target || !summaryFragmentUrl) return Promise.resolve();

    var url = new URL(summaryFragmentUrl, window.location.origin);
    if (window.currentConsultationRouteId) {
        url.searchParams.set('consultation_route_id', window.currentConsultationRouteId);
    }
    url.searchParams.set('_refresh', Date.now());

    return fetch(url.toString(), {
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
    .then(function (r) { return r.text(); })
    .then(function (html) { target.innerHTML = html; });
}

function resetAjaxForm(form, section) {
    form.reset();
    var col = form.closest('.collapse');
    if (col) {
        var bs = bootstrap.Collapse.getInstance(col) || bootstrap.Collapse.getOrCreateInstance(col, { toggle: false });
        if (bs) bs.hide();
    }
    if (section === 'tasks') {
        var modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
        if (modal) modal.hide();
    }

    var ds = document.getElementById('investigationDeptSelect');
    if (ds) {
        ds.value = '';
        var sc = document.getElementById('investigationServicesContainer');
        if (sc) sc.innerHTML = '<span class="text-muted small">Select a department first to load services</span>';
    }
}

function editField(name, label, value, type, attrs) {
    attrs = attrs || '';
    type = type || 'text';
    return '<div class="mb-3"><label class="form-label small">' + escapeHtml(label) + '</label>' +
        '<input type="' + type + '" name="' + escapeHtml(name) + '" class="form-control" value="' + escapeHtml(value || '') + '" ' + attrs + '></div>';
}

function editTextarea(name, label, value, rows, attrs) {
    return '<div class="mb-3"><label class="form-label small">' + escapeHtml(label) + '</label>' +
        '<textarea name="' + escapeHtml(name) + '" class="form-control" rows="' + (rows || 3) + '" ' + (attrs || '') + '>' + escapeHtml(value || '') + '</textarea></div>';
}

function editSelect(name, label, value, options, attrs) {
    var html = '<div class="mb-3"><label class="form-label small">' + escapeHtml(label) + '</label><select name="' + escapeHtml(name) + '" class="form-select" ' + (attrs || '') + '>';
    options.forEach(function (opt) {
        var selected = String(opt.value ?? '') === String(value ?? '') ? ' selected' : '';
        html += '<option value="' + escapeHtml(opt.value) + '"' + selected + '>' + escapeHtml(opt.label) + '</option>';
    });
    return html + '</select></div>';
}

function buildEditFields(type, entry) {
    entry = entry || {};
    if (type === 'complaint') {
        return '<input type="hidden" name="complaint_catalogue_id" value="' + escapeHtml(entry.complaint_catalogue_id || '') + '">' +
            editField('description', 'Complaint', entry.description, 'text', 'required') +
            '<div class="row"><div class="col-md-4">' + editField('duration', 'Duration', entry.duration) + '</div><div class="col-md-4">' +
            editSelect('duration_unit', 'Duration Unit', entry.duration_unit, [
                { value: '', label: '-- Select --' }, { value: 'minutes', label: 'Minutes' }, { value: 'hours', label: 'Hours' }, { value: 'days', label: 'Days' }, { value: 'weeks', label: 'Weeks' }, { value: 'months', label: 'Months' }, { value: 'years', label: 'Years' }
            ]) + '</div><div class="col-md-4">' +
            editSelect('severity', 'Severity', entry.severity, [
                { value: '', label: '-- Select --' }, { value: 'mild', label: 'Mild' }, { value: 'moderate', label: 'Moderate' }, { value: 'severe', label: 'Severe' }, { value: 'critical', label: 'Critical' }
            ]) + '</div></div>' + editTextarea('notes', 'Notes', entry.notes, 2);
    }
    if (type === 'hopc') {
        return editTextarea('content', 'Narrative', entry.content, 4, 'required') +
            '<div class="row"><div class="col-md-3">' + editField('onset', 'Onset', entry.onset) + '</div><div class="col-md-3">' + editField('duration', 'Duration', entry.duration) + '</div><div class="col-md-3">' + editField('location', 'Location', entry.location) + '</div><div class="col-md-3">' + editField('severity', 'Severity', entry.severity) + '</div></div>' +
            editField('associated_symptoms', 'Associated Symptoms', entry.associated_symptoms) +
            '<div class="row"><div class="col-md-6">' + editField('aggravating_factors', 'Aggravating Factors', entry.aggravating_factors) + '</div><div class="col-md-6">' + editField('relieving_factors', 'Relieving Factors', entry.relieving_factors) + '</div></div>';
    }
    if (type === 'examination') {
        return editTextarea('findings', 'Findings', entry.findings, 3, 'required') +
            '<div class="row"><div class="col-md-6">' + editTextarea('general_examination', 'General Examination', entry.general_examination, 2) + '</div><div class="col-md-6">' + editTextarea('systemic_examination', 'Systemic Examination', entry.systemic_examination, 2) + '</div></div>' +
            '<div class="row"><div class="col-md-6">' + editTextarea('cardiovascular', 'Cardiovascular', entry.cardiovascular, 2) + '</div><div class="col-md-6">' + editTextarea('respiratory', 'Respiratory', entry.respiratory, 2) + '</div></div>' +
            '<div class="row"><div class="col-md-6">' + editTextarea('gastrointestinal', 'Gastrointestinal', entry.gastrointestinal, 2) + '</div><div class="col-md-6">' + editTextarea('central_nervous_system', 'Central Nervous System', entry.central_nervous_system, 2) + '</div></div>' +
            '<div class="row"><div class="col-md-6">' + editTextarea('specialty_examination', 'Specialty Examination', entry.specialty_examination, 2) + '</div><div class="col-md-6">' + editTextarea('local_examination', 'Local Examination', entry.local_examination, 2) + '</div></div>' +
            editTextarea('notes', 'Notes', entry.notes, 2);
    }
    if (type === 'diagnosis') {
        return editField('description', 'Description', entry.description, 'text', 'required') +
            '<div class="row"><div class="col-md-6">' + editField('icd_code', 'ICD-10 Code', entry.icd_code) + '</div><div class="col-md-6">' +
            editSelect('type', 'Type', entry.type, [{ value: 'provisional', label: 'Provisional' }, { value: 'final', label: 'Final' }]) + '</div></div>' +
            editField('notes', 'Notes', entry.notes);
    }
    if (type === 'treatment') {
        return editSelect('type', 'Type', entry.type, [
            { value: 'medication', label: 'Medication' }, { value: 'procedure', label: 'Procedure' }, { value: 'referral', label: 'Referral' }, { value: 'advice', label: 'Advice' }
        ], 'required') + editTextarea('description', 'Description', entry.description, 3, 'required');
    }
    if (type === 'prescription') {
        return editTextarea('notes', 'Prescription Notes', entry.notes, 3);
    }
    if (type === 'lab-request') {
        return editSelect('urgency', 'Urgency', entry.urgency, [
            { value: 'routine', label: 'Routine' }, { value: 'urgent', label: 'Urgent' }, { value: 'emergency', label: 'Emergency' }
        ]) + editTextarea('clinical_info', 'Clinical Notes', entry.clinical_info, 3);
    }
    if (type === 'procedure') {
        return editSelect('priority', 'Priority', entry.priority, [
            { value: 'routine', label: 'Routine' }, { value: 'urgent', label: 'Urgent' }, { value: 'emergency', label: 'Emergency' }
        ], 'required') + editField('preferred_datetime', 'Preferred Date/Time', entry.preferred_datetime, 'datetime-local') +
            editTextarea('indication', 'Indication / Reason', entry.indication, 3, 'required') + editTextarea('notes', 'Notes', entry.notes, 2);
    }
    if (type === 'task') {
        var userOptions = [{ value: '', label: 'Unassigned' }].concat((window.taskAssignableUsers || []).map(function (u) { return { value: u.id, label: u.name }; }));
        return editField('title', 'Task Title', entry.title, 'text', 'required') + editTextarea('description', 'Description', entry.description, 2) +
            '<div class="row"><div class="col-md-4">' + editSelect('priority', 'Priority', entry.priority, [{ value: 'low', label: 'Low' }, { value: 'medium', label: 'Medium' }, { value: 'high', label: 'High' }]) + '</div><div class="col-md-4">' +
            editSelect('status', 'Status', entry.status, [{ value: 'pending', label: 'Pending' }, { value: 'in_progress', label: 'In Progress' }, { value: 'completed', label: 'Completed' }, { value: 'cancelled', label: 'Cancelled' }]) + '</div><div class="col-md-4">' + editField('due_date', 'Due Date', entry.due_date, 'date') + '</div></div>' +
            editSelect('assigned_to', 'Assign To', entry.assigned_to, userOptions);
    }
    return '<p class="text-muted mb-0">This entry type cannot be edited here.</p>';
}

function sectionForEntryType(type) {
    return {
        complaint: 'complaints',
        hopc: 'hopc',
        examination: 'examination',
        diagnosis: 'diagnoses',
        treatment: 'treatments',
        prescription: 'prescriptions',
        'lab-request': 'investigations',
        procedure: 'procedures',
        task: 'tasks'
    }[type] || type;
}

function bindEditEntryButtons() {
    document.querySelectorAll('.edit-entry-btn').forEach(function (btn) {
        addConsultationListenerOnce(btn, 'uhmsBound', 'click', function () {
            var modalEl = document.getElementById('editEntryModal');
            var form = document.getElementById('editEntryForm');
            var fields = document.getElementById('editEntryFields');
            var title = document.getElementById('editEntryTitle');
            var errors = document.getElementById('editEntryErrors');
            if (!modalEl || !form || !fields) return;

            var type = this.dataset.entryType;
            var entry = {};
            try { entry = JSON.parse(this.dataset.entry || '{}'); } catch (e) { entry = {}; }
            form.action = this.dataset.url;
            form.dataset.entryType = type;
            title.textContent = 'Edit ' + type.replace('-', ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
            fields.innerHTML = buildEditFields(type, entry);
            errors.classList.add('d-none');
            errors.innerHTML = '';

            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        });
    });
}

var editEntryForm = document.getElementById('editEntryForm');
if (editEntryForm && !hasConsultationBinding(editEntryForm, 'uhmsSubmitBound')) {
    markConsultationBinding(editEntryForm, 'uhmsSubmitBound');
    addConsultationListener(editEntryForm, 'submit', function (e) {
        e.preventDefault();
        var form = this;
        var section = sectionForEntryType(form.dataset.entryType);
        var btn = form.querySelector('[type="submit"]');
        var origHtml = btn ? btn.innerHTML : '';
        var errors = document.getElementById('editEntryErrors');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
        if (errors) { errors.classList.add('d-none'); errors.innerHTML = ''; }

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
            if (!data.success) throw data;
            var modal = bootstrap.Modal.getInstance(document.getElementById('editEntryModal'));
            if (modal) modal.hide();
            Promise.all([refreshConsultationSection(section), refreshConsultationSummary()]).then(function () {
                activateConsultationTab('#' + section + '-section');
                showToast('Updated successfully.');
            });
        })
        .catch(function (err) {
            var msg = 'Update failed.';
            if (err && err.errors) msg = Object.values(err.errors).flat().join('\n');
            else if (err && err.message) msg = err.message;
            if (errors) {
                errors.classList.remove('d-none');
                errors.innerHTML = escapeHtml(msg).replace(/\n/g, '<br>');
            } else {
                alert(msg);
            }
        })
        .finally(function () { if (btn) { btn.disabled = false; btn.innerHTML = origHtml; } });
    });
}
bindEditEntryButtons();

/* ================================================================
   AJAX DELETE
   ================================================================ */
function bindDeleteButtons() {
    document.querySelectorAll('.ajax-delete').forEach(function (btn) {
        addConsultationListenerOnce(btn, 'uhmsBound', 'click', function () {
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
                    if (badge) refreshConsultationSection(badge.replace('badge-', ''));
                    refreshConsultationSummary();
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
    if (hasConsultationBinding(form, 'uhmsSubmitBound')) return;
    markConsultationBinding(form, 'uhmsSubmitBound');
    addConsultationListener(form, 'submit', function (e) {
        e.preventDefault();
        var section  = form.dataset.ajaxForm;
        ensureCurrentRouteInput(form);
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
    resetAjaxForm(form, section);
    Promise.all([
        refreshConsultationSection(section),
        refreshConsultationSummary()
    ]).then(function () {
        activateConsultationTab('#' + section + '-section');
    });
}

/* ================================================================
   DIAGNOSIS — TYPE TOGGLE & SET PRIMARY
   ================================================================ */
function bindDiagnosisButtons() {
    document.querySelectorAll('.toggle-type-btn').forEach(function (btn) {
        addConsultationListenerOnce(btn, 'uhmsBound', 'click', function () {
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
                    refreshConsultationSummary();
                    showToast('Type set to ' + capFirst(newType) + '.');
                }
            })
            .catch(function () { alert('Failed to update type.'); })
            .finally(function () { self.disabled = false; });
        });
    });

    document.querySelectorAll('.set-primary-btn').forEach(function (btn) {
        addConsultationListenerOnce(btn, 'uhmsBound', 'click', function () {
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
                    refreshConsultationSummary();
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
    if (!form || hasConsultationBinding(form, 'uhmsSubmitBound')) return;
    markConsultationBinding(form, 'uhmsSubmitBound');

    addConsultationListener(form, 'submit', function (e) {
        e.preventDefault();

        var errBox = document.getElementById('labReqErrors');
        var btn    = document.getElementById('labReqSubmitBtn');
        var orig   = btn ? btn.innerHTML : '';

        if (errBox) errBox.classList.add('d-none');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...'; }
        ensureCurrentRouteInput(form);

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
                // Refresh the investigations list and summary
                Promise.all([
                    refreshConsultationSection('investigations'),
                    refreshConsultationSummary()
                ]).then(function () {
                    activateConsultationTab('#investigations-section');
                });
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
        (idx > 0 ? '<button aria-label="Close" title="Close" type="button" class="btn btn-outline-danger" onclick="document.getElementById(\'freeItem' + idx + '\').remove()"><i class="ti ti-x"></i></button>' : '') +
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
    if (!form || hasConsultationBinding(form, 'uhmsSubmitBound')) return;
    markConsultationBinding(form, 'uhmsSubmitBound');

    addConsultationListener(form, 'submit', function (e) {
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
        ensureCurrentRouteInput(form);

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

            var modal = bootstrap.Modal.getInstance(document.getElementById('investigationModal'));
            if (modal) modal.hide();

            form.reset();
            showToast(data.message || 'Patient routed successfully.');

            // Refresh the investigations list and summary
            Promise.all([
                refreshConsultationSection('investigations'),
                refreshConsultationSummary()
            ]).then(function () {
                activateConsultationTab('#investigations-section');
            });
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
    if (!select || !window.jQuery || !window.jQuery.fn.select2) return;
    var selectedValue = select.value;
    var $select = window.jQuery(select);
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    select.value = selectedValue;
    $select.select2({
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

if (window.jQuery) {
    window.jQuery('.drug-select').each(function () { initDrugSelect(this); });
}

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
    if (!row || hasConsultationBinding(row, 'uhmsRxCalcBound')) return;
    markConsultationBinding(row, 'uhmsRxCalcBound');

    ['change','input'].forEach(function(evt) {
        addConsultationListener(row.querySelector('[name$="[dosage]"]'), evt, function(){ calcQty(row); });
        addConsultationListener(row.querySelector('[name$="[duration]"]'), evt, function(){ calcQty(row); });
    });
    addConsultationListener(row.querySelector('[name$="[frequency]"]'), 'change', function(){ calcQty(row); });
    // Select2 fires a jQuery event
    if (window.jQuery) {
        window.jQuery(row).find('.drug-select').off('.uhmsRxCalc').on('select2:select.uhmsRxCalc select2:clear.uhmsRxCalc change.uhmsRxCalc', function(){ syncDrugName(row); calcQty(row); });
    }
}

/* Bind on the first (pre-rendered) row */
(function(){
    var firstRow = document.querySelector('#prescriptionItems .prescription-item');
    if (firstRow) bindRxCalc(firstRow);
})();

var rxIdx = 1;
addConsultationListenerOnce(document.getElementById('addItemBtn'), 'uhmsBound', 'click', function () {
    var cont = document.getElementById('prescriptionItems');
    if (!cont) return;
    var source = cont.querySelector('.prescription-item');
    if (!source) return;
    var tpl  = source.cloneNode(true);

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
    if (window.jQuery) {
        window.jQuery(tpl).find('.drug-select').each(function () { initDrugSelect(this); });
    }
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
    document.querySelectorAll('.apply-pattern-btn').forEach(function (btn) {
        addConsultationListenerOnce(btn, 'uhmsBound', 'click', function () {
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
if (psBtn && psInp && psRes) {
    addConsultationListenerOnce(psBtn, 'uhmsBound', 'click', function () {
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
    addConsultationListenerOnce(psInp, 'uhmsBound', 'keypress', function (e) { if (e.key === 'Enter') { e.preventDefault(); psBtn.click(); } });
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
    var complaintSuggestionIndex = {};

    function syncComplaintCatalogueId(value) {
        var hidden = document.getElementById('complaintCatalogueIdInput');
        if (!hidden) return;
        var match = complaintSuggestionIndex[(value || '').toLowerCase()];
        hidden.value = match ? match.id : '';
    }

    function bindSuggest(inputId, datalistId, type) {
        var inp = document.getElementById(inputId);
        var dl  = document.getElementById(datalistId);
        if (!inp || !dl) return;
        if (hasConsultationBinding(inp, 'uhms' + capFirst(type) + 'SuggestBound')) return;
        markConsultationBinding(inp, 'uhms' + capFirst(type) + 'SuggestBound');

        addConsultationListener(inp, 'input', function () {
            var q = this.value.trim();
            clearTimeout(timers[type]);
            if (q.length < 2) { dl.innerHTML = ''; return; }
            timers[type] = setTimeout(function () {
                fetch(suggestUrls[type] + '?q=' + encodeURIComponent(q), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(function (r) { return r.json(); })
                .then(function (items) {
                    if (type === 'complaint') {
                        complaintSuggestionIndex = {};
                        dl.innerHTML = items.map(function (item) {
                            complaintSuggestionIndex[String(item.name || '').toLowerCase()] = item;
                            var label = item.category ? item.category : 'Complaint catalogue';
                            return '<option value="' + escapeHtml(item.name || '') + '" label="' + escapeHtml(label) + '">';
                        }).join('');
                        syncComplaintCatalogueId(inp.value);
                        return;
                    }

                    dl.innerHTML = items.map(function (s) {
                        return '<option value="' + escapeHtml(s) + '">';
                    }).join('');
                });
            }, 280);
        });
        if (type === 'complaint') {
            addConsultationListener(inp, 'change', function () { syncComplaintCatalogueId(this.value); });
            addConsultationListener(inp, 'blur', function () { syncComplaintCatalogueId(this.value); });
        }
    }

    bindSuggest('complaintDescInput', 'complaintSuggestions', 'complaint');
    bindSuggest('diagnosis_description', 'diagnosisSuggestions', 'diagnosis');
}());

/* ================================================================
   ICD-10 AUTOCOMPLETE
   ================================================================ */
runWhenConsultationReady(function () {
    var $jq = window.jQuery;
    if ($jq && $jq('#icd_code_select').length && $jq.fn.select2) {
        var $icdSelect = $jq('#icd_code_select');
        if ($icdSelect.hasClass('select2-hidden-accessible')) {
            $icdSelect.select2('destroy');
        }
        $icdSelect.off('.uhmsIcd').select2({
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
                return $jq('<span>').html('<strong>' + escapeHtml(i.code) + '</strong> — ' + escapeHtml(i.description));
            },
            templateSelection: function (i) { return i.text || i.code; }
        }).on('select2:select.uhmsIcd', function (e) {
            var d = e.params.data;
            $jq('#icd_code_id').val(d.id);
            $jq('#icd_code_manual').val(d.code);
            var desc = $jq('#diagnosis_description');
            if (!desc.val().trim()) desc.val(d.description);
        }).on('select2:clear.uhmsIcd', function () {
            $jq('#icd_code_id').val('');
            $jq('#icd_code_manual').val('');
        });
    }
});

runWhenConsultationReady(function () {
    var dept = document.getElementById('followUpDepartmentSelect');
    var service = document.getElementById('followUpServiceSelect');
    if (!dept || !service) return;

    function syncFollowUpServices() {
        var selectedDepartment = dept.value;
        Array.prototype.forEach.call(service.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            var matches = !selectedDepartment || option.dataset.departmentId === selectedDepartment;
            option.hidden = !matches;
            if (!matches && option.selected) {
                service.value = '';
            }
        });
    }

    addConsultationListenerOnce(dept, 'uhmsFollowUpBound', 'change', syncFollowUpServices);
    syncFollowUpServices();

    @if($errors->has('appointment_date') || $errors->has('start_time') || $errors->has('end_time') || $errors->has('department_id') || $errors->has('service_id') || $errors->has('doctor_id') || $errors->has('reason') || $errors->has('notes') || $errors->has('priority'))
        var modal = document.getElementById('followUpAppointmentModal');
        if (modal && window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    @endif
});

/* ================================================================
   EXPOSE TO GLOBAL SCOPE
   The Inertia legacy bridge wraps each script block in its own
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
