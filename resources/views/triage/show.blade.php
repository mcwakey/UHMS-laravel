@extends('layouts.app')
@section('title', 'Triage Summary — ' . $visit->visit_number)

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2 text-info"></i>Triage Summary</h4>
        <small class="text-muted">{{ $visit->patient->full_name }} &bull; {{ $visit->visit_number }}</small>
    </div>
    <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-arrow-left me-1"></i>Back to Visit
    </a>
</div>

@if($visit->triage)
    @php $triage = $visit->triage; @endphp
    <div class="row">
        <div class="col-lg-8">
            <!-- Score Banner -->
            @if($triage->triage_score)
                <div class="alert alert-{{ $triage->triage_score->color() }} d-flex align-items-center gap-3 mb-3">
                    <i class="ti ti-{{ $triage->triage_score->icon() }} fs-2"></i>
                    <div>
                        <strong class="d-block">{{ $triage->triage_score->label() }}</strong>
                        <span class="small">Triage assessment completed by {{ $triage->triagedBy?->full_name ?? 'System' }}
                            on {{ $triage->triaged_at?->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h6 class="fw-bold mb-0"><i class="ti ti-heart-rate-monitor me-1"></i>Recorded Vitals</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small mb-1">Blood Pressure</div>
                                <div class="fw-bold fs-5">{{ $triage->blood_pressure ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">mmHg</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small mb-1">Heart Rate</div>
                                <div class="fw-bold fs-5">{{ $triage->heart_rate ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">bpm</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small mb-1">Temperature</div>
                                <div class="fw-bold fs-5">{{ $triage->temperature ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">°C</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small mb-1">Respiratory Rate</div>
                                <div class="fw-bold fs-5">{{ $triage->respiratory_rate ?? '—' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">/min</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small mb-1">SpO₂</div>
                                <div class="fw-bold fs-5">{{ $triage->spo2 ? $triage->spo2 . '%' : '—' }}</div>
                                <div class="text-muted" style="font-size:0.75rem">Oxygen Sat.</div>
                            </div>
                        </div>
                        @if($triage->bmi)
                        <div class="col-6 col-md-4">
                            <div class="p-3 rounded bg-light text-center">
                                <div class="text-muted small mb-1">BMI</div>
                                <div class="fw-bold fs-5">{{ $triage->bmi }}</div>
                                <div class="text-muted" style="font-size:0.75rem">kg/m²</div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @if($triage->notes)
                        <div class="mt-3">
                            <label class="text-muted small">Notes</label>
                            <p class="mb-0">{{ $triage->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            @if($triage->department)
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-building-hospital text-primary"></i>
                            <div>
                                <div class="fw-semibold">Assigned to: {{ $triage->department->name }}</div>
                                <div class="text-muted small">Consultation department selected during triage</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <!-- Re-triage option if still in TRIAGE status -->
            @if($visit->status === \App\Enums\VisitStatus::TRIAGE)
                <div class="card mb-3">
                    <div class="card-body">
                        <a href="{{ route('admin.triage.create', $visit) }}" class="btn btn-info w-100">
                            <i class="ti ti-pencil me-1"></i>Re-assess Triage
                        </a>
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header">
                    <h6 class="fw-bold mb-0">Visit Status</h6>
                </div>
                <div class="card-body text-center">
                    <x-status-badge :status="$visit->status" class="fs-6 px-3 py-2" />
                    @if($visit->currentDepartment)
                        <div class="text-muted small mt-2">at {{ $visit->currentDepartment->name }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-info">
        <i class="ti ti-info-circle me-1"></i>No triage record found for this visit.
        @if($visit->status === \App\Enums\VisitStatus::TRIAGE)
            <a href="{{ route('admin.triage.create', $visit) }}" class="alert-link">Start Triage</a>
        @endif
    </div>
@endif
@endsection
