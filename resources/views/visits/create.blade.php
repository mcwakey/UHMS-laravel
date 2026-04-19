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

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

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
                               value="{{ $selectedPatient ? $selectedPatient->patient_number . ' â€” ' . $selectedPatient->full_name : '' }}"
                               autocomplete="off">
                        <input type="hidden" name="patient_id" id="patientId" value="{{ $selectedPatient?->id ?? old('patient_id') }}">
                        @error('patient_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div id="patientResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 999; max-height: 300px; overflow-y: auto;"></div>
                    </div>

                    <!-- Selected Patient Info Card -->
                    <div id="patientInfo" class="{{ $selectedPatient ? '' : 'd-none' }}">
                        <div class="alert alert-light border d-flex align-items-center gap-3 mb-0">
                            <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center">
                                <span id="patientInitial">{{ $selectedPatient ? strtoupper(substr($selectedPatient->first_name, 0, 1)) : '' }}</span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0" id="patientName">{{ $selectedPatient?->full_name }}</h6>
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
                    <span class="badge bg-warning text-dark" id="insuranceFallbackBadge" style="display:none;">Default expired â€” using Cash &amp; Carry</span>
                </div>
                <div class="card-body">
                    <!-- Insurance List (radio selection) -->
                    <div id="insuranceList" class="mb-3">
                        <div class="text-muted text-center py-3">
                            <i class="ti ti-loader me-1"></i>Loading patient insurances...
                        </div>
                    </div>

                    <!-- Selected Insurance Info Panel -->
                    <div id="selectedInsuranceInfo" class="d-none">
                        <div class="alert alert-light border mb-0">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Insurance Type</small>
                                    <span class="fw-medium" id="insInfoType">â€”</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Coverage</small>
                                    <span class="fw-medium" id="insInfoCoverage">â€”</span>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block">Total Billed (YTD)</small>
                                    <span class="fw-medium" id="insInfoBilled">â€”</span>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block">Remaining Balance</small>
                                    <span class="fw-bold" id="insInfoRemaining">â€”</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="visit_insurance_id" id="visitInsuranceId" value="">
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
                            <table class="table table-sm table-bordered mb-0" id="billingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th class="text-center" style="width: 70px;">Qty</th>
                                        <th class="text-end" style="width: 100px;">Unit Price</th>
                                        <th class="text-end" style="width: 100px;">Total</th>
                                        <th style="width: 36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="billingBody"></tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">Grand Total:</td>
                                        <td class="text-end" id="totalAmount">&#8373;0.00</td>
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
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const insuranceCard = document.getElementById('insuranceCard');

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
                    html += '<div class="fw-medium">' + escapeHtml(ins.provider_name) + ' <span class="badge bg-' + ins.type_color + ' ms-1">' + escapeHtml(ins.type_label) + '</span></div>';
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

        const infoPanel = document.getElementById('selectedInsuranceInfo');
        if (selectedInsurance) {
            infoPanel.classList.remove('d-none');
            document.getElementById('insInfoType').innerHTML = '<span class="badge bg-' + selectedInsurance.type_color + '">' + escapeHtml(selectedInsurance.type_label) + '</span>';
            document.getElementById('insInfoCoverage').textContent = (selectedInsurance.coverage_percentage || 0) + '%';

            const remaining = selectedInsurance.remaining_annual_limit;
            document.getElementById('insInfoBilled').textContent = remaining != null
                ? '\u20B5' + formatNumber((selectedInsurance.annual_limit || 0) - remaining)
                : '\u2014';
            document.getElementById('insInfoRemaining').textContent = remaining != null
                ? '\u20B5' + formatNumber(remaining)
                : 'Unlimited';
        } else {
            infoPanel.classList.add('d-none');
        }

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
    }

    /**
     * Resolve the applicable unit price for a service object
     * given the currently selectedInsurance.
     * Priority: provider-specific override -> type default -> base price
     */
    function resolveServicePrice(svc) {
        if (!svc) return 0;
        const insType = selectedInsurance ? selectedInsurance.type : null;            // e.g. 'nhia'
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
            const lineTotal = svc.price * svc.quantity;

            html += '<tr>';
            html += '<td>' + escapeHtml(svc.name);
            html += '<input type="hidden" name="services[' + idx + '][service_catalog_id]" value="' + svc.service_catalog_id + '">';
            html += '<input type="hidden" name="services[' + idx + '][quantity]" value="' + svc.quantity + '">';
            html += '</td>';
            html += '<td class="text-center">'
                + '<div class="input-group input-group-sm" style="width:70px;">'
                + '<button type="button" class="btn btn-outline-secondary btn-xs qty-dec" data-index="' + idx + '">-</button>'
                + '<span class="form-control form-control-sm text-center px-1">' + svc.quantity + '</span>'
                + '<button type="button" class="btn btn-outline-secondary btn-xs qty-inc" data-index="' + idx + '">+</button>'
                + '</div>'
                + '</td>';
            html += '<td class="text-end text-muted">\u20B5' + formatNumber(svc.price) + '</td>';
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

        tbody.querySelectorAll('.qty-dec').forEach(function(btn) {
            btn.addEventListener('click', function() {
                updateServiceQuantity(parseInt(this.dataset.index), selectedServices[parseInt(this.dataset.index)].quantity - 1);
            });
        });

        tbody.querySelectorAll('.qty-inc').forEach(function(btn) {
            btn.addEventListener('click', function() {
                updateServiceQuantity(parseInt(this.dataset.index), selectedServices[parseInt(this.dataset.index)].quantity + 1);
            });
        });

        recalculateBilling();
    }

    function calculateInsuranceForLine(svc) {
        if (!selectedInsurance || selectedInsurance.is_default) return 0;
        if (!selectedInsurance.is_valid) return 0;
        if (selectedInsurance.type === 'nhis' && !svc.is_nhis) return 0;

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
});
</script>
@endpush
