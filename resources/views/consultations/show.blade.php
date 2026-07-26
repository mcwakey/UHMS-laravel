@extends('layouts.app')
@section('title', __('consultations.workspace.page_title', ['visit' => $visit->visit_number]))

@push('styles')
<style>
    .consultation-sidebar .nav-link { padding: 0.4rem 0.75rem; border-radius: 0.4rem; color: #495057; font-size: 0.82rem; }
    .consultation-sidebar .nav-link.active { background-color: #e8f0fe; color: #1a73e8; font-weight: 600; }
    .consultation-sidebar .nav-link i { width: 18px; }
    .consultation-sidebar .badge { font-size: 0.6rem; }
    .consultation-actions-column { min-width: 0; }
    .consultation-quick-actions,
    .consultation-quick-actions .card-body,
    .consultation-quick-actions .d-grid,
    .consultation-quick-actions form { min-width: 0; max-width: 100%; }
    .consultation-quick-actions .d-grid { grid-template-columns: minmax(0, 1fr); }
    .consultation-quick-actions form { width: 100%; }
    .consultation-quick-actions .btn {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        white-space: normal;
        overflow-wrap: anywhere;
        line-height: 1.25;
    }
    .consultation-quick-actions .btn i { flex: 0 0 auto; }
    @media (min-width: 992px) {
        .consultation-side-column {
            flex: 0 0 auto;
            width: 20.833333%;
        }
        .consultation-main-column {
            flex: 0 0 auto;
            width: 58.333333%;
        }
    }
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
    #sessionsDrawer { background: #fff; border: 1px solid #d9e5ff; border-top: 3px solid #0d6efd; border-radius: 0.5rem; box-shadow: 0 0.125rem 0.35rem rgba(15, 23, 42, .05); margin-bottom: 1rem; overflow: hidden; }
    #sessionsDrawer.is-collapsed #sessionsDrawerBody { display: none; }
    #sessionsDrawerHandle { cursor: pointer; user-select: none; padding: .55rem .8rem; background: #f8fbff; color: #0d6efd; display: flex; align-items: center; gap: .5rem; flex-shrink: 0; border-bottom: 1px solid #e8eefc; }
    #sessionsDrawerHandle .ti-chevron-up { transition: transform .25s; }
    #sessionsDrawer.is-collapsed #sessionsDrawerHandle .ti-chevron-up { transform: rotate(180deg); }
    #sessionsDrawerBody { overflow-x: auto; }
    .consultation-preview-offcanvas { width: min(100vw, 1120px) !important; }
    .consultation-preview-offcanvas .offcanvas-body { background: #f8fafc; }
    .specialty-workspace-strip { border-left: 4px solid var(--specialty-accent, #0d6efd); }
</style>
@endpush

@section('content')

<x-patient-long-card :visit="$visit" :show-alerts="true" :active-admission="$consultationAdmission" />

@can('patients.edit')
{{-- <div class="card mb-3 border-danger-subtle"> --}}
    {{-- <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">
            <i class="ti ti-report-medical me-1 text-danger"></i>{{ __('patients.clinical_summary') }}
        </h6>
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clinicalSummaryModal">
            <i class="ti ti-edit me-1"></i>{{ __('common.edit') }}
        </button>
    </div> --}}
    {{-- <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted fs-12 text-uppercase fw-semibold mb-2">{{ __('patients.known_allergies') }}</div>
                    <div class="fw-medium text-wrap">{{ $visit->patient->allergies ?: __('common.not_available') }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted fs-12 text-uppercase fw-semibold mb-2">{{ __('patients.chronic_conditions') }}</div>
                    <div class="fw-medium text-wrap">{{ $visit->patient->chronic_conditions ?: __('common.not_available') }}</div>
                </div>
            </div>
        </div>
    </div> --}}
{{-- </div> --}}

<div class="modal fade" id="clinicalSummaryModal" tabindex="-1" aria-labelledby="clinicalSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ $workspaceRoutes->route('admin.patients.medical-summary.update', $visit->patient) }}">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="clinicalSummaryModalLabel">
                            <i class="ti ti-report-medical me-1 text-danger"></i>Patient Conditions
                        </h5>
                        <p class="text-muted mb-0 fs-13">{{ __('patients.medical_notes') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('patients.known_allergies') }}</label>
                            <textarea name="allergies" class="form-control @error('allergies') is-invalid @enderror" rows="6" placeholder="{{ __('patients.known_allergies_ph') }}">{{ old('allergies', $visit->patient->allergies) }}</textarea>
                            @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('patients.chronic_conditions') }}</label>
                            <textarea name="chronic_conditions" class="form-control @error('chronic_conditions') is-invalid @enderror" rows="6" placeholder="{{ __('patients.chronic_conditions_ph') }}">{{ old('chronic_conditions', $visit->patient->chronic_conditions) }}</textarea>
                            @error('chronic_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('patients.save_medical_summary') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@php
    $routeBadgeClasses = [
        \App\Models\VisitConsultationRoute::STATUS_ACTIVE => 'success',
        \App\Models\VisitConsultationRoute::STATUS_PENDING => 'warning',
        \App\Models\VisitConsultationRoute::STATUS_PAUSED => 'info',
        \App\Models\VisitConsultationRoute::STATUS_COMPLETED => 'secondary',
        \App\Models\VisitConsultationRoute::STATUS_CANCELLED => 'danger',
    ];
    $routeBadge = fn (?string $status) => $routeBadgeClasses[$status ?? ''] ?? 'light text-dark';
    $insuranceLabel = $visit->visitInsurance?->insuranceProvider?->name ?? __('consultations.cash_self_pay');
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
    $canCorrectLocked = auth()->user()?->can('consultation.entries.correct_completed') || auth()->user()?->can('visits.reopen_locked_session');
    $isSelectedRouteLocked = $selectedRoute && $selectedRoute->locked_at;
    $needsStart = $selectedRoute && (in_array($selectedRoute->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true) || ($selectedRoute->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE && ! $selectedRoute->started_at));
    $canEdit = (bool) ($sessionEligibilityActions['can_add_item'] ?? false);
    $canCreateEntries = $canEdit && (auth()->user()?->can('consultations.create') ?? false);
    $canStartSession = $needsStart && (auth()->user()?->can('consultations.create') ?? false) && ! ($sessionEligibilityActions['is_visit_clinically_locked'] ?? false);
    $canTransitionSession = (auth()->user()?->can('consultations.create') ?? false) && ! ($sessionEligibilityActions['is_visit_clinically_locked'] ?? false);
    $eligibilityLockMessage = $sessionEligibilityActions['lock_reason'] ?? null;
    $ownerOf = fn ($entry) => $entry?->creator ?? $entry?->createdBy ?? $entry?->doctor ?? $entry?->requestedBy ?? $entry?->requestingDoctor ?? null;
    $ownerKey = fn ($entry) => ($ownerOf($entry)?->id) ? 'user-'.$ownerOf($entry)->id : 'unknown';
    $ownerName = fn ($entry) => $ownerOf($entry)?->full_name ?? __('consultations.workspace.unknown');
    $ownerDisplayName = fn ($entry) => $ownerName($entry) === __('consultations.workspace.unknown') ? __('consultations.workspace.unknown') : 'Dr. '.$ownerName($entry);
    $isMainOwner = fn ($entry) => $selectedRoute?->doctor_id && $ownerOf($entry)?->id && (int) $selectedRoute->doctor_id === (int) $ownerOf($entry)->id;
    $ownerRoleLabel = fn ($entry) => $isMainOwner($entry) ? 'Main Doctor' : 'Contributor';
    $ownerRoleClass = fn ($entry) => $isMainOwner($entry) ? 'primary' : 'secondary';
    $ownerGroups = fn ($entries) => collect($entries ?? [])->groupBy(fn ($entry) => $ownerKey($entry));
    $entryFooter = function ($entry) {
        $bits = [];
        if ($entry?->created_at) {
            $bits[] = 'Created: '.$entry->created_at->format('d M Y, h:i A');
        }
        if (($updater = $entry?->updater ?? $entry?->updatedBy ?? null) && ($updater?->full_name ?? null) && $entry?->updated_at && $entry?->created_at && $entry->updated_at->gt($entry->created_at)) {
            $bits[] = 'Edited by: '.$updater->full_name.' '.$entry->updated_at->format('d M Y, h:i A');
        }
        if ($entry?->sourcePattern) {
            $bits[] = 'Source Pattern: '.$entry->sourcePattern->name;
        }
        return implode(' · ', $bits);
    };
    $canDeleteEntry = fn ($entry) => $canCreateEntries && auth()->user() && $entryPermissions->canDelete(auth()->user(), $entry);
    $canEditEntry = fn ($entry) => $canCreateEntries && auth()->user() && $entryPermissions->canEdit(auth()->user(), $entry);
    $contributors = $selectedRoute?->contributors?->map(fn ($contributor) => $contributor->user?->full_name)->filter()->unique()->values() ?? collect();
    $isEmergencyRoute = $selectedRoute?->isEmergencySession() ?? false;
    $emergencyCase = $selectedRoute?->emergencyCase;
    $emergencySession = $selectedRoute?->emergencySession;
    $emergencyReadOnly = $isEmergencyRoute && in_array($emergencyCase?->emergency_status, [
        \App\Models\EmergencyCase::STATUS_DISPOSED,
        \App\Models\EmergencyCase::STATUS_CANCELLED,
    ], true);
    $selectedRouteLabel = $isEmergencyRoute
        ? __('consultations.emergency_department_session')
        : ($selectedRoute?->department?->name ?? __('consultations.no_active_session'));
    $specialtyLayout = $specialtyLayout ?? ['sections' => [], 'profile' => []];
    $layoutSections = collect($specialtyLayout['sections'] ?? []);
    $specialtyAccentMap = [
        'primary' => '#0d6efd',
        'success' => '#198754',
        'info' => '#0dcaf0',
        'warning' => '#ffc107',
        'danger' => '#dc3545',
        'secondary' => '#6c757d',
    ];
    $specialtyColor = $specialtyLayout['profile']['color'] ?? 'primary';
    $specialtyAccent = str_starts_with((string) $specialtyColor, '#')
        ? $specialtyColor
        : ($specialtyAccentMap[$specialtyColor] ?? $specialtyAccentMap['primary']);
    $layoutTabSections = $layoutSections
        ->filter(fn ($section) => ! empty($section['tab_target']))
        ->unique('tab_target')
        ->values();
    $treatmentPlanTab = [
        'key' => 'treatments',
        'canonical_key' => 'treatments',
        'label' => __('consultation_specialties.sections.treatment_plan'),
        'translated_label' => __('consultation_specialties.sections.treatment_plan'),
        'component' => 'consultations.partials.specialty.core-section',
        'display_order' => 0,
        'is_required' => false,
        'is_visible' => true,
        'is_core' => true,
        'tab_target' => 'treatments-section',
        'icon' => 'ti-target-arrow',
        'config' => [],
        'form_fields' => [],
    ];
    $followUpTab = [
        'key' => 'follow_up_appointment',
        'canonical_key' => 'follow_up_appointment',
        'label' => __('consultations.workspace.follow_up_title'),
        'translated_label' => __('consultations.workspace.follow_up_title'),
        'component' => 'consultations.partials.specialty.core-section',
        'display_order' => 0,
        'is_required' => false,
        'is_visible' => true,
        'is_core' => true,
        'tab_target' => 'follow-up-section',
        'icon' => 'ti-calendar-time',
        'config' => [],
        'form_fields' => [],
    ];
    if (! $layoutTabSections->contains(fn ($section) => ($section['tab_target'] ?? null) === 'treatments-section')) {
        $insertedTreatmentPlanTab = false;
        $layoutTabSections = $layoutTabSections
            ->flatMap(function ($section) use ($treatmentPlanTab, &$insertedTreatmentPlanTab) {
                $sections = [$section];
                if (! $insertedTreatmentPlanTab && ($section['tab_target'] ?? null) === 'investigations-section') {
                    $sections[] = $treatmentPlanTab;
                    $insertedTreatmentPlanTab = true;
                }

                return $sections;
            })
            ->when(! $insertedTreatmentPlanTab, fn ($sections) => $sections->push($treatmentPlanTab))
            ->values();
    }
    if (! $layoutTabSections->contains(fn ($section) => ($section['tab_target'] ?? null) === 'follow-up-section')) {
        $insertedFollowUpTab = false;
        $layoutTabSections = $layoutTabSections
            ->flatMap(function ($section) use ($followUpTab, &$insertedFollowUpTab) {
                $sections = [$section];
                if (! $insertedFollowUpTab && in_array(($section['tab_target'] ?? null), ['treatments-section', 'prescriptions-section', 'tasks-section'], true)) {
                    $sections[] = $followUpTab;
                    $insertedFollowUpTab = true;
                }

                return $sections;
            })
            ->when(! $insertedFollowUpTab, fn ($sections) => $sections->push($followUpTab))
            ->values();
    }
    $activeTabTarget = $layoutTabSections->first()['tab_target'] ?? 'complaints-section';
    $tabActiveClass = fn (string $target) => $activeTabTarget === $target ? 'show active' : '';
    $sectionLabel = function (string $key, string $fallback) use ($layoutSections) {
        $section = $layoutSections->firstWhere('canonical_key', $key) ?? $layoutSections->firstWhere('key', $key);
        return $section['translated_label'] ?? $fallback;
    };
@endphp

@include('consultations.partials.session-context')

<!-- @include('consultations.partials.specialty-workspace-band') -->

{{-- Phase 14R.3.1 — Maternity context (Obstetrics only, flag-gated).
     Placed after the session/specialty header and before the main specialty
     content. Both partials consume the SAME prepared view model built once in
     HandlesConsultationWorkspace; they never resolve or query on their own, and
     render nothing unless the workspace flag is on and the profile is
     Obstetrics. They do not replace the patient banner, specialty banner,
     admission context, visit status or session controls. --}}
@include('consultations.partials.maternity.context-ribbon', ['maternity' => $maternityContext ?? null])

@include('consultations.partials.specialty-order-sets')

@include('consultations.partials.consultation-gating')

@include('consultations.partials.maternity.context-panel', ['maternity' => $maternityContext ?? null])

{{-- Phase 14R.4.1 — small Gynaecology pregnancy-context card. Explicit-only,
     flag-gated, and renders nothing for non-Gynaecology profiles. Shares the
     single view model built in HandlesConsultationWorkspace. --}}
@include('consultations.partials.maternity.gynaecology-context-card', ['gynaecology' => $gynaecologyContext ?? null])
@include('consultations.partials.maternity.handoff-actions', ['handoffs' => $maternityHandoffs ?? null])
{{-- ============================================================ --}}
{{-- MAIN 3-COLUMN LAYOUT --}}
{{-- ============================================================ --}}

    <div class="col-lg-12">
        <div class="row g-3">

            @include('consultations.partials.workflow-sidebar')
            {{-- =================== MAIN CONTENT =================== --}}
            <div class="col-lg-12 consultation-main-column">
                <div class="tab-content" id="consultationTabContent">

                    {{-- ========================= COMPLAINTS ========================= --}}
                    <div class="tab-pane fade {{ $tabActiveClass('complaints-section') }}" id="complaints-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-message-report me-1"></i>{{ $sectionLabel('complaints', __('consultations.workspace.complaints')) }}</h6>
                                @if($canCreateEntries)
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addComplaintForm">
                                    <i class="ti ti-plus me-1"></i>{{ __('common.add') }}
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addComplaintForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="complaints" data-consultation-form="complaints" data-refresh-section="complaints" data-route-context-required="true" action="{{ $workspaceRoutes->route('admin.consultations.complaints.store', $visit) }}" method="POST">
                                            @csrf
                                            <x-consultation-idempotency-key action="complaint.create" />
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label small">Complaint <span class="text-danger">*</span></label>
                                                    <input type="hidden" name="complaint_catalogue_id" id="complaintCatalogueIdInput">
                                                    <div class="position-relative">
                                                        <input type="text" name="description" id="complaintDescInput" class="form-control" required placeholder="Search catalogue or type a custom complaint..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="complaintSuggestionMenu">
                                                        <div id="complaintSuggestionMenu" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index:1060;max-height:240px;overflow:auto;"></div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Duration</label>
                                                    <input type="text" name="duration" class="form-control" placeholder="e.g., 3">
                                                    <input type="hidden" name="duration_unit" value="">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Severity</label>
                                                    <select name="severity" class="form-select">
                                                        <option value="">-- Select --</option>
                                                        <option value="mild">{{ __('consultations.severity.mild') }}</option>
                                                        <option value="moderate">{{ __('consultations.severity.moderate') }}</option>
                                                        <option value="severe">{{ __('consultations.severity.severe') }}</option>
                                                        <option value="critical">{{ __('consultations.severity.critical') }}</option>
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
                                @endif

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
                                                            data-consultation-action="edit-entry"
                                                            data-entry-type="complaint"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.complaints.update', $complaint) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @endif
                                                    @if($canDeleteEntry($complaint))
                                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                            data-consultation-action="delete-entry"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.complaints.destroy', $complaint) }}"
                                                            data-target="#complaint-{{ $complaint->id }}"
                                                            data-badge="badge-complaints"
                                                            data-confirm="{{ __('consultations.remove_complaint') }}" aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}">
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
                                        <i class="ti ti-message-report fs-1 d-block mb-2"></i>{{ __('consultations.workspace.no_complaints') }}
                                    </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ========================= HISTORY OF PRESENTING COMPLAINT ========================= --}}
                    <div class="tab-pane fade {{ $tabActiveClass('hopc-section') }}" id="hopc-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-file-description me-1"></i>{{ $sectionLabel('hopc', 'History of Presenting Complaint') }} <span class="visually-hidden">HOPC</span></h6>
                                <div>
                                    @if($canCreateEntries)
                                    @can('patients.edit')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clinicalSummaryModal">
                                        <i class="ti ti-edit me-1"></i>Manage Patient Conditions
                                    </button>
                                    @endcan

                                    <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addHopcForm">
                                        <i class="ti ti-plus me-1"></i>Add
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addHopcForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="hopc" data-consultation-form="hopc" data-refresh-section="hopc" data-route-context-required="true" action="{{ $workspaceRoutes->route('admin.consultations.hopc.store', $visit) }}" method="POST">
                                            @csrf
                                            <x-consultation-idempotency-key action="hopc.create" />
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label small">Link to Complaint <small class="text-muted">(optional)</small></label>
                                                    <select name="complaint_id" id="hopcComplaintSelect" class="form-select form-select-sm" data-consultation-action="hydrate-hopc-complaint" data-consultation-refresh-control="true">
                                                        <option value="">{{ __('consultations.general_narrative') }}</option>
                                                        @foreach($record?->complaints ?? [] as $complaint)
                                                            <option value="{{ $complaint->id }}"
                                                                data-description="{{ $complaint->description }}"
                                                                data-duration="{{ trim($complaint->duration.' '.($complaint->duration_unit ?? '')) }}"
                                                                data-severity="{{ $complaint->severity }}">
                                                                {{ Str::limit($complaint->description, 80) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small">Narrative <span class="text-danger">*</span></label>
                                                    <textarea name="content" id="hopcContentInput" class="form-control" rows="4" placeholder="Detailed story behind the complaints..."></textarea>
                                                </div>
                                                <div class="col-md-3"><input name="onset" class="form-control form-control-sm" placeholder="Onset"></div>
                                                <div class="col-md-3"><input name="duration" id="hopcDurationInput" class="form-control form-control-sm" placeholder="Duration"></div>
                                                <div class="col-md-3"><input name="location" class="form-control form-control-sm" placeholder="Location"></div>
                                                <div class="col-md-3"><input name="severity" id="hopcSeverityInput" class="form-control form-control-sm" placeholder="Severity"></div>
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
                                @endif

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
                                                        <small class="badge bg-secondary">Complaint: {{ $hopc->complaint->description }}</small>
                                                    @else
                                                        <small class="badge bg-primary">General Narrative</small>
                                                    @endif
                                                    @if($entryFooter($hopc))<small class="text-muted">{{ $entryFooter($hopc) }}</small>@endif
                                                </div>
                                                <div class="entry-actions d-flex gap-1">
                                                    @if($canEditEntry($hopc))
                                                    @php
                                                        $editEntryPayload = ['content' => $hopc->content, 'complaint_id' => $hopc->complaint_id, 'onset' => $hopc->onset, 'duration' => $hopc->duration, 'location' => $hopc->location, 'severity' => $hopc->severity, 'aggravating_factors' => $hopc->aggravating_factors, 'relieving_factors' => $hopc->relieving_factors, 'associated_symptoms' => $hopc->associated_symptoms];
                                                    @endphp
                                                    <button aria-label="Edit" title="Edit" type="button" class="btn btn-xs btn-outline-primary edit-entry-btn"
                                                            data-consultation-action="edit-entry"
                                                            data-entry-type="hopc"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.hopc.update', $hopc) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @endif
                                                    @if($canDeleteEntry($hopc))
                                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                            data-consultation-action="delete-entry"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.hopc.destroy', $hopc) }}"
                                                            data-target="#hopc-{{ $hopc->id }}"
                                                            data-badge="badge-hopc"
                                                            data-confirm="{{ __('consultations.remove_history') }}" aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}">
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
                    <div class="tab-pane fade {{ $tabActiveClass('examination-section') }}" id="examination-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-zoom-check me-1"></i>{{ $sectionLabel('examination', 'Examination / Physical Examination') }}</h6>
                                @if($canCreateEntries)
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addExaminationForm">
                                    <i class="ti ti-plus me-1"></i>Add
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addExaminationForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="examination" data-consultation-form="examination" data-refresh-section="examination" data-route-context-required="true" action="{{ $workspaceRoutes->route('admin.consultations.examinations.store', $visit) }}" method="POST">
                                            @csrf
                                            <x-consultation-idempotency-key action="examination.create" />
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
                                @endif

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
                                                            data-consultation-action="edit-entry"
                                                            data-entry-type="examination"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.examinations.update', $exam) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @endif
                                                    @if($canDeleteEntry($exam))
                                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                            data-consultation-action="delete-entry"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.examinations.destroy', $exam) }}"
                                                            data-target="#examination-{{ $exam->id }}"
                                                            data-badge="badge-examination"
                                                            data-confirm="{{ __('consultations.remove_examination') }}" aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}">
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
                    <div class="tab-pane fade {{ $tabActiveClass('diagnoses-section') }}" id="diagnoses-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>{{ $sectionLabel('diagnosis', 'Diagnoses') }} <span class="visually-hidden">Diagnoses</span></h6>
                                @if($canCreateEntries)
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addDiagnosisForm">
                                    <i class="ti ti-plus me-1"></i>Add
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addDiagnosisForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="diagnoses" data-consultation-form="diagnoses" data-refresh-section="diagnoses" data-route-context-required="true" action="{{ $workspaceRoutes->route('admin.consultations.diagnoses.store', $visit) }}" method="POST">
                                            @csrf
                                            <x-consultation-idempotency-key action="diagnosis.create" />
                                            @php
                                                $diagnosisFavorites = collect($specialtyFavorites['diagnosis'] ?? []);
                                            @endphp
                                            @if($diagnosisFavorites->isNotEmpty())
                                                <div class="mb-2">
                                                    <div class="small text-muted fw-semibold mb-1">{{ __('consultation_specialties.favorites.specialty_favorites') }}</div>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($diagnosisFavorites->take(8) as $favorite)
                                                            <button type="button" class="btn btn-outline-primary btn-xs" data-consultation-action="insert-favorite" data-target="#diagnosis_description" data-favorite-label="{{ $favorite['label'] }}">
                                                                {{ $favorite['label'] }} <span class="badge bg-primary-subtle text-primary ms-1">{{ __('consultation_specialties.favorites.badge') }}</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label small">ICD-10 <small class="text-muted">(optional search)</small></label>
                                                    <input type="hidden" name="icd_code_id" id="icd_code_id">
                                                    <select id="icd_code_select" class="form-select" style="width:100%">
                                                        <option value="">{{ __('consultations.search_icd10') }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label small">Description <span class="text-danger">*</span></label>
                                                    <input type="text" name="description" id="diagnosis_description" class="form-control" required placeholder="Type diagnosis or search ICD-10 above..." autocomplete="off" list="diagnosisSuggestions">
                                                    <datalist id="diagnosisSuggestions">
                                                        @foreach($diagnosisFavorites as $favorite)
                                                            <option value="{{ $favorite['label'] }}"></option>
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small">ICD-10 Code (Manual)</label>
                                                    <input type="text" name="icd_code" id="icd_code_manual" class="form-control" placeholder="e.g., J06.9">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small">Type</label>
                                                    <select name="type" class="form-select">
                                                        <option value="provisional">{{ __('consultations.diagnosis_type.provisional') }}</option>
                                                        <option value="final">{{ __('consultations.diagnosis_type.final') }}</option>
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
                                @endif

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
                                                    <small class="badge bg-light text-muted">
                                                        @if($diagnosis->icdCodeEntry) ICD-10: <code class="fs-6">{{ $diagnosis->icdCodeEntry->code }}</code> &middot;
                                                        @elseif($diagnosis->icd_code) ICD-10: <code class="fs-6">{{ $diagnosis->icd_code }}</code> &middot; @endif
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
                                                            data-consultation-action="edit-entry"
                                                            data-entry-type="diagnosis"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.diagnoses.update', $diagnosis) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @if($diagnosis->type === 'provisional')
                                                    <button type="button" class="btn btn-xs btn-outline-success mark-final-btn"
                                                            data-consultation-action="mark-diagnosis-final"
                                                            title="{{ __('consultations.mark_as_final') }}"
                                                            data-id="{{ $diagnosis->id }}"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.diagnoses.update', $diagnosis) }}">
                                                        <i class="ti ti-check me-1"></i>{{ __('consultations.final') }}
                                                    </button>
                                                    @endif
                                                    <button type="button" class="btn btn-xs btn-outline-warning set-primary-btn {{ $diagnosis->is_primary ? 'd-none' : '' }}"
                                                            data-consultation-action="set-primary-diagnosis"
                                                            title="{{ __('consultations.set_primary_diagnosis') }}"
                                                            id="set-primary-{{ $diagnosis->id }}"
                                                            data-id="{{ $diagnosis->id }}"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.diagnoses.primary', $diagnosis) }}">
                                                        <i class="ti ti-star"></i>
                                                    </button>
                                                    @if($canDeleteEntry($diagnosis))
                                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                            data-consultation-action="delete-entry"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.diagnoses.destroy', $diagnosis) }}"
                                                            data-target="#diagnosis-{{ $diagnosis->id }}"
                                                            data-badge="badge-diagnoses"
                                                            data-confirm="{{ __('consultations.remove_diagnosis') }}" aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}">
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
                    <div class="tab-pane fade {{ $tabActiveClass('investigations-section') }}" id="investigations-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-test-pipe me-1"></i>{{ $sectionLabel('investigations', 'Investigations') }}</h6>
                                @if($canCreateEntries)
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addInvestigationForm">
                                    <i class="ti ti-plus me-1"></i>Add
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addInvestigationForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="investigations" data-consultation-form="investigations" data-refresh-section="investigations" data-route-context-required="true" action="{{ $workspaceRoutes->route('admin.consultations.investigations.store', $visit) }}" method="POST">
                                            @csrf
                                            <x-consultation-idempotency-key action="investigation.create" />
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small">Department <span class="text-danger">*</span></label>
                                                    @if($investigationDepts->isNotEmpty())
                                                        <select name="department_id" id="investigationDeptSelect" class="form-select" required data-consultation-action="load-investigation-services">
                                                            <option value="">-- Select Department --</option>
                                                            @foreach($investigationDepts as $dept)
                                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <select id="investigationDeptFallbackSelect" class="form-select d-none"></select>
                                                        <input type="text" name="investigation_type" class="form-control" required placeholder="e.g., Blood Test, X-Ray...">
                                                        <small class="text-muted">No investigation departments configured.</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Urgency</label>
                                                    <select name="urgency" class="form-select">
                                                        <option value="routine">{{ __('consultations.urgency.routine') }}</option>
                                                        <option value="urgent">{{ __('consultations.urgency.urgent') }}</option>
                                                        <option value="emergency">{{ __('consultations.urgency.emergency') }}</option>
                                                    </select>
                                                </div>
                                                @if($investigationDepts->isNotEmpty())
                                                <div class="col-12">
                                                    <label class="form-label small">Select Services <span class="text-danger">*</span></label>
                                                    <select name="service_ids[]" id="investigationServicesSelect" class="form-select" multiple disabled data-placeholder="{{ __('consultations.search_services') }}"></select>
                                                    <small id="investigationServicesHelp" class="text-muted">Select a department first to load services</small>
                                                    @php
                                                        $investigationFavorites = collect($specialtyFavorites['investigation'] ?? []);
                                                    @endphp
                                                    @if($investigationFavorites->isNotEmpty())
                                                        <div class="small text-muted fw-semibold mt-2">{{ __('consultation_specialties.favorites.specialty_suggestions') }}</div><div class="mt-1 d-flex flex-wrap gap-1">
                                                            @foreach($investigationFavorites->take(8) as $favorite)
                                                                <span class="badge bg-info-subtle text-info">{{ $favorite['label'] }}</span>
                                                            @endforeach
                                                        </div>
                                                        <small class="text-muted">{{ __('consultation_specialties.favorites.use_global_search_hint') }}</small>
                                                    @endif
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
                                @endif

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
                                        @php
                                            $departmentReqs = collect($reqs);
                                            $firstDepartmentReq = $departmentReqs->first();
                                            $departmentId = $firstDepartmentReq?->target_department_id ?: $firstDepartmentReq?->department_id;
                                            $departmentQueued = $departmentId && collect($visit->queueEntries ?? [])->contains(fn($entry) => (int) $entry->department_id === (int) $departmentId && in_array($entry->status, ['waiting', 'serving'], true));
                                            $departmentSendable = $canEdit
                                                && $selectedRoute
                                                && $departmentId
                                                && ! $departmentQueued
                                                && $departmentReqs->contains(fn($r) => $r->status === 'pending' && (! $r->consultation_route_id || (int) $r->consultation_route_id === (int) $selectedRoute->id));
                                        @endphp
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2 gap-2">
                                                <h6 class="small fw-bold mb-0 text-uppercase text-muted">
                                                    <i class="ti ti-building-hospital me-1"></i>{{ $deptName }}
                                                    <span class="badge bg-light text-dark ms-1">{{ $departmentReqs->count() }} {{ Str::plural('request', $departmentReqs->count()) }}</span>
                                                </h6>
                                                @if($departmentSendable)
                                                <form method="POST"
                                                      action="{{ $workspaceRoutes->route('admin.consultations.investigation-departments.send-to-department', [$visit, $departmentId]) }}"
                                                      class="d-inline"
                                                      data-confirm="{{ __('messages.consultations.investigation_handoff_confirm', ['department' => $deptName]) }}">
                                                    @csrf
                                                    <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute->id }}">
                                                    <button type="submit" class="btn btn-xs btn-outline-info" data-loading-text="{{ __('messages.consultations.investigation_handoff_queueing') }}" title="{{ __('messages.consultations.investigation_handoff_confirm', ['department' => $deptName]) }}">
                                                        <i class="ti ti-route me-1"></i>{{ __('messages.consultations.investigation_handoff_button') }}
                                                    </button>
                                                </form>
                                                @elseif($departmentQueued)
                                                <button type="button" class="btn btn-xs btn-outline-secondary" disabled title="{{ __('messages.consultations.investigation_handoff_queued_title', ['department' => $deptName]) }}">
                                                    <i class="ti ti-clock-check me-1"></i>{{ __('messages.consultations.investigation_handoff_queued') }}
                                                </button>
                                                @endif
                                            </div>
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
                                                                            data-consultation-action="edit-entry"
                                                                            data-entry-type="lab-request"
                                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.lab-request.update', $req) }}"
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
                                                                            data-consultation-action="view-result"
                                                                            data-url="{{ $workspaceRoutes->route('admin.lab.results.view', $item) }}"
                                                                            title="View Result"><i class="ti ti-eye"></i></button>
                                                                    @endif
                                                                    @if($item->result?->is_verified)
                                                                    <a data-no-inertia href="{{ $workspaceRoutes->route('admin.lab.results.print', $item) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Print"><i class="ti ti-printer"></i></a>
                                                                    @endif
                                                                    @if($canCreateEntries)
                                                                    @if($item->isDeletable() && $canEdit)
                                                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                                            data-consultation-action="delete-entry"
                                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.investigation-items.destroy', $item) }}"
                                                                            data-method="DELETE"
                                                                            data-target="#lab-item-{{ $item->id }}"
                                                                            data-confirm="{{ __('consultations.remove_investigation_item') }}"
                                                                            title="{{ __('common.delete') }}"><i class="ti ti-trash"></i></button>
                                                                    @endif
                                                                    @endif
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
                    <div class="tab-pane fade {{ $tabActiveClass('treatments-section') }}" id="treatments-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-target-arrow me-1"></i>{{ $sectionLabel('treatments', __('consultation_specialties.sections.treatment_plan')) }}{{-- Backward-compatible stable clinical-section marker: the visible label is specialty-aware ("Treatment Plan") but the canonical "Treatments" marker keeps the required clinical-order contract stable. --}}<span class="visually-hidden" data-clinical-section="treatments">Treatments</span></h6>
                                @if($canCreateEntries)
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addTreatmentForm">
                                    <i class="ti ti-plus me-1"></i>Add
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addTreatmentForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="treatments" data-consultation-form="treatments" data-refresh-section="treatments" data-route-context-required="true" action="{{ $workspaceRoutes->route('admin.consultations.treatments.store', $visit) }}" method="POST">
                                            @csrf
                                            <x-consultation-idempotency-key action="treatment.create" />
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Type <span class="text-danger">*</span></label>
                                                    <select name="type" class="form-select" required>
                                                        <option value="">-- Select --</option>
                                                        <option value="medication">{{ __('consultations.treatment_type.medication') }}</option>
                                                        <option value="procedure">{{ __('consultations.treatment_type.procedure') }}</option>
                                                        <option value="referral">{{ __('consultations.treatment_type.referral') }}</option>
                                                        <option value="advice">{{ __('consultations.treatment_type.advice') }}</option>
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
                                @endif

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
                                                            data-consultation-action="edit-entry"
                                                            data-entry-type="treatment"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.treatments.update', $treatment) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @endif
                                                    @if($canDeleteEntry($treatment))
                                                    <button type="button" class="btn btn-xs btn-outline-danger ajax-delete"
                                                            data-consultation-action="delete-entry"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.treatments.destroy', $treatment) }}"
                                                            data-target="#treatment-{{ $treatment->id }}"
                                                            data-badge="badge-treatments"
                                                            data-confirm="{{ __('consultations.remove_treatment') }}" aria-label="{{ __('common.delete') }}" title="{{ __('common.delete') }}">
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
                    <div class="tab-pane fade {{ $tabActiveClass('prescriptions-section') }}" id="prescriptions-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-prescription me-1"></i>{{ $sectionLabel('prescription', 'Prescriptions') }} <span class="visually-hidden">Prescriptions</span></h6>
                                @if($canCreateEntries && (auth()->user()?->can('prescriptions.create') ?? false))
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addPrescriptionForm">
                                    <i class="ti ti-plus me-1"></i>New Rx
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries && (auth()->user()?->can('prescriptions.create') ?? false))
                                <div class="collapse mb-3" id="addPrescriptionForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="prescriptions" data-consultation-form="prescriptions" data-refresh-section="prescriptions" data-route-context-required="true" data-prepare="prescription" method="POST" action="{{ $workspaceRoutes->route('admin.consultations.prescriptions.store', $visit) }}" id="prescriptionForm">
                                            @csrf
                                            <x-consultation-idempotency-key action="prescription.create" />
                                            <div id="prescriptionFormErrors" class="alert alert-danger d-none small py-2 mb-2"></div>
                                            <div id="prescriptionSafetyOverride" class="alert alert-warning d-none small py-2 mb-2" data-prescription-safety-panel>
                                                <div class="fw-semibold mb-1"><i class="ti ti-alert-triangle me-1"></i>{{ __('consultation.safety.prescription_warning') }}</div>
                                                <ul class="mb-2 ps-3" data-prescription-safety-warnings></ul>
                                                <div data-prescription-safety-codes></div>
                                                <label class="form-label small fw-semibold" for="safety_override_reason">{{ __('consultation.safety.override_reason') }}</label>
                                                <textarea id="safety_override_reason" name="safety_override_reason" class="form-control form-control-sm" rows="2" maxlength="1000" placeholder="{{ __('consultation.safety.override_required') }}"></textarea>
                                            </div>
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
                                                            @php
                                                                $drugFavorites = collect($specialtyFavorites['drug'] ?? []);
                                                            @endphp
                                                            @if($drugFavorites->isNotEmpty())
                                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                                    @foreach($drugFavorites->take(6) as $favorite)
                                                                        <span class="badge bg-primary-subtle text-primary">{{ $favorite['label'] }}</span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            <input type="hidden" name="items[0][drug_name]" class="drug-name-input">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small">Dosage <span class="text-danger">*</span></label>
                                                            <input type="text" name="items[0][dosage]" class="form-control form-control-sm" required placeholder="500mg">
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label small">Freq <span class="text-danger">*</span></label>
                                                            <select name="items[0][frequency]" class="form-select form-select-sm" required>
                                                                @foreach($prescriptionFrequencyOptions as $option)
                                                                    <option value="{{ $option['value'] }}" @selected($option['value'] === 'TDS')>{{ $option['label'] }}{{ ($option['is_favorite'] ?? false) ? ' - '.__('consultation_specialties.favorites.badge') : '' }}</option>
                                                                @endforeach
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
                                                                <option value="oral">{{ __('consultations.medicine_route.oral') }}</option>
                                                                <option value="IV">IV</option>
                                                                <option value="IM">IM</option>
                                                                <option value="SC">SC</option>
                                                                <option value="topical">{{ __('consultations.medicine_route.topical') }}</option>
                                                                <option value="inhaled">{{ __('consultations.medicine_route.inhaled') }}</option>
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
                                                <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn" data-consultation-action="add-prescription-item">
                                                    <i class="ti ti-plus me-1"></i>Add Medication
                                                </button>
                                                <div class="d-flex gap-2">
                                                    <input type="text" name="notes" class="form-control form-control-sm" style="width:170px" placeholder="Rx Notes...">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="ti ti-check me-1"></i>Create Rx
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endif

                                <div id="prescriptions-list">
                                    @php
                                        $prescriptionTargetDepartment = function($prescription) use ($pharmacyFallbackDepartment) {
                                            $department = $prescription->department;
                                            $type = $department?->type instanceof \App\Enums\DepartmentType ? $department->type->value : (string) $department?->type;
                                            return $type === \App\Enums\DepartmentType::PHARMACY->value ? $department : $pharmacyFallbackDepartment;
                                        };
                                        $prescriptionDeptGroups = collect($record?->prescriptions ?? [])->groupBy(fn($prescription) => $prescriptionTargetDepartment($prescription)?->id ?? 'pharmacy');
                                    @endphp
                                    @forelse($prescriptionDeptGroups as $deptKey => $deptPrescriptions)
                                    @php
                                        $firstPrescription = $deptPrescriptions->first();
                                        $prescriptionDepartment = $firstPrescription ? $prescriptionTargetDepartment($firstPrescription) : null;
                                        $prescriptionDepartmentId = $prescriptionDepartment?->id;
                                        $prescriptionDepartmentName = $prescriptionDepartment?->name ?? 'Pharmacy';
                                        $prescriptionQueued = $prescriptionDepartmentId && collect($visit->queueEntries ?? [])->contains(fn($entry) => (int) $entry->department_id === (int) $prescriptionDepartmentId && in_array($entry->status, ['waiting', 'serving'], true));
                                        $prescriptionSendable = $canEdit
                                            && $selectedRoute
                                            && $prescriptionDepartmentId
                                            && ! $prescriptionQueued
                                            && $deptPrescriptions->contains(fn($prescription) => in_array($prescription->status->value, ['pending', 'partially_selected', 'partially_billed', 'billed'], true) && (! $prescription->consultation_route_id || (int) $prescription->consultation_route_id === (int) $selectedRoute->id));
                                    @endphp
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2 gap-2">
                                            <h6 class="small fw-bold mb-0 text-uppercase text-muted">
                                                <i class="ti ti-building-hospital me-1"></i>{{ $prescriptionDepartmentName }}
                                                <span class="badge bg-light text-dark ms-1">{{ $deptPrescriptions->count() }} {{ Str::plural('prescription', $deptPrescriptions->count()) }}</span>
                                            </h6>
                                            @if($prescriptionSendable)
                                            <form method="POST"
                                                  action="{{ $workspaceRoutes->route('admin.consultations.prescription-departments.send-to-department', [$visit, $prescriptionDepartmentId]) }}"
                                                  class="d-inline"
                                                  data-confirm="{{ __('messages.consultations.investigation_handoff_confirm', ['department' => $prescriptionDepartmentName]) }}">
                                                @csrf
                                                <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute->id }}">
                                                <button type="submit" class="btn btn-xs btn-outline-info" data-loading-text="{{ __('messages.consultations.investigation_handoff_queueing') }}" title="{{ __('messages.consultations.investigation_handoff_confirm', ['department' => $prescriptionDepartmentName]) }}">
                                                    <i class="ti ti-route me-1"></i>{{ __('messages.consultations.investigation_handoff_button') }}
                                                </button>
                                            </form>
                                            @elseif($prescriptionQueued)
                                            <button type="button" class="btn btn-xs btn-outline-secondary" disabled title="{{ __('messages.consultations.investigation_handoff_queued_title', ['department' => $prescriptionDepartmentName]) }}">
                                                <i class="ti ti-clock-check me-1"></i>{{ __('messages.consultations.investigation_handoff_queued') }}
                                            </button>
                                            @endif
                                        </div>
                                    @foreach($ownerGroups($deptPrescriptions) as $group)
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
                                                            data-consultation-action="edit-entry"
                                                            data-entry-type="prescription"
                                                            data-url="{{ $workspaceRoutes->route('admin.consultations.prescriptions.update', $prescription) }}"
                                                            data-entry='@json($editEntryPayload)'>
                                                        <i class="ti ti-edit"></i>
                                                    </button>
                                                    @elseif(! in_array($prescription->status->value, ['pending', 'active']))
                                                        <small class="text-muted"><i class="ti ti-lock me-1"></i>Locked</small>
                                                    @endif
                                                    @if($canDeleteEntry($prescription) && in_array($prescription->status->value, ['pending', 'active']))
                                                    <form method="POST" action="{{ $workspaceRoutes->route('admin.consultations.prescriptions.destroy', $prescription) }}"
                                                        data-preserve-tab="prescriptions-section">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Delete prescription" data-confirm="{{ __('consultations.cancel_delete_prescription') }}">
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
                    <div class="tab-pane fade {{ $tabActiveClass('procedures-section') }}" id="procedures-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-activity-heartbeat me-1"></i>{{ $sectionLabel('procedures', 'Theatre / Procedure Requests') }} <span class="visually-hidden">Procedures</span></h6>
                                @if($canCreateEntries && (auth()->user()?->can('procedure.request') ?? false))
                                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addProcedureForm">
                                    <i class="ti ti-plus me-1"></i>Request Procedure
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries && (auth()->user()?->can('procedure.request') ?? false))
                                <div class="collapse mb-3" id="addProcedureForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="procedures" data-consultation-form="procedures" data-refresh-section="procedures" data-route-context-required="true" method="POST" action="{{ $workspaceRoutes->route('admin.consultations.procedures.store', $visit) }}">
                                            @csrf
                                            <x-consultation-idempotency-key action="procedure.create" />
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small">Theatre / Procedure Department <span class="text-danger">*</span></label>
                                                    <select name="department_id" id="procedureDeptSelect" class="form-select form-select-sm" required data-consultation-action="load-procedure-services">
                                                        <option value="">-- Select department --</option>
                                                        @foreach($procedureDepartments as $dept)
                                                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Service <span class="text-danger">*</span></label>
                                                    <select name="service_catalog_id" id="procedureServiceSelect" class="form-select form-select-sm" required disabled data-placeholder="{{ __('consultations.search_procedure_service') }}">
                                                        <option value="">{{ __('consultations.select_department_first') }}</option>
                                                    </select>
                                                    @php
                                                        $procedureFavorites = collect($specialtyFavorites['procedure'] ?? []);
                                                    @endphp
                                                    @if($procedureFavorites->isNotEmpty())
                                                        <div class="small text-muted fw-semibold mt-2">{{ __('consultation_specialties.favorites.specialty_suggestions') }}</div><div class="mt-1 d-flex flex-wrap gap-1">
                                                            @foreach($procedureFavorites->take(8) as $favorite)
                                                                <span class="badge bg-warning-subtle text-warning">{{ $favorite['label'] }}</span>
                                                            @endforeach
                                                        </div>
                                                        <small class="text-muted">{{ __('consultation_specialties.favorites.use_global_search_hint') }}</small>
                                                    @endif
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small">Priority <span class="text-danger">*</span></label>
                                                    <select name="priority" class="form-select form-select-sm" required>
                                                        <option value="routine">{{ __('consultations.urgency.routine') }}</option>
                                                        <option value="urgent">{{ __('consultations.urgency.urgent') }}</option>
                                                        <option value="emergency">{{ __('consultations.urgency.emergency') }}</option>
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
                                @endif

                                <div id="procedures-list">
                                    @php
                                        $procedureDeptGroups = collect($procedureRequests ?? [])->groupBy(fn($pr) => $pr->department?->name ?? 'Other');
                                    @endphp
                                    @forelse($procedureDeptGroups as $deptName => $deptProcedures)
                                    @php
                                        $firstProcedure = $deptProcedures->first();
                                        $procedureDepartmentId = $firstProcedure?->department_id;
                                        $procedureQueued = $procedureDepartmentId && collect($visit->queueEntries ?? [])->contains(fn($entry) => (int) $entry->department_id === (int) $procedureDepartmentId && in_array($entry->status, ['waiting', 'serving'], true));
                                        $procedureSendable = $canEdit
                                            && $selectedRoute
                                            && $procedureDepartmentId
                                            && ! $procedureQueued
                                            && $deptProcedures->contains(fn($pr) => $pr->status->isOpen() && (! $pr->consultation_route_id || (int) $pr->consultation_route_id === (int) $selectedRoute->id));
                                    @endphp
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2 gap-2">
                                            <h6 class="small fw-bold mb-0 text-uppercase text-muted">
                                                <i class="ti ti-building-hospital me-1"></i>{{ $deptName }}
                                                <span class="badge bg-light text-dark ms-1">{{ $deptProcedures->count() }} {{ Str::plural('request', $deptProcedures->count()) }}</span>
                                            </h6>
                                            @if($procedureSendable)
                                            <form method="POST"
                                                  action="{{ $workspaceRoutes->route('admin.consultations.procedure-departments.send-to-department', [$visit, $procedureDepartmentId]) }}"
                                                  class="d-inline"
                                                  data-confirm="{{ __('messages.consultations.investigation_handoff_confirm', ['department' => $deptName]) }}">
                                                @csrf
                                                <input type="hidden" name="consultation_route_id" value="{{ $selectedRoute->id }}">
                                                <button type="submit" class="btn btn-xs btn-outline-info" data-loading-text="{{ __('messages.consultations.investigation_handoff_queueing') }}" title="{{ __('messages.consultations.investigation_handoff_confirm', ['department' => $deptName]) }}">
                                                    <i class="ti ti-route me-1"></i>{{ __('messages.consultations.investigation_handoff_button') }}
                                                </button>
                                            </form>
                                            @elseif($procedureQueued)
                                            <button type="button" class="btn btn-xs btn-outline-secondary" disabled title="{{ __('messages.consultations.investigation_handoff_queued_title', ['department' => $deptName]) }}">
                                                <i class="ti ti-clock-check me-1"></i>{{ __('messages.consultations.investigation_handoff_queued') }}
                                            </button>
                                            @endif
                                        </div>
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
                                                                <span class="badge" style="background-color: {{ $pr->status->color() }}; color:#fff;">{{ $pr->status->translatedLabel() }}</span>
                                                                <span class="fw-medium">{{ $pr->service?->name ?? 'Procedure' }}</span>
                                                                <small class="text-muted">· {{ $pr->request_number }}</small>
                                                            </p>
                                                            <small class="text-muted">
                                                                {{ __('consultations.urgency.'.strtolower($pr->priority)) }} ·
                                                                Requested {{ optional($pr->requested_at)->format('d M Y H:i') }}
                                                                @if($pr->preferred_datetime) · Preferred {{ optional($pr->preferred_datetime)->format('d M Y H:i') }} @endif
                                                                @if($pr->schedule)
                                                                    · Scheduled {{ optional($pr->schedule->scheduled_start)->format('d M Y H:i') }}
                                                                    @if($pr->schedule->theatreRoom) ({{ $pr->schedule->theatreRoom->name }}) @endif
                                                                @endif
                                                            </small>
                                                            @if($pr->indication)
                                                                <div><small><strong>{{ __('consultations.indication') }}</strong> {{ $pr->indication }}</small></div>
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
                                                                    data-consultation-action="edit-entry"
                                                                    data-entry-type="procedure"
                                                                    data-url="{{ $workspaceRoutes->route('admin.consultations.procedures.update', $pr) }}"
                                                                    data-entry='@json($editEntryPayload)'>
                                                                <i class="ti ti-edit"></i>
                                                            </button>
                                                            @elseif($pr->status !== \App\Enums\ProcedureStatus::REQUESTED)
                                                                <small class="text-muted mt-1"><i class="ti ti-lock me-1"></i>Locked: request already processed</small>
                                                            @endif
                                                            <a class="btn btn-sm btn-outline-primary" href="{{ $workspaceRoutes->route('admin.theatre.show', $pr) }}">
                                                                <i class="ti ti-eye me-1"></i>Open
                                                            </a>
                                                            @if($pr->status === \App\Enums\ProcedureStatus::COMPLETED)
                                                                <a data-no-inertia class="btn btn-sm btn-outline-secondary" href="{{ $workspaceRoutes->route('admin.theatre.report', $pr) }}" target="_blank">Report</a>
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
                                <a href="{{ $workspaceRoutes->route('admin.consultations.history', $visit) }}" class="btn btn-sm btn-outline-info">
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
                                        <a href="{{ $workspaceRoutes->route('admin.consultations.history', $visit) }}" class="btn btn-outline-primary btn-sm">View All {{ $history['total'] }} Records</a>
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
                    @foreach($layoutTabSections->filter(fn ($section) => ! in_array(($section['component'] ?? null), ['consultations.partials.specialty.core-section'], true)) as $section)
                        @include($section['component'], ['section' => $section, 'activeTabTarget' => $activeTabTarget, 'specialtyEntries' => $specialtyEntries ?? [], 'specialtyEntryGroups' => $specialtyEntryGroups ?? []])
                    @endforeach

                    {{-- ========================= PATTERNS ========================= --}}
                    <div class="tab-pane fade" id="patterns-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-template me-1"></i>Medical Patterns</h6>
                                <a href="{{ $workspaceRoutes->route('admin.patterns.create') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-plus me-1"></i>Create Pattern
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                                        <input type="text" id="patternSearchInput" class="form-control" placeholder="Search patterns by complaint..." minlength="3">
                                        <button type="button" class="btn btn-primary" id="patternSearchBtn" data-consultation-action="search-patterns">Search</button>
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
                                            @if($canCreateEntries)
                                            <button type="button" class="btn btn-sm btn-success apply-pattern-btn"
                                                    data-consultation-action="apply-pattern"
                                                    data-pattern-id="{{ $pattern->id }}" data-pattern-name="{{ $pattern->name }}"
                                                    data-pattern-types="{{ $pattern->items->pluck('type')->unique()->implode(',') }}">
                                                <i class="ti ti-check me-1"></i>Apply
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="ti ti-template fs-1 d-block mb-2"></i>No patterns available yet.
                                        <br><a href="{{ $workspaceRoutes->route('admin.patterns.create') }}">{{ __('consultations.create_first_pattern') }}</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- ========================= TASKS ========================= --}}
                    <div class="tab-pane fade {{ $tabActiveClass('tasks-section') }}" id="tasks-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0"><i class="ti ti-checklist me-1"></i>{{ $sectionLabel('tasks', 'Tasks') }}</h6>
                                @if($canCreateEntries)
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addTaskForm">
                                    <i class="ti ti-plus me-1"></i>Add Task
                                </button>
                                @endif
                            </div>
                            <div class="card-body">
                                @if($canCreateEntries)
                                <div class="collapse mb-3" id="addTaskForm">
                                    <div class="card card-body bg-light">
                                        <form data-ajax-form="tasks" data-consultation-form="tasks" data-refresh-section="tasks" data-route-context-required="true" method="POST" action="{{ $workspaceRoutes->route('admin.consultations.tasks.store', $visit) }}">
                                            @csrf
                                            <x-consultation-idempotency-key action="task.create" />
                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <label class="form-label small">Task Title <span class="text-danger">*</span></label>
                                                    @php
                                                        $taskFavorites = collect($specialtyFavorites['task'] ?? []);
                                                    @endphp
                                                    <input type="text" name="title" id="taskTitleInput" class="form-control form-control-sm" required placeholder="e.g., Follow up on lab results" list="taskFavoriteSuggestions">
                                                    <datalist id="taskFavoriteSuggestions">
                                                        @foreach($taskFavorites as $favorite)
                                                            <option value="{{ $favorite['label'] }}"></option>
                                                        @endforeach
                                                    </datalist>
                                                    @if($taskFavorites->isNotEmpty())
                                                        <div class="mt-1 d-flex flex-wrap gap-1">
                                                            @foreach($taskFavorites->take(6) as $favorite)
                                                                <button type="button" class="btn btn-outline-secondary btn-xs" data-consultation-action="insert-favorite" data-target="#taskTitleInput" data-favorite-label="{{ $favorite['label'] }}">{{ $favorite['label'] }}</button>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">{{ __('consultations.task_frequency') }} <span class="text-danger">*</span></label>
                                                    <select name="frequency" class="form-select form-select-sm" required>
                                                        @foreach($taskFrequencyOptions as $option)
                                                            <option value="{{ $option['value'] }}" @selected($option['value'] === 'OD')>{{ $option['label'] }}{{ ($option['is_favorite'] ?? false) ? ' - '.__('consultation_specialties.favorites.badge') : '' }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Priority</label>
                                                    <select name="priority" class="form-select form-select-sm">
                                                        <option value="low">{{ __('consultations.priority.low') }}</option>
                                                        <option value="medium" selected>{{ __('consultations.priority.medium') }}</option>
                                                        <option value="high">{{ __('consultations.priority.high') }}</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Description</label>
                                                    <textarea name="description" class="form-control form-control-sm" rows="2"></textarea>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">{{ __('consultations.task_start_at') }}</label>
                                                    <input type="datetime-local" name="start_at" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Due Date</label>
                                                    <input type="date" name="due_date" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small">Assign To</label>
                                                    <select name="assigned_to" class="form-select form-select-sm">
                                                        <option value="">{{ __('consultations.unassigned') }}</option>
                                                        @foreach($doctors as $doc)
                                                            <option value="{{ $doc->id }}">Dr. {{ $doc->full_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mt-2 d-flex gap-2">
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#addTaskForm">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endif
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
                                            <form method="POST" action="{{ $workspaceRoutes->route('admin.consultations.tasks.toggle', $task) }}" data-preserve-tab="tasks-section">
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
                                                                data-consultation-action="edit-entry"
                                                                data-entry-type="task"
                                                                data-url="{{ $workspaceRoutes->route('admin.consultations.tasks.update', $task) }}"
                                                                data-entry='@json($editEntryPayload)'>
                                                            <i class="ti ti-edit"></i>
                                                        </button>
                                                        @endif
                                                        @if($canDeleteEntry($task))
                                                        <form method="POST" action="{{ $workspaceRoutes->route('admin.consultations.tasks.destroy', $task) }}" class="d-inline" data-preserve-tab="tasks-section">
                                                            @csrf @method('DELETE')
                                                            <button aria-label="Close" title="Close" type="submit" class="btn btn-xs btn-outline-danger" data-confirm="Delete this task?"><i class="ti ti-x"></i></button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($task->description) <small class="text-muted">{{ $task->description }}</small> @endif
                                                <div class="mt-1">
                                                    <small class="text-muted">
                                                        Created: {{ $task->created_at?->format('d M Y, h:i A') }}
                                                        @if($task->assignedUser) &middot; Assigned: {{ $task->assignedUser->full_name }} @endif
                                                        @if($task->frequency) &middot; Frequency: {{ $task->frequency }} @endif
                                                        @if($task->scheduled_at) &middot; Scheduled: {{ $task->scheduled_at->format('d M Y H:i') }} @endif
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

                    {{-- ========================= NEXT APPOINTMENT / FOLLOW-UP ========================= --}}
                    <div class="tab-pane fade {{ $tabActiveClass('follow-up-section') }}" id="follow-up-section" role="tabpanel">
                        <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-0"><i class="ti ti-calendar-time me-1"></i>{{ __('consultations.workspace.follow_up_title') }}</h6>
                                        @if($followUpAppointment)
                                            <small class="text-muted">{{ __('consultations.workspace.current_status', ['status' => $followUpAppointment->status?->translatedLabel() ?? ucfirst((string) $followUpAppointment->status)]) }}</small>
                                        @endif
                                    </div>
                                    @if($followUpAppointment)
                                        <span class="badge bg-primary-subtle text-primary">{{ __('consultations.workspace.set') }}</span>
                                    @endif
                                </div>
                                <div class="card-body">
                                @if(! $selectedRoute)
                                    <x-empty-state icon="ti-route-off" :title="__('consultations.no_active_session')" :message="__('consultations.workspace.select_session_for_follow_up')" />
                                @else
                                    @php
                                        $followUpDepartmentId = (string) ($selectedRoute?->department_id);
                                        $followUpDepartment = $selectedRoute?->department;
                                        $followUpServiceId = (string) old('service_id', $followUpAppointment?->services?->first()?->id);
                                        $followUpDoctor = $selectedRoute?->doctor ?? $selectedRoute?->mainDoctor ?? auth()->user();
                                        $followUpPriority = (string) old('priority', $followUpAppointment?->priority ?? 'normal');
                                        $followUpServices = $consultationServices
                                            ->filter(fn ($service) => (string) $service->department_id === $followUpDepartmentId)
                                            ->values();
                                        $canManageFollowUp = $canCreateEntries && ($followUpAppointment
                                            ? auth()->user()?->can('consultation.followup.update')
                                            : auth()->user()?->can('consultation.followup.create'));
                                        $followUpAction = $followUpAppointment
                                            ? $workspaceRoutes->route('admin.consultations.routes.follow-up.update', [$visit, $selectedRoute, $followUpAppointment])
                                            : $workspaceRoutes->route('admin.consultations.routes.follow-up.store', [$visit, $selectedRoute]);
                                    @endphp

                                    @if($followUpAppointment)
                                        <div class="alert alert-light border d-flex flex-wrap gap-3 align-items-center mb-3">
                                            <div>
                                                <div class="text-muted small">{{ __('consultations.workspace.current_follow_up') }}</div>
                                                <div class="fw-semibold">
                                                    {{ $followUpAppointment->appointment_date?->format('d M Y') }}
                                                    @if($followUpAppointment->start_time)
                                                        &middot; {{ \Carbon\Carbon::parse($followUpAppointment->start_time)->format('h:i A') }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div>
                                                <div class="text-muted small">{{ __('common.department') }}</div>
                                                <div class="fw-semibold">{{ $followUpAppointment->department?->name ?? '-' }}</div>
                                            </div>
                                            <div>
                                                <div class="text-muted small">{{ __('common.doctor') }}</div>
                                                <div class="fw-semibold">{{ $followUpAppointment->doctor?->full_name ?? 'Unassigned' }}</div>
                                            </div>
                                            @if($canCreateEntries && auth()->user()?->can('consultation.followup.cancel'))
                                                <div class="ms-auto">
                                                    <x-confirm-form
                                                        :action="$workspaceRoutes->route('admin.consultations.routes.follow-up.cancel', [$visit, $selectedRoute, $followUpAppointment])"
                                                        method="POST"
                                                        :button-label="__('consultations.workspace.cancel_follow_up_button')"
                                                        button-class="btn btn-outline-danger btn-sm"
                                                        icon="ti-x"
                                                        :confirm-title="__('consultations.cancel_follow_up')"
                                                        :confirm-text="__('consultations.workspace.cancel_follow_up_help')"
                                                        :confirm-button="__('consultations.workspace.yes_cancel')"
                                                        :require-reason="true"
                                                        reason-name="reason"
                                                        :reason-placeholder="__('consultations.follow_up_cancel_reason')"
                                                    />
                                                </div>
                                            @endif
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
                                                    <label class="form-label">{{ __('consultations.workspace.follow_up_date') }} <span class="text-danger">*</span></label>
                                                    <input type="date" name="appointment_date" class="form-control @error('appointment_date') is-invalid @enderror" required value="{{ old('appointment_date', $followUpAppointment?->appointment_date?->toDateString()) }}">
                                                    @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('consultations.workspace.follow_up_time') }}</label>
                                                    <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', $followUpAppointment?->start_time ? substr((string) $followUpAppointment->start_time, 0, 5) : '') }}">
                                                    @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('common.priority') }}</label>
                                                    <select name="priority" class="form-select @error('priority') is-invalid @enderror">
                                                        @foreach(\App\Enums\Priority::cases() as $priority)
                                                            <option value="{{ $priority->value }}" @selected($followUpPriority === $priority->value)>{{ $priority->translatedLabel() }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('common.department') }} <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" value="{{ $followUpDepartment?->name ?? __('consultations.workspace.consultation_department') }}" readonly>
                                                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('consultations.workspace.service') }} <span class="text-danger">*</span></label>
                                                    <select name="service_id" id="followUpServiceSelect" class="form-select @error('service_id') is-invalid @enderror" required>
                                                        <option value="">{{ __('consultations.workspace.select_follow_up_service') }}</option>
                                                        @foreach($followUpServices as $service)
                                                            <option value="{{ $service->id }}" @selected($followUpServiceId === (string) $service->id)>{{ $service->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('service_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">{{ __('common.doctor') }}</label>
                                                    <input type="text" class="form-control" value="{{ $followUpDoctor?->full_name ? 'Dr. '.$followUpDoctor->full_name : __('consultations.unassigned') }}" readonly>
                                                    @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('consultations.workspace.follow_up_reason') }} <span class="text-danger">*</span></label>
                                                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2" required placeholder="{{ __('consultations.workspace.follow_up_reason_placeholder') }}">{{ old('reason', $followUpAppointment?->reason) }}</textarea>
                                                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">{{ __('consultations.workspace.clinical_instruction') }}</label>
                                                    <textarea name="notes" id="followUpInstructionInput" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="{{ __('consultations.workspace.clinical_instruction_placeholder') }}">{{ old('notes', $followUpAppointment?->notes) }}</textarea>
                                                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    @php
                                                        $followUpFavorites = collect($specialtyFavorites['follow_up_instruction'] ?? []);
                                                    @endphp
                                                    @if($followUpFavorites->isNotEmpty())
                                                        <div class="mt-2 d-flex flex-wrap gap-1">
                                                            @foreach($followUpFavorites->take(6) as $favorite)
                                                                <button type="button" class="btn btn-outline-info btn-xs" data-consultation-action="insert-favorite" data-target="#followUpInstructionInput" data-insert-mode="append-line" data-favorite-label="{{ $favorite['label'] }}">
                                                                    {{ $favorite['label'] }}
                                                                </button>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="mt-3 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ti ti-calendar-check me-1"></i>{{ $followUpAppointment ? __('consultations.workspace.update_follow_up') : __('consultations.workspace.set_follow_up') }}
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="alert alert-secondary mb-0">
                                            <i class="ti ti-lock me-1"></i>{{ __('consultations.workspace.follow_up_permission_denied', ['action' => $followUpAppointment ? __('consultations.workspace.permission_action_update') : __('consultations.workspace.permission_action_create')]) }}
                                        </div>
                                    @endif
                                @endif
                                </div>
                        </div>
                    </div>

                    {{-- ========================= NOTES / SUMMARY ========================= --}}
                    <div class="tab-pane fade {{ $tabActiveClass('summary-section') }}" id="summary-section" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="fw-bold mb-0"><i class="ti ti-notes me-1"></i>{{ $sectionLabel('notes', __('consultations.final_clinical_note')) }} <span class="visually-hidden">Notes</span></h6>
                            </div>
                            <div class="card-body">
                                @if(! empty($specialtySummaryBuilder['available'] ?? false))
                                    <div class="border rounded p-2 mb-3 bg-light">
                                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                            <div>
                                                <div class="small fw-semibold">{{ __('consultation_specialties.summary_builder.title') }}</div>
                                                <div class="small text-muted">{{ __('consultation_specialties.summary_builder.generated_from_recorded_data') }}</div>
                                            </div>
                                            <button type="button"
                                                    class="btn btn-outline-primary btn-sm"
                                                    id="generateSpecialtySummaryBtn"
                                                    data-preview-url="{{ $specialtySummaryBuilder['preview_url'] ?? '' }}"
                                                    data-route-id="{{ $selectedRoute?->id }}">
                                                <i class="ti ti-sparkles me-1"></i>{{ __('consultation_specialties.summary_builder.generate') }}
                                            </button>
                                        </div>
                                        <div class="small text-muted mt-1">{{ __('consultation_specialties.summary_builder.not_saved_yet') }}</div>
                                    </div>
                                @endif
                                @if($canCreateEntries)
                                <form data-ajax-form="summary" data-consultation-form="final-note" data-route-context-required="true" data-preserve-values="true" method="POST" action="{{ $workspaceRoutes->route('admin.consultations.final-note.update', $visit) }}">
                                    @csrf
                                    @method('PATCH')
                                    <textarea name="final_note" id="finalNoteTextarea" class="form-control" rows="4" placeholder="{{ __('consultations.final_note_placeholder') }}">{{ old('final_note', $record?->final_note) }}</textarea>
                                    <div class="mt-2 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                    </div>
                                </form>
                                @else
                                    <div class="border rounded p-3 bg-light">{{ $record?->final_note ?: __('consultations.no_clinicians_recorded') }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ __('consultations.generated_summary') }}</h6>
                            </div>
                            <div class="card-body" id="consultation-summary-body">
                                @include('consultations.partials.summary-sections', ['consultationSummary' => $consultationSummary])
                            </div>
                        </div>
                    </div>

                    @if(! empty($specialtySummaryBuilder['available'] ?? false))
                    <div class="modal fade" id="specialtySummaryModal" tabindex="-1" aria-labelledby="specialtySummaryModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="specialtySummaryModalLabel">{{ __('consultation_specialties.summary_builder.preview') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info py-2 small">{{ __('consultation_specialties.summary_builder.not_saved_yet') }}</div>
                                    <div id="specialtySummaryPreviewBody" class="border rounded p-3 bg-light"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('consultation_specialties.summary_builder.close') }}</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="copySpecialtySummaryBtn">{{ __('consultation_specialties.summary_builder.copy') }}</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="insertSpecialtySummaryBtn">{{ __('consultation_specialties.summary_builder.insert') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const generateBtn = document.getElementById('generateSpecialtySummaryBtn');
                        const modalEl = document.getElementById('specialtySummaryModal');
                        const previewBody = document.getElementById('specialtySummaryPreviewBody');
                        const insertBtn = document.getElementById('insertSpecialtySummaryBtn');
                        const copyBtn = document.getElementById('copySpecialtySummaryBtn');
                        const finalNote = document.getElementById('finalNoteTextarea');
                        let summaryText = '';

                        if (!generateBtn || !modalEl || !previewBody) {
                            return;
                        }

                        const modal = window.bootstrap ? new bootstrap.Modal(modalEl) : null;

                        generateBtn.addEventListener('click', async function () {
                            const url = new URL(generateBtn.dataset.previewUrl, window.location.origin);
                            if (generateBtn.dataset.routeId) {
                                url.searchParams.set('consultation_route_id', generateBtn.dataset.routeId);
                            }
                            previewBody.innerHTML = '<div class="text-muted small">{{ __('consultations.loading') }}</div>';
                            modal?.show();

                            const response = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            const json = await response.json();
                            summaryText = json.summary?.plainText || '';
                            previewBody.innerHTML = json.summary?.html || '<div class="text-muted small">{{ __('consultation_specialties.summary_builder.no_data_available') }}</div>';
                        });

                        insertBtn?.addEventListener('click', function () {
                            if (!finalNote || !summaryText) {
                                return;
                            }
                            const existing = finalNote.value.trim();
                            if (existing && !confirm('{{ __('consultation_specialties.summary_builder.replace_existing_confirm') }}')) {
                                finalNote.value = existing + "\n\n" + summaryText;
                            } else {
                                finalNote.value = summaryText;
                            }
                            finalNote.dispatchEvent(new Event('input', { bubbles: true }));
                            modal?.hide();
                        });

                        copyBtn?.addEventListener('click', function () {
                            if (summaryText && navigator.clipboard) {
                                navigator.clipboard.writeText(summaryText);
                            }
                        });
                    });
                    </script>
                    @endif

                </div>
            </div>

            @include('consultations.partials.right-panel')

</div>
</div>{{-- end main row --}}

@include('consultations.partials.modals')


@endsection

@include('consultations.partials.page-config')
