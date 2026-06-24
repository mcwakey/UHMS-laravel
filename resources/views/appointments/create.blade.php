@extends('layouts.app')
@section('title', __('appointments.schedule_title'))

@section('content')
<x-page-header-back
    :title="__('appointments.schedule_title')"
    :href="route('admin.appointments.index')"
/>

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

            <x-patient-selection-card
                :selected-patient="$selectedPatient"
                :title="__('appointments.select_patient')"
                :search-label="__('appointments.search_patient')"
                :search-placeholder="__('appointments.search_patient_placeholder')"
            />

            <x-insurance-selection-card
                :title="__('appointments.insurance')"
                :fallback-label="__('appointments.insurance_fallback_badge')"
                :loading-label="__('appointments.loading_patient_insurances')"
                :show-verification="false"
            >
                <div id="selectedInsuranceInfo" class="d-none">
                    <div class="alert alert-light border mb-0">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">{{ __('appointments.insurance_type') }}</small>
                                <span class="fw-medium" id="insInfoType">&mdash;</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">{{ __('appointments.coverage') }}</small>
                                <span class="fw-medium" id="insInfoCoverage">&mdash;</span>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted d-block">{{ __('appointments.total_billed_ytd') }}</small>
                                <span class="fw-medium" id="insInfoBilled">&mdash;</span>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted d-block">{{ __('appointments.remaining_balance') }}</small>
                                <span class="fw-bold" id="insInfoRemaining">&mdash;</span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-insurance-selection-card>

            <x-visit-details-card
                :title="__('appointments.appointment_details')"
                :visit-type-label="__('appointments.visit_type')"
                :visit-type-placeholder="__('appointments.select_type')"
                :priority-label="__('appointments.priority')"
                visit-type-value="outpatient"
                priority-value="normal"
                :visit-date-label="__('appointments.appointment_date')"
                visit-date-field-name="appointment_date"
                visit-date-id="appointmentDate"
                :visit-date-value="$appointmentDate"
                :visit-date-min="date('Y-m-d', strtotime('+1 day'))"
                :visit-date-required="true"
                :show-scheduling-fields="true"
                :show-scheduling-hint="false"
                :start-time-label="__('appointments.start_time')"
                start-time-value="09:00"
                :start-time-required="true"
                :end-time-label="__('appointments.end_time')"
                :end-time-hint="__('appointments.default_duration_hint')"
                :consultation-mode-label="__('appointments.consultation_mode')"
                :chief-complaint-label="__('appointments.chief_complaint')"
                :chief-complaint-placeholder="__('appointments.complaint_placeholder')"
                :notes-label="__('appointments.notes')"
                :notes-placeholder="__('appointments.notes_placeholder')"
                :notes-rows="3"
                :stack-textareas="false"
            />
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">

            <!-- Department, Services & Doctor -->
            <x-department-services-card
                :departments="$departments"
                :doctors="$doctors"
                :title="__('appointments.department_services_doctor')"
                :department-label="__('common.department')"
                :doctor-label="__('appointments.assign_doctor')"
                :available-services-label="__('appointments.available_services')"
                :selected-services-label="__('appointments.selected_services')"
                department-name="department_id"
                doctor-name="doctor_id"
                :department-required="true"
                :doctor-disabled-until-department="false"
                :show-extra-services-toggle="false"
                billing-table-variant="quantity"
                :department-placeholder="__('appointments.select_department')"
                :doctor-placeholder="__('appointments.select_doctor_optional')"
                :services-placeholder="__('appointments.select_dept_or_doctor_services')"
                :service-filter-placeholder="__('appointments.filter_services')"
                :estimated-total-label="__('appointments.estimated_total')"
            />
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
    const patientIdInput = document.getElementById('patientId');
    const patientInfo = document.getElementById('patientInfo');
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect = document.getElementById('doctorSelect');
    const insuranceCard = document.getElementById('insuranceCard');

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
            'lastVisitLabel' => __('visits.last_visit_label'),
            'admissionPrefix' => __('visits.admission_prefix'),
            'bedPrefix' => __('visits.bed_prefix'),
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

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function patientDisplayText(patient) {
        if (!patient) return '';
        return [patient.patient_number, patient.full_name].filter(Boolean).join(' - ') || patient.text || '';
    }

    function syncPatientSelectOption(patient) {
        if (!patient || !patient.id) return;

        const id = String(patient.id);
        const text = patientDisplayText(patient);
        let option = Array.from(searchInput.options).find(function(opt) {
            return String(opt.value) === id;
        });

        if (!option) {
            option = new Option(text, id, true, true);
            searchInput.appendChild(option);
        } else {
            option.textContent = text;
            option.selected = true;
        }

        if (hasSelect2()) {
            jQuery(searchInput).trigger('change.select2');
        }
    }

    function initPatientSearchSelect2() {
        if (!hasSelect2()) return;

        const $patient = jQuery(searchInput);
        if ($patient.hasClass('select2-hidden-accessible')) return;

        $patient.select2({
            placeholder: searchInput.dataset.placeholder || '',
            allowClear: true,
            minimumInputLength: 2,
            width: '100%',
            ajax: {
                url: '{{ route("admin.visits.patient-search") }}',
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return {
                        results: (data || []).map(function(patient) {
                            patient.id = String(patient.id);
                            patient.text = patientDisplayText(patient);
                            return patient;
                        }),
                    };
                },
                cache: true,
            },
            templateResult: function(patient) {
                if (patient.loading) return patient.text;

                return jQuery('<span>').html(
                    '<span class="fw-medium">' + escapeHtml(patient.full_name || patient.text || '') + '</span>' +
                    '<small class="text-muted d-block">' + [patient.patient_number || '', patient.phone || i18n.noPhone].filter(Boolean).map(escapeHtml).join(' &bull; ') + '</small>'
                );
            },
            templateSelection: function(patient) {
                return patientDisplayText(patient);
            },
        }).on('select2:select', function(event) {
            selectPatient(event.params.data);
        }).on('select2:clear', function() {
            clearPatient({ keepSelect: true });
        });
    }

    initPatientSearchSelect2();

    window.selectPatient = function(patient) {
        patientIdInput.value = patient.id;
        syncPatientSelectOption(patient);
        document.getElementById('patientInitial').textContent = patient.full_name.charAt(0).toUpperCase();
        document.getElementById('patientName').textContent = [patient.full_name, patient.gender].filter(Boolean).join(' \u2022 ');
        document.getElementById('patientNumber').textContent = patient.patient_number;
        document.getElementById('patientPhone').textContent = patient.phone || i18n.noPhone;

        const lastVisitEl = document.getElementById('patientLastVisit');
        if (patient.last_visit_date) {
            lastVisitEl.innerHTML = '&bull; ' + escapeHtml(i18n.lastVisitLabel) + ' <strong>' + escapeHtml(patient.last_visit_date) + '</strong>';
            lastVisitEl.classList.remove('d-none');
        } else {
            lastVisitEl.classList.add('d-none');
        }

        const deceasedWarning = document.getElementById('deceasedWarning');
        if (patient.is_deceased) {
            deceasedWarning.classList.remove('d-none');
        } else {
            deceasedWarning.classList.add('d-none');
        }

        const activeAdmissionWarning = document.getElementById('activeAdmissionWarning');
        const activeAdmissionText = document.getElementById('activeAdmissionText');
        if (patient.active_admission) {
            const admission = patient.active_admission;
            let text = i18n.admissionPrefix + ' ' + admission.admission_number;
            if (admission.ward || admission.bed) {
                text += ' - ' + [admission.ward, admission.bed ? i18n.bedPrefix + ' ' + admission.bed : null].filter(Boolean).join(' / ');
            }
            activeAdmissionText.textContent = text + '.';
            activeAdmissionWarning.classList.remove('d-none');
        } else {
            activeAdmissionText.textContent = '';
            activeAdmissionWarning.classList.add('d-none');
            const overrideReason = document.getElementById('admissionOverrideReason');
            if (overrideReason) {
                overrideReason.value = '';
            }
        }

        patientInfo.classList.remove('d-none');
        loadPatientInsurances(patient.id);
    };

    window.clearPatient = function(options) {
        options = options || {};
        patientIdInput.value = '';
        if (!options.keepSelect) {
            searchInput.value = '';
            if (hasSelect2()) {
                jQuery(searchInput).val(null).trigger('change.select2');
            }
        }
        patientInfo.classList.add('d-none'); insuranceCard.classList.add('d-none');
        document.getElementById('deceasedWarning').classList.add('d-none');
        document.getElementById('activeAdmissionWarning').classList.add('d-none');
        document.getElementById('activeAdmissionText').textContent = '';
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
