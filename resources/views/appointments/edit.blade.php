@extends('layouts.app')
@section('title', __('appointments.edit_title') . ' - ' . $appointment->appointment_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('appointments.edit_title') }} <span class="text-muted fw-normal fs-5">{{ $appointment->appointment_number }}</span></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>{{ __('appointments.back_to_appointment') }}
        </a>
    </div>
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

<form method="POST" action="{{ route('admin.appointments.update', $appointment) }}" id="appointmentForm">
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
                                &bull; {{ $appointment->patient->phone ?? 'No phone' }}
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

            <!-- Insurance Selection -->
            <div class="card" id="insuranceCard">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>{{ __('appointments.insurance') }}</h5>
                    <span class="badge bg-warning text-dark" id="insuranceFallbackBadge" style="display:none;">Default expired — using Cash &amp; Carry</span>
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
                    <input type="hidden" name="visit_insurance_id" id="visitInsuranceId" value="{{ old('visit_insurance_id', $appointment->visit_insurance_id) }}">
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
                                @foreach(\App\Enums\VisitType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ old('visit_type', $appointment->visit_type->value ?? '') == $type->value ? 'selected' : '' }}>{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                            @error('visit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.priority') }} <span class="text-danger">*</span></label>
                            <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                @foreach(\App\Enums\Priority::cases() as $priority)
                                    <option value="{{ $priority->value }}" {{ old('priority', $appointment->priority ?? 'normal') == $priority->value ? 'selected' : '' }}>{{ $priority->translatedLabel() }}</option>
                                @endforeach
                            </select>
                            @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.appointment_date') }} <span class="text-danger">*</span></label>
                            <input type="date" name="appointment_date" class="form-control @error('appointment_date') is-invalid @enderror"
                                   value="{{ old('appointment_date', $appointment->appointment_date->format('Y-m-d')) }}" required>
                            @error('appointment_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.start_time') }} <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
                                   value="{{ old('start_time', \Carbon\Carbon::parse($appointment->start_time)->format('H:i')) }}" required>
                            @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.end_time') }}</label>
                            <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror"
                                   value="{{ old('end_time', $appointment->end_time ? \Carbon\Carbon::parse($appointment->end_time)->format('H:i') : '') }}">
                            @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('appointments.consultation_mode') }}</label>
                            <select name="consultation_mode" class="form-select @error('consultation_mode') is-invalid @enderror">
                                @foreach(\App\Enums\ConsultationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" {{ old('consultation_mode', $appointment->consultation_mode ?? 'in_person') == $mode->value ? 'selected' : '' }}>{{ $mode->translatedLabel() }}</option>
                                @endforeach
                            </select>
                            @error('consultation_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('appointments.chief_complaint') }}</label>
                        <textarea name="chief_complaint" class="form-control @error('chief_complaint') is-invalid @enderror" rows="3">{{ old('chief_complaint', $appointment->chief_complaint ?? $appointment->reason) }}</textarea>
                        @error('chief_complaint')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('appointments.notes') }}</label>
                        <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $appointment->notes) }}</textarea>
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
                                    <option value="{{ $dept->id }}" {{ old('department_id', $appointment->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('appointments.assign_doctor') }}</label>
                            <select name="doctor_id" id="doctorSelect" class="form-select @error('doctor_id') is-invalid @enderror">
                                <option value="">{{ __('appointments.select_doctor_optional') }}</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}" {{ old('doctor_id', $appointment->doctor_id) == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->full_name }}</option>
                                @endforeach
                            </select>
                            @error('doctor_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('appointments.available_services') }}</label>
                        <div id="servicesList" class="border rounded p-3 bg-light">
                            <div class="text-muted text-center py-3" id="servicesPlaceholder">
                                <i class="ti ti-list-search me-1"></i>{{ __('appointments.select_dept_or_doctor_load_services') }}
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

                    <div id="selectedServicesCard" class="{{ $appointment->services->isNotEmpty() ? '' : 'd-none' }}">
                        <label class="form-label fw-bold"><i class="ti ti-receipt me-1"></i>{{ __('appointments.selected_services') }}</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" id="billingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('appointments.service') }}</th>
                                        <th class="text-center" style="width:70px;">{{ __('appointments.qty') }}</th>
                                        <th class="text-end" style="width:100px;">{{ __('appointments.unit_price') }}</th>
                                        <th class="text-end" style="width:100px;">{{ __('common.total') }}</th>
                                        <th style="width:36px;"></th>
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

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="ti ti-device-floppy me-1"></i>{{ __('appointments.update_appointment') }}
                </button>
                <a href="{{ route('admin.appointments.show', $appointment) }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
            </div>
        </div>
    </div>
</form>
@endsection

@php
    $existingServicesJson = $appointment->services->map(fn($s) => [
        'service_catalog_id' => $s->id,
        'name'     => $s->name,
        'price'    => (float) ($s->price ?? 0),
        'quantity' => (int) $s->pivot->quantity,
        'originalService' => [
            'id'       => $s->id,
            'name'     => $s->name,
            'price'    => (float) ($s->price ?? 0),
            'code'     => $s->code ?? '',
            'category' => $s->category ?? '',
            'provider_prices' => [],
            'type_prices'     => [],
        ],
    ]);
@endphp
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const departmentSelect = document.getElementById('departmentSelect');
    const doctorSelect    = document.getElementById('doctorSelect');

    let patientInsurances = [];
    let selectedInsurance = null;
    let availableServices = [];
    let selectedServices  = [];
    const patientId       = {{ $appointment->patient_id }};
    const currentInsId    = {{ $appointment->visit_insurance_id ?? 'null' }};

    // Existing services from the appointment
    let existingServices = {!! json_encode($existingServicesJson) !!};
    selectedServices = existingServices.map(s => Object.assign({}, s));

    /* ---------- Insurance ---------- */
    function loadPatientInsurances() {
        fetch('{{ route("admin.visits.patient-insurances") }}?patient_id=' + patientId, {
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
                html = '<div class="text-muted text-center py-2">No insurances found. Defaulting to Cash &amp; Carry.</div>';
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
                                <small class="text-muted">${ins.membership_number ? 'Member: ' + escapeHtml(ins.membership_number) + ' &bull; ' : ''}${ins.expiry_date ? 'Expires: ' + ins.expiry_date : 'No expiry'}</small>
                             </div>`;
                    html += `<div class="text-end">
                                <span class="badge ${badgeClass}">${ins.is_valid ? 'Valid' : (ins.is_expired ? 'Expired' : 'Inactive')}</span>
                                ${ins.coverage_percentage != null ? `<div class="small text-muted mt-1">${ins.coverage_percentage}% coverage</div>` : ''}
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
                '<div class="text-danger text-center py-2">Failed to load insurances.</div>';
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
            document.getElementById('insInfoRemaining').textContent = rem != null ? '₵' + formatNumber(rem) : 'Unlimited';
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
        if (!this.value) { showServicesPlaceholder(); return; }
        showServicesLoading();
        fetch('{{ route("admin.visits.department-services") }}?department_id=' + this.value, {
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
        fetch('{{ route("admin.visits.services-for-doctor") }}?doctor_id=' + this.value, {
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
        document.getElementById('servicesPlaceholder').classList.add('d-none');
        document.getElementById('servicesContent').classList.remove('d-none');
        let html = '';
        availableServices.forEach(svc => {
            html += `<div class="service-item d-flex align-items-center justify-content-between py-2 px-2 border-bottom bg-white rounded mb-1" data-name="${escapeHtml(svc.name.toLowerCase())}">`;
            html += `<div><span class="fw-medium">${escapeHtml(svc.name)}</span> <span class="badge bg-light text-dark ms-1">${escapeHtml(svc.code)}</span><div class="small text-muted">${escapeHtml(svc.category)}</div></div>`;
            html += `<div class="d-flex align-items-center gap-2">
                        <span class="fw-bold text-success svc-price-display" data-svc-id="${svc.id}">₵${formatNumber(resolveServicePrice(svc))}</span>
                        <button aria-label="Add" title="Add" type="button" class="btn btn-sm btn-outline-primary add-service-btn" data-id="${svc.id}" data-name="${escapeHtml(svc.name)}">
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

    document.getElementById('serviceFilter').addEventListener('input', function() {
        const f = this.value.toLowerCase();
        document.querySelectorAll('.service-item').forEach(item => {
            item.style.display = item.dataset.name.includes(f) ? '' : 'none';
        });
    });

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
            html += `<tr>
                <td>${escapeHtml(svc.name)}
                    <input type="hidden" name="services[${idx}][service_catalog_id]" value="${svc.service_catalog_id}">
                    <input type="hidden" name="services[${idx}][quantity]" value="${svc.quantity}">
                </td>
                <td class="text-center">
                    <div class="input-group input-group-sm" style="width:70px;">
                        <button type="button" class="btn btn-outline-secondary btn-xs qty-dec" data-index="${idx}">-</button>
                        <span class="form-control form-control-sm text-center px-1">${svc.quantity}</span>
                        <button type="button" class="btn btn-outline-secondary btn-xs qty-inc" data-index="${idx}">+</button>
                    </div>
                </td>
                <td class="text-end text-muted">₵${formatNumber(svc.price)}</td>
                <td class="text-end fw-medium">₵${formatNumber(svc.price * svc.quantity)}</td>
                <td class="text-center">
                    <button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-service-btn" data-index="${idx}">
                        <i class="ti ti-trash"></i>
                    </button>
                </td>
            </tr>`;
        });
        tbody.innerHTML = html;
        tbody.querySelectorAll('.remove-service-btn').forEach(b =>
            b.addEventListener('click', function() { removeServiceFromBilling(parseInt(this.dataset.index)); }));
        tbody.querySelectorAll('.qty-dec').forEach(b =>
            b.addEventListener('click', function() { updateServiceQuantity(parseInt(this.dataset.index), selectedServices[parseInt(this.dataset.index)].quantity - 1); }));
        tbody.querySelectorAll('.qty-inc').forEach(b =>
            b.addEventListener('click', function() { updateServiceQuantity(parseInt(this.dataset.index), selectedServices[parseInt(this.dataset.index)].quantity + 1); }));
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
    loadPatientInsurances();
    if (departmentSelect.value) departmentSelect.dispatchEvent(new Event('change'));
    renderBillingTable();
});
</script>
@endpush
