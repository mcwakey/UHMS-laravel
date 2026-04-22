@extends('layouts.app')
@section('title', 'Triage Queue')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
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

<div class="row">
    <!-- In Triage -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-info rounded-pill">{{ $visits->count() }}</span>
                <h6 class="fw-bold mb-0">Awaiting Triage</h6>
            </div>
            <div class="card-body p-0">
                @forelse($visits as $visit)
                    <div class="d-flex align-items-center px-3 py-2 border-bottom hover-bg-light">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $visit->patient->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $visit->patient->patient_number }}
                                &bull; {{ $visit->visit_number }}
                                &bull; <span class="badge bg-{{ $visit->priority->color() }} py-0">{{ $visit->priority->label() }}</span>
                            </div>
                            @if($visit->chief_complaint)
                                <div class="text-muted" style="font-size:0.78rem;">{{ Str::limit($visit->chief_complaint, 60) }}</div>
                            @endif
                        </div>
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info btn-sm ms-2">
                            <i class="ti ti-stethoscope me-1"></i>Triage
                        </a>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">
                        <i class="ti ti-circle-check fs-3 d-block mb-1 text-success"></i>
                        No patients awaiting triage
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Waiting Consultation (recently triaged) -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-pill">{{ $waitingConsultation->count() }}</span>
                <h6 class="fw-bold mb-0">Waiting Consultation</h6>
            </div>
            <div class="card-body p-0">
                @forelse($waitingConsultation as $visit)
                    <div class="d-flex align-items-center px-3 py-2 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $visit->patient->full_name }}</div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                {{ $visit->visit_number }}
                                &bull; {{ $visit->currentDepartment?->name ?? '—' }}
                                @if($visit->triage_score)
                                    &bull; <span class="badge bg-{{ $visit->triage_score->color() }} py-0">{{ $visit->triage_score->label() }}</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-outline-primary btn-sm ms-2">
                            <i class="ti ti-eye me-1"></i>View
                        </a>
                    </div>
                @empty
                    <div class="text-center text-muted py-4 small">
                        <i class="ti ti-hourglass-empty fs-3 d-block mb-1"></i>
                        No patients waiting for consultation
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
