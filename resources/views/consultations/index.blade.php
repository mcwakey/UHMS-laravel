@extends('layouts.app')
@section('title', 'Consultations')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Consultations</h4>
        <small class="text-muted">Route-aware consultation queue</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
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
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="my_patients" value="1" id="myPatients" @checked(request('my_patients'))>
                    <label class="form-check-label" for="myPatients">My Patients Only</label>
                </div>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary ms-1">Clear</a>
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
                        <th>Patient</th>
                        <th>Visit #</th>
                        <th>Route Department</th>
                        <th>Route Service</th>
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
                    @endphp
                    <tr>
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
                            <span class="fw-medium">{{ $visit->visit_number }}</span>
                            <div class="small text-muted">{{ $visit->visit_type?->label() }}</div>
                        </td>
                        <td><span class="badge bg-light text-dark">{{ $route->department?->name ?? '-' }}</span></td>
                        <td>
                            <div class="fw-medium small">{{ $route->service?->name ?? '-' }}</div>
                            <small class="text-muted">{{ Str::limit($visit->chief_complaint, 36) ?? '-' }}</small>
                        </td>
                        <td>
                            <span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span>
                            @if($visit->triage_score)
                                <span class="badge bg-{{ $visit->triage_score->color() }} ms-1">{{ $visit->triage_score->label() }}</span>
                            @endif
                        </td>
                        <td>{{ $route->doctor ? 'Dr. ' . $route->doctor->full_name : '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $isActive ? 'success' : 'warning' }}">{{ $route->status }}</span>
                            <div class="small text-muted">{{ $visit->status->label() }}</div>
                        </td>
                        <td><small>{{ ($route->activated_at ?? $route->started_at ?? $route->created_at)->diffForHumans(null, true) }}</small></td>
                        <td>
                            @if($isPending)
                                @can('consultations.create')
                                <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $route]) }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ti ti-player-play me-1"></i>{{ $visit->status === \App\Enums\VisitStatus::CONSULTING ? 'Activate Session' : 'Start Session' }}
                                    </button>
                                </form>
                                @endcan
                            @elseif($isActive)
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-success">
                                    <i class="ti ti-pencil me-1"></i>Continue Consultation
                                </a>
                            @else
                                <a href="{{ route('admin.consultations.routes.show', [$visit, $route]) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-eye me-1"></i>Open Session
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
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
