@extends('layouts.app')
@section('title', 'Doctor Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Doctor Dashboard</h4>
        <p class="text-muted mb-0">Welcome back, {{ auth()->user()->full_name }}</p>
    </div>
</div>

<!-- Stats Row -->
<div class="row">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Today's Patients</p>
                <h4 class="fw-bold mb-0">{{ $stats['total_today'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Waiting</p>
                <h4 class="fw-bold mb-0">{{ $stats['waiting'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Consulting</p>
                <h4 class="fw-bold mb-0">{{ $stats['consulting'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Completed</p>
                <h4 class="fw-bold mb-0">{{ $stats['completed_today'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Pending Lab</p>
                <h4 class="fw-bold mb-0">{{ $stats['pending_lab'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-secondary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Pending Rx</p>
                <h4 class="fw-bold mb-0">{{ $stats['pending_prescriptions'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Patient Queue -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Today's Patient Queue</h5>
                <span class="badge bg-primary">{{ $stats['total_today'] ?? 0 }} patients</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Visit #</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayVisits as $visit)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.visits.show', $visit) }}" class="text-primary fw-medium">
                                        {{ $visit->visit_number }}
                                    </a>
                                </td>
                                <td>
                                    <div>
                                        <strong>{{ $visit->patient->full_name }}</strong>
                                        <br><small class="text-muted">{{ $visit->patient->patient_number }}</small>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark">{{ $visit->visit_type->label() }}</span></td>
                                <td><span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span></td>
                                <td>{{ $visit->department?->name ?? '—' }}</td>
                                <td><span class="badge bg-{{ $visit->status->color() }}">{{ $visit->status->label() }}</span></td>
                                <td>
                                    @if($visit->status->value === 'waiting' || $visit->status->value === 'consulting')
                                        <a href="{{ route('doctor.consultation.show', $visit) }}" class="btn btn-sm btn-primary">
                                            <i class="ti ti-stethoscope me-1"></i>Consult
                                        </a>
                                    @elseif($visit->status->value === 'completed')
                                        <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="ti ti-eye me-1"></i>View
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ti ti-mood-happy fs-1 d-block mb-2"></i>
                                    No patients assigned yet today
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Upcoming This Week -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Upcoming This Week</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($upcomingVisits as $visit)
                            <tr>
                                <td>{{ $visit->visit_date->format('D, d M') }}</td>
                                <td>{{ $visit->patient->full_name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $visit->visit_type->label() }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No upcoming visits</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Completed -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recently Completed</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient</th>
                                <th>Department</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCompleted as $visit)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.visits.show', $visit) }}" class="text-primary">
                                        {{ $visit->patient->full_name }}
                                    </a>
                                </td>
                                <td>{{ $visit->department?->name ?? '—' }}</td>
                                <td>{{ $visit->visit_date->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No completed visits</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
