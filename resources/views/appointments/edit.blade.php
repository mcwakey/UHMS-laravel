@extends('layouts.app')
@section('title', __('appointments.edit_title') . ' - ' . $appointment->appointment_number)

@section('content')
<x-page-header-back
    :title="__('appointments.edit_title') . ' - ' . $appointment->appointment_number"
    :href="$workspaceRoutes->route('admin.appointments.show', $appointment)"
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

<form method="POST" action="{{ $workspaceRoutes->route('admin.appointments.update', $appointment) }}" id="appointmentForm">
    @csrf
    @method('PUT')

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">

            <!-- Patient (read-only) -->
            <div class="card">
                <div class="card-header">
                    <h5 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>{{ __('common.patient') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-lg bg-primary rounded-circle text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.3rem;">
                            {{ strtoupper(substr($appointment->patient->first_name, 0, 1)) }}
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $appointment->patient->full_name }}</h6>
                            <small class="text-muted">
                                {{ $appointment->patient->patient_number }}
                                &bull; <x-patient-protected-field field="phone" :value="$appointment->patient->phone" :fallback="__('visits.no_phone')" />
                                @if($appointment->patient->date_of_birth)
                                    &bull; Age {{ $appointment->patient->date_of_birth->age }}
                                @endif
                            </small>
                        </div>
                        <span class="ms-auto badge bg-{{ $appointment->status->color() }} fs-7">{{ $appointment->status->translatedLabel() }}</span>
                    </div>
                    <input type="hidden" name="patient_id" value="{{ $appointment->patient_id }}">
                </div>
            </div>

            <x-insurance-selection-card
                :hidden="false"
                :title="__('appointments.insurance')"
                :fallback-label="__('appointments.insurance_fallback_badge')"
                :loading-label="__('appointments.loading_patient_insurances')"
                :show-verification="false"
                :selected-insurance-id="$appointment->visit_insurance_id"
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
                :visit-type-value="data_get($appointment->visit_type, 'value', $appointment->visit_type)"
                :priority-value="data_get($appointment->priority, 'value', $appointment->priority ?? 'normal')"
                :visit-date-label="__('appointments.appointment_date')"
                visit-date-field-name="appointment_date"
                visit-date-id="appointmentDate"
                :visit-date-value="$appointment->appointment_date?->format('Y-m-d')"
                :visit-date-required="true"
                :show-scheduling-fields="true"
                :show-scheduling-hint="false"
                :start-time-label="__('appointments.start_time')"
                :start-time-value="$appointment->start_time"
                :start-time-required="true"
                :end-time-label="__('appointments.end_time')"
                :end-time-value="$appointment->end_time"
                :consultation-mode-label="__('appointments.consultation_mode')"
                :consultation-mode-value="data_get($appointment->consultation_mode, 'value', $appointment->consultation_mode ?? 'in_person')"
                :chief-complaint-label="__('appointments.chief_complaint')"
                :chief-complaint-value="$appointment->chief_complaint ?? $appointment->reason"
                :notes-label="__('appointments.notes')"
                :notes-value="$appointment->notes"
                :notes-rows="2"
                :stack-textareas="true"
            />
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">

            <x-department-services-card
                :departments="$departments"
                :doctors="$doctors"
                :selected-department-id="$appointment->department_id"
                :selected-doctor-id="$appointment->doctor_id"
                :selected-services-visible="$appointment->services->isNotEmpty()"
            />
            <input type="hidden" name="_services_present" value="1">

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="ti ti-device-floppy me-1"></i>{{ __('appointments.update_appointment') }}
                </button>
                <a href="{{ $workspaceRoutes->route('admin.appointments.show', $appointment) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection

@php
    $existingServicesJson = $appointment->services->map(function ($s) {
        $typePrices = [];
        $providerPrices = [];

        foreach ($s->prices as $price) {
            if ($price->insurance_provider_id === null) {
                $typePrices[$price->insurance_type] = (float) $price->price;
            } else {
                $providerPrices[$price->insurance_provider_id][$price->insurance_type] = (float) $price->price;
            }
        }

        $departmentType = $s->department?->type ?? $s->department_type;

        if ($departmentType instanceof \App\Enums\DepartmentType) {
            $departmentType = $departmentType->value;
        }

        return [
            'service_catalog_id' => $s->id,
            'name'     => $s->name,
            'price'    => (float) ($s->pivot->unit_price ?? $s->price ?? 0),
            'quantity' => (int) $s->pivot->quantity,
            'originalService' => [
                'id'       => $s->id,
                'name'     => $s->name,
                'price'    => (float) ($s->price ?? 0),
                'base_price' => (float) ($s->price ?? 0),
                'code'     => $s->code ?? '',
                'category' => $s->category ?? '',
                'department_id' => $s->department_id,
                'department_type' => (string) $departmentType,
                'provider_prices' => $providerPrices,
                'type_prices'     => $typePrices,
            ],
        ];
    });
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @php
        $appointmentEditI18nData = [
            'noInsurancesCash' => __('appointments.no_insurances_cash'),
            'member' => __('appointments.member_label'),
            'expires' => __('appointments.expires_label'),
            'noExpiry' => __('appointments.no_expiry'),
            'valid' => __('appointments.valid_status'),
            'expired' => __('appointments.expired_status'),
            'inactive' => __('appointments.inactive_status'),
            'coverage' => __('appointments.coverage'),
            'failedLoadInsurances' => __('appointments.failed_load_insurances'),
            'unlimited' => __('appointments.unlimited'),
            'add' => __('common.add'),
            'delete' => __('common.delete'),
            'selectDoctorOptional' => __('appointments.select_doctor_optional'),
        ];
    @endphp
    const i18n = @json($appointmentEditI18nData);
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect    = document.getElementById('doctorSelect');
    const showExtraServicesInput = document.getElementById('showExtraServices');

    let patientInsurances = [];
    let selectedInsurance = null;
    let availableServices = [];
    let selectedServices  = [];
    const patientId       = {{ $appointment->patient_id }};
    const currentInsId    = {{ $appointment->visit_insurance_id ?? 'null' }};

    // Existing services from the appointment
    let existingServices = {!! json_encode($existingServicesJson) !!};
    selectedServices = existingServices.map(s => Object.assign({}, s));

    function hasSelect2() {
        return window.jQuery && jQuery.fn && jQuery.fn.select2;
    }

    function refreshAppointmentSelect2(select) {
        if (!hasSelect2()) return;

        const $select = jQuery(select);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.prop('disabled', select.disabled).trigger('change.select2');
        }
    }

    function initSearchableAppointmentSelects() {
        if (!hasSelect2()) return;

        const searchableOptions = function(select, fallbackPlaceholder) {
            return {
                placeholder: select.dataset.placeholder || fallbackPlaceholder,
                allowClear: true,
                minimumResultsForSearch: 0,
                width: '100%',
            };
        };

        const $department = jQuery(departmentSelect);
        if (!$department.hasClass('select2-hidden-accessible')) {
            $department.select2(searchableOptions(departmentSelect, 'Search department...'));
            $department.on('select2:select select2:clear', function() {
                window.setTimeout(function() {
                    departmentSelect.dispatchEvent(new Event('change'));
                }, 0);
            });
        }

        const $doctor = jQuery(doctorSelect);
        if (!$doctor.hasClass('select2-hidden-accessible')) {
            $doctor.select2(searchableOptions(doctorSelect, 'Search doctor/staff...'));
        }
    }

    /* ---------- Insurance ---------- */
    function loadPatientInsurances() {
        fetch('{{ $workspaceRoutes->route("admin.visits.patient-insurances") }}?patient_id=' + patientId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            patientInsurances = data.insurances || [];
            const defaultId   = currentInsId || data.default_insurance_id;
            document.getElementById('insuranceFallbackBadge').style.display =
                (data.is_fallback && !currentInsId) ? '' : 'none';

            let html = '';
            if (patientInsurances.length === 0) {
                html = '<div class="text-muted text-center py-2">' + escapeHtml(i18n.noInsurancesCash) + '</div>';
            } else {
                html = '<div class="list-group">';
                patientInsurances.forEach(ins => {
                    const isDefault  = ins.id == defaultId;
                    const isDisabled = !ins.is_valid && !ins.is_default;
                    const badgeClass = ins.is_valid ? 'bg-success' : (ins.is_expired ? 'bg-danger' : 'bg-secondary');
                    html += `<label class="list-group-item list-group-item-action d-flex align-items-center gap-3 ${isDisabled ? 'opacity-50' : ''}">`;
                    html += `<input type="radio" name="_insurance_radio" class="form-check-input insurance-radio"
                                    value="${ins.id}" data-ins-id="${ins.id}"
                                    ${isDefault ? 'checked' : ''} ${isDisabled ? 'disabled' : ''}>`;
                    html += `<div class="flex-grow-1">
                                <div class="fw-medium">${escapeHtml(ins.provider_name)}
                                    <span class="badge bg-${ins.type_color} ms-1">${escapeHtml(ins.type_label)}</span>
                                </div>
                                <small class="text-muted">${ins.membership_number ? escapeHtml(i18n.member) + ' ' + escapeHtml(ins.membership_number) + ' &bull; ' : ''}${ins.expiry_date ? escapeHtml(i18n.expires) + ' ' + ins.expiry_date : escapeHtml(i18n.noExpiry)}</small>
                             </div>`;
                    html += `<div class="text-end">
                                <span class="badge ${badgeClass}">${ins.is_valid ? escapeHtml(i18n.valid) : (ins.is_expired ? escapeHtml(i18n.expired) : escapeHtml(i18n.inactive))}</span>
                                ${ins.coverage_percentage != null ? `<div class="small text-muted mt-1">${ins.coverage_percentage}% ${escapeHtml(i18n.coverage)}</div>` : ''}
                             </div></label>`;
                });
                html += '</div>';
            }
            document.getElementById('insuranceList').innerHTML = html;
            document.querySelectorAll('.insurance-radio').forEach(radio => {
                radio.addEventListener('change', function() { selectInsurance(parseInt(this.dataset.insId)); });
            });
            if (defaultId) selectInsurance(defaultId);
        })
        .catch(() => {
            document.getElementById('insuranceList').innerHTML =
                '<div class="text-danger text-center py-2">' + escapeHtml(i18n.failedLoadInsurances) + '</div>';
        });
    }

    function selectInsurance(insId) {
        selectedInsurance = patientInsurances.find(i => i.id === insId) || null;
        document.getElementById('visitInsuranceId').value = insId || '';
        const panel = document.getElementById('selectedInsuranceInfo');
        if (selectedInsurance) {
            panel.classList.remove('d-none');
            document.getElementById('insInfoType').innerHTML =
                `<span class="badge bg-${selectedInsurance.type_color}">${escapeHtml(selectedInsurance.type_label)}</span>`;
            document.getElementById('insInfoCoverage').textContent  = (selectedInsurance.coverage_percentage || 0) + '%';
            const rem = selectedInsurance.remaining_annual_limit;
            document.getElementById('insInfoBilled').textContent    = rem != null ? '₵' + formatNumber((selectedInsurance.annual_limit || 0) - rem) : '—';
            document.getElementById('insInfoRemaining').textContent = rem != null ? '₵' + formatNumber(rem) : i18n.unlimited;
        } else {
            panel.classList.add('d-none');
        }
        selectedServices.forEach(svc => {
            svc.price = resolveServicePrice(svc.originalService);
        });
        recalculateBilling(); renderBillingTable();
    }

    function resolveServicePrice(svc) {
        if (!svc) return 0;
        const insType       = selectedInsurance ? selectedInsurance.type : null;
        const insProviderId = selectedInsurance ? selectedInsurance.provider_id : null;
        if (insType && insProviderId && svc.provider_prices &&
            svc.provider_prices[insProviderId] &&
            svc.provider_prices[insProviderId][insType] !== undefined) {
            return svc.provider_prices[insProviderId][insType];
        }
        if (insType && svc.type_prices && svc.type_prices[insType] !== undefined) return svc.type_prices[insType];
        return svc.price;
    }

    /* ---------- Department / Services ---------- */
    departmentSelect.addEventListener('change', function() {
        if (!this.value) {
            doctorSelect.disabled = true;
            doctorSelect.innerHTML = '<option value="">' + escapeHtml(i18n.selectDoctorOptional) + '</option>';
            refreshAppointmentSelect2(doctorSelect);
            showServicesPlaceholder();
            return;
        }
        showServicesLoading();
        fetch('{{ $workspaceRoutes->route("admin.visits.department-services") }}?department_id=' + this.value, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            availableServices = data;
            selectedServices.forEach(sel => {
                const found = availableServices.find(s => s.id === sel.service_catalog_id);
                if (found) { sel.originalService = found; sel.price = resolveServicePrice(found); }
            });
            renderServicesList(); renderBillingTable();
        });
    });

    doctorSelect.addEventListener('change', function() {
        if (!this.value || departmentSelect.value) return;
        showServicesLoading();
        fetch('{{ $workspaceRoutes->route("admin.visits.services-for-doctor") }}?doctor_id=' + this.value, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => { availableServices = data; renderServicesList(); });
    });

    function renderServicesList() {
        if (availableServices.length === 0) {
            document.getElementById('servicesPlaceholder').innerHTML =
                '<i class="ti ti-info-circle me-1 text-muted"></i>No services found for this selection';
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            document.getElementById('servicesContent').classList.add('d-none'); return;
        }

        const visibleServices = getVisibleAvailableServices();
        if (visibleServices.length === 0) {
            document.getElementById('servicesPlaceholder').innerHTML =
                '<i class="ti ti-info-circle me-1 text-muted"></i>No services found for this selection';
            document.getElementById('servicesPlaceholder').classList.remove('d-none');
            document.getElementById('servicesContent').classList.add('d-none'); return;
        }

        document.getElementById('servicesPlaceholder').classList.add('d-none');
        document.getElementById('servicesContent').classList.remove('d-none');
        let html = '';
        visibleServices.forEach(svc => {
            html += `<div class="service-item d-flex align-items-center justify-content-between py-2 px-2 border-bottom bg-white rounded mb-1" data-name="${escapeHtml(svc.name.toLowerCase())}">`;
            html += `<div><span class="fw-medium">${escapeHtml(svc.name)}</span> <span class="badge bg-light text-dark ms-1">${escapeHtml(svc.code)}</span><div class="small text-muted">${escapeHtml(svc.category)}</div></div>`;
            html += `<div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-success svc-price-display" data-svc-id="${svc.id}">₵${formatNumber(resolveServicePrice(svc))}</span>
                        <button aria-label="${escapeHtml(i18n.add)}" title="${escapeHtml(i18n.add)}" type="button" class="btn btn-sm btn-outline-primary add-service-btn" data-id="${svc.id}" data-name="${escapeHtml(svc.name)}">
                            <i class="ti ti-plus"></i>
                        </button></div></div>`;
        });
        document.getElementById('servicesItems').innerHTML = html;
        document.querySelectorAll('.add-service-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                addServiceToBilling(parseInt(this.dataset.id), this.dataset.name,
                    availableServices.find(s => s.id === parseInt(this.dataset.id)));
            });
        });
    }

    function isConsultationService(svc) {
        return String(svc?.category || '').toLowerCase() === 'consultation';
    }

    function getVisibleAvailableServices() {
        if (showExtraServicesInput && showExtraServicesInput.checked) {
            return availableServices;
        }

        return availableServices.filter(isConsultationService);
    }

    document.getElementById('serviceFilter').addEventListener('input', function() {
        const f = this.value.toLowerCase();
        document.querySelectorAll('.service-item').forEach(item => {
            item.style.display = item.dataset.name.includes(f) ? '' : 'none';
        });
    });

    if (showExtraServicesInput) {
        showExtraServicesInput.addEventListener('change', function() {
            const serviceFilter = document.getElementById('serviceFilter');
            if (serviceFilter) serviceFilter.value = '';
            renderServicesList();
        });
    }

    function showServicesPlaceholder() {
        document.getElementById('servicesPlaceholder').innerHTML =
            '<i class="ti ti-list-search me-1"></i>Select a department or doctor to load services';
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }
    function showServicesLoading() {
        document.getElementById('servicesPlaceholder').innerHTML = '<i class="ti ti-loader me-1"></i>Loading services...';
        document.getElementById('servicesPlaceholder').classList.remove('d-none');
        document.getElementById('servicesContent').classList.add('d-none');
    }

    /* ---------- Billing Table ---------- */
    function addServiceToBilling(serviceId, serviceName, svcObj) {
        const price    = resolveServicePrice(svcObj);
        const existing = selectedServices.find(s => s.service_catalog_id === serviceId);
        if (existing) { existing.quantity++; existing.price = price; }
        else selectedServices.push({ service_catalog_id: serviceId, name: serviceName, price, quantity: 1, originalService: svcObj });
        renderBillingTable();
    }

    function removeServiceFromBilling(index) { selectedServices.splice(index, 1); renderBillingTable(); }
    function updateServiceQuantity(index, qty) {
        if (qty < 1) { removeServiceFromBilling(index); return; }
        selectedServices[index].quantity = qty;
        renderBillingTable();
    }

    function renderBillingTable() {
        const card  = document.getElementById('selectedServicesCard');
        const tbody = document.getElementById('billingBody');
        if (selectedServices.length === 0) { card.classList.add('d-none'); tbody.innerHTML = ''; recalculateBilling(); return; }
        card.classList.remove('d-none');
        let html = '';
        selectedServices.forEach((svc, idx) => {
            const qty = svc.quantity || 1;
            html += `<tr>
                <td>${escapeHtml(svc.name)}
                    <input type="hidden" name="services[${idx}][service_catalog_id]" value="${svc.service_catalog_id}">
                    <input type="hidden" name="services[${idx}][quantity]" value="${qty}">
                </td>
                <td class="text-end fw-medium">₵${formatNumber(svc.price * qty)}</td>
                <td class="text-center">
                    <button aria-label="${escapeHtml(i18n.delete)}" title="${escapeHtml(i18n.delete)}" type="button" class="btn btn-sm btn-outline-danger remove-service-btn" data-index="${idx}">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>`;
        });
        tbody.innerHTML = html;
        tbody.querySelectorAll('.remove-service-btn').forEach(b =>
            b.addEventListener('click', function() { removeServiceFromBilling(parseInt(this.dataset.index)); }));
        recalculateBilling();
    }

    function recalculateBilling() {
        document.getElementById('totalAmount').textContent =
            '₵' + formatNumber(selectedServices.reduce((s, svc) => s + svc.price * svc.quantity, 0));
    }

    function escapeHtml(str) {
        return str == null ? '' : String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function formatNumber(n) {
        return parseFloat(n || 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Initial
    initSearchableAppointmentSelects();
    loadPatientInsurances();
    if (departmentSelect.value) departmentSelect.dispatchEvent(new Event('change'));
    renderBillingTable();
});
</script>
@endpush
