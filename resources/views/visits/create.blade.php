@extends('layouts.app')
@section('title', 'Create Visit')

@section('content')
<!-- Page Header -->
<div class="uhms-page-header d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Create New Visit</h4>
    </div>
    <div>
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a>
    </div>
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div id="visitFormFeedback" class="alert d-none" role="alert"></div>

<form method="POST" action="{{ route('admin.visits.store') }}" id="visitForm">
    @csrf

    <div class="row">
        <!-- Left Column - Patient, Insurance, Visit Details -->
        <div class="col-lg-8">
            <!-- Patient Search -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-search me-1"></i>Select Patient</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label">Search Patient <span class="text-danger">*</span></label>
                        <input type="text" id="patientSearch" class="form-control form-control-lg @error('patient_id') is-invalid @enderror"
                               placeholder="Type patient name, ID, phone, or Ghana Card number..."
                               value="{{ $selectedPatient ? $selectedPatient->patient_number . ' - ' . $selectedPatient->full_name : '' }}"
                               autocomplete="off">
                        <input type="hidden" name="patient_id" id="patientId" value="{{ $selectedPatient?->id ?? old('patient_id') }}">
                        @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="patientResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 999; max-height: 300px; overflow-y: auto;"></div>
                    </div>

                    <!-- Selected Patient Info Card -->
                    <div id="patientInfo" class="{{ $selectedPatient ? '' : 'd-none' }}">
                        @if($selectedPatient?->is_deceased)
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-2">
                            <i class="ti ti-skull fs-18 flex-shrink-0"></i>
                            <span><strong>This patient is marked as deceased and cannot start a new visit.</strong></span>
                        </div>
                        @endif
                        <div id="deceasedWarning" class="alert alert-danger d-flex align-items-center gap-2 mb-2 d-none">
                            <i class="ti ti-skull fs-18 flex-shrink-0"></i>
                            <span><strong>This patient is marked as deceased and cannot start a new visit.</strong></span>
                        </div>
                        <div class="alert alert-light border d-flex align-items-center gap-3 mb-0">
                            <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center">
                                <span id="patientInitial">{{ $selectedPatient ? strtoupper(substr($selectedPatient->first_name, 0, 1)) : '' }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0" id="patientName">{{ $selectedPatient?->full_name }} &bull; {{ $selectedPatient?->gender }}</h6>
                                <small class="text-muted">
                                    <span id="patientNumber">{{ $selectedPatient?->patient_number }}</span>
                                    &bull; <span id="patientPhone">{{ $selectedPatient?->phone }}</span>
                                    <span id="patientLastVisit" class="{{ $selectedPatient && $selectedPatient->visits()->exists() ? '' : 'd-none' }}">
                                        &bull; Last visit: <strong>{{ $selectedPatient ? ($selectedPatient->visits()->latest('visit_date')->value('visit_date') ? \Carbon\Carbon::parse($selectedPatient->visits()->latest('visit_date')->value('visit_date'))->format('d M Y') : '') : '' }}</strong>
                                    </span>
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPatient()">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance Selection -->
            <div class="card d-none" id="insuranceCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>Insurance</h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark" id="insuranceFallbackBadge" style="display:none;">Default expired - using Cash &amp; Carry</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addInsuranceBtn">
                            <i class="ti ti-plus me-1"></i>Add Insurance
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Insurance List (radio selection) -->
                    <div id="insuranceList" class="mb-3">
                        <div class="text-muted text-center py-3">
                            <i class="ti ti-loader me-1"></i>Loading patient insurances...
                        </div>
                    </div>

                    <input type="hidden" name="visit_insurance_id" id="visitInsuranceId" value="">
                    <input type="hidden" name="insurance_verification_id" id="insuranceVerificationId" value="">

                    {{-- Provider-agnostic verification panel.
                         Visibility / inputs are driven entirely by the response from
                         /admin/insurance/verify; no provider names appear here. --}}
                    <div id="verificationPanel" class="border rounded p-3 mt-3 d-none">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0"><i class="ti ti-shield-lock me-1"></i>Verification</h6>
                            <span class="badge bg-secondary" id="verificationStatusBadge">Not started</span>
                        </div>
                        <div class="text-muted small mb-2" id="verificationProviderMeta">&mdash;</div>

                        <div class="row g-2 align-items-end" id="verificationCodeRow" style="display:none;">
                            <div class="col-sm-8">
                                <label class="form-label mb-1">Authorization / Reference / CC Code</label>
                                <input type="text" id="verificationReferenceInput" name="verification_reference_code"
                                       class="form-control" placeholder="Enter code issued by the provider"
                                       autocomplete="off">
                            </div>
                            <div class="col-sm-4 d-grid">
                                <button type="button" class="btn btn-primary" id="runVerificationBtn">
                                    <i class="ti ti-shield-check me-1"></i>Verify
                                </button>
                            </div>
                        </div>

                        <div class="row g-2 align-items-end mt-1" id="verificationManualRow" style="display:none;">
                            <div class="col-12 d-grid">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="runVerificationBtn2">
                                    <i class="ti ti-shield-check me-1"></i>Verify
                                </button>
                            </div>
                        </div>

                        <div id="verificationFeedback" class="small mt-2"></div>
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
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chief Complaint</label>
                            <textarea name="chief_complaint" class="form-control @error('chief_complaint') is-invalid @enderror" rows="3" placeholder="Primary reason for visit...">{{ old('chief_complaint') }}</textarea>
                            @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Additional notes...">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Department, Services & Submit -->
        <div class="col-lg-4">
            <!-- Department, Services & Doctor Selection -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>Department, Services & Doctor</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <small class="text-muted">(filters services)</small></label>
                            <select id="departmentSelect" class="form-select @error('department_id') is-invalid @enderror">
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Assign Doctor</label>
                            <select name="assigned_doctor_id" id="doctorSelect" class="form-select @error('assigned_doctor_id') is-invalid @enderror">
                                <option value="">Select Doctor (optional)</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ old('assigned_doctor_id') == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->full_name }}</option>
                                @endforeach
                            </select>
                            @error('assigned_doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <!-- Service Selection -->
                    <div class="mb-3">
                        <label class="form-label">Available Services</label>
                        <div id="servicesList" class="border rounded p-3 bg-light">
                            <div class="text-muted text-center py-3" id="servicesPlaceholder">
                                <i class="ti ti-list-search me-1"></i>Select a department or doctor to load available services
                            </div>
                            <div id="servicesContent" class="d-none">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    <input type="text" id="serviceFilter" class="form-control" placeholder="Filter services...">
                                </div>
                                <div id="servicesItems" style="max-height: 280px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Services (Billing Lines) -->
                    <div id="selectedServicesCard" class="d-none">
                        <label class="form-label fw-bold"><i class="ti ti-receipt me-1"></i>Selected Services</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-bordered mb-0" id="billingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th class="text-end" style="width: 120px;">Price</th>
                                        <th class="text-center" style="width: 50px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="billingBody"></tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td class="text-end text-primary">Overall Total:</td>
                                        <td class="text-end text-primary" id="totalAmount">&#8373;0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
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
                        <li class="mb-2"><i class="ti ti-check text-warning me-1"></i>Staff pushes to <strong>Triage</strong> → queue # assigned</li>
                        <li><i class="ti ti-check text-primary me-1"></i>Billing lines created for selected services</li>
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

{{-- ──────────────────────────────────────────────────────────────────────
     Add / Edit / Renew Patient Insurance Modal — SPA: no full reload
──────────────────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="insuranceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="insuranceForm" autocomplete="off">
                @csrf
                <input type="hidden" id="insuranceFormPatientId" name="_patient_id">
                <input type="hidden" id="insuranceFormInsuranceId" name="_insurance_id">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="ti ti-shield-plus me-1"></i><span id="insuranceModalTitle">Add Insurance</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="insuranceFormFeedback" class="alert d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Insurance Provider <span class="text-danger">*</span></label>
                            <select name="insurance_provider_id" id="insuranceProviderSelect" class="form-select" required>
                                <option value="">Select Provider</option>
                                @foreach($insuranceProviders as $prov)
                                    <option value="{{ $prov->id }}"
                                            data-type="{{ $prov->type?->value }}"
                                            data-tiers='@json($prov->tiers->map(fn($t) => ["id"=>$t->id, "name"=>$t->name]))'>
                                        {{ $prov->name }} ({{ $prov->type?->label() }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tier</label>
                            <select name="insurance_tier_id" id="insuranceTierSelect" class="form-select">
                                <option value="">— Default —</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Membership Number</label>
                            <input type="text" name="membership_number" class="form-control" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Policy Number</label>
                            <input type="text" name="policy_number" class="form-control" maxlength="50">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CCC Code <small class="text-muted">(optional)</small></label>
                            <input type="text" name="ccc_code" class="form-control" maxlength="64">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Member Type</label>
                            <select name="member_type" class="form-select">
                                <option value="holder" selected>Card Holder</option>
                                <option value="beneficiary">Beneficiary</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="insIsPrimary">
                                <label class="form-check-label" for="insIsPrimary">Set as primary insurance</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="insuranceFormSaveBtn">
                        <i class="ti ti-device-floppy me-1"></i>Save Insurance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
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
    const walkInInfo = document.getElementById('walkInInfo');
    const scheduledInfo = document.getElementById('scheduledInfo');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtn = document.getElementById('submitBtn');
    const visitForm = document.getElementById('visitForm');
    const formFeedback = document.getElementById('visitFormFeedback');
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const insuranceCard = document.getElementById('insuranceCard');
    const selectedServicesCard = document.getElementById('selectedServicesCard');
    const serviceFilterInput = document.getElementById('serviceFilter');
    const defaultVisitDate = new Date().toISOString().split('T')[0];

    let debounceTimer;
    let patientInsurances = [];
    let selectedInsurance = null;
    let availableServices = [];
    let selectedServices = []; // [{service_catalog_id, name, price, quantity}]
    let allDoctors = @json($doctors->map(fn($d) => ['id' => $d->id, 'name' => 'Dr. ' . $d->full_name]));

    // ==========================================
    // Scheduling toggle based on date
    // ==========================================
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

    function showFormFeedback(type, html) {
        formFeedback.className = 'alert alert-' + type;
        formFeedback.innerHTML = html;
        formFeedback.classList.remove('d-none');
        window.scrollTo({ top: formFeedback.offsetTop - 100, behavior: 'smooth' });
    }

    function clearFormFeedback() {
        formFeedback.className = 'alert d-none';
        formFeedback.innerHTML = '';
    }

    function clearValidationErrors() {
        visitForm.querySelectorAll('.is-invalid').forEach(function(element) {
            element.classList.remove('is-invalid');
        });

        visitForm.querySelectorAll('.dynamic-invalid-feedback').forEach(function(element) {
            element.remove();
        });

        selectedServicesCard.classList.remove('border', 'border-danger');
    }

    function inputNameFromDot(field) {
        return field.split('.').reduce(function(name, part, index) {
            return index === 0 ? part : name + '[' + part + ']';
        }, '');
    }

    function resolveFieldElement(field) {
        if (field === 'patient_id') {
            return searchInput;
        }

        return visitForm.querySelector('[name="' + inputNameFromDot(field) + '"]');
    }

    function appendFieldError(field, message) {
        if (field.startsWith('services.')) {
            selectedServicesCard.classList.add('border', 'border-danger');
            return false;
        }

        const fieldElement = resolveFieldElement(field);

        if (!fieldElement || fieldElement.type === 'hidden') {
            return false;
        }

        fieldElement.classList.add('is-invalid');

        const anchor = fieldElement.closest('.input-group') || fieldElement;
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block dynamic-invalid-feedback';
        feedback.textContent = message;
        anchor.insertAdjacentElement('afterend', feedback);

        return true;
    }

    function applyValidationErrors(errors) {
        const generalErrors = [];

        Object.entries(errors).forEach(function(entry) {
            const field = entry[0];
            const messages = entry[1];
            const message = Array.isArray(messages) ? messages[0] : messages;

            if (!appendFieldError(field, message)) {
                generalErrors.push(message);
            }
        });

        if (generalErrors.length > 0) {
            showFormFeedback('danger', generalErrors.map(function(message) {
                return '<div>' + escapeHtml(message) + '</div>';
            }).join(''));
            return;
        }

        showFormFeedback('danger', 'Please correct the highlighted fields and try again.');
    }

    function setSubmitting(isSubmitting) {
        submitBtn.disabled = isSubmitting;
        submitBtnText.textContent = isSubmitting ? 'Saving...' : '';

        if (!isSubmitting) {
            checkScheduling();
        }
    }

    function resetVisitFormState() {
        visitForm.reset();
        clearPatient();

        selectedServices = [];
        availableServices = [];
        patientInsurances = [];
        selectedInsurance = null;

        resultsDiv.innerHTML = '';
        resultsDiv.classList.add('d-none');
        document.getElementById('insuranceList').innerHTML = '<div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>Loading patient insurances...</div>';
        document.getElementById('insuranceFallbackBadge').style.display = 'none';
        document.getElementById('visitInsuranceId').value = '';

        departmentSelect.value = '';
        repopulateDoctorSelect(allDoctors);
        showServicesPlaceholder();
        renderBillingTable();

        if (serviceFilterInput) {
            serviceFilterInput.value = '';
        }

        visitDateInput.value = defaultVisitDate;
        checkScheduling();
    }

    visitForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        clearFormFeedback();
        clearValidationErrors();
        setSubmitting(true);

        try {
            const response = await fetch(visitForm.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(visitForm),
            });

            const isJson = (response.headers.get('content-type') || '').includes('application/json');
            const payload = isJson ? await response.json() : {};

            if (response.ok) {
                resetVisitFormState();
                showFormFeedback(
                    'success',
                    '<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">'
                        + '<div><div class="fw-bold">' + escapeHtml(payload.message || 'Visit created successfully.') + '</div>'
                        + '<div class="small text-muted">Current status: ' + escapeHtml(payload.status_label || '') + '</div></div>'
                        + '<div class="d-flex gap-2">'
                        + '<a href="' + escapeHtml(payload.redirect_url || '#') + '" class="btn btn-sm btn-success">Open Visit</a>'
                        + '<button type="button" class="btn btn-sm btn-outline-success" id="createAnotherVisitBtn">Create Another</button>'
                        + '</div></div>'
                );

                const createAnotherBtn = document.getElementById('createAnotherVisitBtn');
                if (createAnotherBtn) {
                    createAnotherBtn.addEventListener('click', function() {
                        clearFormFeedback();
                        searchInput.focus();
                    });
                }

                return;
            }

            if (response.status === 422 && payload.errors) {
                applyValidationErrors(payload.errors);
                return;
            }

            const message = payload.message || 'Failed to create visit. Please try again.';
            showFormFeedback('danger', escapeHtml(message));
        } catch (error) {
            showFormFeedback('danger', 'Network error while creating visit. Please try again.');
        } finally {
            setSubmitting(false);
        }
    });

    // ==========================================
    // Patient search
    // ==========================================
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        if (query.length < 2) { resultsDiv.classList.add('d-none'); return; }

        debounceTimer = setTimeout(function() {
            fetch('{{ route("admin.visits.patient-search") }}?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length === 0) {
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">No patients found</div>';
                    } else {
                        data.forEach(function(patient) {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            let info = escapeHtml(patient.patient_number) + ' &bull; ' + escapeHtml(patient.phone || 'No phone');
                            if (patient.last_visit_date) {
                                info += ' &bull; Last visit: <strong>' + escapeHtml(patient.last_visit_date) + '</strong>';
                            }
                            item.innerHTML = '<div class="fw-medium">' + escapeHtml(patient.full_name) + '</div>' +
                                '<small class="text-muted">' + info + '</small>';
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
        searchInput.value = patient.patient_number + ' \u2014 ' + patient.full_name;
        document.getElementById('patientInitial').textContent = patient.full_name.charAt(0).toUpperCase();
        document.getElementById('patientName').textContent = patient.full_name;
        document.getElementById('patientNumber').textContent = patient.patient_number;
        document.getElementById('patientPhone').textContent = patient.phone || 'No phone';

        const lastVisitEl = document.getElementById('patientLastVisit');
        if (patient.last_visit_date) {
            lastVisitEl.innerHTML = '&bull; Last visit: <strong>' + escapeHtml(patient.last_visit_date) + '</strong>';
            lastVisitEl.classList.remove('d-none');
        } else {
            lastVisitEl.classList.add('d-none');
        }

        // Show deceased warning if applicable
        const deceasedWarning = document.getElementById('deceasedWarning');
        if (patient.is_deceased) {
            deceasedWarning.classList.remove('d-none');
        } else {
            deceasedWarning.classList.add('d-none');
        }

        patientInfo.classList.remove('d-none');
        resultsDiv.classList.add('d-none');

        loadPatientInsurances(patient.id);
    };

    window.clearPatient = function() {
        patientIdInput.value = '';
        searchInput.value = '';
        patientInfo.classList.add('d-none');
        insuranceCard.classList.add('d-none');
        document.getElementById('visitInsuranceId').value = '';
        patientInsurances = [];
        selectedInsurance = null;
        recalculateBilling();
    };

    // ==========================================
    // Insurance Loading & Selection
    // ==========================================
    function loadPatientInsurances(patientId) {
        insuranceCard.classList.remove('d-none');
        document.getElementById('insuranceList').innerHTML = '<div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>Loading...</div>';

        fetch('{{ route("admin.visits.patient-insurances") }}?patient_id=' + patientId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            patientInsurances = data.insurances || [];
            const defaultId = data.default_insurance_id;
            const isFallback = data.is_fallback;

            document.getElementById('insuranceFallbackBadge').style.display = isFallback ? '' : 'none';

            let html = '';
            if (patientInsurances.length === 0) {
                html = '<div class="text-muted text-center py-2">No insurances found. Defaulting to Cash & Carry.</div>';
            } else {
                html = '<div class="list-group">';
                patientInsurances.forEach(function(ins) {
                    const isDefault = ins.id == defaultId;
                    const isDisabled = !ins.is_valid && !ins.is_default;
                    const badgeClass = ins.is_valid ? 'bg-success' : (ins.is_expired ? 'bg-danger' : 'bg-secondary');
                    const statusText = ins.is_valid ? 'Valid' : (ins.is_expired ? 'Expired' : 'Inactive');

                    html += '<label class="list-group-item list-group-item-action d-flex align-items-center gap-3 ' + (isDisabled ? 'opacity-50' : '') + '">';
                    html += '<input type="radio" name="_insurance_radio" class="form-check-input insurance-radio" value="' + ins.id + '" data-ins-id="' + ins.id + '"';
                    if (isDefault) html += ' checked';
                    if (isDisabled) html += ' disabled';
                    html += '>';
                    html += '<div class="flex-grow-1">';
                    html += '<div class="fw-medium">' + escapeHtml(ins.provider_name) + ' <span class="badge bg-' + ins.type_color + ' ms-1">' + escapeHtml(ins.type_label) + '</span>';
                    if (ins.tier_name) html += ' <span class="badge bg-primary bg-opacity-75 ms-1">' + escapeHtml(ins.tier_name) + '</span>';
                    const memberBadge = ins.member_type === 'beneficiary' ? 'bg-warning text-dark' : 'bg-info';
                    const memberLabel = ins.member_type === 'beneficiary' ? 'Beneficiary' : 'Card Holder';
                    html += ' <span class="badge ' + memberBadge + ' ms-1">' + memberLabel + '</span>';
                    html += '</div>';
                    html += '<small class="text-muted">';
                    if (ins.membership_number) html += 'Member: ' + escapeHtml(ins.membership_number) + ' &bull; ';
                    if (ins.expiry_date) html += 'Expires: ' + ins.expiry_date;
                    else html += 'No expiry';
                    html += '</small>';
                    html += '</div>';
                    html += '<div class="text-end">';
                    html += '<span class="badge ' + badgeClass + '">' + statusText + '</span>';
                    if (ins.coverage_percentage != null) {
                        html += '<div class="small text-muted mt-1">' + ins.coverage_percentage + '% coverage</div>';
                    }
                    if (!ins.is_default) {
                        const editLabel = ins.is_expired ? 'Renew' : 'Edit';
                        const editIcon  = ins.is_expired ? 'ti-refresh' : 'ti-pencil';
                        html += '<button type="button" class="btn btn-link btn-sm p-0 mt-1 edit-insurance-btn" data-ins-id="' + ins.id + '">'
                              + '<i class="ti ' + editIcon + ' me-1"></i>' + editLabel
                              + '</button>';
                    }
                    html += '</div>';
                    html += '</label>';
                });
                html += '</div>';
            }

            document.getElementById('insuranceList').innerHTML = html;

            // Attach radio change handlers
            document.querySelectorAll('.insurance-radio').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    selectInsurance(parseInt(this.dataset.insId));
                });
            });

            // Attach edit/renew handlers
            document.querySelectorAll('.edit-insurance-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openInsuranceModal('edit', parseInt(this.dataset.insId));
                });
            });

            // Auto-select the default
            if (defaultId) {
                selectInsurance(defaultId);
            }
        })
        .catch(function() {
            document.getElementById('insuranceList').innerHTML = '<div class="text-danger text-center py-2">Failed to load insurances.</div>';
        });
    }

    function selectInsurance(insId) {
        selectedInsurance = patientInsurances.find(i => i.id === insId) || null;
        document.getElementById('visitInsuranceId').value = insId || '';

        // Re-price all already-selected services for the new insurance
        selectedServices.forEach(function(svc) {
            svc.price = resolveServicePrice(svc.originalService);
        });

        recalculateBilling();
        renderBillingTable();

        // Update displayed prices in the available services list
        availableServices.forEach(function(svc) {
            const el = document.querySelector('.svc-price-display[data-svc-id="' + svc.id + '"]');
            if (el) {
                el.textContent = '\u20B5' + formatNumber(resolveServicePrice(svc));
            }
        });

        // Hook: notify the verification panel that the insurance changed.
        if (typeof onInsuranceSelectionChanged === 'function') {
            onInsuranceSelectionChanged(insId);
        }
    }

    /**
     * Resolve the applicable unit price for a service object
     * given the currently selectedInsurance.
     * Priority: provider-specific override -> type default -> base price
     */
    function resolveServicePrice(svc) {
        if (!svc) return 0;
        const insType = selectedInsurance ? selectedInsurance.type : null;
        const insProviderId = selectedInsurance ? selectedInsurance.provider_id : null; // numeric

        // Provider-specific override
        if (insType && insProviderId && svc.provider_prices
            && svc.provider_prices[insProviderId]
            && svc.provider_prices[insProviderId][insType] !== undefined) {
            return svc.provider_prices[insProviderId][insType];
        }
        // Type default
        if (insType && svc.type_prices && svc.type_prices[insType] !== undefined) {
            return svc.type_prices[insType];
        }
        // Base price fallback
        return svc.price;
    }

    // ==========================================
    // Department â†’ Services loading
    // ==========================================
    departmentSelect.addEventListener('change', function() {
        const deptId = this.value;
        if (!deptId) {
            showServicesPlaceholder();
            return;
        }
        loadServicesForDepartment(deptId);
    });

    function loadServicesForDepartment(deptId) {
        showServicesLoading();

        fetch('{{ route("admin.visits.department-services") }}?department_id=' + deptId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            availableServices = data;
            renderServicesList();
            updateDoctorsForSelectedServices();
        })
        .catch(() => {
            document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-alert-circle me-1 text-danger"></i>Failed to load services';
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
        });
    }

    // ==========================================
    // Doctor â†’ Services loading
    // ==========================================
    doctorSelect.addEventListener('change', function() {
        const doctorId = this.value;
        if (!doctorId) return;

        if (!departmentSelect.value) {
            loadServicesForDoctor(doctorId);
        }
    });

    function loadServicesForDoctor(doctorId) {
        showServicesLoading();

        fetch('{{ route("admin.visits.services-for-doctor") }}?doctor_id=' + doctorId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            availableServices = data;
            renderServicesList();
        });
    }

    // ==========================================
    // Services rendering and selection
    // ==========================================
    function renderServicesList() {
        if (availableServices.length === 0) {
            document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-info-circle me-1 text-muted"></i>No services found for this selection';
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            document.getElementById('servicesContent').classList.add('d-none');
            return;
        }

        document.getElementById('servicesPlaceholder').classList.add('d-none');
        document.getElementById('servicesContent').classList.remove('d-none');

        const container = document.getElementById('servicesItems');
        let html = '';
        availableServices.forEach(function(svc) {
            const displayPrice = resolveServicePrice(svc);
            const fmtPrice = '\u20B5' + formatNumber(displayPrice);

            html += '<div class="service-item d-flex align-items-center justify-content-between py-2 px-2 border-bottom bg-white rounded mb-1" data-name="' + escapeHtml(svc.name.toLowerCase()) + '">';
            html += '<div>';
            html += '<span class="fw-medium">' + escapeHtml(svc.name) + '</span>';
            html += ' <span class="badge bg-light text-dark ms-1">' + escapeHtml(svc.code) + '</span>';
            html += '<div class="small text-muted">' + escapeHtml(svc.category) + '</div>';
            html += '</div>';
            html += '<div class="d-flex align-items-center gap-2">';
            html += '<span class="fw-bold text-success svc-price-display" data-svc-id="' + svc.id + '">' + fmtPrice + '</span>';
            html += '<button type="button" class="btn btn-sm btn-outline-primary add-service-btn"'
                + ' data-id="' + svc.id + '"'
                + ' data-name="' + escapeHtml(svc.name) + '"'
                + ' title="Add to billing">';
            html += '<i class="ti ti-plus"></i></button>';
            html += '</div>';
            html += '</div>';
        });
        container.innerHTML = html;

        container.querySelectorAll('.add-service-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const svcId = parseInt(this.dataset.id);
                const svcObj = availableServices.find(s => s.id === svcId);
                addServiceToBilling(svcId, this.dataset.name, svcObj);
            });
        });
    }

    document.getElementById('serviceFilter').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.service-item').forEach(function(item) {
            item.style.display = item.dataset.name.includes(filter) ? '' : 'none';
        });
    });

    function showServicesPlaceholder() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-list-search me-1"></i>Select a department or doctor to load available services';
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }

    function showServicesLoading() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-loader me-1"></i>Loading services...';
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }

    // ==========================================
    // Billing line management
    // ==========================================
    function addServiceToBilling(serviceId, serviceName, svcObj) {
        const resolvedPrice = resolveServicePrice(svcObj);
        const existing = selectedServices.find(s => s.service_catalog_id === serviceId);
        if (existing) {
            existing.quantity++;
            existing.price = resolvedPrice; // refresh price in case insurance changed
        } else {
            selectedServices.push({
                service_catalog_id: serviceId,
                name: serviceName,
                price: resolvedPrice,
                quantity: 1,
                originalService: svcObj,
            });
        }

        renderBillingTable();
        updateDoctorsForSelectedServices();
    }

    function removeServiceFromBilling(index) {
        selectedServices.splice(index, 1);
        renderBillingTable();
        updateDoctorsForSelectedServices();
    }

    function updateServiceQuantity(index, newQty) {
        if (newQty < 1) { removeServiceFromBilling(index); return; }
        selectedServices[index].quantity = newQty;
        renderBillingTable();
    }

    function renderBillingTable() {
        const card = document.getElementById('selectedServicesCard');
        const tbody = document.getElementById('billingBody');

        if (selectedServices.length === 0) {
            card.classList.add('d-none');
            tbody.innerHTML = '';
            recalculateBilling();
            return;
        }

        card.classList.remove('d-none');
        let html = '';

        selectedServices.forEach(function(svc, idx) {
            // Quantity is always 1 — UI no longer exposes a quantity selector.
            const qty = svc.quantity || 1;
            const lineTotal = svc.price * qty;

            html += '<tr>';
            html += '<td>' + escapeHtml(svc.name);
            html += '<input type="hidden" name="services[' + idx + '][service_catalog_id]" value="' + svc.service_catalog_id + '">';
            html += '<input type="hidden" name="services[' + idx + '][quantity]" value="' + qty + '">';
            html += '</td>';
            html += '<td class="text-end fw-medium">\u20B5' + formatNumber(lineTotal) + '</td>';
            html += '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-service-btn" data-index="' + idx + '"><i class="ti ti-trash"></i></button></td>';
            html += '</tr>';
        });

        tbody.innerHTML = html;

        tbody.querySelectorAll('.remove-service-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                removeServiceFromBilling(parseInt(this.dataset.index));
            });
        });

        recalculateBilling();
    }

    function calculateInsuranceForLine(svc) {
        if (!selectedInsurance || selectedInsurance.is_default) return 0;
        if (!selectedInsurance.is_valid) return 0;

        const coverageRate = (selectedInsurance.coverage_percentage || 0) / 100;
        return Math.round(svc.price * svc.quantity * coverageRate * 100) / 100;
    }

    function recalculateBilling() {
        let totalAmount = 0;

        selectedServices.forEach(function(svc) {
            totalAmount += svc.price * svc.quantity;
        });

        document.getElementById('totalAmount').textContent = '\u20B5' + formatNumber(totalAmount);
    }

    // ==========================================
    // Dynamic doctor filtering based on selected services
    // ==========================================
    function updateDoctorsForSelectedServices() {
        const serviceIds = selectedServices.map(s => s.service_catalog_id);
        if (serviceIds.length === 0) {
            repopulateDoctorSelect(allDoctors);
            return;
        }

        const params = serviceIds.map(id => 'service_ids[]=' + id).join('&');
        fetch('{{ route("admin.visits.doctors-for-services") }}?' + params, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => { repopulateDoctorSelect(data); });
    }

    function repopulateDoctorSelect(doctors) {
        const currentVal = doctorSelect.value;
        doctorSelect.innerHTML = '<option value="">Select Doctor (optional)</option>';
        doctors.forEach(function(doc) {
            const opt = document.createElement('option');
            opt.value = doc.id;
            opt.textContent = doc.name;
            if (doc.specialties && doc.specialties.length > 0) {
                opt.textContent += ' (' + doc.specialties.join(', ') + ')';
            }
            if (doc.id == currentVal) opt.selected = true;
            doctorSelect.appendChild(opt);
        });
    }

    // ==========================================
    // Auto-load if patient is pre-selected
    // ==========================================
    @if($selectedPatient)
        loadPatientInsurances({{ $selectedPatient->id }});
    @endif

    // ==========================================
    // Utility functions
    // ==========================================
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }

    function formatNumber(num) {
        return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // ==========================================
    // GENERIC INSURANCE VERIFICATION (provider-agnostic)
    // ==========================================
    const verifyUrl = "{{ route('admin.insurance.verify') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function resetVerificationPanel() {
        const panel = document.getElementById('verificationPanel');
        if (!panel) return;
        panel.classList.add('d-none');
        document.getElementById('insuranceVerificationId').value = '';
        document.getElementById('verificationFeedback').innerHTML = '';
        document.getElementById('verificationCodeRow').style.display = 'none';
        document.getElementById('verificationManualRow').style.display = 'none';
        const refInput = document.getElementById('verificationReferenceInput');
        if (refInput) refInput.value = '';
    }

    function renderVerificationStatus(data) {
        const badge = document.getElementById('verificationStatusBadge');
        badge.className = 'badge bg-' + (data.status_color || 'secondary');
        badge.textContent = data.status_label || data.status || 'Unknown';

        const meta = [];
        if (data.provider?.name)    meta.push(data.provider.name);
        if (data.provider?.method)  meta.push('method: ' + data.provider.method);
        if (data.provider?.channel) meta.push('via ' + data.provider.channel);
        if (data.driver)            meta.push('driver: ' + data.driver);
        document.getElementById('verificationProviderMeta').textContent = meta.join(' \u2022 ') || '\u2014';

        const parts = [];
        if (data.message)        parts.push('<div>' + escapeHtml(data.message) + '</div>');
        if (data.member_name)    parts.push('<div><strong>Member:</strong> ' + escapeHtml(data.member_name) + '</div>');
        if (data.reference_code) parts.push('<div><strong>Reference:</strong> <code>' + escapeHtml(data.reference_code) + '</code></div>');
        if (data.expires_at)     parts.push('<div><strong>Expires:</strong> ' + escapeHtml(data.expires_at) + '</div>');
        document.getElementById('verificationFeedback').innerHTML = parts.join('');

        if (data.acceptable && data.verification_id) {
            document.getElementById('insuranceVerificationId').value = data.verification_id;
        } else {
            document.getElementById('insuranceVerificationId').value = '';
        }

        const codeRow   = document.getElementById('verificationCodeRow');
        const manualRow = document.getElementById('verificationManualRow');
        if (data.requires_reference_code) {
            codeRow.style.display = '';
            manualRow.style.display = 'none';
        } else if (data.acceptable) {
            codeRow.style.display = 'none';
            manualRow.style.display = 'none';
        } else {
            codeRow.style.display = 'none';
            manualRow.style.display = '';
        }
    }

    async function runVerification(refCode) {
        const piId = document.getElementById('visitInsuranceId').value;
        if (!piId) return;
        const fb = document.getElementById('verificationFeedback');
        fb.innerHTML = '<i class="ti ti-loader me-1"></i>Contacting provider...';
        try {
            const resp = await fetch(verifyUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    patient_insurance_id: piId,
                    reference_code: refCode || null,
                }),
            });
            const data = await resp.json();
            if (!resp.ok) {
                fb.innerHTML = '<div class="text-danger">' + escapeHtml(data.message || 'Verification request failed.') + '</div>';
                return;
            }
            renderVerificationStatus(data);
        } catch (e) {
            fb.innerHTML = '<div class="text-danger">Verification request failed: ' + escapeHtml(e.message) + '</div>';
        }
    }

    // Hook called from selectInsurance() whenever the user changes selection.
    function onInsuranceSelectionChanged(insId) {
        resetVerificationPanel();
        if (!insId) return;
        document.getElementById('verificationPanel').classList.remove('d-none');
        runVerification(null);
    }

    document.getElementById('runVerificationBtn').addEventListener('click', () => {
        const code = document.getElementById('verificationReferenceInput').value.trim();
        runVerification(code || null);
    });
    document.getElementById('runVerificationBtn2').addEventListener('click', () => {
        runVerification(null);
    });

    // ==========================================
    // Insurance Add/Edit/Renew Modal (SPA)
    // ==========================================
    const insuranceModalEl   = document.getElementById('insuranceModal');
    const insuranceModal     = insuranceModalEl ? new bootstrap.Modal(insuranceModalEl) : null;
    const insuranceForm      = document.getElementById('insuranceForm');
    const insuranceFormFb    = document.getElementById('insuranceFormFeedback');
    const providerSelect     = document.getElementById('insuranceProviderSelect');
    const tierSelect         = document.getElementById('insuranceTierSelect');
    const insModalTitle      = document.getElementById('insuranceModalTitle');
    const insSaveBtn         = document.getElementById('insuranceFormSaveBtn');
    const insIdInput         = document.getElementById('insuranceFormInsuranceId');
    const insPatientIdInput  = document.getElementById('insuranceFormPatientId');

    function clearInsuranceForm() {
        insuranceForm.reset();
        insIdInput.value = '';
        insPatientIdInput.value = '';
        tierSelect.innerHTML = '<option value="">— Default —</option>';
        insuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        insuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());
        insuranceFormFb.classList.add('d-none');
        insuranceFormFb.innerHTML = '';
    }

    function repopulateTiers(providerOption) {
        tierSelect.innerHTML = '<option value="">— Default —</option>';
        if (!providerOption) return;
        let tiers = [];
        try { tiers = JSON.parse(providerOption.dataset.tiers || '[]'); } catch (e) {}
        tiers.forEach(function(t) {
            const opt = document.createElement('option');
            opt.value = t.id;
            opt.textContent = t.name;
            tierSelect.appendChild(opt);
        });
    }

    if (providerSelect) {
        providerSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            repopulateTiers(opt);
        });
    }

    function openInsuranceModal(mode, insuranceId) {
        if (!insuranceModal) return;
        const patientId = patientIdInput.value;
        if (!patientId) {
            alert('Please select a patient first.');
            return;
        }
        clearInsuranceForm();
        insPatientIdInput.value = patientId;
        insModalTitle.textContent = (mode === 'edit') ? 'Edit / Renew Insurance' : 'Add Insurance';

        if (mode === 'edit' && insuranceId) {
            const ins = patientInsurances.find(i => i.id === insuranceId);
            if (ins) {
                insIdInput.value = ins.id;
                providerSelect.value = ins.provider_id;
                repopulateTiers(providerSelect.options[providerSelect.selectedIndex]);
                if (ins.tier_id) tierSelect.value = ins.tier_id;
                insuranceForm.elements['membership_number'].value = ins.membership_number || '';
                insuranceForm.elements['policy_number'].value = ins.policy_number || '';
                insuranceForm.elements['member_type'].value = ins.member_type || 'holder';
                insuranceForm.elements['expiry_date'].value = ins.expiry_date || '';
                insuranceForm.elements['is_primary'].checked = !!ins.is_primary;
            }
        }

        insuranceModal.show();
    }

    const addInsBtn = document.getElementById('addInsuranceBtn');
    if (addInsBtn) {
        addInsBtn.addEventListener('click', function() {
            openInsuranceModal('add', null);
        });
    }

    function showInsuranceFb(type, text) {
        insuranceFormFb.className = 'alert alert-' + type;
        insuranceFormFb.innerHTML = text;
        insuranceFormFb.classList.remove('d-none');
    }

    function applyInsuranceErrors(errors) {
        Object.entries(errors || {}).forEach(([field, msgs]) => {
            const el = insuranceForm.querySelector('[name="' + field + '"]');
            if (!el) return;
            el.classList.add('is-invalid');
            const fb = document.createElement('div');
            fb.className = 'invalid-feedback d-block dynamic-invalid-feedback';
            fb.textContent = Array.isArray(msgs) ? msgs[0] : msgs;
            el.parentNode.insertBefore(fb, el.nextSibling);
        });
    }

    if (insuranceForm) {
        insuranceForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            insuranceFormFb.classList.add('d-none');
            insuranceForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            insuranceForm.querySelectorAll('.dynamic-invalid-feedback').forEach(el => el.remove());

            const patientId = insPatientIdInput.value;
            const insId     = insIdInput.value;
            const isEdit    = !!insId;

            const baseUrl = '{{ url("/admin/patients") }}/' + patientId + '/insurances' + (isEdit ? ('/' + insId) : '');
            const fd = new FormData(insuranceForm);
            // Strip our private form-only fields and force HTTP method.
            fd.delete('_patient_id');
            fd.delete('_insurance_id');
            if (isEdit) fd.append('_method', 'PUT');

            insSaveBtn.disabled = true;
            const originalLabel = insSaveBtn.innerHTML;
            insSaveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

            try {
                const resp = await fetch(baseUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: fd,
                });

                const ct = resp.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await resp.json() : {};

                if (resp.ok) {
                    showInsuranceFb('success', data.message || 'Insurance saved.');
                    // Reload insurance list and auto-select the new/edited one
                    await reloadInsuranceListAndSelect(data.insurance_id || (isEdit ? parseInt(insId) : null));
                    setTimeout(() => insuranceModal.hide(), 600);
                    return;
                }

                if (resp.status === 422 && data.errors) {
                    applyInsuranceErrors(data.errors);
                    showInsuranceFb('danger', data.message || 'Please correct the highlighted fields.');
                    return;
                }

                showInsuranceFb('danger', data.message || 'Failed to save insurance. Please try again.');
            } catch (err) {
                showInsuranceFb('danger', 'Network error while saving insurance.');
            } finally {
                insSaveBtn.disabled = false;
                insSaveBtn.innerHTML = originalLabel;
            }
        });
    }

    function reloadInsuranceListAndSelect(targetInsuranceId) {
        return new Promise(function(resolve) {
            const patientId = patientIdInput.value;
            if (!patientId) { resolve(); return; }
            fetch('{{ route("admin.visits.patient-insurances") }}?patient_id=' + patientId, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                patientInsurances = data.insurances || [];
                // Re-render list using the same rendering routine
                loadPatientInsurances(patientId);
                if (targetInsuranceId) {
                    setTimeout(() => selectInsurance(targetInsuranceId), 200);
                }
                resolve();
            })
            .catch(() => resolve());
        });
    }
});
</script>
@endpush
