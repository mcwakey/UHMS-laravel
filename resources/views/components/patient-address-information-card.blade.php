@props([
    'patient' => null,
    'regions' => [],
    'digitalAddressPattern' => null,
    'digitalAddressPlaceholder' => '',
])

@php
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
                <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="2">{{ old('address', $patient?->address) }}</textarea>
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
                    <input type="text" name="digital_address" id="digitalAddressInput" class="form-control js-digital-address-mask @error('digital_address') is-invalid @enderror" value="{{ old('digital_address', $patient?->digital_address) }}" placeholder="{{ $digitalAddressPlaceholder }}" inputmode="text" @if($digitalAddressPattern) pattern="{{ $digitalAddressPattern }}" @endif>
                    <button class="btn btn-outline-secondary" type="button" id="detectDigitalAddressBtn" title="Use device location">
                        <i class="ti ti-current-location"></i>
                    </button>
                </div>
                @error('digital_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
