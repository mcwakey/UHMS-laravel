@extends('layouts.app')
@section('title', __('patients.register_patient'))

@section('content')
<!-- Page Header -->
<x-page-header-back
    :title="__('patients.register_patient')"
    :href="$workspaceRoutes->route('admin.patients.index')"
/>

@php
    $phonePattern = $countrySettings['phone_pattern'] ?? null;
    $phonePlaceholder = $countrySettings['phone_placeholder'] ?? '';
    $digitalAddressPattern = $countrySettings['digital_address_pattern'] ?? null;
    $digitalAddressPlaceholder = $countrySettings['digital_address_placeholder'] ?? '';
@endphp

<form method="POST" action="{{ $workspaceRoutes->route('admin.patients.store') }}" enctype="multipart/form-data">
    @csrf

    <x-patient-personal-information-card :occupations="$occupations" />

    <x-patient-contact-identification-card
        :phone-pattern="$phonePattern"
        :phone-placeholder="$phonePlaceholder"
    />

    <x-patient-address-information-card
        :regions="$regions"
        :digital-address-pattern="$digitalAddressPattern"
        :digital-address-placeholder="$digitalAddressPlaceholder"
    />

    <x-patient-emergency-contacts-card
        :phone-pattern="$phonePattern"
        :phone-placeholder="$phonePlaceholder"
    />
    <!-- Insurance -->
    @php
        $registrationInsuranceTypes = $insuranceProviders
            ->where('is_default', false)
            ->map(function ($provider) {
                $value = $provider->type instanceof \BackedEnum
                    ? $provider->type->value
                    : ((string) ($provider->type ?: strtolower((string) $provider->insuranceType?->code)));

                return [
                    'value' => $value,
                    'label' => $provider->type instanceof \BackedEnum && method_exists($provider->type, 'label')
                        ? $provider->type->label()
                        : ($provider->insuranceType?->name ?? ucfirst($value)),
                ];
            })
            ->filter(fn ($type) => filled($type['value']))
            ->unique('value')
            ->sortBy('label')
            ->values();

        $insuranceI18n = [
            'select_type_first' => __('patients.select_type_first'),
            'loading_providers' => __('patients.loading_providers'),
            'no_providers'      => __('patients.no_providers'),
            'select_provider'   => __('patients.select_provider'),
            'loading_tiers'     => __('patients.loading_tiers'),
            'no_tiers'          => __('patients.no_tiers'),
            'select_tier'       => __('patients.select_tier'),
        ];
    @endphp
    <x-patient-registration-insurance-card :registration-insurance-types="$registrationInsuranceTypes" />

    <x-patient-medical-notes-card />
    <!-- Submit -->
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a href="{{ $workspaceRoutes->route('admin.patients.index') }}" class="btn btn-outline-secondary">{{ __('common.cancel') }}</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('patients.register_patient') }}</button>
    </div>
</form>

@push('scripts')
@include('patients.partials.avatar-camera-scripts')
@include('patients.partials.registration-input-scripts')
<script>
(function () {
    // ── Emergency contacts ─────────────────────────────────────────────
    @php $ecRelationships = ['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other']; @endphp
    const relationships = {!! json_encode($ecRelationships) !!};
    @php
        $ecI18n = [
            'contact'  => __('patients.contact_name'),
            'primary'  => __('patients.primary_contact_badge'),
            'name'     => __('common.name'),
            'phone'    => __('common.phone'),
            'secondary_phone' => __('patients.secondary_phone'),
            'optional' => __('common.optional'),
            'relationship' => __('patients.relationship'),
            'select'   => __('patients.select'),
        ];
    @endphp
    const ecI18n = @json($ecI18n);
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
                <span class="fw-medium small text-muted ec-label">${ecI18n.contact} #${idx + 1}</span>
                <button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-ec"><i class="ti ti-trash"></i></button>
            </div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label form-label-sm">${ecI18n.name} <span class="text-danger">*</span></label>
                    <input type="text" name="emergency_contacts[${idx}][name]" class="form-control form-control-sm" placeholder="${ecI18n.name}">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">${ecI18n.phone} <span class="text-danger">*</span></label>
                    <input type="tel" name="emergency_contacts[${idx}][phone]" class="form-control form-control-sm js-phone-mask" placeholder="{{ $phonePlaceholder }}" inputmode="tel" @if($phonePattern) pattern="{{ $phonePattern }}" @endif>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">${ecI18n.secondary_phone}</label>
                    <input type="tel" name="emergency_contacts[${idx}][phone_secondary]" class="form-control form-control-sm" placeholder="${ecI18n.optional}" inputmode="tel">
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm">${ecI18n.relationship}</label>
                    <select name="emergency_contacts[${idx}][relationship]" class="form-select form-select-sm">
                        <option value="">${ecI18n.select}</option>
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
                ? `${ecI18n.contact} #1 <span class="badge bg-primary ms-1">${ecI18n.primary}</span>`
                : `${ecI18n.contact} #${i + 1}`;
            const btn = row.querySelector('.remove-ec');
            if (btn) btn.classList.toggle('d-none', i === 0);
        });
    }

    // ── Insurance rows ─────────────────────────────────────────────────
    @php
        $registrationInsuranceTypeOptions = $registrationInsuranceTypes;
    @endphp
    const insTypes = {!! json_encode($registrationInsuranceTypeOptions) !!};
    const providerByTypeUrl = '{{ route("admin.insurance-providers.by-type") }}';
    const tiersForProviderUrl = '{{ route("admin.insurance-providers.tiers.for-patient", ":pid") }}';
    const registrationInsuranceI18n = @json($insuranceI18n);
    @php
        $insI18n = [
            'insurance'      => __('patients.tab_insurance'),
            'primary'        => __('patients.primary_contact_badge'),
            'type'           => __('patients.insurance_type'),
            'provider'       => __('patients.insurance_provider_label'),
            'tier'           => __('patients.insurance_tier'),
            'membership'     => __('patients.membership_card_number'),
            'policy'         => __('patients.policy_number'),
            'expiry'         => __('patients.expiry_date'),
        ];
    @endphp
    const insI18n = @json($insI18n);
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

    function resetInsProvider(row, message) {
        if (message === undefined) message = registrationInsuranceI18n.select_type_first;
        const provider = row.querySelector('.ins-provider');
        provider.innerHTML = `<option value="">${message}</option>`;
        provider.disabled = true;
    }

    function resetInsTier(row, message) {
        if (message === undefined) message = registrationInsuranceI18n.select_type_first;
        const tier = row.querySelector('.ins-tier');
        tier.innerHTML = `<option value="">${message}</option>`;
        tier.disabled = true;
    }

    function toggleInsuranceExtras(row, show) {
        row.querySelectorAll('.ins-row-extra').forEach(el => el.style.display = show ? '' : 'none');
    }

    function loadProvidersForRow(row, selectedProvider, selectedTier) {
        if (selectedProvider === undefined) selectedProvider = '';
        if (selectedTier === undefined) selectedTier = '';
        const type = row.querySelector('.ins-type').value;
        const provider = row.querySelector('.ins-provider');

        resetInsProvider(row, type ? registrationInsuranceI18n.loading_providers : registrationInsuranceI18n.select_type_first);
        resetInsTier(row, registrationInsuranceI18n.select_type_first);
        toggleInsuranceExtras(row, false);

        if (!type) return;

        fetch(providerByTypeUrl + '?type=' + encodeURIComponent(type), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(providers => {
            if (!providers.length) {
                resetInsProvider(row, registrationInsuranceI18n.no_providers);
                return;
            }

            provider.innerHTML = `<option value="">${registrationInsuranceI18n.select_provider}</option>`;
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
        .catch(() => resetInsProvider(row, registrationInsuranceI18n.no_providers));
    }

    function loadTiersForRow(row, selectedTier) {
        if (selectedTier === undefined) selectedTier = '';
        const providerId = row.querySelector('.ins-provider').value;
        const tier = row.querySelector('.ins-tier');

        if (!providerId) {
            resetInsTier(row, registrationInsuranceI18n.select_type_first);
            toggleInsuranceExtras(row, false);
            return;
        }

        tier.innerHTML = `<option value="">${registrationInsuranceI18n.loading_tiers}</option>`;
        tier.disabled = true;
        toggleInsuranceExtras(row, true);

        fetch(tiersForProviderUrl.replace(':pid', providerId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(tiers => {
            if (!tiers.length) {
                resetInsTier(row, registrationInsuranceI18n.no_tiers);
                return;
            }

            tier.innerHTML = `<option value="">${registrationInsuranceI18n.select_tier}</option>`;
            tiers.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name + (t.coverage_percentage ? ' (' + t.coverage_percentage + '% coverage)' : '');
                if ((selectedTier && selectedTier == t.id) || (!selectedTier && t.is_default)) opt.selected = true;
                tier.appendChild(opt);
            });
            tier.disabled = false;
        })
        .catch(() => resetInsTier(row, registrationInsuranceI18n.no_tiers));
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
                <span class="fw-medium small text-muted ins-label">${insI18n.insurance} #${idx + 1}</span>
                <button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-ins"><i class="ti ti-trash"></i></button>
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label form-label-sm">${insI18n.type}</label>
                    <select name="insurances[${idx}][type]" class="form-select form-select-sm ins-type" data-idx="${idx}">
                        <option value="">{{ __('patients.insurance_none') }}</option>
                        ${buildInsTypeOptions('')}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">${insI18n.provider}</label>
                    <select name="insurances[${idx}][provider_id]" class="form-select form-select-sm ins-provider" data-idx="${idx}" disabled>
                        <option value="">${registrationInsuranceI18n.select_type_first}</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">${insI18n.tier}</label>
                    <select name="insurances[${idx}][insurance_tier_id]" class="form-select form-select-sm ins-tier" data-idx="${idx}" disabled>
                        <option value="">${registrationInsuranceI18n.select_type_first}</option>
                    </select>
                </div>
                <div class="col-md-4 ins-row-extra" style="display:none">
                    <label class="form-label form-label-sm">${insI18n.membership}</label>
                    <input type="text" name="insurances[${idx}][membership_number]" class="form-control form-control-sm" placeholder="e.g. INS-123456789">
                </div>
                <div class="col-md-4 ins-row-extra" style="display:none">
                    <label class="form-label form-label-sm">${insI18n.policy}</label>
                    <input type="text" name="insurances[${idx}][policy_number]" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 ins-row-extra" style="display:none">
                    <label class="form-label form-label-sm">${insI18n.expiry}</label>
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
                ? `${insI18n.insurance} #1 <span class="badge bg-primary ms-1">${insI18n.primary}</span>`
                : `${insI18n.insurance} #${i + 1}`;
            const btn = row.querySelector('.remove-ins');
            if (btn) btn.classList.toggle('d-none', i === 0);
        });
    }
})();
</script>
@endpush
@endsection
