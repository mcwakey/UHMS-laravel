@extends('layouts.app')
@section('title', __('appointments.schedule_title'))

@section('content')
<!-- Page Header -->
<!-- <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('appointments.schedule_title') }}</h4>
    </div> -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ route('admin.appointments.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>{{ __('appointments.schedule_title') }}</a>
    </h6>
    <!-- <div>
        <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>{{ __('appointments.back_to_appointments') }}
        </a>
    </div> -->
</div>

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('admin.appointments.store') }}" id="appointmentForm">
    @csrf

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">

            <!-- Patient Search -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-search me-1"></i>{{ __('appointments.select_patient') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 position-relative">
                        <label class="form-label">{{ __('appointments.search_patient') }} <span class="text-danger">*</span></label>
                        <input type="text" id="patientSearch" class="form-control form-control-lg @error('patient_id') is-invalid @enderror"
                               placeholder="{{ __('appointments.search_patient_placeholder') }}"
                               value="{{ $selectedPatient ? $selectedPatient->patient_number . ' — ' . $selectedPatient->full_name : '' }}"
                               autocomplete="off">
                        <input type="hidden" name="patient_id" id="patientId" value="{{ $selectedPatient?->id ?? old('patient_id') }}">
                        @error('patient_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div id="patientResults" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 999; max-height: 300px; overflow-y: auto;"></div>
                    </div>

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
                                </small>
                            </div>
                            <button aria-label="{{ __('common.close') }}" title="{{ __('common.close') }}" type="button" class="btn btn-sm btn-outline-danger" onclick="clearPatient()">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Insurance Selection -->
            <div class="card d-none" id="insuranceCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('appointments.insurance') }}</h5>
                    <span class="badge bg-warning text-dark" id="insuranceFallbackBadge" style="display:none;">{{ __('appointments.insurance_fallback_badge') }}</span>
                </div>
                <div class="card-body">
                    <div id="insuranceList" class="mb-3">
                        <div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>{{ __('appointments.loading_patient_insurances') }}</div>
                    </div>
                    <div id="selectedInsuranceInfo" class="d-none">
                        <div class="alert alert-light border mb-0">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">{{ __('appointments.insurance_type') }}</small>
                                    <span class="fw-medium" id="insInfoType">—</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">{{ __('appointments.coverage') }}</small>
                                    <span class="fw-medium" id="insInfoCoverage">—</span>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block">{{ __('appointments.total_billed_ytd') }}</small>
                                    <span class="fw-medium" id="insInfoBilled">—</span>
                                </div>
                                <div class="col-6 mt-2">
                                    <small class="text-muted d-block">{{ __('appointments.remaining_balance') }}</small>
                                    <span class="fw-bold" id="insInfoRemaining">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="visit_insurance_id" id="visitInsuranceId" value="">
                </div>
            </div>

            <!-- Appointment Details -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-clipboard-text me-1"></i>{{ __('appointments.appointment_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.visit_type') }} <span class="text-danger">*</span></label>
                            <select name="visit_type" class="form-select @error('visit_type') is-invalid @enderror" required>
                                <option value="">{{ __('appointments.select_type') }}</option>
                                @foreach(\App\Enums\VisitType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ old('visit_type', 'outpatient') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                            @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.priority') }} <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach(\App\Enums\Priority::cases() as $priority)
                                    <option value="{{ $priority->value }}" {{ old('priority', 'normal') == $priority->value ? 'selected' : '' }}>{{ $priority->translatedLabel() }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.appointment_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" id="appointmentDate" class="form-control @error('appointment_date') is-invalid @enderror"
                                   value="{{ old('appointment_date', $appointmentDate) }}"
                                   min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                            @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.start_time') }} <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="{{ old('start_time', '09:00') }}" required>
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.end_time') }}</label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="{{ old('end_time') }}">
                            <small class="text-muted">{{ __('appointments.default_duration_hint') }}</small>
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.consultation_mode') }}</label>
                            <select name="consultation_mode" class="form-select @error('consultation_mode') is-invalid @enderror">
                                @foreach(\App\Enums\ConsultationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" {{ old('consultation_mode', 'in_person') == $mode->value ? 'selected' : '' }}>{{ $mode->translatedLabel() }}</option>
                                @endforeach
                            </select>
                            @error('consultation_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('appointments.chief_complaint') }}</label>
                        <textarea name="chief_complaint" class="form-control @error('chief_complaint') is-invalid @enderror" rows="3" placeholder="{{ __('appointments.complaint_placeholder') }}">{{ old('chief_complaint') }}</textarea>
                        @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('appointments.notes') }}</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" placeholder="{{ __('appointments.notes_placeholder') }}">{{ old('notes') }}</textarea>
                        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">

            <!-- Department, Services & Doctor -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-building-hospital me-1"></i>{{ __('appointments.department_services_doctor') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('common.department') }} <span class="text-danger">*</span></label>
                            <select id="departmentSelect" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                <option value="">{{ __('appointments.select_department') }}</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('appointments.assign_doctor') }}</label>
                            <select name="doctor_id" id="doctorSelect" class="form-select @error('doctor_id') is-invalid @enderror">
                                <option value="">{{ __('appointments.select_doctor_optional') }}</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ old('doctor_id') == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->full_name }}</option>
                                @endforeach
                            </select>
                            @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('appointments.available_services') }}</label>
                        <div id="servicesList" class="border rounded p-3 bg-light">
                            <div class="text-muted text-center py-3" id="servicesPlaceholder">
                                <i class="ti ti-list-search me-1"></i>{{ __('appointments.select_dept_or_doctor_services') }}
                            </div>
                            <div id="servicesContent" class="d-none">
                                <div class="input-group mb-2">
                                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                                    <input type="text" id="serviceFilter" class="form-control" placeholder="{{ __('appointments.filter_services') }}">
                                </div>
                                <div id="servicesItems" style="max-height: 280px; overflow-y: auto;"></div>
                            </div>
                        </div>
                    </div>

                    <div id="selectedServicesCard" class="d-none">
                        <label class="form-label fw-bold"><i class="ti ti-receipt me-1"></i>{{ __('appointments.selected_services') }}</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="billingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('appointments.service') }}</th>
                                        <th class="text-center" style="width: 70px;">{{ __('appointments.qty') }}</th>
                                        <th class="text-end" style="width: 100px;">{{ __('appointments.unit_price') }}</th>
                                        <th class="text-end" style="width: 100px;">{{ __('common.total') }}</th>
                                        <th style="width: 36px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="billingBody"></tbody>
                                <tfoot>
                                    <tr class="table-light fw-bold">
                                        <td colspan="3" class="text-end">{{ __('appointments.estimated_total') }}</td>
                                        <td class="text-end" id="totalAmount">&#8373;0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="fw-bold mb-3"><i class="ti ti-calendar-event me-1 text-primary"></i>{{ __('appointments.what_happens_next') }}</h6>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>{{ __('appointments.appointment_is_scheduled') }}</li>
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>{{ __('appointments.patient_can_be_notified') }}</li>
                        <li class="mb-2"><i class="ti ti-check text-primary me-1"></i>{{ __('appointments.staff_checks_in') }}</li>
                        <li><i class="ti ti-check text-success me-1"></i>{{ __('appointments.checkin_creates_visit') }}</li>
                    </ul>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="ti ti-calendar-plus me-1"></i>{{ __('appointments.schedule_appointment') }}
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
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const insuranceCard = document.getElementById('insuranceCard');

    let debounceTimer;
    let patientInsurances = [];
    let selectedInsurance = null;
    let availableServices = [];
    let selectedServices = [];
    let allDoctors = @json($doctors->map(fn($d) => ['id' => $d->id, 'name' => 'Dr. ' . $d->full_name]));
    @php
        $appointmentCreateI18nData = [
            'noPatientsFound' => __('appointments.no_patients_found'),
            'noPhone' => __('appointments.no_phone'),
            'loading' => __('appointments.loading'),
            'noInsurancesCash' => __('appointments.no_insurances_cash'),
            'member' => __('appointments.member_label'),
            'expires' => __('appointments.expires_label'),
            'noExpiry' => __('appointments.no_expiry'),
            'valid' => __('appointments.valid_status'),
            'expired' => __('appointments.expired_status'),
            'inactive' => __('appointments.inactive_status'),
            'coverage' => __('appointments.coverage'),
            'unlimited' => __('appointments.unlimited'),
            'failedLoadInsurances' => __('appointments.failed_load_insurances'),
            'noServicesFound' => __('appointments.no_services_found'),
            'selectServices' => __('appointments.select_dept_or_doctor_services'),
            'loadingServices' => __('appointments.loading_services'),
            'add' => __('common.add'),
            'delete' => __('common.delete'),
            'selectDoctorOptional' => __('appointments.select_doctor_optional'),
        ];
    @endphp
    const i18n = @json($appointmentCreateI18nData);

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
                        resultsDiv.innerHTML = '<div class="list-group-item text-muted">' + escapeHtml(i18n.noPatientsFound) + '</div>';
                    } else {
                        data.forEach(function(patient) {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.className = 'list-group-item list-group-item-action';
                            item.innerHTML = '<div class="fw-medium">' + escapeHtml(patient.full_name) + '</div><small class="text-muted">' + escapeHtml(patient.patient_number) + ' &bull; ' + escapeHtml(patient.phone || i18n.noPhone) + '</small>';
                            item.addEventListener('click', function(e) { e.preventDefault(); selectPatient(patient); });
                            resultsDiv.appendChild(item);
                        });
                    }
                    resultsDiv.classList.remove('d-none');
                });
        }, 300);
    });

    searchInput.addEventListener('blur', function() { setTimeout(function() { resultsDiv.classList.add('d-none'); }, 200); });

    window.selectPatient = function(patient) {
        patientIdInput.value = patient.id;
        searchInput.value = patient.patient_number + ' \u2014 ' + patient.full_name;
        document.getElementById('patientInitial').textContent = patient.full_name.charAt(0).toUpperCase();
        document.getElementById('patientName').textContent = patient.full_name;
        document.getElementById('patientNumber').textContent = patient.patient_number;
        document.getElementById('patientPhone').textContent = patient.phone || i18n.noPhone;
        patientInfo.classList.remove('d-none');
        resultsDiv.classList.add('d-none');
        loadPatientInsurances(patient.id);
    };

    window.clearPatient = function() {
        patientIdInput.value = ''; searchInput.value = '';
        patientInfo.classList.add('d-none'); insuranceCard.classList.add('d-none');
        document.getElementById('visitInsuranceId').value = '';
        patientInsurances = []; selectedInsurance = null; recalculateBilling();
    };

    @if($selectedPatient)
    loadPatientInsurances({{ $selectedPatient->id }});
    @endif

    function loadPatientInsurances(patientId) {
        insuranceCard.classList.remove('d-none');
        document.getElementById('insuranceList').innerHTML = '<div class="text-muted text-center py-3"><i class="ti ti-loader me-1"></i>' + escapeHtml(i18n.loading) + '</div>';
        fetch('{{ route("admin.visits.patient-insurances") }}?patient_id=' + patientId, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            patientInsurances = data.insurances || [];
            const defaultId = data.default_insurance_id;
            document.getElementById('insuranceFallbackBadge').style.display = data.is_fallback ? '' : 'none';
            let html = '';
            if (patientInsurances.length === 0) {
                html = '<div class="text-muted text-center py-2">' + escapeHtml(i18n.noInsurancesCash) + '</div>';
            } else {
                html = '<div class="list-group">';
                patientInsurances.forEach(function(ins) {
                    const isDefault = ins.id == defaultId, isDisabled = !ins.is_valid && !ins.is_default;
                    const badgeClass = ins.is_valid ? 'bg-success' : (ins.is_expired ? 'bg-danger' : 'bg-secondary');
                    html += '<label class="list-group-item list-group-item-action d-flex align-items-center gap-3 ' + (isDisabled ? 'opacity-50' : '') + '">';
                    html += '<input type="radio" name="_insurance_radio" class="form-check-input insurance-radio" value="' + ins.id + '" data-ins-id="' + ins.id + '"' + (isDefault ? ' checked' : '') + (isDisabled ? ' disabled' : '') + '>';
                    html += '<div class="flex-grow-1"><div class="fw-medium">' + escapeHtml(ins.provider_name) + ' <span class="badge bg-' + ins.type_color + ' ms-1">' + escapeHtml(ins.type_label) + '</span></div>';
                    html += '<small class="text-muted">' + (ins.membership_number ? escapeHtml(i18n.member) + ' ' + escapeHtml(ins.membership_number) + ' &bull; ' : '') + (ins.expiry_date ? escapeHtml(i18n.expires) + ' ' + ins.expiry_date : escapeHtml(i18n.noExpiry)) + '</small></div>';
                    html += '<div class="text-end"><span class="badge ' + badgeClass + '">' + (ins.is_valid ? escapeHtml(i18n.valid) : (ins.is_expired ? escapeHtml(i18n.expired) : escapeHtml(i18n.inactive))) + '</span>' + (ins.coverage_percentage != null ? '<div class="small text-muted mt-1">' + ins.coverage_percentage + '% ' + escapeHtml(i18n.coverage) + '</div>' : '') + '</div></label>';
                });
                html += '</div>';
            }
            document.getElementById('insuranceList').innerHTML = html;
            document.querySelectorAll('.insurance-radio').forEach(function(radio) { radio.addEventListener('change', function() { selectInsurance(parseInt(this.dataset.insId)); }); });
            if (defaultId) selectInsurance(defaultId);
        })
        .catch(function() { document.getElementById('insuranceList').innerHTML = '<div class="text-danger text-center py-2">' + escapeHtml(i18n.failedLoadInsurances) + '</div>'; });
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
            document.getElementById('insInfoBilled').textContent = remaining != null ? '\u20B5' + formatNumber((selectedInsurance.annual_limit || 0) - remaining) : '\u2014';
            document.getElementById('insInfoRemaining').textContent = remaining != null ? '\u20B5' + formatNumber(remaining) : i18n.unlimited;
        } else { infoPanel.classList.add('d-none'); }
        selectedServices.forEach(svc => { svc.price = resolveServicePrice(svc.originalService); });
        recalculateBilling(); renderBillingTable();
        availableServices.forEach(svc => { const el = document.querySelector('.svc-price-display[data-svc-id="' + svc.id + '"]'); if (el) el.textContent = '\u20B5' + formatNumber(resolveServicePrice(svc)); });
    }

    function resolveServicePrice(svc) {
        if (!svc) return 0;
        const insType = selectedInsurance ? selectedInsurance.type : null;
        const insProviderId = selectedInsurance ? selectedInsurance.provider_id : null;
        if (insType && insProviderId && svc.provider_prices && svc.provider_prices[insProviderId] && svc.provider_prices[insProviderId][insType] !== undefined) return svc.provider_prices[insProviderId][insType];
        if (insType && svc.type_prices && svc.type_prices[insType] !== undefined) return svc.type_prices[insType];
        return svc.price;
    }

    departmentSelect.addEventListener('change', function() {
        if (!this.value) { showServicesPlaceholder(); return; }
        showServicesLoading();
        fetch('{{ route("admin.visits.department-services") }}?department_id=' + this.value, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(data => { availableServices = data; renderServicesList(); updateDoctorsForSelectedServices(); });
    });

    doctorSelect.addEventListener('change', function() {
        if (!this.value || departmentSelect.value) return;
        showServicesLoading();
        fetch('{{ route("admin.visits.services-for-doctor") }}?doctor_id=' + this.value, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(data => { availableServices = data; renderServicesList(); });
    });

    function renderServicesList() {
        if (availableServices.length === 0) {
            document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-info-circle me-1 text-muted"></i>' + escapeHtml(i18n.noServicesFound);
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            document.getElementById('servicesContent').classList.add('d-none'); return;
        }
        document.getElementById('servicesPlaceholder').classList.add('d-none');
        document.getElementById('servicesContent').classList.remove('d-none');
        let html = '';
        availableServices.forEach(svc => {
            html += '<div class="service-item d-flex align-items-center justify-content-between py-2 px-2 border-bottom bg-white rounded mb-1" data-name="' + escapeHtml(svc.name.toLowerCase()) + '">';
            html += '<div><span class="fw-medium">' + escapeHtml(svc.name) + '</span> <span class="badge bg-light text-dark ms-1">' + escapeHtml(svc.code) + '</span><div class="small text-muted">' + escapeHtml(svc.category) + '</div></div>';
            html += '<div class="d-flex align-items-center gap-2"><span class="fw-bold text-success svc-price-display" data-svc-id="' + svc.id + '">\u20B5' + formatNumber(resolveServicePrice(svc)) + '</span>';
            html += '<button aria-label="' + escapeHtml(i18n.add) + '" title="' + escapeHtml(i18n.add) + '" type="button" class="btn btn-sm btn-outline-primary add-service-btn" data-id="' + svc.id + '" data-name="' + escapeHtml(svc.name) + '"><i class="ti ti-plus"></i></button></div></div>';
        });
        document.getElementById('servicesItems').innerHTML = html;
        document.querySelectorAll('.add-service-btn').forEach(btn => btn.addEventListener('click', function() {
            addServiceToBilling(parseInt(this.dataset.id), this.dataset.name, availableServices.find(s => s.id === parseInt(this.dataset.id)));
        }));
    }

    document.getElementById('serviceFilter').addEventListener('input', function() {
        const f = this.value.toLowerCase();
        document.querySelectorAll('.service-item').forEach(item => { item.style.display = item.dataset.name.includes(f) ? '' : 'none'; });
    });

    function showServicesPlaceholder() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-list-search me-1"></i>' + escapeHtml(i18n.selectServices);
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }
    function showServicesLoading() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-loader me-1"></i>' + escapeHtml(i18n.loadingServices);
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }

    function addServiceToBilling(serviceId, serviceName, svcObj) {
        const price = resolveServicePrice(svcObj);
        const existing = selectedServices.find(s => s.service_catalog_id === serviceId);
        if (existing) { existing.quantity++; existing.price = price; }
        else selectedServices.push({ service_catalog_id: serviceId, name: serviceName, price, quantity: 1, originalService: svcObj });
        renderBillingTable(); updateDoctorsForSelectedServices();
    }

    function removeServiceFromBilling(index) { selectedServices.splice(index, 1); renderBillingTable(); updateDoctorsForSelectedServices(); }
    function updateServiceQuantity(index, qty) { if (qty < 1) { removeServiceFromBilling(index); return; } selectedServices[index].quantity = qty; renderBillingTable(); }

    function renderBillingTable() {
        const card = document.getElementById('selectedServicesCard'), tbody = document.getElementById('billingBody');
        if (selectedServices.length === 0) { card.classList.add('d-none'); tbody.innerHTML = ''; recalculateBilling(); return; }
        card.classList.remove('d-none');
        let html = '';
        selectedServices.forEach((svc, idx) => {
            html += '<tr><td>' + escapeHtml(svc.name) + '<input type="hidden" name="services[' + idx + '][service_catalog_id]" value="' + svc.service_catalog_id + '"><input type="hidden" name="services[' + idx + '][quantity]" value="' + svc.quantity + '"></td>';
            html += '<td class="text-center"><div class="input-group input-group-sm" style="width:70px;"><button type="button" class="btn btn-outline-secondary btn-xs qty-dec" data-index="' + idx + '">-</button><span class="form-control form-control-sm text-center px-1">' + svc.quantity + '</span><button type="button" class="btn btn-outline-secondary btn-xs qty-inc" data-index="' + idx + '">+</button></div></td>';
            html += '<td class="text-end text-muted">\u20B5' + formatNumber(svc.price) + '</td><td class="text-end fw-medium">\u20B5' + formatNumber(svc.price * svc.quantity) + '</td>';
            html += '<td class="text-center"><button aria-label="' + escapeHtml(i18n.delete) + '" title="' + escapeHtml(i18n.delete) + '" type="button" class="btn btn-sm btn-outline-danger remove-service-btn" data-index="' + idx + '"><i class="ti ti-trash"></i></button></td></tr>';
        });
        tbody.innerHTML = html;
        tbody.querySelectorAll('.remove-service-btn').forEach(b => b.addEventListener('click', function() { removeServiceFromBilling(parseInt(this.dataset.index)); }));
        tbody.querySelectorAll('.qty-dec').forEach(b => b.addEventListener('click', function() { updateServiceQuantity(parseInt(this.dataset.index), selectedServices[parseInt(this.dataset.index)].quantity - 1); }));
        tbody.querySelectorAll('.qty-inc').forEach(b => b.addEventListener('click', function() { updateServiceQuantity(parseInt(this.dataset.index), selectedServices[parseInt(this.dataset.index)].quantity + 1); }));
        recalculateBilling();
    }

    function recalculateBilling() {
        document.getElementById('totalAmount').textContent = '\u20B5' + formatNumber(selectedServices.reduce((s, svc) => s + svc.price * svc.quantity, 0));
    }

    function updateDoctorsForSelectedServices() {
        const ids = selectedServices.map(s => s.service_catalog_id);
        if (ids.length === 0) { repopulateDoctorSelect(allDoctors); return; }
        fetch('{{ route("admin.visits.doctors-for-services") }}?' + ids.map(id => 'service_ids[]=' + id).join('&'), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json()).then(data => repopulateDoctorSelect(data));
    }

    function repopulateDoctorSelect(doctors) {
        const cur = doctorSelect.value;
        doctorSelect.innerHTML = '<option value="">' + escapeHtml(i18n.selectDoctorOptional) + '</option>';
        doctors.forEach(doc => {
            const opt = document.createElement('option');
            opt.value = doc.id; opt.textContent = doc.name;
            if (doc.specialties && doc.specialties.length) opt.textContent += ' (' + doc.specialties.join(', ') + ')';
            if (String(doc.id) === String(cur)) opt.selected = true;
            doctorSelect.appendChild(opt);
        });
    }

    function escapeHtml(str) { return str == null ? '' : String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function formatNumber(n) { return parseFloat(n || 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
});
</script>
@endpush
