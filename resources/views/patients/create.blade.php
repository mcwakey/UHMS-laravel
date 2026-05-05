@extends('layouts.app')
@section('title', 'Register Patient')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Register New Patient</h4>
    </div>
    <div>
        <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Back to Patients
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.patients.store') }}" enctype="multipart/form-data">
    @csrf

    <!-- Personal Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-user me-1"></i>Personal Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-12 mb-3">
                    <label class="form-label mb-1 fw-medium">Profile Image</label>
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xxl rounded-circle bg-light text-muted me-3 d-flex align-items-center justify-content-center" id="avatar-preview">
                            <i class="ti ti-user-plus fs-16"></i>
                        </div>
                        <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*" style="max-width: 300px;">
                        @error('avatar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Names</label>
                    <input type="text" name="other_names" class="form-control @error('other_names') is-invalid @enderror" value="{{ old('other_names') }}">
                    @error('other_names')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" value="{{ old('date_of_birth') }}" required>
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        <option value="">Select Gender</option>
                        @foreach(\App\Enums\Gender::cases() as $g)
                            <option value="{{ $g->value }}" {{ old('gender') == $g->value ? 'selected' : '' }}>{{ $g->label() }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Marital Status</label>
                    <select name="marital_status" class="form-select @error('marital_status') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(\App\Enums\MaritalStatus::cases() as $ms)
                            <option value="{{ $ms->value }}" {{ old('marital_status') == $ms->value ? 'selected' : '' }}>{{ $ms->label() }}</option>
                        @endforeach
                    </select>
                    @error('marital_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Religion</label>
                    <select name="religion" class="form-select @error('religion') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(['Christianity', 'Islam', 'Traditional', 'Hindu', 'Buddhist', 'Other', 'None'] as $rel)
                            <option value="{{ $rel }}" {{ old('religion') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    @error('religion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select @error('blood_group') is-invalid @enderror">
                        <option value="">Select</option>
                        @foreach(\App\Enums\BloodGroup::cases() as $bg)
                            <option value="{{ $bg->value }}" {{ old('blood_group') == $bg->value ? 'selected' : '' }}>{{ $bg->label() }}</option>
                        @endforeach
                    </select>
                    @error('blood_group')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Occupation</label>
                    <select name="occupation" class="form-select @error('occupation') is-invalid @enderror">
                        <option value="">Select Occupation</option>
                        @foreach(['Accountant','Architect','Artist','Baker','Banker','Barber','Business Owner','Carpenter','Cashier','Chef','Civil Servant','Clergy','Cleaner','Construction Worker','Consultant','Dentist','Doctor','Driver','Electrician','Engineer','Farmer','Fisherman','Graphic Designer','Hairdresser','Journalist','Lawyer','Lecturer','Mechanic','Miner','Musician','Nurse','Pharmacist','Photographer','Pilot','Plumber','Police Officer','Politician','Programmer','Retired','Salesperson','Secretary','Security Guard','Social Worker','Student','Surveyor','Tailor','Teacher','Technician','Trader','Unemployed','Veterinarian','Welder','Other'] as $occ)
                            <option value="{{ $occ }}" {{ old('occupation') == $occ ? 'selected' : '' }}>{{ $occ }}</option>
                        @endforeach
                    </select>
                    @error('occupation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Contact & Identification -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-phone me-1"></i>Contact & Identification</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}" placeholder="e.g. 0241234567" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Secondary Phone</label>
                    <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary') }}">
                    @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Ghana Card Number</label>
                    <input type="text" name="ghana_card_number" class="form-control @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number') }}" placeholder="GHA-XXXXXXXXX-X">
                    @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Address Information -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-map-pin me-1"></i>Address Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address') }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}">
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Town</label>
                    <input type="text" name="town" class="form-control @error('town') is-invalid @enderror" value="{{ old('town') }}">
                    @error('town')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-select @error('region') is-invalid @enderror">
                        <option value="">Select Region</option>
                        @foreach(['Greater Accra', 'Ashanti', 'Western', 'Central', 'Eastern', 'Volta', 'Northern', 'Upper East', 'Upper West', 'Bono', 'Bono East', 'Ahafo', 'Western North', 'Oti', 'North East', 'Savannah'] as $region)
                            <option value="{{ $region }}" {{ old('region') == $region ? 'selected' : '' }}>{{ $region }}</option>
                        @endforeach
                    </select>
                    @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Digital Address (GPS)</label>
                    <input type="text" name="digital_address" class="form-control @error('digital_address') is-invalid @enderror" value="{{ old('digital_address') }}" placeholder="e.g. GA-123-4567">
                    @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contacts -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="fw-bold mb-0"><i class="ti ti-urgent me-1"></i>Emergency Contacts</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-ec-btn">
                <i class="ti ti-plus me-1"></i>Add Another
            </button>
        </div>
        <div class="card-body">
            <div id="ec-wrapper">
                <div class="ec-row border rounded p-3 mb-2" data-index="0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-medium small text-muted ec-label">Contact #1 <span class="badge bg-primary ms-1">Primary</span></span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-ec d-none"><i class="ti ti-trash"></i></button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Name <span class="text-danger">*</span></label>
                            <input type="text" name="emergency_contacts[0][name]" class="form-control form-control-sm @error('emergency_contacts.0.name') is-invalid @enderror" value="{{ old('emergency_contacts.0.name') }}" placeholder="Full name">
                            @error('emergency_contacts.0.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="emergency_contacts[0][phone]" class="form-control form-control-sm @error('emergency_contacts.0.phone') is-invalid @enderror" value="{{ old('emergency_contacts.0.phone') }}" placeholder="e.g. 0241234567">
                            @error('emergency_contacts.0.phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Secondary Phone</label>
                            <input type="tel" name="emergency_contacts[0][phone_secondary]" class="form-control form-control-sm" value="{{ old('emergency_contacts.0.phone_secondary') }}" placeholder="Optional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Relationship</label>
                            <select name="emergency_contacts[0][relationship]" class="form-select form-select-sm">
                                <option value="">Select</option>
                                @foreach(['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'] as $rel)
                                    <option value="{{ $rel }}" {{ old('emergency_contacts.0.relationship') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <small class="text-muted"><i class="ti ti-info-circle me-1"></i>The first contact will be set as primary. Additional contacts can always be managed from the patient's profile.</small>
        </div>
    </div>

    <!-- Insurance -->
    @php
        $registrationInsuranceTypes = $insuranceProviders
            ->where('is_default', false)
            ->pluck('type')
            ->filter()
            ->unique(fn ($type) => $type instanceof \BackedEnum ? $type->value : (string) $type)
            ->sortBy(fn ($type) => $type instanceof \BackedEnum ? $type->label() : ucfirst((string) $type));
    @endphp
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-shield-check me-1"></i>Insurance</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info small py-2 mb-3">
                <i class="ti ti-info-circle me-1"></i>
                <strong>Cash &amp; Carry</strong> is applied by default for all visits. Add insurance plans here if the patient is insured.
            </div>
            <div id="ins-wrapper">
                <div class="ins-row border rounded p-3 mb-2" data-index="0">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-medium small text-muted ins-label">Insurance #1 <span class="badge bg-primary ms-1">Primary</span></span>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-ins d-none"><i class="ti ti-trash"></i></button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Insurance Type</label>
                            <select name="insurances[0][type]" class="form-select form-select-sm ins-type" data-idx="0" data-selected-provider="{{ old('insurances.0.provider_id') }}" data-selected-tier="{{ old('insurances.0.insurance_tier_id') }}">
                                <option value="">— None —</option>
                                @foreach($registrationInsuranceTypes as $type)
                                    @php
                                        $typeValue = $type instanceof \BackedEnum ? $type->value : (string) $type;
                                        $typeLabel = method_exists($type, 'label') ? $type->label() : ucfirst($typeValue);
                                    @endphp
                                    <option value="{{ $typeValue }}" {{ old('insurances.0.type') === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                            @error('insurances.0.type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Provider</label>
                            <select name="insurances[0][provider_id]" class="form-select form-select-sm ins-provider" data-idx="0" disabled>
                                <option value="">Select type first</option>
                            </select>
                            @error('insurances.0.provider_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Tier</label>
                            <select name="insurances[0][insurance_tier_id]" class="form-select form-select-sm ins-tier" data-idx="0" disabled>
                                <option value="">Select provider first</option>
                            </select>
                            @error('insurances.0.insurance_tier_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 ins-row-extra" style="display:none">
                            <label class="form-label form-label-sm">Membership / Card Number</label>
                            <input type="text" name="insurances[0][membership_number]" class="form-control form-control-sm" value="{{ old('insurances.0.membership_number') }}" placeholder="e.g. NHIS-123456789">
                        </div>
                        <div class="col-md-4 ins-row-extra" style="display:none">
                            <label class="form-label form-label-sm">Policy Number</label>
                            <input type="text" name="insurances[0][policy_number]" class="form-control form-control-sm" value="{{ old('insurances.0.policy_number') }}">
                        </div>
                        <div class="col-md-4 ins-row-extra" style="display:none">
                            <label class="form-label form-label-sm">Expiry Date</label>
                            <input type="date" name="insurances[0][expiry_date]" class="form-control form-control-sm" value="{{ old('insurances.0.expiry_date') }}">
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="add-ins-btn">
                <i class="ti ti-plus me-1"></i>Add Another Insurance
            </button>
        </div>
    </div>

    <!-- Medical Notes -->
    <div class="card">
        <div class="card-header">
            <h5 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>Medical Notes</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Known Allergies</label>
                    <textarea name="allergies" class="form-control @error('allergies') is-invalid @enderror" rows="3" placeholder="List any known allergies...">{{ old('allergies') }}</textarea>
                    @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Chronic Conditions</label>
                    <textarea name="chronic_conditions" class="form-control @error('chronic_conditions') is-invalid @enderror" rows="3" placeholder="List any chronic conditions...">{{ old('chronic_conditions') }}</textarea>
                    @error('chronic_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Register Patient</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    // ── Emergency contacts ─────────────────────────────────────────────
    @php $ecRelationships = ['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other']; @endphp
    const relationships = {!! json_encode($ecRelationships) !!};
    let ecCount = 1;

    function buildEcOptions(selected) {
        return relationships.map(r =>
            `<option value="${r}"${r === selected ? ' selected' : ''}>${r}</option>`
        ).join('');
    }

    document.getElementById('add-ec-btn').addEventListener('click', function () {
        const idx = ecCount++;
        const row = document.createElement('div');
        row.className = 'ec-row border rounded p-3 mb-2';
        row.dataset.index = idx;
        row.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium small text-muted ec-label">Contact #${idx + 1}</span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-ec"><i class="ti ti-trash"></i></button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Name <span class="text-danger">*</span></label>
                    <input type="text" name="emergency_contacts[${idx}][name]" class="form-control form-control-sm" placeholder="Full name">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="emergency_contacts[${idx}][phone]" class="form-control form-control-sm" placeholder="e.g. 0241234567">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Secondary Phone</label>
                    <input type="tel" name="emergency_contacts[${idx}][phone_secondary]" class="form-control form-control-sm" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Relationship</label>
                    <select name="emergency_contacts[${idx}][relationship]" class="form-select form-select-sm">
                        <option value="">Select</option>
                        ${buildEcOptions('')}
                    </select>
                </div>
            </div>`;
        document.getElementById('ec-wrapper').appendChild(row);
    });

    document.getElementById('ec-wrapper').addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-ec');
        if (btn) {
            btn.closest('.ec-row').remove();
            renumberEcRows();
        }
    });

    function renumberEcRows() {
        document.querySelectorAll('#ec-wrapper .ec-row').forEach((row, i) => {
            const label = row.querySelector('.ec-label');
            label.innerHTML = i === 0
                ? `Contact #1 <span class="badge bg-primary ms-1">Primary</span>`
                : `Contact #${i + 1}`;
            const btn = row.querySelector('.remove-ec');
            if (btn) btn.classList.toggle('d-none', i === 0);
        });
    }

    // ── Insurance rows ─────────────────────────────────────────────────
    @php
        $registrationInsuranceTypeOptions = $registrationInsuranceTypes->map(function ($type) {
            $typeValue = $type instanceof \BackedEnum ? $type->value : (string) $type;
            return [
                'value' => $typeValue,
                'label' => method_exists($type, 'label') ? $type->label() : ucfirst($typeValue),
            ];
        })->values();
    @endphp
    const insTypes = {!! json_encode($registrationInsuranceTypeOptions) !!};
    const providerByTypeUrl = '{{ route("admin.insurance-providers.by-type") }}';
    const tiersForProviderUrl = '{{ route("admin.insurance-providers.tiers.for-patient", ":pid") }}';
    let insCount = 1;

    function escapeOptionText(value) {
        const span = document.createElement('span');
        span.textContent = value ?? '';
        return span.innerHTML;
    }

    function buildInsTypeOptions(selected) {
        return insTypes.map(t =>
            `<option value="${t.value}"${t.value == selected ? ' selected' : ''}>${escapeOptionText(t.label)}</option>`
        ).join('');
    }

    function resetInsProvider(row, message = 'Select type first') {
        const provider = row.querySelector('.ins-provider');
        provider.innerHTML = `<option value="">${message}</option>`;
        provider.disabled = true;
    }

    function resetInsTier(row, message = 'Select provider first') {
        const tier = row.querySelector('.ins-tier');
        tier.innerHTML = `<option value="">${message}</option>`;
        tier.disabled = true;
    }

    function toggleInsuranceExtras(row, show) {
        row.querySelectorAll('.ins-row-extra').forEach(el => el.style.display = show ? '' : 'none');
    }

    function loadProvidersForRow(row, selectedProvider = '', selectedTier = '') {
        const type = row.querySelector('.ins-type').value;
        const provider = row.querySelector('.ins-provider');

        resetInsProvider(row, type ? 'Loading providers...' : 'Select type first');
        resetInsTier(row);
        toggleInsuranceExtras(row, false);

        if (!type) return;

        fetch(providerByTypeUrl + '?type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(providers => {
            if (!providers.length) {
                resetInsProvider(row, 'No providers for type');
                return;
            }

            provider.innerHTML = '<option value="">Select Provider</option>';
            providers.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.short_name ? p.name + ' (' + p.short_name + ')' : p.name;
                provider.appendChild(opt);
            });
            provider.disabled = false;

            if (selectedProvider) {
                provider.value = selectedProvider;
                if (provider.value) {
                    toggleInsuranceExtras(row, true);
                    loadTiersForRow(row, selectedTier);
                }
            }
        })
        .catch(() => resetInsProvider(row, 'Failed to load providers'));
    }

    function loadTiersForRow(row, selectedTier = '') {
        const providerId = row.querySelector('.ins-provider').value;
        const tier = row.querySelector('.ins-tier');

        if (!providerId) {
            resetInsTier(row);
            toggleInsuranceExtras(row, false);
            return;
        }

        tier.innerHTML = '<option value="">Loading tiers...</option>';
        tier.disabled = true;
        toggleInsuranceExtras(row, true);

        fetch(tiersForProviderUrl.replace(':pid', providerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(tiers => {
            if (!tiers.length) {
                resetInsTier(row, 'No tiers available');
                return;
            }

            tier.innerHTML = '<option value="">Select Tier</option>';
            tiers.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.coverage_percentage ? ' (' + t.coverage_percentage + '% coverage)' : '');
                if ((selectedTier && selectedTier == t.id) || (!selectedTier && t.is_default)) opt.selected = true;
                tier.appendChild(opt);
            });
            tier.disabled = false;
        })
        .catch(() => resetInsTier(row, 'Failed to load tiers'));
    }

    // Cascade insurance type -> provider -> tier per row.
    document.getElementById('ins-wrapper').addEventListener('change', function (e) {
        if (e.target.classList.contains('ins-type')) {
            loadProvidersForRow(e.target.closest('.ins-row'));
        }

        if (e.target.classList.contains('ins-provider')) {
            const row = e.target.closest('.ins-row');
            loadTiersForRow(row);
        }
    });

    // Restore cascade state on page load (after validation failure)
    document.querySelectorAll('.ins-row').forEach(row => {
        const type = row.querySelector('.ins-type');
        if (type && type.value) {
            loadProvidersForRow(row, type.dataset.selectedProvider || '', type.dataset.selectedTier || '');
        }
    });

    document.getElementById('add-ins-btn').addEventListener('click', function () {
        const idx = insCount++;
        const row = document.createElement('div');
        row.className = 'ins-row border rounded p-3 mb-2';
        row.dataset.index = idx;
        row.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-medium small text-muted ins-label">Insurance #${idx + 1}</span>
                <button type="button" class="btn btn-sm btn-outline-danger remove-ins"><i class="ti ti-trash"></i></button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Insurance Type</label>
                    <select name="insurances[${idx}][type]" class="form-select form-select-sm ins-type" data-idx="${idx}">
                        <option value="">— None —</option>
                        ${buildInsTypeOptions('')}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Provider</label>
                    <select name="insurances[${idx}][provider_id]" class="form-select form-select-sm ins-provider" data-idx="${idx}" disabled>
                        <option value="">Select type first</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Tier</label>
                    <select name="insurances[${idx}][insurance_tier_id]" class="form-select form-select-sm ins-tier" data-idx="${idx}" disabled>
                        <option value="">Select provider first</option>
                    </select>
                </div>
                <div class="col-md-4 ins-row-extra" style="display:none">
                    <label class="form-label form-label-sm">Membership / Card Number</label>
                    <input type="text" name="insurances[${idx}][membership_number]" class="form-control form-control-sm" placeholder="e.g. NHIS-123456789">
                </div>
                <div class="col-md-4 ins-row-extra" style="display:none">
                    <label class="form-label form-label-sm">Policy Number</label>
                    <input type="text" name="insurances[${idx}][policy_number]" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 ins-row-extra" style="display:none">
                    <label class="form-label form-label-sm">Expiry Date</label>
                    <input type="date" name="insurances[${idx}][expiry_date]" class="form-control form-control-sm">
                </div>
            </div>`;
        document.getElementById('ins-wrapper').appendChild(row);
        row.querySelector('.remove-ins').addEventListener('click', function () {
            row.remove();
            renumberInsRows();
        });
    });

    document.getElementById('ins-wrapper').addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-ins');
        if (btn) {
            btn.closest('.ins-row').remove();
            renumberInsRows();
        }
    });

    function renumberInsRows() {
        document.querySelectorAll('#ins-wrapper .ins-row').forEach((row, i) => {
            const label = row.querySelector('.ins-label');
            label.innerHTML = i === 0
                ? `Insurance #1 <span class="badge bg-primary ms-1">Primary</span>`
                : `Insurance #${i + 1}`;
            const btn = row.querySelector('.remove-ins');
            if (btn) btn.classList.toggle('d-none', i === 0);
        });
    }
})();
</script>
@endpush
@endsection
