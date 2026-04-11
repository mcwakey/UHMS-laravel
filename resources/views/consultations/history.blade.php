@extends('layouts.app')
@section('title', 'Medical History - ' . $visit->patient->full_name)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Medical History</h4>
        <small class="text-muted">{{ $visit->patient->full_name }} ({{ $visit->patient->patient_number }})</small>
    </div>
    <div>
        <a href="{{ route('admin.consultations.show', $visit) }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Consultation
        </a>
    </div>
</div>

<!-- Patient Summary -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row">
            <div class="col-md-3"><small class="text-muted">Age:</small> <span class="fw-medium">{{ $visit->patient->age }}y</span></div>
            <div class="col-md-3"><small class="text-muted">Gender:</small> <span class="fw-medium">{{ $visit->patient->gender->value }}</span></div>
            <div class="col-md-3"><small class="text-muted">Blood Group:</small> <span class="fw-medium">{{ $visit->patient->blood_group?->value ?? 'N/A' }}</span></div>
            <div class="col-md-3"><small class="text-muted">NHIS:</small> <span class="fw-medium">{{ $visit->patient->is_nhis_active ? 'Active' : 'Inactive' }}</span></div>
        </div>
    </div>
</div>

@if($visit->patient->allergies)
<div class="alert alert-danger py-2 mb-3">
    <i class="ti ti-alert-triangle me-1"></i><strong>Allergies:</strong> {{ $visit->patient->allergies }}
</div>
@endif

@if($visit->patient->chronic_conditions)
<div class="alert alert-warning py-2 mb-3">
    <i class="ti ti-heart-rate-monitor me-1"></i><strong>Chronic Conditions:</strong> {{ $visit->patient->chronic_conditions }}
</div>
@endif

<!-- Medical Records -->
@if(count($history['records']) > 0)
    @foreach($history['records'] as $record)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0">{{ $record->visit->visit_number ?? 'Record' }}</h6>
                <small class="text-muted">{{ $record->created_at->format('d M Y, h:i A') }} &middot; Dr. {{ $record->doctor->full_name ?? 'Unknown' }}</small>
            </div>
            <span class="badge bg-{{ $record->visit?->status->color() ?? 'secondary' }}">{{ $record->visit?->status->label() ?? '—' }}</span>
        </div>
        <div class="card-body">
            <!-- Complaints -->
            @if($record->complaints->count())
            <div class="mb-3">
                <h6 class="small fw-bold text-primary"><i class="ti ti-message-report me-1"></i>Complaints</h6>
                @foreach($record->complaints as $complaint)
                <div class="ms-3 mb-1">
                    <span>{{ $complaint->description }}</span>
                    @if($complaint->duration) <small class="text-muted">({{ $complaint->duration }})</small> @endif
                    @if($complaint->severity) <span class="badge bg-{{ $complaint->severity === 'severe' ? 'danger' : ($complaint->severity === 'moderate' ? 'warning' : 'info') }} badge-sm">{{ ucfirst($complaint->severity) }}</span> @endif
                </div>
                @endforeach
            </div>
            @endif

            <!-- Diagnoses -->
            @if($record->diagnoses->count())
            <div class="mb-3">
                <h6 class="small fw-bold text-success"><i class="ti ti-report-medical me-1"></i>Diagnoses</h6>
                @foreach($record->diagnoses as $diagnosis)
                <div class="ms-3 mb-1">
                    <span>{{ $diagnosis->description }}</span>
                    @if($diagnosis->icd_code) <code class="ms-1">{{ $diagnosis->icd_code }}</code> @endif
                    <span class="badge bg-{{ $diagnosis->type === 'final' ? 'success' : 'warning' }}">{{ ucfirst($diagnosis->type) }}</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Prescriptions -->
            @if($record->prescriptions->count())
            <div class="mb-3">
                <h6 class="small fw-bold text-dark"><i class="ti ti-prescription me-1"></i>Prescriptions</h6>
                @foreach($record->prescriptions as $prescription)
                <div class="ms-3 mb-2">
                    <span class="fw-medium">{{ $prescription->prescription_number }}</span>
                    <span class="badge bg-{{ $prescription->status->color() }}">{{ $prescription->status->label() }}</span>
                    <div class="ms-3">
                        @foreach($prescription->items as $item)
                        <small class="d-block text-muted">{{ $item->drug_name }} — {{ $item->dosage }} {{ $item->frequency }} x {{ $item->duration }}</small>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endforeach
@else
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="ti ti-history fs-1 d-block mb-2"></i>
            <p>No previous medical history found for this patient.</p>
        </div>
    </div>
@endif
@endsection
