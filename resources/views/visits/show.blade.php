@extends('layouts.app')
@section('title', __('visits.visit_number') . $visit->visit_number)

@push('styles')
<style>
    .btn-xs { padding: 0.15rem 0.35rem; font-size: 0.72rem; line-height: 1.4; }
</style>
@endpush

@section('content')
<x-page-header-back
        :title="__('visits.title') . ' - ' . $visit->visit_number"
        :href="$workspaceRoutes->route('admin.visits.index')"
    >
    <x-slot:actions>
        @can('emergency.case.create')
        @if(!in_array($visit->status, [\App\Enums\VisitStatus::COMPLETED, \App\Enums\VisitStatus::CANCELLED, \App\Enums\VisitStatus::NO_SHOW], true))
        <a href="{{ route('admin.emergency.cases.create', ['visit_id' => $visit->id]) }}" class="btn btn-outline-danger btn-md">
            <i class="ti ti-ambulance me-1"></i>{{ __('visits.create_emergency_case') }}
        </a>
        @endif
        @endcan
        @can('visits.create')
        <a href="{{ $workspaceRoutes->route('admin.visits.create', ['patient_id' => $visit->patient_id]) }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>{{ __('visits.new_visit_btn') }}
        </a>
        @endcan
    </x-slot:actions>
</x-page-header-back>

{{-- Previous-visit outstanding balance advisory (permission-aware; never blocks emergency). --}}
<x-billing.previous-balance-alert :visit="$visit" />
@include('billing.partials.previous-balance-override-modal', ['visit' => $visit])

<!-- @include('partials.patient-journey-widget', ['visit' => $visit]) -->

<!-- Page Header -->
<!-- <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ $workspaceRoutes->route('admin.visits.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i> </a>
    </h6>
    <div class="flex-grow-1">
        {{-- <a href="{{ route('admin.visits.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>Create New Visit</a> --}}
        <h4 class="fw-bold mb-0">{{ __('visits.visit_number') }}{{ $visit->visit_number }}</h4>
        <small class="text-muted">{{ $visit->created_at->format('d M Y, h:i A') }} {{ $visit->createdBy?->full_name }}</small>
    </div>
    <div class="d-flex gap-2">
        {{-- <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a> --}}
        @can('emergency.case.create')
        @if(!in_array($visit->status, [\App\Enums\VisitStatus::COMPLETED, \App\Enums\VisitStatus::CANCELLED, \App\Enums\VisitStatus::NO_SHOW], true))
        <a href="{{ route('admin.emergency.cases.create', ['visit_id' => $visit->id]) }}" class="btn btn-outline-danger btn-md">
            <i class="ti ti-ambulance me-1"></i>{{ __('visits.create_emergency_case') }}
        </a>
        @endif
        @endcan
        @can('visits.create')
        <a href="{{ $workspaceRoutes->route('admin.visits.create', ['patient_id' => $visit->patient_id]) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-plus me-1"></i>{{ __('visits.new_visit_btn') }}
        </a>
        @endcan
        {{-- @can('visits.edit')
        <a href="{{ $workspaceRoutes->route('admin.visits.edit', $visit) }}" class="btn btn-outline-warning btn-md">
            <i class="ti ti-pencil me-1"></i>Edit Visit
        </a>
        @endcan --}}
        {{-- @can('invoices.create')
        <a href="{{ route('admin.billing.invoices.create', ['visit_id' => $visit->id]) }}" class="btn btn-success btn-md">
            <i class="ti ti-file-invoice me-1"></i>Bill Visit
        </a>
        @endcan --}}
    </div>
</div> -->

<div id="visitShowMainContent">
<div class="row">
    <!-- Left Column — Visit Info -->
    <div class="col-lg-8">
        <!-- Status Bar -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0"><i class="ti ti-timeline me-2"></i>{{ __('visits.visit_status_flow') }}</h6>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if($visit->visit_source)
                            <span class="badge bg-light text-dark border" title="{{ __('visit_flow.ui.source_label') }}">
                                <i class="ti ti-arrow-guide me-1"></i>{{ __('visit_flow.source.'.$visit->visit_source) }}
                            </span>
                        @endif
                        @if($visit->attendance_class)
                            <span class="badge bg-light text-dark border" title="{{ __('visit_flow.ui.attendance_label') }}">
                                <i class="ti ti-user-check me-1"></i>{{ __('visit_flow.attendance_class.'.$visit->attendance_class) }}
                            </span>
                        @endif
                        @if($visit->triage_score)
                            <x-status-badge :status="$visit->triage_score" icon="ti-activity" class="px-2 py-2" />
                        @endif
                        <x-status-badge :status="$visit->status" class="fs-14 px-3 py-2" />
                    </div>
                </div>
                <!-- Status Timeline -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    @php
                        // Dynamic status-flow reflecting THIS visit's parcours: the
                        // arrival segment depends on the source (direct → walked_in,
                        // appointment → scheduled/checked_in) and is only shown for
                        // states actually visited; the clinical path is previewed.
                        $visitedStatuses = $visit->statusLogs->pluck('to_status')->filter()->all();
                        $currentStatus = $visit->status;

                        $arrivalStates = [
                            \App\Enums\VisitStatus::SCHEDULED,
                            \App\Enums\VisitStatus::CHECKED_IN,
                            \App\Enums\VisitStatus::CREATED,
                            \App\Enums\VisitStatus::REGISTERED,
                            \App\Enums\VisitStatus::WALKED_IN,
                        ];

                        $arrival = $visit->visit_source === 'appointment'
                            ? [\App\Enums\VisitStatus::SCHEDULED, \App\Enums\VisitStatus::CHECKED_IN]
                            : [\App\Enums\VisitStatus::CREATED, \App\Enums\VisitStatus::REGISTERED, \App\Enums\VisitStatus::WALKED_IN];

                        $statusFlow = collect(array_merge($arrival, [
                            \App\Enums\VisitStatus::QUEUED,
                            \App\Enums\VisitStatus::TRIAGE,
                            \App\Enums\VisitStatus::WAITING,
                            \App\Enums\VisitStatus::CONSULTING,
                            \App\Enums\VisitStatus::ADMITTED,
                            \App\Enums\VisitStatus::COMPLETED,
                        ]))
                            ->filter(fn ($s) => ! in_array($s, $arrivalStates, true) || in_array($s->value, $visitedStatuses, true))
                            ->values()
                            ->all();
                    @endphp
                    @foreach($statusFlow as $i => $flowStatus)
                        @php
                            $isVisited = in_array($flowStatus->value, $visitedStatuses);
                            $isCurrent = $currentStatus === $flowStatus;
                            $stepClass = $isCurrent ? 'bg-' . $flowStatus->color() . ' text-white' : ($isVisited ? 'bg-success-subtle text-success' : 'bg-light text-muted');
                        @endphp
                        <span class="badge rounded-pill {{ $stepClass }} px-2 py-1 small">
                            @if($isVisited && !$isCurrent)<i class="ti ti-check me-1"></i>@endif
                            {{ $flowStatus->translatedLabel() }}
                        </span>
                        @if(!$loop->last)
                            <i class="ti ti-chevron-right text-muted small"></i>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Status Transition Actions -->
        @php
            $isWaiting  = $visit->status === \App\Enums\VisitStatus::QUEUED;
            $isTriage   = $visit->status === \App\Enums\VisitStatus::TRIAGE;
            $serviceDepts = $visit->invoices
                ->flatMap->items
                ->pluck('department')
                ->filter()
                ->unique('id');

            // Statuses that can use the department send button (triage or at a service dept)
            $canSendToDept = in_array($visit->status, [
                \App\Enums\VisitStatus::TRIAGE,
                \App\Enums\VisitStatus::WAITING,
                \App\Enums\VisitStatus::ACTIVE,
                \App\Enums\VisitStatus::CONSULTING,
                \App\Enums\VisitStatus::EMERGENCY,
            ]);
            $consultationRoutes = $visit->consultationRoutes->sortBy(fn ($route) => match ($route->status) {
                \App\Models\VisitConsultationRoute::STATUS_ACTIVE => 0,
                \App\Models\VisitConsultationRoute::STATUS_PENDING => 1,
                \App\Models\VisitConsultationRoute::STATUS_PAUSED => 2,
                \App\Models\VisitConsultationRoute::STATUS_COMPLETED => 3,
                default => 4,
            });
            $activeConsultationRoute = $consultationRoutes->firstWhere('status', \App\Models\VisitConsultationRoute::STATUS_ACTIVE);
            $reopenPolicy = app(\App\Services\Consultation\ConsultationReopenEligibilityService::class);
            $sessionEligibility = app(\App\Services\Consultation\ConsultationSessionEligibilityService::class);
            $isVisitClinicallyLocked = $sessionEligibility->isVisitClinicallyLocked($visit);
            $hasReopenedActiveRoute = $activeConsultationRoute && $activeConsultationRoute->reopened_at;
            $canQueueConsultationRoute = ! $isVisitClinicallyLocked
                && ($isWaiting || $isTriage || $visit->status->allowedTransitions() || $hasReopenedActiveRoute);
            $routeBadgeClasses = [
                \App\Models\VisitConsultationRoute::STATUS_ACTIVE => 'success',
                \App\Models\VisitConsultationRoute::STATUS_PENDING => 'warning',
                \App\Models\VisitConsultationRoute::STATUS_PAUSED => 'info',
                \App\Models\VisitConsultationRoute::STATUS_COMPLETED => 'secondary',
                \App\Models\VisitConsultationRoute::STATUS_CANCELLED => 'danger',
            ];
            $routeBadge = fn (?string $status) => $routeBadgeClasses[$status ?? ''] ?? 'light text-dark';
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
            $activeConsultationServiceNames = $routeServiceNames($activeConsultationRoute);
        @endphp


        <!-- @include('partials.patient-journey-widget', ['visit' => $visit]) -->

        <!-- Visit Details Card -->
        <x-visit-summary-card :visit="$visit" />

        {{-- Triage Summary Card (shown once triage exists) --}}
        @if($visit->triage)
        @php
            $triage = $visit->triage;
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-info"></i>{{ __('visits.triage_assessment') }}</h6>
                {{-- @if($triage->triage_score)
                    <x-status-badge :status="$triage->triage_score" />
                @endif --}}

                <div>
                    <a href="{{ route('admin.triage.show', $visit) }}" class="btn btn-outline-info btn-sm">
                        <i class="ti ti-eye me-1"></i>{{ __('visits.full_triage_report') }}
                    </a>
                    @if($visit->status === \App\Enums\VisitStatus::TRIAGE)
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info btn-sm">
                            <i class="ti ti-pencil me-1"></i>{{ __('visits.re_assess') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @if($triage->blood_pressure)
                        <div class="col-6 col-md-2 text-center">
                            <div class="text-muted" style="font-size:0.72rem">BP</div>
                            <div class="fw-semibold small">{{ $triage->blood_pressure }} mmHg</div>
                        </div>
                    @endif
                    @if($triage->heart_rate)
                        <div class="col-6 col-md-2 text-center">
                            <div class="text-muted" style="font-size:0.72rem">{{ __('visits.heart_rate') }}</div>
                            <div class="fw-semibold small">{{ $triage->heart_rate }} bpm</div>
                        </div>
                    @endif
                    @if($triage->temperature)
                        <div class="col-6 col-md-2 text-center">
                            <div class="text-muted" style="font-size:0.72rem">{{ __('visits.temp_abbr') }}</div>
                            <div class="fw-semibold small">{{ $triage->temperature }} °C</div>
                        </div>
                    @endif
                    @if($triage->spo2)
                        <div class="col-6 col-md-2 text-center">
                            <div class="text-muted" style="font-size:0.72rem">SpO₂</div>
                            <div class="fw-semibold small">{{ $triage->spo2 }}%</div>
                        </div>
                    @endif
                    @if($triage->respiratory_rate)
                        <div class="col-6 col-md-2 text-center">
                            <div class="text-muted" style="font-size:0.72rem">{{ __('visits.resp_rate_abbr') }}</div>
                            <div class="fw-semibold small">{{ $triage->respiratory_rate }}/min</div>
                        </div>
                    @endif
                    @if($triage->bmi)
                        <div class="col-6 col-md-2 text-center">
                            @php
                                $bmiCat = $triage->bmi < 18.5 ? [__('visits.bmi_underweight'), 'warning'] : ($triage->bmi < 25 ? [__('visits.bmi_normal'), 'success'] : ($triage->bmi < 30 ? [__('visits.bmi_overweight'), 'warning'] : [__('visits.bmi_obese'), 'danger']));
                            @endphp
                            <div class="text-muted" style="font-size:0.72rem">BMI</div>
                            <div class="fw-semibold small">{{ $triage->bmi }} kg/m²</div>
                            <span class="badge bg-{{ $bmiCat[1] }}" style="font-size:0.65rem">{{ $bmiCat[0] }}</span>
                        </div>
                    @endif
                </div>
                @if($triage->department)
                    <div class="mt-2 small text-muted">
                        <i class="ti ti-building-hospital me-1"></i>{{ __('visits.assigned_to') }}: <strong>{{ $triage->department->name }}</strong>
                    </div>
                @endif
            </div>
        </div>
        @endif

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="ti ti-switch-horizontal me-1"></i>{{ __('visits.transition_visit') }}</h6>
                <div>
                    @can('visits.preview')
                    <a href="{{ $workspaceRoutes->route('admin.visits.preview', $visit) }}" class="btn btn-outline-info btn-sm">
                        <i class="ti ti-eye me-1"></i>{{ __('visits.preview_visit_btn') }}
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h6 class="fw-bold mb-0"><i class="ti ti-route me-1 text-primary"></i>{{ __('visits.current_routing') }}</h6>
                        @can('consultations.view')
                        @if($activeConsultationRoute)
                            <a href="{{ route('admin.consultations.routes.show', [$visit, $activeConsultationRoute]) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-external-link me-1"></i>{{ __('visits.open_active_session') }}
                            </a>
                        @endif
                        @endcan
                    </div>
                    @if($activeConsultationRoute)
                    <div class="row g-2 small">
                        <div class="col-md-3">
                            <span class="text-muted d-block">{{ __('visits.active_dept_session') }}</span>
                            <span class="fw-semibold">{{ $activeConsultationRoute->department?->name ?? '-' }}</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted d-block">{{ __('visits.linked_services') }}</span>
                            <span class="fw-semibold">{{ $activeConsultationServiceNames->implode(', ') ?: '-' }}</span>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block">{{ __('visits.doctor') }}</span>
                            <span class="fw-semibold">{{ $activeConsultationRoute->doctor ? 'Dr. ' . $activeConsultationRoute->doctor->full_name : __('visits.unassigned') }}</span>
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block">{{ __('visits.route_status') }}</span>
                            <x-status-badge :status="$activeConsultationRoute->status" domain="consultation_route" />
                        </div>
                        <div class="col-md-2">
                            <span class="text-muted d-block">{{ __('visits.started_at') }}</span>
                            <span class="fw-semibold">{{ $activeConsultationRoute->started_at?->format('d M, h:i A') ?? '-' }}</span>
                        </div>
                    </div>
                    @else
                        <p class="text-muted small mb-0">{{ __('visits.no_active_session') }}</p>
                    @endif
                </div>

                <div class="border rounded p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <h6 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1 text-primary"></i>{{ __('visits.available_sessions') }}</h6>
                        <span class="badge bg-light text-dark">{{ trans_choice('visits.existing_routes', $consultationRoutes->count(), ['count' => $consultationRoutes->count()]) }}</span>
                    </div>
                    @if($consultationRoutes->isNotEmpty())
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('visits.department') }}</th>
                                    <th>{{ __('visits.linked_services') }}</th>
                                    <th>{{ __('visits.doctor') }}</th>
                                    <th>{{ __('visits.status') }}</th>
                                    <th>{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($consultationRoutes as $route)
                                @php
                                    $serviceNames = $routeServiceNames($route);
                                    $routeReopenEligibility = auth()->user()
                                        ? $reopenPolicy->canReopen(auth()->user(), $visit, $route)
                                        : null;
                                @endphp
                                <tr>
                                    <td>{{ $route->department?->name ?? '-' }}</td>
                                    <td>{{ $serviceNames->implode(', ') ?: '-' }}</td>
                                    <td>{{ $route->doctor ? 'Dr. ' . $route->doctor->full_name : __('visits.unassigned') }}</td>
                                    <td><x-status-badge :status="$route->status" domain="consultation_route" /></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @can('consultations.view')
                                            <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-xs btn-outline-primary">{{ __('visits.open_btn') }}</a>
                                            @endcan
                                            @can('consultation.routes.activate')
                                            @if(in_array($route->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                                            <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $route]) }}">
                                                @csrf
                                                <input type="hidden" name="return_to_visit" value="1">
                                                <input type="hidden" name="reason" value="{{ __('consultations.reopen.visit_details_reason') }}">
                                                <button type="submit" class="btn btn-xs btn-primary">{{ __('visits.activate_btn') }}</button>
                                            </form>
                                            @endif
                                            @endcan

                                            @can('consultation.routes.complete')
                                            @if($route->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE)
                                            <form method="POST" action="{{ route('admin.consultations.routes.complete', [$visit, $route]) }}">
                                                @csrf
                                                <input type="hidden" name="return_to_visit" value="1">
                                                <button type="submit" class="btn btn-xs btn-success" onclick="return confirm('{{ __('visits.complete_session_confirm') }}')">{{ __('visits.complete_btn') }}</button>
                                            </form>
                                            @endif
                                            @endcan

                                            @can('consultation.routes.cancel')
                                            @if(in_array($route->status, [\App\Models\VisitConsultationRoute::STATUS_PENDING, \App\Models\VisitConsultationRoute::STATUS_PAUSED], true))
                                            <form method="POST" action="{{ route('admin.consultations.routes.cancel', [$visit, $route]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-danger" onclick="return confirm('{{ __('visits.cancel_route_confirm') }}')">{{ __('visits.cancel_btn') }}</button>
                                            </form>
                                            @endif
                                            @endcan
                                            @can('consultations.reopen')
                                            @if($route->status === \App\Models\VisitConsultationRoute::STATUS_COMPLETED && $routeReopenEligibility?->allowed)
                                            <form method="POST" action="{{ route('admin.consultations.routes.reopen', [$visit, $route]) }}">
                                                @csrf
                                                <input type="hidden" name="return_to_visit" value="1">
                                                <input type="hidden" name="reason" value="{{ __('consultations.reopen.visit_details_reason') }}">
                                                <button type="submit" class="btn btn-xs btn-primary" onclick="return confirm('{{ __('consultations.reopen.confirm') }}')">{{ __('visits.actions.reopen_consultation') }}</button>
                                            </form>
                                            @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif


                    @if($canQueueConsultationRoute)
                    @can('consultations.create')
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="ti ti-plus text-primary"></i>
                        <h6 class="fw-bold mb-0">{{ __('visits.queue_another_dept') }}</h6>
                    </div>
                    <form method="POST" action="{{ route('admin.consultations.routes.store', $visit) }}" class="row g-2 align-items-end js-queue-consultation-route-form">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('visits.consultation_dept') }}</label>
                            <select name="department_id" id="visitRouteDeptSelect" class="form-select form-select-sm" required>
                                <option value="">{{ __('visits.select_department') }}</option>
                                @foreach($consultationDepartments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="visitRouteShowOtherServices" disabled>
                                <label class="form-check-label small text-muted" for="visitRouteShowOtherServices">{{ __('visits.show_other_services') }}</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            {{-- <label class="form-label small">Services to add/bill</label> --}}
                            <select name="service_ids[]" id="visitRouteServiceSelect" class="form-select form-select-sm" disabled multiple size="3" required>
                                <option value="">{{ __('visits.select_dept_first') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('visits.doctor_optional') }}</label>
                            <select name="doctor_id" id="visitRouteDoctorSelect" class="form-select form-select-sm" disabled>
                                <option value="">{{ __('visits.select_dept_first') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('visits.notes') }}</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="{{ __('visits.notes') }}">
                        </div>
                        <div class="col-md-1">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="activate_now" value="1" id="visitRouteActivateNow">
                                <label class="form-check-label small" for="visitRouteActivateNow">{{ __('visits.activate_label') }}</label>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('visits.queue_btn') }}</button>
                        </div>
                        <div class="col-12">
                            <div class="alert d-none mb-0 js-queue-route-feedback" role="alert"></div>
                        </div>
                    </form>
                    @endcan
                    @endif
                </div>

                {{-- WAITING: Triage / Cancelled / Reschedule only --}}
                <p class="text-muted small mb-2">{{ __('visits.next_step') }}</p>
                @if($isWaiting)
                <div class="d-flex flex-wrap gap-2">
                    @foreach([\App\Enums\VisitStatus::TRIAGE, \App\Enums\VisitStatus::CANCELLED, \App\Enums\VisitStatus::RESCHEDULED] as $nextStatus)
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.visits.transition', $visit) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm"
                                    onclick="return confirm('{{ __('visits.move_to_confirm', ['status' => $nextStatus->translatedLabel()]) }}')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->translatedLabel() }}
                            </button>
                        </form>
                    @endforeach
                </div>

                {{-- TRIAGE: primary action is triage assessment form --}}
                @elseif($isTriage)
                <div class="mb-3">
                    <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info">
                        <i class="ti ti-stethoscope me-1"></i>{{ __('visits.start_triage') }}
                    </a>
                    @if($visit->triage)
                        <a href="{{ route('admin.triage.show', $visit) }}" class="btn btn-outline-info btn-sm ms-2">
                            <i class="ti ti-eye me-1"></i>{{ __('visits.view_triage_record') }}
                        </a>
                    @endif
                {{-- </div>
                <div class="d-flex flex-wrap gap-2"> --}}
                    <form method="POST" action="{{ $workspaceRoutes->route('admin.visits.transition', $visit) }}" class="d-inline ms-1">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ \App\Enums\VisitStatus::CANCELLED->value }}">
                        <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('{{ __('visits.cancel_visit_confirm') }}')">
                            <i class="ti ti-x me-1"></i>{{ __('visits.cancel_visit_btn') }}
                        </button>
                    </form>
                </div>

                {{-- All other statuses: standard transition buttons --}}
                @elseif($visit->status->allowedTransitions())
                {{-- @if($canSendToDept && $serviceDepts->isNotEmpty())
                    <p class="text-muted small mb-2">Send to another department:</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($serviceDepts as $dept)
                            <form method="POST" action="{{ $workspaceRoutes->route('admin.visits.send-to-department', $visit) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="department_id" value="{{ $dept->id }}">
                                <button type="submit"
                                        class="btn btn-outline-{{ $dept->type?->color() ?? 'primary' }} btn-sm"
                                        onclick="return confirm('Send patient to {{ $dept->name }}?')">
                                    <i class="ti ti-building-hospital me-1"></i>{{ $dept->name }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif --}}
                <div class="d-flex flex-wrap gap-2">
                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                        @php
                            $isWaitingTriageTarget = $nextStatus === \App\Enums\VisitStatus::QUEUED;
                            $transitionLabel = $isWaitingTriageTarget
                                ? __('visits.send_to_triage_queue')
                                : $nextStatus->translatedLabel();
                        @endphp
                        <form method="POST" action="{{ $workspaceRoutes->route('admin.visits.transition', $visit) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            @if($isWaitingTriageTarget)
                                <input type="hidden" name="notes" value="{{ __('visits.waiting_for_triage_note') }}">
                            @endif
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm"
                                    onclick="return confirm('{{ __('visits.move_to_confirm', ['status' => $transitionLabel]) }}')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $transitionLabel }}
                            </button>
                        </form>
                    @endforeach
                </div>
                @endif

            </div>
        </div>

        {{-- Department History Card (shown after triage assigns dept) --}}
        {{-- @if($visit->departmentHistory->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-details me-1"></i>Department Journey</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Department</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->departmentHistory as $hist)
                                <tr>
                                    <td class="small fw-semibold">{{ $hist->department?->name ?? '—' }}</td>
                                    <td><span class="badge bg-{{ $hist->typeColor() }}">{{ $hist->typeLabel() }}</span></td>
                                    <td><span class="badge bg-{{ $hist->statusColor() }}">{{ $hist->statusLabel() }}</span></td>
                                    <td class="text-muted small">{{ $hist->created_at->format('h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif --}}

        <!-- Visit Invoice (replaces legacy visit_services display) -->
        @php
            $visitInvoice = $visit->invoices
                ->whereNotIn('status', [\App\Enums\InvoiceStatus::CANCELLED ?? null, \App\Enums\InvoiceStatus::REFUNDED ?? null])
                ->sortByDesc('id')
                ->first();
        @endphp
        @if($visitInvoice && $visitInvoice->items->isNotEmpty())
        @php
            $sourceLabels = [
                'cash_and_carry'         => ['Cash & Carry',    'secondary'],
                'cash_price'             => ['Cash & Carry',    'secondary'],
                'provider_specific'      => ['Provider Rate',   'success'],
                'payer_specific_price'   => ['Provider Rate',   'success'],
                'insurance_type'         => ['Insurance Type',  'info'],
                'insurance_type_default' => ['Insurance Type',  'info'],
                'base_price'             => ['Base Price',      'light text-dark'],
                'drug_price'             => ['Drug Price',      'light text-dark'],
            ];
            $sourceTypeGroups = [
                'visit_service'                     => 'consultation_visit_services',
                'service_catalog'                   => 'consultation_visit_services',
                'consultation_service'              => 'consultation_visit_services',
                'visit_consultation_route_service'  => 'consultation_visit_services',
            ];
            $sourceTypeLabels = [
                'consultation_visit_services'              => 'Consultation / Visit Services',
                'visit_service'                            => 'Consultation / Visit Services',
                'service_catalog'                          => 'Consultation / Visit Services',
                'consultation_service'                     => 'Consultation / Visit Services',
                'visit_consultation_route_service'         => 'Consultation / Visit Services',
                'lab_request_item'                         => 'Investigations',
                'investigation_service'                    => 'Investigations',
                'investigation_consumable'                 => 'Investigation Consumables',
                'prescription_item'                        => 'Pharmacy',
                'pharmacy_product'                         => 'Pharmacy',
                'pharmacy_billing_selection'               => 'Pharmacy',
                'ward_charge'                              => 'Ward / Admission',
                'ward_consumable'                          => 'Ward / Admission',
                'admission_fee'                            => 'Ward / Admission',
                'admission_bed_charge'                     => 'Ward / Admission',
                'admission_daily_consumable_charge'        => 'Ward / Admission',
                'scan_request_item'                        => 'Scans',
                'xray_request_item'                        => 'X-Ray',
                'procedure'                                => 'Procedures',
                'procedure_service'                        => 'Procedures',
                'procedure_consumable'                     => 'Procedure Consumables',
                'emergency_consumable'                     => 'Emergency',
                'emergency_bed_charge'                     => 'Emergency',
                'emergency_daily_consumable_charge'        => 'Emergency',
            ];
            $visitInvoiceSourceKey = fn ($item) => $sourceTypeGroups[
                $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other')
            ] ?? ($item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other'));
            $groupedItems = $visitInvoice->items->sortBy(fn ($item) => implode('|', [
                $visitInvoiceSourceKey($item),
                $item->department?->name ?? 'zz_unassigned',
                str_pad((string) $item->id, 10, '0', STR_PAD_LEFT),
            ]));
            $currentGroup = null;
        @endphp
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">
                    <i class="ti ti-receipt me-1"></i>{{ __('visits.visit_invoice') }}
                    <span class="badge bg-secondary ms-2">{{ $visitInvoice->invoice_number }}</span>
                </h6>
                @can('billing.view')
                <a href="{{ route('admin.billing.invoices.show', $visitInvoice) }}" class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-external-link me-1"></i>{{ __('visits.open_invoice') }}
                </a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('visits.service_description') }}</th>
                                <th>{{ __('visits.pricing_col') }}</th>
                                <th class="text-end">{{ __('visits.price_col') }}</th>
                                <th class="text-end">{{ __('visits.covered_col') }}</th>
                                {{-- <th class="text-end">Total</th> --}}
                                <th class="text-end">{{ __('visits.patient_pays_col') }}</th>
                                <th class="text-end">{{ __('visits.balance_col') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $totalAmt = 0; $totalIns = 0; $totalPatient = 0; $totalBalance = 0; @endphp
                            @foreach($groupedItems as $item)
                            @php
                                $src       = $item->pricing_source ?? 'cash_and_carry';
                                $meta      = $sourceLabels[$src] ?? [ucfirst(str_replace('_',' ',$src)), 'light text-dark'];
                                $payer     = $item->payer_type ?? 'cash';
                                $rawSourceKey  = $item->source_type ?: ($item->service_catalog_id ? 'service_catalog' : 'other');
                                $sourceKey  = $sourceTypeGroups[$rawSourceKey] ?? $rawSourceKey;
                                $departmentKey = $item->department_id ? 'department_'.$item->department_id : 'department_none';
                                $groupKey  = $sourceKey.'|'.$departmentKey;
                                $groupLabel = $sourceTypeLabels[$sourceKey] ?? ucfirst(str_replace('_',' ',$sourceKey));
                                $departmentLabel = $item->department?->name ?? 'Unassigned Department';
                                $selectedPrice = $item->selected_price !== null ? (float) $item->selected_price : (float) ($item->unit_price ?? 0);
                            @endphp
                            @if($currentGroup !== $groupKey)
                            <tr class="table-secondary">
                                <th colspan="6" class="small text-uppercase">
                                    <i class="ti ti-folder me-1"></i>{{ $groupLabel }}
                                    <span class="badge bg-light text-dark ms-2">{{ $departmentLabel }}</span>
                                </th>
                            </tr>
                            @php $currentGroup = $groupKey; @endphp
                            @endif
                            <tr>
                                <td>
                                    <div class="fw-medium small">{{ $item->description }}</div>
                                    @if($item->department)
                                        <span class="badge bg-light text-dark mt-1">{{ $item->department->name }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $meta[1] }}">{{ $meta[0] }}</span>
                                    <div class="small text-muted mt-1">
                                        <i class="ti ti-{{ $payer === 'insurance' ? 'shield-check' : 'cash' }} me-1"></i>{{ ucfirst($payer) }}
                                    </div>
                                </td>
                                {{-- <td class="text-end fw-semibold small">&#8373;{{ number_format($selectedPrice, 2) }}</td> --}}

                                <td class="text-end">
                                    @if($item->cash_price > $selectedPrice)
                                    <div class="small text-muted text-decoration-line-through">&#8373;{{ number_format($item->cash_price, 2) }}</div>
                                    @endif
                                    <span class="fw-semibold">&#8373;{{ number_format($selectedPrice, 2) }}</span>
                                </td>
                                {{-- <td class="text-end small">&#8373;{{ number_format($item->total_price, 2) }}</td> --}}
                                <td class="text-end">
                                    @if((float) $item->insurance_covered > 0)
                                    <span class="text-success">&#8373;{{ number_format($item->insurance_covered, 2) }}</span>
                                    @else
                                    —
                                    @endif
                                </td>
                                <td class="text-end small">
                                    @if((float) $item->discount_amount > 0)
                                    <!-- <span class="">-&#8373;{{ number_format($item->discount_amount, 2) }}</span> -->
                                    <div class="small text-danger">-&#8373;{{ number_format($item->discount_amount, 2) }}</div>
                                    @endif
                                    &#8373;{{ number_format($item->patient_payable, 2) }}
                                </td>
                                <td class="text-end small {{ (float) $item->balance > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    &#8373;{{ number_format($item->balance, 2) }}
                                </td>
                            </tr>
                            @php
                                $totalAmt     += $item->total_price;
                                $totalIns     += ($item->insurance_covered ?? 0);
                                $totalPatient += $item->patient_payable;
                                $totalBalance += $item->balance;
                            @endphp
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-light fw-bold">
                                <td colspan="3" class="text-end text-muted">{{ __('visits.subtotal_row') }}</td>
                                <td class="text-end text-success">&#8373;{{ number_format($totalIns, 2) }}</td>
                                <td class="text-end text-muted">&#8373;{{ number_format($totalPatient, 2) }}</td>
                                <td class="text-end {{ $totalBalance > 0 ? 'text-danger' : 'text-success' }}">&#8373;{{ number_format($totalBalance, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif

        {{-- Legacy visit_services block intentionally removed.
             Visit billing is now represented by the visit invoice above. --}}

    </div>

    <!-- Right Column — Patient Card -->
    <div class="col-lg-4">
        <!-- Patient Card -->
        <x-patient-card :patient="$visit->patient" :visit="$visit" />

        <!-- Queue Info -->
        @if($visit->queueEntries->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-numbers me-1"></i>{{ __('visits.queue_history') }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('visits.department') }}</th>
                                <th>{{ __('visits.status') }}</th>
                                <th>{{ __('common.time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->queueEntries as $qe)
                            <tr>
                                <td class="fw-bold">{{ $qe->queue_number }}</td>
                                <td>
                                    @if($qe->department)
                                        <span class="badge bg-light text-dark">{{ $qe->department->name }}</span>
                                    @else
                                        <span class="badge bg-info text-white">{{ __('visits.triage') }}</span>
                                    @endif
                                </td>
                                <td><span class="badge bg-{{ $qe->status_badge }}">{{ $qe->status_label }}</span></td>
                                <td class="small text-muted">{{ $qe->created_at->format('h:i A') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- @if($visitInvoice && $visitInvoice->items->isNotEmpty())
                <div class="border-top px-3 py-2">
                    <p class="text-muted small fw-bold mb-1">Invoice Items</p>
                    <div class="d-flex flex-column gap-1">
                        @foreach($visitInvoice->items as $invItem)
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small fw-medium">{{ $invItem->description }}</span>
                                @if(($invItem->payer_type ?? 'cash') === 'insurance')
                                    <span class="badge bg-info-subtle text-info ms-1" style="font-size:0.6rem"><i class="ti ti-shield-check"></i></span>
                                @endif
                                @if($invItem->department)
                                    <span class="badge bg-light text-dark ms-1 small">{{ $invItem->department->name }}</span>
                                @endif
                            </div>
                            <div class="text-end small text-muted">
                                &#8373;{{ number_format($invItem->total_price, 2) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @php
                        $visitTotal   = $visitInvoice->items->sum('total_price');
                        $visitIns     = $visitInvoice->items->sum('insurance_covered');
                        $visitPatient = $visitInvoice->items->sum('patient_payable');
                    @endphp
                    <div class="d-flex justify-content-between border-top mt-2 pt-1 small">
                        <span class="text-muted">Insurance Covers</span>
                        <span class="text-success">&#8373;{{ number_format($visitIns, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between small fw-bold">
                        <span>Patient Pays</span>
                        <span>&#8373;{{ number_format($visitPatient, 2) }}</span>
                    </div>
                </div>
                @endif --}}
            </div>
        </div>
        @endif

        <!-- Timestamps -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clock me-1"></i>{{ __('visits.timestamps') }}</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">{{ __('visits.registered') }}</span>
                    <span class="small">{{ $visit->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">{{ __('visits.checked_in') }}</span>
                    <span class="small">{{ $visit->checked_in_at?->format('h:i A') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">{{ __('visits.checked_out') }}</span>
                    <span class="small">{{ $visit->checked_out_at?->format('h:i A') ?? '—' }}</span>
                </div>
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-timeline me-1"></i>{{ __('visits.status_history') }}</h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    @foreach($visit->statusLogs as $log)
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar avatar-sm rounded-circle bg-{{ \App\Enums\VisitStatus::from($log->to_status)->color() }} text-white d-flex align-items-center justify-content-center">
                                <i class="ti ti-arrow-right fs-12"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    @if($log->from_status)
                                        <span class="badge bg-light text-dark">{{ \App\Enums\VisitStatus::from($log->from_status)->translatedLabel() }}</span>
                                        <i class="ti ti-arrow-right text-muted mx-1"></i>
                                    @endif
                                    <span class="badge bg-{{ \App\Enums\VisitStatus::from($log->to_status)->color() }}">{{ \App\Enums\VisitStatus::from($log->to_status)->translatedLabel() }}</span>
                                </div>
                                <small class="text-muted">{{ $log->timestamp->format('h:i A') }}</small>
                            </div>
                            <small class="text-muted">by {{ $log->changedBy?->full_name ?? 'System' }}</small>
                            @if($log->notes)
                                <div class="text-muted small mt-1">{{ $log->notes }}</div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
</div>

@module('insurance')
@can('visits.edit')
<div class="modal fade" id="changeVisitInsuranceModal" tabindex="-1" aria-labelledby="changeVisitInsuranceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.visits.insurance.update', $visit) }}" class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title" id="changeVisitInsuranceModalLabel">
                    <i class="ti ti-shield-check me-1"></i>{{ __('visits.change_visit_insurance') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small">
                    {{ __('visits.insurance_change_warning') }}
                </div>

                <label for="visitInsuranceChangeSelect" class="form-label">{{ __('visits.active_ins_new_items') }}</label>
                <select name="visit_insurance_id" id="visitInsuranceChangeSelect" class="form-select @error('visit_insurance_id') is-invalid @enderror" required>
                    @foreach(($patientInsuranceOptions ?? []) as $option)
                        @php
                            $optionValid = (bool) ($option['is_valid'] ?? false);
                            $optionLabel = $option['provider_name'] ?? 'Insurance';
                            $optionDetails = collect([
                                $option['type_label'] ?? null,
                                $option['tier_name'] ?? null,
                                $option['membership_number'] ? '#'.$option['membership_number'] : null,
                                ! $optionValid ? 'inactive/expired' : null,
                            ])->filter()->implode(' - ');
                        @endphp
                        <option value="{{ $option['id'] }}"
                            @selected((int) old('visit_insurance_id', $visit->visit_insurance_id) === (int) $option['id'])
                            @disabled(! $optionValid)>
                            {{ $optionLabel }}{{ $optionDetails ? ' - '.$optionDetails : '' }}
                        </option>
                    @endforeach
                </select>
                @error('visit_insurance_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                @can('patients.edit')
                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" id="visitShowAddInsuranceBtn">
                            <i class="ti ti-plus me-1"></i>{{ __('patients.add_insurance') }}
                        </button>
                    </div>
                @endcan
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i>{{ __('visits.save_insurance') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@can('patients.edit')
<x-patient-insurance-form-modal
    :insurance-providers="$insuranceProviders ?? collect()"
    modal-id="visitShowInsuranceModal"
    form-id="visitShowInsuranceForm"
    patient-input-id="visitShowInsurancePatientId"
    insurance-input-id="visitShowInsuranceId"
    title-id="visitShowInsuranceModalTitle"
    feedback-id="visitShowInsuranceFeedback"
    provider-select-id="visitShowInsuranceProviderSelect"
    tier-select-id="visitShowInsuranceTierSelect"
    primary-checkbox-id="visitShowInsurancePrimary"
    save-button-id="visitShowInsuranceSaveBtn"
/>
@endcan
@endmodule

@push('scripts')
@can('patients.edit')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const addButton = document.getElementById('visitShowAddInsuranceBtn');
    const addModalEl = document.getElementById('visitShowInsuranceModal');
    const changeModalEl = document.getElementById('changeVisitInsuranceModal');
    const form = document.getElementById('visitShowInsuranceForm');
    const patientInput = document.getElementById('visitShowInsurancePatientId');
    const insuranceInput = document.getElementById('visitShowInsuranceId');
    const providerSelect = document.getElementById('visitShowInsuranceProviderSelect');
    const tierSelect = document.getElementById('visitShowInsuranceTierSelect');
    const feedback = document.getElementById('visitShowInsuranceFeedback');
    const saveButton = document.getElementById('visitShowInsuranceSaveBtn');
    const changeSelect = document.getElementById('visitInsuranceChangeSelect');

    if (!addButton || !addModalEl || !form || !providerSelect || !tierSelect || !changeSelect) {
        return;
    }

    const patientId = @json($visit->patient_id);
    const storeUrl = @json($workspaceRoutes->route('admin.patients.insurances.store', $visit->patient));
    const insuranceListUrl = @json($workspaceRoutes->route('admin.visits.patient-insurances'));
    const addModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(addModalEl) : null;
    const changeModal = changeModalEl && window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(changeModalEl) : null;

    function setFeedback(type, message) {
        if (!feedback) return;
        feedback.className = 'alert alert-' + type;
        feedback.textContent = message;
        feedback.classList.remove('d-none');
    }

    function resetFeedback() {
        if (!feedback) return;
        feedback.className = 'alert d-none';
        feedback.textContent = '';
    }

    function clearValidation() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());
    }

    function showValidation(errors) {
        Object.entries(errors || {}).forEach(([field, messages]) => {
            const input = form.querySelector('[name="' + field + '"]');
            if (!input) return;
            input.classList.add('is-invalid');
            const error = document.createElement('div');
            error.className = 'invalid-feedback d-block dynamic-invalid-feedback';
            error.textContent = Array.isArray(messages) ? messages[0] : messages;
            input.parentNode.insertBefore(error, input.nextSibling);
        });
    }

    function populateTiers() {
        tierSelect.innerHTML = '<option value="">{{ __('visits.tier_default') }}</option>';
        const selected = providerSelect.options[providerSelect.selectedIndex];
        if (!selected) return;
        let tiers = [];
        try {
            tiers = JSON.parse(selected.dataset.tiers || '[]');
        } catch (error) {
            tiers = [];
        }
        tiers.forEach(tier => {
            const option = document.createElement('option');
            option.value = tier.id;
            option.textContent = tier.name;
            tierSelect.appendChild(option);
        });
    }

    function optionText(insurance) {
        const parts = [
            insurance.type_label,
            insurance.tier_name,
            insurance.membership_number ? '#' + insurance.membership_number : null,
            insurance.is_valid ? null : 'inactive/expired',
        ].filter(Boolean);

        return (insurance.provider_name || 'Insurance') + (parts.length ? ' - ' + parts.join(' - ') : '');
    }

    async function refreshInsuranceOptions(selectedId) {
        const response = await fetch(insuranceListUrl + '?patient_id=' + encodeURIComponent(patientId), {
            headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
        });
        const data = await response.json();

        changeSelect.innerHTML = '';
        (data.insurances || []).forEach(insurance => {
            const option = document.createElement('option');
            option.value = insurance.id;
            option.textContent = optionText(insurance);
            option.disabled = !insurance.is_valid;
            option.selected = String(insurance.id) === String(selectedId);
            changeSelect.appendChild(option);
        });
    }

    providerSelect.addEventListener('change', populateTiers);

    addButton.addEventListener('click', () => {
        form.reset();
        clearValidation();
        resetFeedback();
        patientInput.value = patientId;
        insuranceInput.value = '';
        populateTiers();
        changeModal?.hide();
        addModal?.show();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearValidation();
        resetFeedback();

        const formData = new FormData(form);
        formData.delete('_patient_id');
        formData.delete('_insurance_id');

        const originalLabel = saveButton?.innerHTML;
        if (saveButton) {
            saveButton.disabled = true;
            saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('common.save') }}';
        }

        try {
            const response = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': @json(csrf_token()),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });
            const data = (response.headers.get('content-type') || '').includes('application/json')
                ? await response.json()
                : {};

            if (response.ok) {
                await refreshInsuranceOptions(data.insurance_id);
                addModal?.hide();
                changeModal?.show();
                return;
            }

            if (response.status === 422 && data.errors) {
                showValidation(data.errors);
            }
            setFeedback('danger', data.message || 'Unable to save insurance.');
        } catch (error) {
            setFeedback('danger', 'Network error while saving insurance.');
        } finally {
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML = originalLabel;
            }
        }
    });
});
</script>
@endcan
<script>
document.addEventListener('DOMContentLoaded', () => {
    const endpointTemplate = @json(route('admin.departments.visit-options', ['department' => '__ID__']));

    function optionList(select, placeholder, rows, labelFn, includePlaceholder = true) {
        select.innerHTML = '';
        if (includePlaceholder) {
            select.insertAdjacentHTML('beforeend', '<option value="">' + placeholder + '</option>');
        }
        rows.forEach(row => {
            select.insertAdjacentHTML('beforeend', '<option value="' + row.id + '">' + labelFn(row) + '</option>');
        });
    }

    function isConsultationService(service) {
        return String(service?.category || '').toLowerCase() === 'consultation';
    }

    function initVisitRouteChooser(scope = document) {
        const deptSelect = scope.querySelector('#visitRouteDeptSelect');
        const serviceSelect = scope.querySelector('#visitRouteServiceSelect');
        const showOtherServices = scope.querySelector('#visitRouteShowOtherServices');
        const doctorSelect = scope.querySelector('#visitRouteDoctorSelect');
        if (!deptSelect || !serviceSelect || !doctorSelect || deptSelect.dataset.routeChooserReady) return;

        deptSelect.dataset.routeChooserReady = 'true';
        let routeServices = [];

        function visibleRouteServices() {
            if (showOtherServices && showOtherServices.checked) {
                return routeServices;
            }

            return routeServices.filter(isConsultationService);
        }

        function renderRouteServices() {
            const visibleServices = visibleRouteServices();

            optionList(serviceSelect, '', visibleServices, row => row.name, false);
        }

        deptSelect.addEventListener('change', async () => {
            serviceSelect.disabled = true;
            doctorSelect.disabled = true;
            if (showOtherServices) {
                showOtherServices.checked = false;
                showOtherServices.disabled = true;
            }
            serviceSelect.innerHTML = '<option value="">{{ __('visits.loading_services_doctors') }}</option>';
            doctorSelect.innerHTML = '<option value="">{{ __('visits.loading_services_doctors') }}</option>';

            if (!deptSelect.value) {
                routeServices = [];
                serviceSelect.innerHTML = '<option value="">{{ __('visits.select_dept_first') }}</option>';
                doctorSelect.innerHTML = '<option value="">{{ __('visits.select_dept_first') }}</option>';
                return;
            }

            const response = await fetch(endpointTemplate.replace('__ID__', deptSelect.value), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const payload = await response.json();
            routeServices = payload.services || [];
            const doctors = payload.doctors || [];

            serviceSelect.disabled = false;
            doctorSelect.disabled = false;
            if (showOtherServices) {
                showOtherServices.disabled = !routeServices.some(service => !isConsultationService(service));
            }
            renderRouteServices();
            optionList(doctorSelect, doctors.length ? '{{ __('visits.select_doctor') }}' : '{{ __('visits.select_dept_first') }}', doctors, row => row.name);
        });

        if (showOtherServices) {
            showOtherServices.addEventListener('change', renderRouteServices);
        }
    }

    function showQueueRouteFeedback(form, type, message) {
        const feedback = form.querySelector('.js-queue-route-feedback');
        if (!feedback) return;

        feedback.className = 'alert alert-' + type + ' mb-0 js-queue-route-feedback';
        feedback.textContent = message;
        feedback.classList.remove('d-none');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    async function refreshVisitShowContent(message) {
        const currentContent = document.getElementById('visitShowMainContent');
        if (!currentContent) return;

        const response = await fetch(window.location.href, {
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const html = await response.text();
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        const replacement = parsed.getElementById('visitShowMainContent');
        if (!replacement) return;

        currentContent.innerHTML = replacement.innerHTML;
        if (message) {
            currentContent.insertAdjacentHTML(
                'afterbegin',
                '<div class="alert alert-success alert-dismissible fade show" role="alert">'
                    + escapeHtml(message)
                    + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
                + '</div>'
            );
        }
        initVisitRouteChooser(currentContent);
    }

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('.js-queue-consultation-route-form');
        if (!form) return;

        event.preventDefault();

        const submitBtn = form.querySelector('[type="submit"]');
        const originalLabel = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __('visits.queue_btn') }}';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });
            const payload = await response.json();

            if (!response.ok) {
                showQueueRouteFeedback(form, 'danger', payload.error || payload.message || 'Unable to queue consultation route.');
                return;
            }

            await refreshVisitShowContent(payload.success || payload.message || '');
        } catch (error) {
            showQueueRouteFeedback(form, 'danger', error.message || 'Unable to queue consultation route.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalLabel;
            }
        }
    });

    initVisitRouteChooser();
});
</script>
@if($errors->has('visit_insurance_id'))
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('changeVisitInsuranceModal');
    if (modal && window.bootstrap) {
        window.bootstrap.Modal.getOrCreateInstance(modal).show();
    }
});
</script>
@endif
@endpush
@endsection
