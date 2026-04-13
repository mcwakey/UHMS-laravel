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
        <!-- LEFT COLUMN -->
        <div class="col-lg-8">

            {{-- â‘  PATIENT SELECTION --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-search me-1"></i>Select Patient</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label">Search Patient <span class="text-danger">*</span></label>
                        <input type="text" id="patientSearch"
                               class="form-control form-control-lg @error('patient_id') is-invalid @enderror"
                               placeholder="Name, patient ID, phone, or Ghana Cardâ€¦"
                               value="{{ $selectedPatient ? $selectedPatient->patient_number . ' â€” ' . $selectedPatient->full_name : '' }}"
                               autocomplete="off">
                        <input type="hidden" name="patient_id" id="patientId" value="{{ $selectedPatient?->id ?? old('patient_id') }}">
                        @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="patientResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index:999;max-height:300px;overflow-y:auto;"></div>
                    </div>
                    <div id="patientInfo" class="{{ $selectedPatient ? '' : 'd-none' }}">
                        <div class="alert alert-light border d-flex align-items-center gap-3 mb-0">
                            <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center fw-bold fs-5">
                                <span id="patientInitial">{{ $selectedPatient ? strtoupper(substr($selectedPatient->first_name,0,1)) : '' }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold" id="patientName">{{ $selectedPatient?->full_name }}</h6>
                                <small class="text-muted">
                                    <span id="patientNumber">{{ $selectedPatient?->patient_number }}</span>
                                    &bull; <span id="patientPhone">{{ $selectedPatient?->phone }}</span>
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearPatient()"><i class="ti ti-x"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- â‘¡ INSURANCE PANEL --}}
            <div class="card d-none" id="insurancePanel">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>Insurance</h5>
                    <span id="insTypeBadge" class="badge bg-secondary">â€”</span>
                </div>
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-6">
                            <label class="form-label mb-1 text-muted small">Selected Insurance</label>
                            <select name="visit_insurance_id" id="visitInsuranceSelect" class="form-select @error('visit_insurance_id') is-invalid @enderror">
                                <option value="">Loadingâ€¦</option>
                            </select>
                            @error('visit_insurance_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <div id="insValidityBadge" class="mb-1"><span class="badge bg-secondary px-3 py-2">â€”</span></div>
                            <small id="insMembership" class="text-muted d-block">â€”</small>
                            <small id="insExpiry" class="text-muted d-block">â€”</small>
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-4 text-center">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold text-primary" id="insCoveragePct">â€”</div>
                                <small class="text-muted">Coverage</small>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold text-success" id="insAnnualLimit">â€”</div>
                                <small class="text-muted">Annual Limit</small>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div class="p-2 bg-light rounded">
                                <div class="fw-bold text-info" id="insRemaining">â€”</div>
                                <small class="text-muted">Remaining</small>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-warning d-none mt-2 mb-0 py-2" id="insExpiryWarning">
                        <i class="ti ti-alert-triangle me-1"></i>
                        The selected insurance is <strong>expired</strong>. Cash &amp; Carry will be applied automatically.
                    </div>
                </div>
            </div>

            {{-- â‘¢ VISIT DETAILS --}}
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
                                @foreach(\App\Enums\Priority::cases() as $p)
                                    <option value="{{ $p->value }}" {{ old('priority','normal') == $p->value ? 'selected' : '' }}>{{ $p->label() }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Visit Date</label>
                            <input type="date" name="visit_date" id="visitDate"
                                   class="form-control @error('visit_date') is-invalid @enderror"
                                   value="{{ old('visit_date', date('Y-m-d')) }}">
                            @error('visit_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Today = walk-in. Future = scheduled.</small>
                        </div>
                    </div>
                    <div class="row" id="schedulingFields" style="display:none;">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Consultation Mode</label>
                            <select name="consultation_mode" class="form-select">
                                @foreach(\App\Enums\ConsultationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" {{ old('consultation_mode','in_person') == $mode->value ? 'selected' : '' }}>{{ $mode->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Chief Complaint</label>
                        <textarea name="chief_complaint" class="form-control" rows="3" placeholder="Primary reason for visitâ€¦">{{ old('chief_complaint') }}</textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Additional notesâ€¦">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- â‘£ SERVICES & BILLING --}}
            <div class="card d-none" id="servicesCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="ti ti-stethoscope me-1"></i>Services &amp; Billing</h5>
                    <small class="text-muted" id="servicesHint">Select a department to load services</small>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Add Service</label>
                        <div class="input-group">
                            <select id="serviceSelector" class="form-select">
                                <option value="">Choose a service from the selected departmentâ€¦</option>
                            </select>
                            <button type="button" class="btn btn-outline-primary" onclick="addSelectedService()">
                                <i class="ti ti-plus"></i> Add
                            </button>
                        </div>
                        <small class="text-muted">You can add the same service multiple times.</small>
                    </div>
                    <div id="serviceLineWrap" class="d-none">
                        <table class="table table-sm table-bordered align-middle mb-2">
                            <thead class="table-light">
                                <tr>
                                    <th>Service</th>
                                    <th class="text-center" style="width:80px;">Qty</th>
                                    <th class="text-end" style="width:110px;">Unit Price</th>
                                    <th class="text-end" style="width:110px;">Ins. Covered</th>
                                    <th class="text-end" style="width:110px;">Patient Pays</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="serviceLinesTbody"></tbody>
                        </table>
                        <div class="d-flex justify-content-end">
                            <table class="table table-sm w-auto mb-0">
                                <tr><td class="text-end text-muted pe-3">Subtotal</td><td class="text-end fw-medium pe-2" id="totalSubtotal">â‚µ0.00</td></tr>
                                <tr><td class="text-end text-muted pe-3">Insurance Covers</td><td class="text-end fw-medium text-success pe-2" id="totalCovered">â‚µ0.00</td></tr>
                                <tr class="table-light"><td class="text-end fw-bold pe-3">Patient Pays</td><td class="text-end fw-bold text-danger pe-2" id="totalPatient">â‚µ0.00</td></tr>
                            </table>
                        </div>
                    </div>
                    <div id="serviceInputsContainer"></div>
                </div>
            </div>

        </div><!-- /col-lg-8 -->

        <!-- RIGHT COLUMN -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" id="departmentSelect" class="form-select @error('department_id') is-invalid @enderror">
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">Patient added to this queue</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label d-flex align-items-center gap-2">
                            Assign Doctor
                            <span class="badge bg-light text-muted" id="doctorBadge" style="display:none;font-weight:normal;">Filtered by specialty</span>
                        </label>
                        <select name="assigned_doctor_id" id="doctorSelect" class="form-select @error('assigned_doctor_id') is-invalid @enderror">
                            <option value="">Select Doctor (optional)</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}" {{ old('assigned_doctor_id') == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->full_name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted" id="doctorHint">Add services to filter doctors by specialty</small>
                    </div>
                </div>
            </div>

            <div class="card bg-light" id="walkInInfo">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="ti ti-info-circle me-1"></i>Walk-in Visit</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Registered immediately</li>
                        <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Moves to <strong>Waiting</strong></li>
                        <li class="mb-1"><i class="ti ti-check text-success me-1"></i>Queue number assigned</li>
                        <li><i class="ti ti-check text-success me-1"></i>Appears on Queue Board</li>
                    </ul>
                </div>
            </div>
            <div class="card bg-light d-none" id="scheduledInfo">
                <div class="card-body">
                    <h6 class="fw-bold mb-2"><i class="ti ti-calendar-event me-1 text-primary"></i>Scheduled Visit</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-1"><i class="ti ti-check text-primary me-1"></i>Status: <strong>Scheduled</strong></li>
                        <li class="mb-1"><i class="ti ti-check text-primary me-1"></i>Check-in on visit day</li>
                        <li><i class="ti ti-check text-primary me-1"></i>Auto-moves to Waiting on arrival</li>
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
(function () {
    'use strict';

    /* â”€â”€ State â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    let currentPatientId  = null;
    let resolvedInsurance = null;
    let serviceLines      = [];
    let debounceTimer;

    const AJAX_HEADERS = {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'};
    const CSRF         = '{{ csrf_token() }}';

    /* â”€â”€ DOM refs â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    const $search        = document.getElementById('patientSearch');
    const $results       = document.getElementById('patientResults');
    const $patientId     = document.getElementById('patientId');
    const $patientInfo   = document.getElementById('patientInfo');
    const $visitDate     = document.getElementById('visitDate');
    const $sched         = document.getElementById('schedulingFields');
    const $walkInInfo    = document.getElementById('walkInInfo');
    const $schedInfo     = document.getElementById('scheduledInfo');
    const $submitTxt     = document.getElementById('submitBtnText');
    const $insPanel      = document.getElementById('insurancePanel');
    const $insSelect     = document.getElementById('visitInsuranceSelect');
    const $svcCard       = document.getElementById('servicesCard');
    const $svcSelector   = document.getElementById('serviceSelector');
    const $svcLineWrap   = document.getElementById('serviceLineWrap');
    const $svcTbody      = document.getElementById('serviceLinesTbody');
    const $svcInputs     = document.getElementById('serviceInputsContainer');
    const $deptSelect    = document.getElementById('departmentSelect');
    const $doctorSelect  = document.getElementById('doctorSelect');
    const $doctorBadge   = document.getElementById('doctorBadge');
    const $doctorHint    = document.getElementById('doctorHint');

    /* â”€â”€ Scheduling toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function checkScheduling() {
        const today = new Date().toISOString().split('T')[0];
        const fut   = $visitDate.value > today;
        $sched.style.display = fut ? '' : 'none';
        $walkInInfo.classList.toggle('d-none', fut);
        $schedInfo.classList.toggle('d-none', !fut);
        $submitTxt.textContent = fut ? 'Schedule Visit' : 'Create Visit';
    }
    $visitDate.addEventListener('change', checkScheduling);
    checkScheduling();

    /* â”€â”€ Patient search â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $search.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length < 2) { $results.classList.add('d-none'); return; }
        debounceTimer = setTimeout(() => {
            fetch('{{ route("admin.visits.patient-search") }}?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    $results.innerHTML = '';
                    if (!data.length) {
                        $results.innerHTML = '<div class="list-group-item text-muted">No patients found</div>';
                    } else {
                        data.forEach(p => {
                            const a = document.createElement('a');
                            a.href = '#';
                            a.className = 'list-group-item list-group-item-action';
                            a.innerHTML = `<div class="fw-medium">${p.full_name}</div><small class="text-muted">${p.patient_number} &bull; ${p.phone||'No phone'}</small>`;
                            a.addEventListener('click', e => { e.preventDefault(); selectPatient(p); });
                            $results.appendChild(a);
                        });
                    }
                    $results.classList.remove('d-none');
                });
        }, 300);
    });
    $search.addEventListener('blur', () => setTimeout(() => $results.classList.add('d-none'), 200));

    window.selectPatient = function (p) {
        currentPatientId          = p.id;
        $patientId.value          = p.id;
        $search.value             = p.patient_number + ' â€” ' + p.full_name;
        document.getElementById('patientInitial').textContent = p.full_name.charAt(0).toUpperCase();
        document.getElementById('patientName').textContent    = p.full_name;
        document.getElementById('patientNumber').textContent  = p.patient_number;
        document.getElementById('patientPhone').textContent   = p.phone || 'No phone';
        $patientInfo.classList.remove('d-none');
        $results.classList.add('d-none');
        loadInsuranceStatus(p.id);
        if ($deptSelect.value) loadServicesForDept($deptSelect.value);
    };

    window.clearPatient = function () {
        currentPatientId = null; resolvedInsurance = null;
        $patientId.value = ''; $search.value = '';
        $patientInfo.classList.add('d-none');
        $insPanel.classList.add('d-none');
        $svcCard.classList.add('d-none');
        serviceLines = []; renderLines();
    };

    /* â”€â”€ Insurance status â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function loadInsuranceStatus(patientId) {
        $insPanel.classList.remove('d-none');
        $insSelect.innerHTML = '<option value="">Loadingâ€¦</option>';
        fetch(`{{ url('admin/patients') }}/${patientId}/insurance-status`, {headers: AJAX_HEADERS})
            .then(r => r.json())
            .then(data => {
                resolvedInsurance = data.resolved_display;
                // Build select options
                $insSelect.innerHTML = '';
                data.insurances.forEach(ins => {
                    const opt    = document.createElement('option');
                    opt.value    = ins.id;
                    opt.textContent = ins.provider_name
                        + (ins.membership_number ? ` (${ins.membership_number})` : '')
                        + (ins.is_expired ? ' [EXPIRED]' : '')
                        + (ins.is_default_provider ? ' â€” Self/Cash' : '');
                    if (ins.is_expired) opt.className = 'text-danger';
                    if (ins.id === data.resolved_insurance_id) opt.selected = true;
                    $insSelect.appendChild(opt);
                });
                renderInsPanel(resolvedInsurance);
                if (serviceLines.length) recalc();
            })
            .catch(() => { $insSelect.innerHTML = '<option value="">Cash & Carry (Default)</option>'; });
    }

    function renderInsPanel(ins) {
        if (!ins) return;
        document.getElementById('insTypeBadge').textContent  = ins.type_label;
        document.getElementById('insTypeBadge').className    = `badge bg-${ins.type_color}`;
        document.getElementById('insValidityBadge').innerHTML = ins.is_valid
            ? '<span class="badge bg-success px-3 py-2"><i class="ti ti-shield-check me-1"></i>Valid</span>'
            : '<span class="badge bg-danger px-3 py-2"><i class="ti ti-shield-x me-1"></i>Expired / Inactive</span>';
        document.getElementById('insMembership').textContent = ins.membership_number ? 'Membership: ' + ins.membership_number : '';
        document.getElementById('insExpiry').textContent     = ins.expiry_date ? 'Expires: ' + ins.expiry_date : 'No expiry date';
        document.getElementById('insCoveragePct').textContent  = ins.coverage_percentage ? ins.coverage_percentage + '%' : (ins.is_default_provider ? 'You Pay 100%' : '0%');
        document.getElementById('insAnnualLimit').textContent  = ins.annual_limit ? 'â‚µ' + fmt(ins.annual_limit) : 'Unlimited';
        document.getElementById('insRemaining').textContent   = ins.remaining_annual_limit !== null ? 'â‚µ' + fmt(ins.remaining_annual_limit) : 'â€”';
        document.getElementById('insExpiryWarning').classList.toggle('d-none', ins.is_valid || !!ins.is_default_provider);
    }

    $insSelect.addEventListener('change', function () {
        if (!currentPatientId) return;
        fetch(`{{ url('admin/patients') }}/${currentPatientId}/insurance-status`, {headers: AJAX_HEADERS})
            .then(r => r.json())
            .then(data => {
                const sel = data.insurances.find(i => i.id == $insSelect.value) || data.resolved_display;
                resolvedInsurance = sel;
                renderInsPanel(sel);
                if (serviceLines.length) recalc();
            });
    });

    /* â”€â”€ Department â†’ services â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $deptSelect.addEventListener('change', function () {
        if (!this.value) { $svcCard.classList.add('d-none'); return; }
        $svcCard.classList.remove('d-none');
        loadServicesForDept(this.value);
    });

    function loadServicesForDept(deptId) {
        $svcSelector.innerHTML = '<option value="">Loading servicesâ€¦</option>';
        document.getElementById('servicesHint').textContent = 'Loadingâ€¦';
        fetch(`{{ url('admin/visits/ajax/departments') }}/${deptId}/services`, {headers: AJAX_HEADERS})
            .then(r => r.json())
            .then(svcs => {
                $svcSelector.innerHTML = '<option value="">â€” Choose a service â€”</option>';
                if (!svcs.length) {
                    $svcSelector.innerHTML = '<option value="">No services in this department</option>';
                    document.getElementById('servicesHint').textContent = 'No services yet';
                    return;
                }
                svcs.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id; o.textContent = s.name + ' â€” â‚µ' + fmt(s.price);
                    o.dataset.price = s.price; o.dataset.name = s.name; o.dataset.code = s.code;
                    $svcSelector.appendChild(o);
                });
                document.getElementById('servicesHint').textContent = svcs.length + ' service(s) available';
            });
    }

    /* â”€â”€ Add / remove service lines â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    window.addSelectedService = function () {
        const o = $svcSelector.options[$svcSelector.selectedIndex];
        if (!o || !o.value) return;
        serviceLines.push({id: +o.value, name: o.dataset.name, code: o.dataset.code,
                           qty: 1, unit_price: +o.dataset.price, covered: 0, payable: +o.dataset.price});
        $svcSelector.selectedIndex = 0;
        renderLines();
        if (currentPatientId) recalc(); else updateDoctors();
    };

    window.removeServiceLine = function (i) {
        serviceLines.splice(i, 1); renderLines();
        if (currentPatientId && serviceLines.length) recalc(); updateDoctors();
    };

    window.updateQty = function (i, v) {
        serviceLines[i].qty = Math.max(1, +v || 1); renderLines();
        if (currentPatientId) recalc();
    };

    /* Calculate prices via API */
    function recalc() {
        if (!currentPatientId || !serviceLines.length) return;
        fetch('{{ route("admin.visits.ajax.calculate-services") }}', {
            method  : 'POST',
            headers : {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,...AJAX_HEADERS},
            body    : JSON.stringify({patient_id: currentPatientId, insurance_id: $insSelect.value||null,
                                      services: serviceLines.map(l => ({id:l.id,quantity:l.qty}))})
        })
        .then(r => r.json())
        .then(resp => {
            resp.lines.forEach((al, i) => { if (serviceLines[i]) { serviceLines[i].unit_price = al.unit_price; serviceLines[i].covered = al.insurance_covered; serviceLines[i].payable = al.patient_payable; } });
            if (resp.insurance) { resolvedInsurance = resp.insurance; renderInsPanel(resp.insurance); }
            renderLines(); renderTotals(resp.totals); updateDoctors();
        });
    }

    /* â”€â”€ Render lines â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function renderLines() {
        if (!serviceLines.length) { $svcLineWrap.classList.add('d-none'); $svcInputs.innerHTML = ''; renderTotals({subtotal:0,insurance_covered:0,patient_payable:0}); return; }
        $svcLineWrap.classList.remove('d-none');
        $svcTbody.innerHTML = ''; $svcInputs.innerHTML = '';
        serviceLines.forEach((l, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td><div class="fw-medium">${l.name}</div><small class="text-muted">${l.code||''}</small></td>
                <td class="text-center"><input type="number" min="1" max="99" value="${l.qty}" class="form-control form-control-sm text-center" style="width:65px;" onchange="updateQty(${i},this.value)"></td>
                <td class="text-end">â‚µ${fmt(l.unit_price)}</td>
                <td class="text-end text-success">â‚µ${fmt(l.covered)}</td>
                <td class="text-end text-danger fw-medium">â‚µ${fmt(l.payable)}</td>
                <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger px-1 py-0" onclick="removeServiceLine(${i})"><i class="ti ti-x"></i></button></td>`;
            $svcTbody.appendChild(tr);
            $svcInputs.insertAdjacentHTML('beforeend',
                `<input type="hidden" name="services[${i}][id]" value="${l.id}"><input type="hidden" name="services[${i}][quantity]" value="${l.qty}">`);
        });
        const sub = serviceLines.reduce((s,l) => s + l.unit_price*l.qty, 0);
        const cov = serviceLines.reduce((s,l) => s + l.covered, 0);
        const pay = serviceLines.reduce((s,l) => s + l.payable, 0);
        renderTotals({subtotal:sub, insurance_covered:cov, patient_payable:pay});
    }

    function renderTotals(t) {
        document.getElementById('totalSubtotal').textContent = 'â‚µ' + fmt(t.subtotal||0);
        document.getElementById('totalCovered' ).textContent = 'â‚µ' + fmt(t.insurance_covered||0);
        document.getElementById('totalPatient' ).textContent = 'â‚µ' + fmt(t.patient_payable||0);
    }

    /* â”€â”€ Doctor filtering by specialty â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    function updateDoctors() {
        if (!serviceLines.length) { $doctorBadge.style.display='none'; $doctorHint.textContent='Add services to filter doctors by specialty'; return; }
        const qs = serviceLines.map(l => `service_ids[]=${l.id}`).join('&');
        fetch(`{{ route('admin.visits.ajax.doctors-by-services') }}?${qs}`, {headers: AJAX_HEADERS})
            .then(r => r.json())
            .then(docs => {
                const prev = $doctorSelect.value;
                $doctorSelect.innerHTML = '<option value="">Select Doctor (optional)</option>';
                docs.forEach(d => {
                    const o = document.createElement('option');
                    o.value = d.id; o.textContent = d.name + (d.specialties ? ` [${d.specialties}]` : '');
                    if (d.id == prev) o.selected = true;
                    $doctorSelect.appendChild(o);
                });
                $doctorBadge.style.display = '';
                $doctorHint.textContent    = docs.length + ' doctor(s) match selected services';
            });
    }

    /* â”€â”€ Doctor â†’ services â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    $doctorSelect.addEventListener('change', function () {
        if (!this.value || !$deptSelect.value) return;
        fetch(`{{ url('admin/visits/ajax/doctors') }}/${this.value}/services`, {headers: AJAX_HEADERS})
            .then(r => r.json())
            .then(svcs => {
                if (!svcs.length) return;
                $svcSelector.innerHTML = '<option value="">â€” Choose a service â€”</option>';
                svcs.forEach(s => {
                    const o = document.createElement('option');
                    o.value = s.id; o.textContent = s.name + ' â€” â‚µ' + fmt(s.price);
                    o.dataset.price = s.price; o.dataset.name = s.name; o.dataset.code = s.code;
                    $svcSelector.appendChild(o);
                });
                $svcCard.classList.remove('d-none');
            });
    });

    function fmt(n) { return parseFloat(n||0).toLocaleString('en-GH',{minimumFractionDigits:2,maximumFractionDigits:2}); }

    @if($selectedPatient)
    selectPatient({id:{{ $selectedPatient->id }}, full_name:@json($selectedPatient->full_name), patient_number:@json($selectedPatient->patient_number), phone:@json($selectedPatient->phone)});
    @endif
})();
</script>
@endpush
