@extends('layouts.app')
@section('title', 'Visits / OPD')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Visits / OPD</h4>
    </div>
    <div class="d-flex gap-2">
        @can('queue.view')
        <a href="{{ route('admin.queue.board') }}" class="btn btn-outline-info btn-md">
            <i class="ti ti-list-numbers me-1"></i>Queue Board
        </a>
        @endcan
        @can('visits.create')
        <a href="{{ route('admin.visits.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>New Visit
        </a>
        @endcan
    </div>
</div>

<!-- Today's Stats -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Today's Total</p>
                <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Waiting</p>
                <h4 class="fw-bold mb-0">{{ $stats['waiting'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Consulting</p>
                <h4 class="fw-bold mb-0">{{ $stats['consulting'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Completed</p>
                <h4 class="fw-bold mb-0">{{ $stats['completed'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Emergency</p>
                <h4 class="fw-bold mb-0">{{ $stats['emergency'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-secondary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Cancelled</p>
                <h4 class="fw-bold mb-0">{{ $stats['cancelled'] }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.visits.index') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Visit #, patient name, phone..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(\App\Enums\VisitStatus::cases() as $status)
                            <option value="{{ $status->value }}" {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Visit Type</label>
                    <select name="visit_type" class="form-select">
                        <option value="">All Types</option>
                        @foreach(\App\Enums\VisitType::cases() as $type)
                            <option value="{{ $type->value }}" {{ ($filters['visit_type'] ?? '') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-filter"></i></button>
                        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Visit List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visit #</th>
                        <th>Patient</th>
                        <th>Age</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Doctor</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Duration</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                    <tr>
                        <td>
                            <a href="{{ route('admin.visits.show', $visit) }}" class="fw-medium text-primary">
                                {{ $visit->visit_number }}
                            </a>
                        </td>
                        <td>
                            <div>
                                <a href="{{ route('admin.patients.show', $visit->patient) }}" class="fw-medium">{{ $visit->patient->full_name }}</a>
                                <br><small class="text-muted">{{ $visit->patient->patient_number }}</small>
                            </div>
                        </td>
                        <td>{{ $visit->patient_age ?? $visit->patient->age }}y</td>
                        <td>
                            <span class="badge bg-{{ $visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                                {{ $visit->visit_type->label() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span>
                            @if($visit->triage_score)
                                <span class="badge bg-{{ $visit->triage_score->color() }} ms-1">{{ $visit->triage_score->label() }}</span>
                            @endif
                        </td>
                        <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $visit->status->color() }}">{{ $visit->status->label() }}</span>
                        </td>
                        <td>{{ $visit->visit_date->format('d M Y') }}</td>
                        <td>{{ $visit->duration ?? '—' }}</td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.show', $visit) }}"><i class="ti ti-eye me-2"></i>View Details</a></li>
                                    @can('visits.preview')
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.preview', $visit) }}"><i class="ti ti-eye-search me-2"></i>Preview Visit</a></li>
                                    @endcan
                                    @can('visits.edit')
                                    <li><a class="dropdown-item" href="{{ route('admin.visits.edit', $visit) }}"><i class="ti ti-pencil me-2"></i>Edit Visit</a></li>
                                    @endcan
                                    @if($visit->status->allowedTransitions())
                                    <li><hr class="dropdown-divider"></li>
                                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                                        <li>
                                            <form method="POST" action="{{ route('admin.visits.transition', $visit) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-arrow-right me-2"></i>{{ $nextStatus->label() }}
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">
                            <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                            No visits found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($visits->hasPages())
    <div class="card-footer">
        {{ $visits->withQueryString()->links() }}
    </div>
    @endif
</div>
@endsection
