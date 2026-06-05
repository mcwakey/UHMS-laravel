@extends('layouts.app')
@section('title', 'Consultations')

@section('content')
<x-page-header title="Consultations" description="Route-aware consultation queue" icon="ti-stethoscope">
    <x-slot:actions>
        <div class="bg-white border shadow-sm rounded px-1 pb-0 text-center d-flex align-items-center justify-content-center">
            <a aria-label="Consultation queue" title="Consultation queue" href="{{ route('admin.consultations.index') }}" class="bg-light rounded p-1 d-flex align-items-center justify-content-center">
                <i class="ti ti-list fs-14 text-body"></i>
            </a>
            @can('appointments.view')
            <a aria-label="Appointment calendar" title="Appointment calendar" href="{{ route('admin.appointments.calendar') }}" class="bg-white rounded p-1 d-flex align-items-center justify-content-center">
                <i class="ti ti-calendar-event fs-14 text-body"></i>
            </a>
            @endcan
        </div>
    </x-slot:actions>
</x-page-header>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end" data-auto-filter-form="consultations-index">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Patient name, visit number..." value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Visit Type</label>
                <select name="visit_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach(\App\Enums\VisitType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(($filters['visit_type'] ?? '') == $type->value)>{{ $type->label() }}</option>
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
                    <label class="form-check-label" for="myPatients">My Patients Only</label>
                </div>
            </div>
            {{-- <div class="col-md-1"> --}}
            <div class="col-md-auto">
                <div class="d-flex gap-1">
                    <button aria-label="Filter" title="Filter" type="submit" class="btn btn-primary"><i class="ti ti-filter"></i> Filter</button>
                    <a aria-label="Close" title="Close" href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
                </div>
                {{-- </div> --}}
                {{-- <button type="submit" class="btn btn-primary"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary ms-1">Clear</a> --}}
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Queue #</th>
                        <th>Visit #</th>
                        <th>Patient</th>
                        <th>Department</th>
                        <th>Services</th>
                        <th>Priority</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Waiting Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($routes as $route)
                    @php
                        $visit = $route->visit;
                        $isActive = $route->status === \App\Models\VisitConsultationRoute::STATUS_ACTIVE;
                        $isPending = $route->status === \App\Models\VisitConsultationRoute::STATUS_PENDING;
                        $serviceNames = $route->routeServices
                            ->map(fn ($routeService) => $routeService->service?->name)
                            ->filter()
                            ->values();
                        if ($serviceNames->isEmpty() && $route->service) {
                            $serviceNames = collect([$route->service->name]);
                        }
                        $routeDepartmentLabel = $route->isEmergencySession()
                            ? 'Emergency Department Session'
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
                            <span class="fw-medium">{{ $visit->visit_number }}</span>
                            <div class="small text-muted">{{ $visit->visit_type?->label() }}</div>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $visit->patient->full_name }}</div>
                            <small class="text-muted">
                                {{ $visit->patient->patient_number }}
                                @if($visit->patient->age)
                                    &middot; {{ $visit->patient->age }}y
                                @endif
                                @if($visit->patient->gender)
                                    {{ $visit->patient->gender->value }}
                                @endif
                            </small>
                        </td>
                        <td>
                            <span class="badge {{ $route->isEmergencySession() ? 'bg-danger' : 'bg-light text-dark' }}">{{ $routeDepartmentLabel }}</span>
                        </td>
                        <td>
                            <div class="fw-medium small">{{ $serviceNames->implode(', ') ?: '-' }}</div>
                            <small class="text-muted">{{ Str::limit($visit->chief_complaint, 36) ?? '-' }}</small>
                        </td>
                        <td>
                            <x-status-badge :status="$visit->priority" />
                            @if($visit->triage_score)
                                <x-status-badge :status="$visit->triage_score" class="ms-1" />
                            @endif
                        </td>
                        <td>{{ $route->doctor || $route->mainDoctor ? 'Dr. ' . ($route->doctor?->full_name ?? $route->mainDoctor?->full_name) : '-' }}</td>
                        <td>
                            <x-status-badge :status="$route->status" domain="consultation_route" />
                            <div class="small text-muted">{{ $visit->status->label() }}</div>
                        </td>
                        <td><small>{{ ($route->activated_at ?? $route->started_at ?? $route->created_at)->diffForHumans(null, true) }}</small></td>
                        <td>
                            @if($isPending)
                                @can('consultations.create')
                                <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $route]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ti ti-player-play me-1"></i>{{ $visit->status === \App\Enums\VisitStatus::CONSULTING ? 'Activate' : 'Start' }}
                                    </button>
                                </form>
                                @endcan
                            @elseif($isActive)
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-success">
                                    <i class="ti ti-pencil me-1"></i>Continue
                                </a>
                            @else
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-eye me-1"></i>Open
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            <i class="ti ti-stethoscope fs-1 d-block mb-2"></i>
                            No active consultations at the moment.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-center mt-3">
    {{ $routes->withQueryString()->links() }}
</div>
@endsection

@push('scripts')
    @include('partials.date-range-filter-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.querySelector('[data-auto-filter-form="consultations-index"]');
            if (!filterForm) {
                return;
            }

            let searchTimer = null;
            const searchInput = filterForm.querySelector('input[name="search"]');

            filterForm.querySelectorAll('select, input[type="checkbox"]').forEach(function (field) {
                field.addEventListener('change', function () {
                    filterForm.requestSubmit();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(function () {
                        filterForm.requestSubmit();
                    }, 400);
                });
            }
        });
    </script>
@endpush
