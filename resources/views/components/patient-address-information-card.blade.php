@props([
    'patient' => null,
    'regions' => [],
    'digitalAddressPattern' => null,
    'digitalAddressPlaceholder' => '',
])

@php
    $privacy = app(\App\Services\PatientPrivacyService::class);
    $isCreate = $patient === null;
    $addressCanEdit = $isCreate ? $privacy->canCaptureOnCreate('address') : $privacy->canEdit('address');
    $digitalCanEdit = $isCreate ? $privacy->canCaptureOnCreate('digital_address') : $privacy->canEdit('digital_address');
    $addressDisplay = $addressCanEdit
        ? old('address', $patient?->address)
        : ($isCreate ? __('patients.privacy.hidden_sensitive_patient_data') : $privacy->displayForPatient('address', $patient?->address, $patient));
    $digitalDisplay = $digitalCanEdit
        ? old('digital_address', $patient?->digital_address)
        : ($isCreate ? __('patients.privacy.hidden_sensitive_patient_data') : $privacy->displayForPatient('digital_address', $patient?->digital_address, $patient));
    $regions = collect($regions);
    $selectedRegion = old('region', $patient?->region);
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-map-pin me-1"></i>{{ __('patients.address_information') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label">{{ __('patients.address') }}</label>
                <textarea @if($addressCanEdit) name="address" @endif class="form-control @error('address') is-invalid @enderror" rows="2" @unless($addressCanEdit) disabled @endunless>{{ $addressDisplay }}</textarea>
                @unless($addressCanEdit)<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.region') }}</label>
                <select name="region" id="patientRegionSelect" class="form-select patient-searchable-select @error('region') is-invalid @enderror" data-placeholder="{{ __('patients.select_region') }}">
                    <option value="">{{ __('patients.select_region') }}</option>
                    @foreach($regions as $region)
                        <option value="{{ $region->name }}" {{ $selectedRegion == $region->name ? 'selected' : '' }}>{{ $region->name }}</option>
                    @endforeach
                </select>
                @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.city') }}</label>
                <select name="city" id="patientCitySelect" class="form-select patient-searchable-select @error('city') is-invalid @enderror" data-selected="{{ old('city', $patient?->city) }}" data-placeholder="{{ __('patients.city') }}" disabled>
                    <option value="">{{ __('patients.city') }}</option>
                </select>
                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.town') }}</label>
                <select name="town" id="patientTownSelect" class="form-select patient-searchable-select @error('town') is-invalid @enderror" data-selected="{{ old('town', $patient?->town) }}" data-placeholder="{{ __('patients.town') }}" disabled>
                    <option value="">{{ __('patients.town') }}</option>
                </select>
                @error('town')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.digital_address_gps') }}</label>
                <div class="input-group">
                    <input type="text" @if($digitalCanEdit) name="digital_address" @endif id="digitalAddressInput" class="form-control js-digital-address-mask @error('digital_address') is-invalid @enderror" value="{{ $digitalDisplay }}" placeholder="{{ $digitalAddressPlaceholder }}" inputmode="text" @if($digitalAddressPattern && $digitalCanEdit) pattern="{{ $digitalAddressPattern }}" @endif @unless($digitalCanEdit) disabled @endunless>
                    <button class="btn btn-outline-secondary" type="button" id="detectDigitalAddressBtn" title="Use device location" @unless($digitalCanEdit) disabled @endunless>
                        <i class="ti ti-current-location"></i>
                    </button>
                </div>
                @unless($digitalCanEdit)<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
