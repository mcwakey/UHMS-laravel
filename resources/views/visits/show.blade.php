@extends('layouts.app')
@section('title', 'Visit ' . $visit->visit_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Visit {{ $visit->visit_number }}</h4>
        <small class="text-muted">Created {{ $visit->created_at->format('d M Y, h:i A') }} by {{ $visit->createdBy?->full_name }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a>
        @can('visits.create')
        <a href="{{ route('admin.visits.create', ['patient_id' => $visit->patient_id]) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-plus me-1"></i>New Visit for Patient
        </a>
        @endcan
    </div>
</div>

<div class="row">
    <!-- Left Column — Visit Info -->
    <div class="col-lg-8">
        <!-- Status Bar -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0">Visit Status Flow</h6>
                    <span class="badge bg-{{ $visit->status->color() }} fs-14 px-3 py-2">{{ $visit->status->label() }}</span>
                </div>
                <!-- Status Timeline -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    @php
                        $statusFlow = [
                            \App\Enums\VisitStatus::REGISTERED,
                            \App\Enums\VisitStatus::WAITING,
                            \App\Enums\VisitStatus::TRIAGE,
                            \App\Enums\VisitStatus::CONSULTING,
                            \App\Enums\VisitStatus::LAB,
                            \App\Enums\VisitStatus::PHARMACY,
                            \App\Enums\VisitStatus::BILLING,
                            \App\Enums\VisitStatus::COMPLETED,
                        ];
                        $visitedStatuses = $visit->statusLogs->pluck('to_status')->toArray();
                        $currentStatus = $visit->status;
                    @endphp
                    @foreach($statusFlow as $i => $flowStatus)
                        @php
                            $isVisited = in_array($flowStatus->value, $visitedStatuses);
                            $isCurrent = $currentStatus === $flowStatus;
                            $stepClass = $isCurrent ? 'bg-' . $flowStatus->color() . ' text-white' : ($isVisited ? 'bg-success-subtle text-success' : 'bg-light text-muted');
                        @endphp
                        <span class="badge rounded-pill {{ $stepClass }} px-2 py-1 small">
                            @if($isVisited && !$isCurrent)<i class="ti ti-check me-1"></i>@endif
                            {{ $flowStatus->label() }}
                        </span>
                        @if(!$loop->last)
                            <i class="ti ti-chevron-right text-muted small"></i>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Status Transition Actions -->
        @if($visit->status->allowedTransitions())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-switch-horizontal me-1"></i>Transition Visit</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                        <form method="POST" action="{{ route('admin.visits.transition', $visit) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm"
                                    onclick="return confirm('Move visit to {{ $nextStatus->label() }}?')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Visit Details Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>Visit Details</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Visit Type</label>
                        <div>
                            <span class="badge bg-{{ $visit->visit_type === \App\Enums\VisitType::EMERGENCY ? 'danger' : ($visit->visit_type === \App\Enums\VisitType::INPATIENT ? 'info' : 'light text-dark') }}">
                                {{ $visit->visit_type->label() }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Priority</label>
                        <div><span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span></div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Visit Date</label>
                        <div class="fw-medium">{{ $visit->visit_date->format('d M Y') }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Department</label>
                        <div class="fw-medium">{{ $visit->department?->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Assigned Doctor</label>
                        <div class="fw-medium">{{ $visit->assignedDoctor ? 'Dr. ' . $visit->assignedDoctor->full_name : '—' }}</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="text-muted small mb-1">Duration</label>
                        <div class="fw-medium">{{ $visit->duration ?? '—' }}</div>
                    </div>
                </div>
                @if($visit->chief_complaint)
                <div class="mb-3">
                    <label class="text-muted small mb-1">Chief Complaint</label>
                    <div class="bg-light rounded p-3">{{ $visit->chief_complaint }}</div>
                </div>
                @endif
                @if($visit->notes)
                <div>
                    <label class="text-muted small mb-1">Notes</label>
                    <div class="bg-light rounded p-3">{{ $visit->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Status Timeline -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-timeline me-1"></i>Status History</h6>
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
                                        <span class="badge bg-light text-dark">{{ \App\Enums\VisitStatus::from($log->from_status)->label() }}</span>
                                        <i class="ti ti-arrow-right text-muted mx-1"></i>
                                    @endif
                                    <span class="badge bg-{{ \App\Enums\VisitStatus::from($log->to_status)->color() }}">{{ \App\Enums\VisitStatus::from($log->to_status)->label() }}</span>
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

    <!-- Right Column — Patient Card -->
    <div class="col-lg-4">
        <!-- Patient Card -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>Patient</h6>
            </div>
            <div class="card-body text-center">
                @if($visit->patient->avatar)
                    <img src="{{ asset('storage/' . $visit->patient->avatar) }}" class="avatar avatar-xl rounded-circle mb-3" alt="">
                @else
                    <div class="avatar avatar-xl bg-primary rounded-circle text-white mx-auto mb-3 d-flex align-items-center justify-content-center">
                        <span class="fs-24">{{ strtoupper(substr($visit->patient->first_name, 0, 1) . substr($visit->patient->last_name, 0, 1)) }}</span>
                    </div>
                @endif
                <h5 class="fw-bold mb-1">{{ $visit->patient->full_name }}</h5>
                <p class="text-muted mb-2">{{ $visit->patient->patient_number }}</p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    @if($visit->patient->is_nhis_active)
                        <span class="badge bg-success">NHIS Active</span>
                    @endif
                    @if($visit->patient->blood_group)
                        <span class="badge bg-danger">{{ $visit->patient->blood_group->label() }}</span>
                    @endif
                </div>

                <div class="text-start">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Age</span>
                        <span class="fw-medium">{{ $visit->patient->age }} years</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Gender</span>
                        <span class="fw-medium">{{ $visit->patient->gender?->label() ?? '—' }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Phone</span>
                        <span class="fw-medium">{{ $visit->patient->phone }}</span>
                    </div>
                    @if($visit->patient->allergies)
                    <div class="mt-3">
                        <span class="text-muted small">Allergies</span>
                        <div class="alert alert-warning py-1 px-2 mt-1 mb-0 small">{{ $visit->patient->allergies }}</div>
                    </div>
                    @endif
                    @if($visit->patient->chronic_conditions)
                    <div class="mt-2">
                        <span class="text-muted small">Chronic Conditions</span>
                        <div class="alert alert-info py-1 px-2 mt-1 mb-0 small">{{ $visit->patient->chronic_conditions }}</div>
                    </div>
                    @endif
                </div>

                <a href="{{ route('admin.patients.show', $visit->patient) }}" class="btn btn-outline-primary btn-sm mt-3 w-100">
                    <i class="ti ti-external-link me-1"></i>View Full Profile
                </a>
            </div>
        </div>

        <!-- Queue Info -->
        @if($visit->queueEntries->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-list-numbers me-1"></i>Queue History</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Dept</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($visit->queueEntries as $qe)
                            <tr>
                                <td class="fw-bold">{{ $qe->queue_number }}</td>
                                <td>{{ $qe->department?->name ?? '—' }}</td>
                                <td><span class="badge bg-{{ $qe->status_badge }}">{{ $qe->status_label }}</span></td>
                                <td class="small text-muted">{{ $qe->created_at->format('h:i A') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Timestamps -->
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-clock me-1"></i>Timestamps</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Registered</span>
                    <span class="small">{{ $visit->created_at->format('d M Y, h:i A') }}</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Checked In</span>
                    <span class="small">{{ $visit->checked_in_at?->format('h:i A') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Checked Out</span>
                    <span class="small">{{ $visit->checked_out_at?->format('h:i A') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
