@props([
    'patient' => null,
    'showPlaceholders' => true,
])

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>{{ __('patients.medical_notes') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('patients.known_allergies') }}</label>
                <textarea name="allergies" class="form-control @error('allergies') is-invalid @enderror" rows="3" @if($showPlaceholders) placeholder="{{ __('patients.known_allergies_ph') }}" @endif>{{ old('allergies', $patient?->allergies) }}</textarea>
                @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('patients.chronic_conditions') }}</label>
                <textarea name="chronic_conditions" class="form-control @error('chronic_conditions') is-invalid @enderror" rows="3" @if($showPlaceholders) placeholder="{{ __('patients.chronic_conditions_ph') }}" @endif>{{ old('chronic_conditions', $patient?->chronic_conditions) }}</textarea>
                @error('chronic_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
