@extends('layouts.app')
@section('title', 'Triage Queue')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2 text-info"></i>Triage Queue</h4>
        <small class="text-muted">Patients awaiting triage assessment today</small>
    </div>
    <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>All Visits
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ti ti-circle-check me-1"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-3">
    {{-- ── Awaiting Triage (WAITING) ───────────────────────────────────────── --}}
    @php $waiting = $visits->where('status', \App\Enums\VisitStatus::WAITING)->values(); @endphp
    <div class="col-6">
        <div class="card border-warning border-opacity-50">
            <div class="card-header d-flex align-items-center gap-2 bg-warning bg-opacity-10">
                <i class="ti ti-clock-hour4 text-warning fs-5"></i>
                <h6 class="fw-bold mb-0 text-warning">Awaiting Triage</h6>
                <span class="ms-auto badge bg-warning text-dark rounded-pill">{{ $waiting->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($waiting as $visit)
                    @php $queueEntry = $visit->queueEntries->first(); @endphp
                    <div class="d-flex align-items-center px-3 py-2 border-bottom hover-bg-light">
                        <div class="flex-shrink-0 text-center me-3">
                            @if($queueEntry)
                                <span class="badge bg-warning text-dark fs-6">#{{ $queueEntry->queue_number }}</span>
                                <div class="text-muted" style="font-size:0.7rem;">Queue</div>
                            @else
                                <span class="badge bg-light text-muted">-</span>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $visit->patient->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $visit->patient->patient_number }}
                                &bull; {{ $visit->visit_number }}
                                &bull; <x-status-badge :status="$visit->priority" class="py-0" />
                                &bull; <x-status-badge :status="$visit->status" class="py-0" />
                            </div>
                            @if($visit->chief_complaint)
                                <div class="text-muted" style="font-size:0.78rem;">{{ Str::limit($visit->chief_complaint, 60) }}</div>
                            @endif
                        </div>
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-warning btn-sm ms-2">
                            <i class="ti ti-stethoscope me-1"></i>Start Triage
                        </a>
                    </div>
                @empty
                    <div class="text-center text-muted py-2 small">
                        <i class="ti ti-circle-check fs-3 d-block mb-1 text-success"></i>
                        No patients awaiting triage
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ── On Assessment (TRIAGE) ──────────────────────────────────────────── --}}
    @php $onAssessment = $visits->where('status', \App\Enums\VisitStatus::TRIAGE)->values(); @endphp
    <div class="col-6">
        <div class="card border-info border-opacity-50">
            <div class="card-header d-flex align-items-center gap-2 bg-info bg-opacity-10">
                <i class="ti ti-activity text-info fs-5"></i>
                <h6 class="fw-bold mb-0 text-info">On Assessment</h6>
                <span class="ms-auto badge bg-info rounded-pill">{{ $onAssessment->count() }}</span>
            </div>
            <div class="card-body p-0">
                @forelse($onAssessment as $visit)
                    @php $queueEntry = $visit->queueEntries->first(); @endphp
                    <div class="d-flex align-items-center px-3 py-2 border-bottom hover-bg-light">
                        <div class="flex-shrink-0 text-center me-3">
                            @if($queueEntry)
                                <span class="badge bg-info fs-6">#{{ $queueEntry->queue_number }}</span>
                                <div class="text-muted" style="font-size:0.7rem;">Queue</div>
                            @else
                                <span class="badge bg-light text-muted">-</span>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $visit->patient->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $visit->patient->patient_number }}
                                &bull; {{ $visit->visit_number }}
                                &bull; <x-status-badge :status="$visit->priority" class="py-0" />
                                &bull; <x-status-badge :status="$visit->status" class="py-0" />
                            </div>
                            @if($visit->chief_complaint)
                                <div class="text-muted" style="font-size:0.78rem;">{{ Str::limit($visit->chief_complaint, 60) }}</div>
                            @endif
                        </div>
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info btn-sm ms-2">
                            <i class="ti ti-arrow-right me-1"></i>Continue
                        </a>
                    </div>
                @empty
                    <div class="text-center text-muted py-2 small">
                        <i class="ti ti-circle-check fs-3 d-block mb-1 text-success"></i>
                        No patients currently on assessment
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
