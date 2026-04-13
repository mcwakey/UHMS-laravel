@extends('layouts.app')
@section('title', 'Consultation - ' . $visit->visit_number)

@push('styles')
<style>
    .consultation-sidebar .nav-link { padding: 0.5rem 1rem; border-radius: 0.5rem; color: #495057; }
    .consultation-sidebar .nav-link.active { background-color: #e8f0fe; color: #1a73e8; font-weight: 600; }
    .consultation-sidebar .nav-link i { width: 20px; }
    .consultation-sidebar .badge { font-size: 0.65rem; }
    .ehr-item { border-left: 3px solid #dee2e6; padding-left: 1rem; margin-bottom: 1rem; }
    .ehr-item:hover { border-left-color: #0d6efd; }
    .severity-mild { border-left-color: #ffc107; }
    .severity-moderate { border-left-color: #fd7e14; }
    .severity-severe { border-left-color: #dc3545; }
</style>
@endpush

@section('content')
<!-- Patient Header Bar -->
<div class="card mb-3 border-primary">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                        <span class="text-primary fw-bold">{{ strtoupper(substr($visit->patient->first_name, 0, 1) . substr($visit->patient->last_name, 0, 1)) }}</span>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold">{{ $visit->patient->full_name }}</h5>
                        <small class="text-muted">
                            {{ $visit->patient->patient_number }} &middot;
                            {{ $visit->patient->age }}y &middot;
                            {{ $visit->patient->gender->value }} &middot;
                            Blood: {{ $visit->patient->blood_group?->value ?? 'N/A' }}
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge bg-{{ $visit->status->color() }} px-3 py-2 fs-14">{{ $visit->status->label() }}</span>
                <span class="badge bg-{{ $visit->priority->color() }} px-2 py-2 ms-1">{{ $visit->priority->label() }}</span>
                <span class="text-muted ms-2 small">{{ $visit->visit_number }}</span>
            </div>
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

<div class="row">
    <!-- LEFT SIDEBAR -->
    <div class="col-lg-3">
        <!-- Navigation -->
        <div class="card mb-3">
            <div class="card-body p-2">
                <nav class="consultation-sidebar">
                    <ul class="nav flex-column gap-1">
                        <li class="nav-item">
                            <a class="nav-link active" href="#vitals-section" data-bs-toggle="pill">
                                <i class="ti ti-heartbeat me-2"></i>Vitals
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $vitals->count() }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#complaints-section" data-bs-toggle="pill">
                                <i class="ti ti-message-report me-2"></i>Complaints
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $record?->complaints?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#diagnoses-section" data-bs-toggle="pill">
                                <i class="ti ti-report-medical me-2"></i>Diagnoses
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $record?->diagnoses?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#investigations-section" data-bs-toggle="pill">
                                <i class="ti ti-test-pipe me-2"></i>Investigations
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $record?->investigations?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#lab-section" data-bs-toggle="pill">
                                <i class="ti ti-flask me-2"></i>Lab Requests
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $labRequests->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#treatments-section" data-bs-toggle="pill">
                                <i class="ti ti-vaccine me-2"></i>Treatments
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $record?->treatments?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#prescriptions-section" data-bs-toggle="pill">
                                <i class="ti ti-prescription me-2"></i>Prescriptions
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $record?->prescriptions?->count() ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#history-section" data-bs-toggle="pill">
                                <i class="ti ti-history me-2"></i>History
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $history['total'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#patterns-section" data-bs-toggle="pill">
                                <i class="ti ti-template me-2"></i>Patterns
                                <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $patterns->count() }}</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header py-2">
                <h6 class="fw-bold mb-0 small">Quick Actions</h6>
            </div>
            <div class="card-body p-2">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="ti ti-eye me-1"></i>View Visit
                    </a>
                    <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-outline-info btn-sm">
                        <i class="ti ti-history me-1"></i>Full History
                    </a>
                    @can('consultations.create')
                    <button type="button" class="btn btn-outline-purple btn-sm" data-bs-toggle="modal" data-bs-target="#savePatternModal">
                        <i class="ti ti-template me-1"></i>Save as Pattern
                    </button>
                    @endcan
                    @if($visit->status->allowedTransitions())
                    <hr class="my-1">
                    <small class="text-muted fw-bold px-1">Transition Visit</small>
                    @foreach($visit->status->allowedTransitions() as $nextStatus)
                        <form method="POST" action="{{ route('admin.consultations.transition', $visit) }}" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $nextStatus->value }}">
                            <button type="submit" class="btn btn-{{ $nextStatus->color() }} btn-sm w-100"
                                    onclick="return confirm('Move to {{ $nextStatus->label() }}?')">
                                <i class="ti ti-arrow-right me-1"></i>{{ $nextStatus->label() }}
                            </button>
                        </form>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="col-lg-9">
        <div class="tab-content">

            {{-- ============================================================ --}}
            {{-- VITALS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade show active" id="vitals-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-heartbeat me-1"></i>Vitals</h6>
                        @can('vitals.create')
                        <a href="{{ route('admin.vitals.create', ['visit_id' => $visit->id]) }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i>Record Vitals
                        </a>
                        @endcan
                    </div>
                    <div class="card-body">
                        @if($vitals->count() > 0)
                            @php $latest = $vitals->first(); @endphp
                            <!-- Latest Vitals Summary -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">Blood Pressure</small>
                                        <span class="fw-bold fs-6">{{ $latest->blood_pressure ?? '—' }}</span>
                                        <small class="text-muted d-block">mmHg</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">Heart Rate</small>
                                        <span class="fw-bold fs-6">{{ $latest->heart_rate ?? '—' }}</span>
                                        <small class="text-muted d-block">bpm</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">Temperature</small>
                                        <span class="fw-bold fs-6">{{ $latest->temperature ?? '—' }}</span>
                                        <small class="text-muted d-block">°C</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">SpO2</small>
                                        <span class="fw-bold fs-6">{{ $latest->spo2 ?? '—' }}</span>
                                        <small class="text-muted d-block">%</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">Resp. Rate</small>
                                        <span class="fw-bold fs-6">{{ $latest->respiratory_rate ?? '—' }}</span>
                                        <small class="text-muted d-block">/min</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">Weight</small>
                                        <span class="fw-bold fs-6">{{ $latest->weight ?? '—' }}</span>
                                        <small class="text-muted d-block">kg</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">Height</small>
                                        <span class="fw-bold fs-6">{{ $latest->height ?? '—' }}</span>
                                        <small class="text-muted d-block">cm</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="border rounded p-2 text-center">
                                        <small class="text-muted d-block">BMI</small>
                                        <span class="fw-bold fs-6">{{ $latest->bmi ?? '—' }}</span>
                                        <small class="text-muted d-block">kg/m²</small>
                                    </div>
                                </div>
                            </div>
                            @if($latest->blood_sugar)
                            <div class="mb-3">
                                <small class="text-muted">Blood Sugar:</small> <span class="fw-medium">{{ $latest->blood_sugar }} mmol/L</span>
                            </div>
                            @endif
                            @if($latest->notes)
                            <div class="bg-light rounded p-2 mb-3">
                                <small class="text-muted d-block">Notes:</small>
                                {{ $latest->notes }}
                            </div>
                            @endif
                            <small class="text-muted">Recorded by {{ $latest->recordedBy?->full_name }} at {{ $latest->recorded_at->format('d M Y, h:i A') }}</small>

                            @if($vitals->count() > 1)
                            <hr>
                            <h6 class="small fw-bold text-muted">Previous Readings</h6>
                            @foreach($vitals->skip(1) as $v)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <small>BP: {{ $v->blood_pressure ?? '—' }} | HR: {{ $v->heart_rate ?? '—' }} | T: {{ $v->temperature ?? '—' }}°C | SpO2: {{ $v->spo2 ?? '—' }}%</small>
                                </div>
                                <small class="text-muted">{{ $v->recorded_at->format('d M, h:i A') }}</small>
                            </div>
                            @endforeach
                            @endif
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-heartbeat fs-1 d-block mb-2"></i>
                                No vitals recorded for this visit yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- COMPLAINTS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="complaints-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-message-report me-1"></i>Complaints</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addComplaintForm">
                            <i class="ti ti-plus me-1"></i>Add Complaint
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        <!-- Add Complaint Form -->
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addComplaintForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.complaints.store', $visit) }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-12">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <textarea name="description" class="form-control" rows="2" required placeholder="Describe the complaint..."></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Duration</label>
                                            <input type="text" name="duration" class="form-control" placeholder="e.g., 3 days, 1 week">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Severity</label>
                                            <select name="severity" class="form-select">
                                                <option value="">-- Select --</option>
                                                <option value="mild">Mild</option>
                                                <option value="moderate">Moderate</option>
                                                <option value="severe">Severe</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <!-- Complaints List -->
                        @if($record && $record->complaints->count() > 0)
                            @foreach($record->complaints as $complaint)
                            <div class="ehr-item severity-{{ $complaint->severity ?? 'mild' }}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">{{ $complaint->description }}</p>
                                        <small class="text-muted">
                                            @if($complaint->duration) Duration: {{ $complaint->duration }} &middot; @endif
                                            @if($complaint->severity) Severity: <span class="badge bg-{{ $complaint->severity === 'severe' ? 'danger' : ($complaint->severity === 'moderate' ? 'warning' : 'info') }}">{{ ucfirst($complaint->severity) }}</span> @endif
                                        </small>
                                    </div>
                                    @can('consultations.create')
                                    <form method="POST" action="{{ route('admin.consultations.complaints.destroy', $complaint) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this complaint?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-message-report fs-1 d-block mb-2"></i>
                                No complaints recorded yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- DIAGNOSES TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="diagnoses-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>Diagnoses</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addDiagnosisForm">
                            <i class="ti ti-plus me-1"></i>Add Diagnosis
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        <!-- Add Diagnosis Form -->
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addDiagnosisForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.diagnoses.store', $visit) }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-12">
                                            <label class="form-label small">ICD-10 Code <small class="text-muted">(Search by code or description)</small></label>
                                            <input type="hidden" name="icd_code_id" id="icd_code_id">
                                            <select id="icd_code_select" class="form-select" style="width:100%">
                                                <option value="">Type to search ICD-10 codes...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <textarea name="description" id="diagnosis_description" class="form-control" rows="2" required placeholder="Diagnosis description..."></textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">ICD-10 Code (Manual)</label>
                                            <input type="text" name="icd_code" id="icd_code_manual" class="form-control" placeholder="e.g., J06.9">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Type</label>
                                            <select name="type" class="form-select">
                                                <option value="provisional">Provisional</option>
                                                <option value="final">Final</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small">Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Additional notes...">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <!-- Diagnoses List -->
                        @if($record && $record->diagnoses->count() > 0)
                            @foreach($record->diagnoses as $diagnosis)
                            <div class="ehr-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">
                                            {{ $diagnosis->description }}
                                            <span class="badge bg-{{ $diagnosis->type === 'final' ? 'success' : 'warning' }}">{{ ucfirst($diagnosis->type) }}</span>
                                        </p>
                                        <small class="text-muted">
                                            @if($diagnosis->icdCodeEntry) ICD-10: <code>{{ $diagnosis->icdCodeEntry->code }}</code> — {{ $diagnosis->icdCodeEntry->description }} &middot;
                                            @elseif($diagnosis->icd_code) ICD-10: <code>{{ $diagnosis->icd_code }}</code> &middot; @endif
                                            @if($diagnosis->notes) {{ $diagnosis->notes }} @endif
                                        </small>
                                    </div>
                                    @can('consultations.create')
                                    <form method="POST" action="{{ route('admin.consultations.diagnoses.destroy', $diagnosis) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this diagnosis?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-report-medical fs-1 d-block mb-2"></i>
                                No diagnoses recorded yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- INVESTIGATIONS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="investigations-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-test-pipe me-1"></i>Investigations</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addInvestigationForm">
                            <i class="ti ti-plus me-1"></i>Add Investigation
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        <!-- Add Investigation Form -->
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addInvestigationForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.investigations.store', $visit) }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Type <span class="text-danger">*</span></label>
                                            <select name="investigation_type" class="form-select" required>
                                                <option value="">-- Select --</option>
                                                <option value="Blood Test">Blood Test</option>
                                                <option value="Urine Test">Urine Test</option>
                                                <option value="X-Ray">X-Ray</option>
                                                <option value="Ultrasound">Ultrasound</option>
                                                <option value="CT Scan">CT Scan</option>
                                                <option value="MRI">MRI</option>
                                                <option value="ECG">ECG</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <textarea name="description" class="form-control" rows="2" required placeholder="Investigation details..."></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Urgency</label>
                                            <select name="urgency" class="form-select">
                                                <option value="routine">Routine</option>
                                                <option value="urgent">Urgent</option>
                                                <option value="emergency">Emergency</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Notes</label>
                                            <input type="text" name="notes" class="form-control" placeholder="Clinical notes...">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <!-- Investigations List -->
                        @if($record && $record->investigations->count() > 0)
                            @foreach($record->investigations as $investigation)
                            <div class="ehr-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">
                                            <span class="badge bg-dark">{{ $investigation->investigation_type }}</span>
                                            {{ $investigation->description }}
                                        </p>
                                        <small class="text-muted">
                                            Urgency: <span class="badge bg-{{ $investigation->urgency === 'emergency' ? 'danger' : ($investigation->urgency === 'urgent' ? 'warning' : 'secondary') }}">{{ ucfirst($investigation->urgency) }}</span>
                                            &middot; Status: <span class="badge bg-{{ $investigation->status === 'completed' ? 'success' : ($investigation->status === 'in_progress' ? 'info' : 'warning') }}">{{ ucfirst(str_replace('_', ' ', $investigation->status)) }}</span>
                                            @if($investigation->notes) &middot; {{ $investigation->notes }} @endif
                                        </small>
                                    </div>
                                    @can('consultations.create')
                                    <form method="POST" action="{{ route('admin.consultations.investigations.destroy', $investigation) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this investigation?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-test-pipe fs-1 d-block mb-2"></i>
                                No investigations requested yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- LAB REQUESTS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="lab-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-flask me-1"></i>Lab Requests</h6>
                        @can('lab.requests.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#sendToLabForm">
                            <i class="ti ti-send me-1"></i>Send to Lab
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        {{-- Send to Lab Form --}}
                        @can('lab.requests.create')
                        <div class="collapse mb-3" id="sendToLabForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.lab-request.store', $visit) }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small">Test Category</label>
                                            <select id="labCategorySelect" class="form-select" onchange="loadTestsByCategory(this.value)">
                                                <option value="">-- Select Category --</option>
                                                @foreach($labCategories as $cat)
                                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small">Urgency</label>
                                            <select name="urgency" class="form-select">
                                                <option value="routine">Routine</option>
                                                <option value="urgent">Urgent</option>
                                                <option value="emergency">Emergency</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Select Tests <span class="text-danger">*</span></label>
                                            <div id="labTestsContainer" class="border rounded p-2" style="min-height: 60px;">
                                                <span class="text-muted small">Select a category first to load tests</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small">Clinical Information</label>
                                            <textarea name="clinical_info" class="form-control" rows="2" placeholder="Relevant clinical notes for the lab..."></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-send me-1"></i>Submit Lab Request</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        {{-- Existing Lab Requests --}}
                        @if($labRequests->count() > 0)
                            @foreach($labRequests as $labReq)
                            <div class="ehr-item mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1">
                                            <a href="{{ route('admin.lab.requests.show', $labReq) }}" class="fw-medium text-primary">{{ $labReq->request_number }}</a>
                                            <span class="badge bg-{{ $labReq->status_color }} ms-1">{{ $labReq->status_label }}</span>
                                            <span class="badge bg-{{ $labReq->urgency_color }} ms-1">{{ ucfirst($labReq->urgency) }}</span>
                                        </p>
                                        <div class="d-flex flex-wrap gap-1 mb-1">
                                            @foreach($labReq->items as $item)
                                                <span class="badge bg-light text-dark border">
                                                    {{ $item->labTest->name }}
                                                    @if($item->result)
                                                        <i class="ti ti-check text-success ms-1"></i>
                                                        @if($item->result->is_abnormal)
                                                            <i class="ti ti-alert-triangle text-danger ms-1"></i>
                                                        @endif
                                                    @endif
                                                </span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">
                                            {{ $labReq->created_at->format('d M Y H:i') }}
                                            &middot; Progress: {{ $labReq->completion_percentage }}%
                                            @if($labReq->requestedBy)
                                                &middot; By: {{ $labReq->requestedBy->name }}
                                            @endif
                                        </small>
                                        {{-- Show results if completed --}}
                                        @if($labReq->status === 'completed')
                                        <div class="mt-2">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                    <tr><th>Test</th><th>Result</th><th>Normal Range</th><th>Status</th></tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($labReq->items as $item)
                                                    @if($item->result)
                                                    <tr class="{{ $item->result->is_abnormal ? 'table-danger' : '' }}">
                                                        <td>{{ $item->labTest->name }}</td>
                                                        <td class="{{ $item->result->is_abnormal ? 'text-danger fw-bold' : '' }}">{{ $item->result->result_value }}</td>
                                                        <td><small>{{ $item->labTest->normal_range ?? '-' }} {{ $item->labTest->unit ?? '' }}</small></td>
                                                        <td>
                                                            @if($item->result->is_verified)
                                                                <span class="badge bg-success">Verified</span>
                                                            @else
                                                                <span class="badge bg-warning">Unverified</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                    </div>
                                    <a href="{{ route('admin.lab.requests.show', $labReq) }}" class="btn btn-sm btn-outline-primary ms-2">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-flask fs-1 d-block mb-2"></i>
                                No lab requests for this visit.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- TREATMENTS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="treatments-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-vaccine me-1"></i>Treatments</h6>
                        @can('consultations.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addTreatmentForm">
                            <i class="ti ti-plus me-1"></i>Add Treatment
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        <!-- Add Treatment Form -->
                        @can('consultations.create')
                        <div class="collapse mb-3" id="addTreatmentForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.treatments.store', $visit) }}">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small">Type <span class="text-danger">*</span></label>
                                            <select name="type" class="form-select" required>
                                                <option value="">-- Select --</option>
                                                <option value="medication">Medication</option>
                                                <option value="procedure">Procedure</option>
                                                <option value="referral">Referral</option>
                                                <option value="advice">Advice</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small">Description <span class="text-danger">*</span></label>
                                            <textarea name="description" class="form-control" rows="2" required placeholder="Treatment details..."></textarea>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-check me-1"></i>Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <!-- Treatments List -->
                        @if($record && $record->treatments->count() > 0)
                            @foreach($record->treatments as $treatment)
                            <div class="ehr-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <p class="mb-1">
                                            <span class="badge bg-{{ $treatment->type === 'medication' ? 'primary' : ($treatment->type === 'procedure' ? 'info' : ($treatment->type === 'referral' ? 'warning' : 'secondary')) }}">{{ ucfirst($treatment->type) }}</span>
                                            {{ $treatment->description }}
                                        </p>
                                    </div>
                                    @can('consultations.create')
                                    <form method="POST" action="{{ route('admin.consultations.treatments.destroy', $treatment) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this treatment?')">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-vaccine fs-1 d-block mb-2"></i>
                                No treatments recorded yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- PRESCRIPTIONS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="prescriptions-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-prescription me-1"></i>Prescriptions</h6>
                        @can('prescriptions.create')
                        <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addPrescriptionForm">
                            <i class="ti ti-plus me-1"></i>New Prescription
                        </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        <!-- Add Prescription Form -->
                        @can('prescriptions.create')
                        <div class="collapse mb-3" id="addPrescriptionForm">
                            <div class="card card-body bg-light">
                                <form method="POST" action="{{ route('admin.consultations.prescriptions.store', $visit) }}" id="prescriptionForm">
                                    @csrf
                                    <div id="prescriptionItems">
                                        <div class="prescription-item border rounded p-2 mb-2">
                                            <div class="row g-2">
                                                <div class="col-md-4">
                                                    <label class="form-label small">Drug Name <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[0][drug_name]" class="form-control form-control-sm" required placeholder="Drug name">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Dosage <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[0][dosage]" class="form-control form-control-sm" required placeholder="e.g., 500mg">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Frequency <span class="text-danger">*</span></label>
                                                    <select name="items[0][frequency]" class="form-select form-select-sm" required>
                                                        <option value="OD">OD (Once daily)</option>
                                                        <option value="BD">BD (Twice daily)</option>
                                                        <option value="TDS" selected>TDS (Three times)</option>
                                                        <option value="QDS">QDS (Four times)</option>
                                                        <option value="STAT">STAT (Immediately)</option>
                                                        <option value="PRN">PRN (As needed)</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Duration <span class="text-danger">*</span></label>
                                                    <input type="text" name="items[0][duration]" class="form-control form-control-sm" required placeholder="e.g., 5 days">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small">Qty <span class="text-danger">*</span></label>
                                                    <input type="number" name="items[0][quantity]" class="form-control form-control-sm" required min="1" value="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small">Route</label>
                                                    <select name="items[0][route]" class="form-select form-select-sm">
                                                        <option value="oral">Oral</option>
                                                        <option value="IV">IV</option>
                                                        <option value="IM">IM</option>
                                                        <option value="SC">SC</option>
                                                        <option value="topical">Topical</option>
                                                        <option value="rectal">Rectal</option>
                                                        <option value="sublingual">Sublingual</option>
                                                        <option value="inhaled">Inhaled</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-9">
                                                    <label class="form-label small">Instructions</label>
                                                    <input type="text" name="items[0][instructions]" class="form-control form-control-sm" placeholder="Special instructions...">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addItemBtn">
                                            <i class="ti ti-plus me-1"></i>Add Drug
                                        </button>
                                        <div>
                                            <label class="form-label small d-block">Rx Notes</label>
                                            <input type="text" name="notes" class="form-control form-control-sm d-inline-block" style="width: 250px" placeholder="Prescription notes...">
                                            <button type="submit" class="btn btn-primary btn-sm ms-2">
                                                <i class="ti ti-check me-1"></i>Create Prescription
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endcan

                        <!-- Prescriptions List -->
                        @if($record && $record->prescriptions->count() > 0)
                            @foreach($record->prescriptions as $prescription)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="fw-bold">{{ $prescription->prescription_number }}</span>
                                        <span class="badge bg-{{ $prescription->status->color() }} ms-2">{{ $prescription->status->label() }}</span>
                                    </div>
                                    <small class="text-muted">{{ $prescription->created_at->format('d M Y, h:i A') }}</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless mb-0">
                                        <thead>
                                            <tr class="text-muted small">
                                                <th>Drug</th>
                                                <th>Dosage</th>
                                                <th>Frequency</th>
                                                <th>Duration</th>
                                                <th>Qty</th>
                                                <th>Route</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($prescription->items as $item)
                                            <tr>
                                                <td class="fw-medium">{{ $item->drug_name }}</td>
                                                <td>{{ $item->dosage }}</td>
                                                <td>{{ $item->frequency }}</td>
                                                <td>{{ $item->duration }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ $item->route }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($prescription->notes)
                                <small class="text-muted mt-1 d-block">Notes: {{ $prescription->notes }}</small>
                                @endif
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-prescription fs-1 d-block mb-2"></i>
                                No prescriptions created yet.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- HISTORY TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="history-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-history me-1"></i>Medical History</h6>
                        <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-sm btn-outline-info">
                            <i class="ti ti-external-link me-1"></i>Full History
                        </a>
                    </div>
                    <div class="card-body">
                        @if(count($history['records']) > 0)
                            @foreach($history['records'] as $pastRecord)
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <span class="fw-bold">{{ $pastRecord->visit->visit_number ?? 'Unknown' }}</span>
                                        <small class="text-muted ms-2">{{ $pastRecord->created_at->format('d M Y') }}</small>
                                    </div>
                                </div>
                                @if($pastRecord->complaints->count())
                                <div class="mb-2">
                                    <small class="text-muted fw-bold">Complaints:</small>
                                    @foreach($pastRecord->complaints as $c)
                                        <span class="badge bg-light text-dark">{{ Str::limit($c->description, 50) }}</span>
                                    @endforeach
                                </div>
                                @endif
                                @if($pastRecord->diagnoses->count())
                                <div class="mb-2">
                                    <small class="text-muted fw-bold">Diagnoses:</small>
                                    @foreach($pastRecord->diagnoses as $d)
                                        <span class="badge bg-info-subtle text-info">{{ Str::limit($d->description, 50) }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                            @if($history['total'] > 10)
                            <div class="text-center">
                                <a href="{{ route('admin.consultations.history', $visit) }}" class="btn btn-outline-primary btn-sm">
                                    View All {{ $history['total'] }} Records
                                </a>
                            </div>
                            @endif
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-history fs-1 d-block mb-2"></i>
                                No previous medical history found.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ============================================================ --}}
            {{-- PATTERNS TAB --}}
            {{-- ============================================================ --}}
            <div class="tab-pane fade" id="patterns-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0"><i class="ti ti-template me-1"></i>Medical Patterns</h6>
                        <a href="{{ route('admin.patterns.create') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-plus me-1"></i>Create Pattern
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Pattern Search -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-search"></i></span>
                                <input type="text" id="patternSearchInput" class="form-control" placeholder="Type a complaint to find matching patterns..." minlength="3">
                                <button type="button" class="btn btn-primary" id="patternSearchBtn">
                                    <i class="ti ti-search me-1"></i>Search
                                </button>
                            </div>
                            <small class="text-muted">Enter at least 3 characters to search for patterns by complaint or name.</small>
                        </div>

                        <!-- Search Results -->
                        <div id="patternSearchResults" class="mb-3" style="display:none;"></div>

                        <!-- Frequent Patterns -->
                        <h6 class="fw-bold small text-muted mb-2"><i class="ti ti-flame me-1"></i>Frequently Used</h6>
                        @if($patterns->count() > 0)
                            @foreach($patterns as $pattern)
                            <div class="border rounded p-3 mb-2 pattern-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $pattern->name }}</h6>
                                        <div class="mb-1">
                                            @foreach($pattern->items->groupBy('type') as $type => $items)
                                                <span class="badge bg-{{ $items->first()->getTypeColor() }}-subtle text-{{ $items->first()->getTypeColor() }} me-1">
                                                    {{ $items->count() }} {{ $items->first()->getTypeLabel() }}{{ $items->count() > 1 ? 's' : '' }}
                                                </span>
                                            @endforeach
                                        </div>
                                        <small class="text-muted">
                                            @if($pattern->is_system) <span class="badge bg-primary-subtle text-primary">System</span> @else Personal @endif
                                            &middot; Used {{ $pattern->usage_count }} times
                                        </small>
                                    </div>
                                    @can('consultations.create')
                                    <button type="button" class="btn btn-sm btn-success apply-pattern-btn"
                                            data-pattern-id="{{ $pattern->id }}" data-pattern-name="{{ $pattern->name }}">
                                        <i class="ti ti-check me-1"></i>Apply
                                    </button>
                                    @endcan
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-template fs-1 d-block mb-2"></i>
                                No patterns available yet.
                                <br><a href="{{ route('admin.patterns.create') }}">Create your first pattern</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Save as Pattern Modal -->
@can('consultations.create')
<div class="modal fade" id="savePatternModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.patterns.from-record', $visit) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-template me-2"></i>Save as Pattern</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Save the current consultation data (complaints, diagnoses, treatments, prescriptions) as a reusable pattern.</p>
                    <div class="mb-3">
                        <label class="form-label">Pattern Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g., Common Cold, Malaria Uncomplicated">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scope</label>
                        <select name="scope" class="form-select">
                            <option value="personal">Personal (Only me)</option>
                            <option value="system">System-Wide (All doctors)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Save Pattern</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
    // Dynamic prescription item adding
    let itemIndex = 1;
    document.getElementById('addItemBtn')?.addEventListener('click', function() {
        const container = document.getElementById('prescriptionItems');
        const template = container.querySelector('.prescription-item').cloneNode(true);

        // Update all input names
        template.querySelectorAll('[name]').forEach(function(input) {
            input.name = input.name.replace(/items\[\d+\]/, 'items[' + itemIndex + ']');
            if (input.tagName === 'INPUT') input.value = input.type === 'number' ? '1' : '';
        });

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-1';
        removeBtn.innerHTML = '<i class="ti ti-x"></i>';
        removeBtn.onclick = function() { template.remove(); };
        template.style.position = 'relative';
        template.appendChild(removeBtn);

        container.appendChild(template);
        itemIndex++;
    });

    // Pattern search
    var searchBtn = document.getElementById('patternSearchBtn');
    var searchInput = document.getElementById('patternSearchInput');
    var searchResults = document.getElementById('patternSearchResults');

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text || ''));
        return div.innerHTML;
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            var query = searchInput.value.trim();
            if (query.length < 3) {
                searchResults.innerHTML = '<div class="alert alert-warning py-2">Please enter at least 3 characters.</div>';
                searchResults.style.display = 'block';
                return;
            }

            searchResults.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Searching...</div>';
            searchResults.style.display = 'block';

            fetch('{{ route("admin.patterns.suggest") }}?query=' + encodeURIComponent(query), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.patterns && data.patterns.length > 0) {
                    var html = '<h6 class="fw-bold small text-muted mb-2"><i class="ti ti-sparkles me-1"></i>Suggestions</h6>';
                    data.patterns.forEach(function(p) {
                        html += '<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-center">';
                        html += '<div><strong>' + escapeHtml(p.name) + '</strong> <small class="text-muted">(' + p.items.length + ' items, used ' + p.usage_count + 'x)</small></div>';
                        html += '<button type="button" class="btn btn-sm btn-success apply-pattern-btn" data-pattern-id="' + p.id + '" data-pattern-name="' + escapeHtml(p.name) + '"><i class="ti ti-check me-1"></i>Apply</button>';
                        html += '</div>';
                    });
                    searchResults.innerHTML = html;
                    bindApplyButtons();
                } else {
                    searchResults.innerHTML = '<div class="alert alert-info py-2 mb-0">No matching patterns found.</div>';
                }
            })
            .catch(function() {
                searchResults.innerHTML = '<div class="alert alert-danger py-2 mb-0">Search failed. Please try again.</div>';
            });
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); searchBtn.click(); }
        });
    }

    // Apply pattern
    function bindApplyButtons() {
        document.querySelectorAll('.apply-pattern-btn').forEach(function(btn) {
            btn.removeEventListener('click', applyPattern);
            btn.addEventListener('click', applyPattern);
        });
    }

    function applyPattern() {
        var patternId = this.dataset.patternId;
        var patternName = this.dataset.patternName;
        var btn = this;

        if (!confirm('Apply pattern "' + patternName + '"? This will add all items from the pattern to this consultation.')) return;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch('{{ url("admin/patterns") }}/' + patternId + '/apply', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ visit_id: {{ $visit->id }} })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // Reload to show applied items
                window.location.reload();
            } else {
                alert('Failed to apply pattern.');
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-check me-1"></i>Apply';
            }
        })
        .catch(function() {
            alert('Failed to apply pattern. Please try again.');
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-check me-1"></i>Apply';
        });
    }

    bindApplyButtons();

    // Lab test category → test loading
    function loadTestsByCategory(categoryId) {
        var container = document.getElementById('labTestsContainer');
        if (!categoryId) {
            container.innerHTML = '<span class="text-muted small">Select a category first to load tests</span>';
            return;
        }
        container.innerHTML = '<div class="text-center py-2"><div class="spinner-border spinner-border-sm text-primary"></div> Loading tests...</div>';

        fetch('{{ url("admin/lab/tests/category") }}/' + categoryId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(tests) {
            if (tests.length === 0) {
                container.innerHTML = '<span class="text-muted small">No active tests in this category</span>';
                return;
            }
            var html = '<div class="row g-1">';
            tests.forEach(function(test) {
                html += '<div class="col-md-6"><div class="form-check">';
                html += '<input type="checkbox" name="test_ids[]" value="' + test.id + '" class="form-check-input" id="labTest' + test.id + '">';
                html += '<label class="form-check-label" for="labTest' + test.id + '">';
                html += escapeHtml(test.name) + ' <small class="text-muted">(' + escapeHtml(test.code) + ')';
                if (test.price) html += ' - GH₵' + parseFloat(test.price).toFixed(2);
                html += '</small></label></div></div>';
            });
            html += '</div>';
            container.innerHTML = html;
        })
        .catch(function() {
            container.innerHTML = '<span class="text-danger small">Failed to load tests. Try again.</span>';
        });
    }

    // ICD-10 Code Autocomplete (Select2 AJAX)
    $(document).ready(function() {
        if ($('#icd_code_select').length && $.fn.select2) {
            $('#icd_code_select').select2({
                placeholder: 'Type to search ICD-10 codes...',
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: '{{ route("admin.icd-search") }}',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return { q: params.term };
                    },
                    processResults: function(data) {
                        return { results: data.results };
                    },
                    cache: true
                },
                templateResult: function(item) {
                    if (item.loading) return item.text;
                    return $('<span>').html('<strong>' + escapeHtml(item.code) + '</strong> — ' + escapeHtml(item.description));
                },
                templateSelection: function(item) {
                    return item.text || item.code;
                }
            }).on('select2:select', function(e) {
                var data = e.params.data;
                // Set the hidden icd_code_id
                $('#icd_code_id').val(data.id);
                // Auto-fill the manual code field
                $('#icd_code_manual').val(data.code);
                // Auto-fill description if empty
                var descField = $('#diagnosis_description');
                if (!descField.val().trim()) {
                    descField.val(data.description);
                }
            }).on('select2:clear', function() {
                $('#icd_code_id').val('');
                $('#icd_code_manual').val('');
            });
        }
    });
</script>
@endpush
