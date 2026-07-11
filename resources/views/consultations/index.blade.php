@extends('layouts.app')
@section('title', __('consultations.title'))

@section('content')
<x-page-header :title="__('consultations.title')" icon="ti-stethoscope" />

<x-filter-bar
    :action="route('admin.consultations.index')"
    :reset-url="route('admin.consultations.index')"
    ajax
    ajax-target="#consultationsIndexResults"
>
    <input type="hidden" name="per_page" value="{{ $filters['per_page'] ?? $routes->perPage() }}" data-filter-per-page-input>

    <div class="col-md-3">
        <label class="form-label small">{{ __('common.search') }}</label>
        <input type="text" name="search" class="form-control" placeholder="{{ __('consultations.search_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('consultations.visit_type') }}</label>
        <select name="visit_type" class="form-select">
            <option value="">{{ __('consultations.all_types') }}</option>
            @foreach(\App\Enums\VisitType::cases() as $type)
                <option value="{{ $type->value }}" @selected(($filters['visit_type'] ?? '') == $type->value)>{{ $type->translatedLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        @include('partials.date-range-filter', [
            'id' => 'consultationDateRangePicker',
            'value' => $filters['date_range'] ?? '',
            'labelClass' => 'small',
            'submitOnApply' => true,
        ])
    </div>
    <div class="col-md-2">
        <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="my_patients" value="1" id="myPatients" @checked(request('my_patients'))>
            <label class="form-check-label" for="myPatients">{{ __('consultations.my_patients_only') }}</label>
        </div>
    </div>
    <x-slot:actions>
        <a aria-label="{{ __('common.reset') }}" title="{{ __('common.reset') }}" href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary btn-icon" data-filter-reset><i class="ti ti-x"></i></a>
    </x-slot:actions>
</x-filter-bar>

<div id="consultationsIndexResults">
<x-data-table
    id="consultationsDataTable"
    :paginator="$routes"
    show-summary
    show-per-page
    :current-per-page="$filters['per_page'] ?? $routes->perPage()"
    :per-page-options="[10, 15, 25, 50, 100]"
>
    <x-slot:head>
        <tr>
            <th>{{ __('consultations.queue_number') }}</th>
            <th>{{ __('consultations.visit_number') }}</th>
            <th>{{ __('common.patient') }}</th>
            <th>{{ __('common.type') }}</th>
            <th>{{ __('common.department') }}</th>
            <th>{{ __('consultations.services') }}</th>
            <th>{{ __('common.priority') }}</th>
            <th>{{ __('common.status') }}</th>
            <th>{{ __('consultations.waiting_time') }}</th>
            <th>{{ __('common.action') }}</th>
        </tr>
    </x-slot:head>

                    @forelse($routes as $route)
                    @php
                        $visit = $route->visit;
                        $isActive = $route->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE;
                        $isPending = $route->status === \App\Models\VisitConsultationRoute::STATUS_PENDING;
                        $reopenPolicy = app(\App\Services\Consultation\ConsultationReopenEligibilityService::class);
                        $reopenEligibility = $route->status === \App\Models\VisitConsultationRoute::STATUS_COMPLETED && auth()->user()
                            ? $reopenPolicy->canReopen(auth()->user(), $visit, $route)
                            : null;
                        $activeAdmission = $visit->admission && $reopenPolicy->isActiveAdmission($visit->admission);
                        $dischargedToday = $visit->admission && $reopenPolicy->isDischargedToday($visit->admission);
                        $serviceNames = $route->routeServices
                            ->map(fn ($routeService) => $routeService->service?->name)
                            ->filter()
                            ->values();
                        if ($serviceNames->isEmpty() && $route->service) {
                            $serviceNames = collect([$route->service->name]);
                        }
                        $routeDepartmentLabel = $route->isEmergencySession()
                            ? __('consultations.emergency_department_session')
                            : ($route->department?->name ?? '-');
                        $queueEntries = $visit->queueEntries ?? collect();
                        $queueEntry = $queueEntries
                            ->filter(fn ($entry) => $route->department_id && (int) $entry->department_id === (int) $route->department_id)
                            ->filter(fn ($entry) => $entry->status === 'waiting')
                            ->sortBy('queue_number')
                            ->first();
                    @endphp
                    <tr>
                        <td>
                            @if($queueEntry)
                                <span class="badge bg-soft-primary text-primary">#{{ $queueEntry->queue_number }}</span>
                                <div class="small text-muted">{{ $queueEntry->status_label }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium text-primary">
                                {{ $visit->visit_number }}
                            </a>
                            <!-- <span class="fw-medium">{{ $visit->visit_number }}</span> -->
                            <div class="small text-muted">{{ $visit->patient->patient_number }}</div>
                            <!-- <div class="small text-muted">{{ $visit->visit_type?->translatedLabel() }}</div> -->
                        </td>
                        <td>
                            <!-- <div class="fw-medium">{{ $visit->patient->full_name }}</div>
                            <small class="text-muted">
                                {{ $visit->patient->patient_number }}
                                @if($visit->patient->age)
                                    &middot; {{ $visit->patient->age }}y
                                @endif
                                @if($visit->patient->gender)
                                    {{ $visit->patient->gender->value }}
                                @endif
                            </small> -->
                            <div>
                                <a href="{{ route('admin.patients.show', $visit->patient) }}" class="fw-medium">{{ $visit->patient->full_name }}</a>
                                <div class="small text-muted">{{ $visit->patient->gender }} · {{ $visit->patient->age }}y</div>
                                @if(($pbMap[$visit->patient_id] ?? 0) > 0)
                                    <x-billing.outstanding-badge :amount="$pbMap[$visit->patient_id]" :show-amount="$pbCanAmount" class="mt-1" />
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                                {{ $visit->visit_type->translatedLabel() }}
                            </span>
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                @if($activeAdmission)
                                    <span class="badge bg-info-subtle text-info border">{{ __('visits.badges.on_admission') }}</span>
                                @endif
                                @if($dischargedToday)
                                    <span class="badge bg-success-subtle text-success border">{{ __('visits.badges.discharged_today') }}</span>
                                @endif
                                @if($reopenEligibility?->allowed)
                                    <span class="badge bg-warning-subtle text-warning border">{{ __('visits.badges.reopen_available') }}</span>
                                @elseif($route->status === \App\Models\VisitConsultationRoute::STATUS_COMPLETED)
                                    <span class="badge bg-secondary-subtle text-secondary border">{{ __('visits.badges.read_only') }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $route->isEmergencySession() ? 'bg-danger' : 'bg-light text-dark' }}">{{ $routeDepartmentLabel }}</span>
                        </td>
                        <td>
                            <div class="fw-medium small">{{ $serviceNames->implode(', ') ?: '-' }}</div>
                            <!-- <small class="text-muted">{{ Str::limit($visit->chief_complaint, 36) ?? '-' }}</small> -->
                            <small class="text-muted">{{ $route->doctor || $route->mainDoctor ? 'Dr. ' . ($route->doctor?->full_name ?? $route->mainDoctor?->full_name) : '-' }}</small>
                        </td>
                        <td>
                            <x-status-badge :status="$visit->priority" />
                            @if($visit->triage_score)
                                <x-status-badge :status="$visit->triage_score" class="ms-1" />
                            @endif
                        </td>
                        <!-- <td>{{ $route->doctor || $route->mainDoctor ? 'Dr. ' . ($route->doctor?->full_name ?? $route->mainDoctor?->full_name) : '-' }}</td> -->
                        <td>
                            <x-status-badge :status="$route->status" domain="consultation_route" />
                            <br/>
                            <x-status-badge :status="$visit->status" />
                            <!-- <div class="small text-muted">{{ $visit->status->translatedLabel() }}</div> -->
                        </td>
                        <td><small>{{ ($route->activated_at ?? $route->started_at ?? $route->created_at)->diffForHumans(null, true) }}</small></td>
                        <td>
                            @if($isPending)
                                @can('consultations.create')
                                <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $route]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ti ti-player-play me-1"></i>{{ $visit->status === \App\Enums\VisitStatus::CONSULTING ? __('consultations.activate') : __('consultations.start') }}
                                    </button>
                                </form>
                                @endcan
                            @elseif($isActive)
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-success">
                                    <i class="ti ti-pencil me-1"></i>{{ __('consultations.continue') }}
                                </a>
                            @elseif($reopenEligibility?->allowed)
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-warning">
                                    <i class="ti ti-lock-open me-1"></i>{{ __('visits.actions.reopen_consultation') }}
                                </a>
                            @else
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-eye me-1"></i>{{ __('consultations.open') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="ti ti-stethoscope fs-1 d-block mb-2"></i>
                            {{ __('consultations.no_active_consultations') }}
                        </td>
                    </tr>
                    @endforelse
</x-data-table>
</div>
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
@endpush
