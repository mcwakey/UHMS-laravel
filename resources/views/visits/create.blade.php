@extends('layouts.app')
@section('title', 'Create Visit')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Create New Visit</h4>
    </div>
    <div>
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.visits.store') }}" id="visitForm">
    @csrf

    <div class="row">
        <!-- Left Column — Patient Selection & Visit Details -->
        <div class="col-lg-8">
            <!-- Patient Search -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-search me-1"></i>Select Patient</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Search Patient <span class="text-danger">*</span></label>
                        <input type="text" id="patientSearch" class="form-control form-control-lg @error('patient_id') is-invalid @enderror"
                               placeholder="Type patient name, ID, phone, or Ghana Card number..."
                               value="{{ $selectedPatient ? $selectedPatient->patient_number . ' — ' . $selectedPatient->full_name : '' }}"
                               autocomplete="off">
                        <input type="hidden" name="patient_id" id="patientId" value="{{ $selectedPatient?->id ?? old('patient_id') }}">
                        @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="patientResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 999; max-height: 300px; overflow-y: auto;"></div>
                    </div>

                    <!-- Selected Patient Info Card -->
                    <div id="patientInfo" class="{{ $selectedPatient ? '' : 'd-none' }}">
                        <div class="alert alert-light border d-flex align-items-center gap-3">
                            <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center">
                                <span id="patientInitial">{{ $selectedPatient ? strtoupper(substr($selectedPatient->first_name, 0, 1)) : '' }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0" id="patientName">{{ $selectedPatient?->full_name }}</h6>
                                <small class="text-muted">
                                    <span id="patientNumber">{{ $selectedPatient?->patient_number }}</span>
                                    &bull; <span id="patientPhone">{{ $selectedPatient?->phone }}</span>
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPatient()">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Details -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>Visit Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Visit Type <span class="text-danger">*</span></label>
                            <select name="visit_type" class="form-select @error('visit_type') is-invalid @enderror" required>
                                <option value="">Select Type</option>
                                @foreach(\App\Enums\VisitType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ old('visit_type') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                                @endforeach
                            </select>
                            @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Priority <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach(\App\Enums\Priority::cases() as $priority)
                                    <option value="{{ $priority->value }}" {{ old('priority', 'normal') == $priority->value ? 'selected' : '' }}>{{ $priority->label() }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Visit Date</label>
                            <input type="date" name="visit_date" class="form-control @error('visit_date') is-invalid @enderror" value="{{ old('visit_date', date('Y-m-d')) }}" id="visitDate">
                            @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted" id="schedulingHint">Today = walk-in. Future date = scheduled visit.</small>
                        </div>
                    </div>

                    {{-- Scheduling Fields (shown when future date selected) --}}
                    <div class="row" id="schedulingFields" style="display: none;">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time') }}">
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}">
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Consultation Mode</label>
                            <select name="consultation_mode" class="form-select @error('consultation_mode') is-invalid @enderror">
                                @foreach(\App\Enums\ConsultationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" {{ old('consultation_mode', 'in_person') == $mode->value ? 'selected' : '' }}>{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                            @error('consultation_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    {{-- Insurance Selection --}}
                    <div class="mb-3" id="insuranceSelection" style="display: none;">
                        <label class="form-label">Visit Insurance</label>
                        <select name="visit_insurance_id" class="form-select @error('visit_insurance_id') is-invalid @enderror" id="visitInsuranceSelect">
                            <option value="">Cash & Carry (Default)</option>
                        </select>
                        @error('visit_insurance_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Select which insurance to use for this visit.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chief Complaint</label>
                        <textarea name="chief_complaint" class="form-control @error('chief_complaint') is-invalid @enderror" rows="3" placeholder="Primary reason for visit...">{{ old('chief_complaint') }}</textarea>
                        @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column — Assignment -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Patient will be added to this department's queue</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign Doctor</label>
                        <select name="assigned_doctor_id" class="form-select @error('assigned_doctor_id') is-invalid @enderror">
                            <option value="">Select Doctor (optional)</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ old('assigned_doctor_id') == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->full_name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <!-- Quick Info -->
            <div class="card bg-light" id="walkInInfo">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="ti ti-info-circle me-1"></i>What Happens Next</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i>Visit is registered</li>
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i>Patient moves to <strong>Waiting</strong></li>
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i>Queue number assigned</li>
                        <li><i class="ti ti-check text-success me-1"></i>Appears on Queue Board</li>
                    </ul>
                </div>
            </div>
            <div class="card bg-light d-none" id="scheduledInfo">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="ti ti-calendar-event me-1 text-primary"></i>Scheduling a Future Visit</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>Visit is <strong>Scheduled</strong></li>
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>Patient will be notified</li>
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>Check-in on visit day</li>
                        <li><i class="ti ti-check text-primary me-1"></i>Auto-transitions to Waiting on check-in</li>
                    </ul>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="ti ti-plus me-1"></i><span id="submitBtnText">Create Visit</span>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patientSearch');
    const resultsDiv = document.getElementById('patientResults');
    const patientIdInput = document.getElementById('patientId');
    const patientInfo = document.getElementById('patientInfo');
    const visitDateInput = document.getElementById('visitDate');
    const schedulingFields = document.getElementById('schedulingFields');
    const insuranceSelection = document.getElementById('insuranceSelection');
    const walkInInfo = document.getElementById('walkInInfo');
    const scheduledInfo = document.getElementById('scheduledInfo');
    const submitBtnText = document.getElementById('submitBtnText');
    let debounceTimer;

    // Scheduling toggle based on date
    function checkScheduling() {
        const today = new Date().toISOString().split('T')[0];
        const selectedDate = visitDateInput.value;
        const isFuture = selectedDate > today;

        schedulingFields.style.display = isFuture ? '' : 'none';
        walkInInfo.classList.toggle('d-none', isFuture);
        scheduledInfo.classList.toggle('d-none', !isFuture);
        submitBtnText.textContent = isFuture ? 'Schedule Visit' : 'Create Visit';
    }

    visitDateInput.addEventListener('change', checkScheduling);
    checkScheduling();

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();

        if (query.length < 2) {
            resultsDiv.classList.add('d-none');
            return;
        }

        debounceTimer = setTimeout(function() {
            fetch('{{ route("admin.visits.patient-search") }}?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">No patients found</div>';
                    } else {
                        data.forEach(function(patient) {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = '<div class="fw-medium">' + patient.full_name + '</div>' +
                                '<small class="text-muted">' + patient.patient_number + ' &bull; ' + (patient.phone || 'No phone') + '</small>';
                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                selectPatient(patient);
                            });
                            resultsDiv.appendChild(item);
                        });
                    }
                    resultsDiv.classList.remove('d-none');
                });
        }, 300);
    });

    searchInput.addEventListener('blur', function() {
        setTimeout(function() { resultsDiv.classList.add('d-none'); }, 200);
    });

    window.selectPatient = function(patient) {
        patientIdInput.value = patient.id;
        searchInput.value = patient.patient_number + ' — ' + patient.full_name;
        document.getElementById('patientInitial').textContent = patient.full_name.charAt(0).toUpperCase();
        document.getElementById('patientName').textContent = patient.full_name;
        document.getElementById('patientNumber').textContent = patient.patient_number;
        document.getElementById('patientPhone').textContent = patient.phone || 'No phone';
        patientInfo.classList.remove('d-none');
        resultsDiv.classList.add('d-none');

        // Load patient insurances
        loadPatientInsurances(patient.id);
    };

    window.clearPatient = function() {
        patientIdInput.value = '';
        searchInput.value = '';
        patientInfo.classList.add('d-none');
        insuranceSelection.style.display = 'none';
        document.getElementById('visitInsuranceSelect').innerHTML = '<option value="">Cash & Carry (Default)</option>';
    };

    function loadPatientInsurances(patientId) {
        // We show the insurance selection box; options populated from patient data
        insuranceSelection.style.display = '';
        const select = document.getElementById('visitInsuranceSelect');
        select.innerHTML = '<option value="">Cash & Carry (Default)</option><option disabled>Loading...</option>';

        fetch('{{ url("admin/patients") }}/' + patientId + '?format=insurances', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Cash & Carry (Default)</option>';
            if (data.insurances && data.insurances.length > 0) {
                data.insurances.forEach(function(ins) {
                    const opt = document.createElement('option');
                    opt.value = ins.id;
                    opt.textContent = ins.provider_name + (ins.membership_number ? ' (' + ins.membership_number + ')' : '') + (ins.is_expired ? ' [EXPIRED]' : '');
                    if (ins.is_expired) opt.disabled = true;
                    if (ins.is_primary) opt.selected = true;
                    select.appendChild(opt);
                });
            }
        })
        .catch(() => {
            select.innerHTML = '<option value="">Cash & Carry (Default)</option>';
        });
    }
});
</script>
@endpush
