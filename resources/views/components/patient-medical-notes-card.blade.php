@props([
    'patient' => null,
    'showPlaceholders' => true,
])

@php
    $privacy = app(\App\Services\PatientPrivacyService::class);
    $isCreate = $patient === null;
    $allergiesCanEdit = $isCreate ? $privacy->canCaptureOnCreate('allergies') : $privacy->canEdit('allergies');
    $chronicCanEdit = $isCreate ? $privacy->canCaptureOnCreate('chronic_conditions') : $privacy->canEdit('chronic_conditions');
    $allergiesDisplay = $allergiesCanEdit
        ? old('allergies', $patient?->allergies)
        : ($isCreate ? __('patients.privacy.clinical_sensitive_hidden') : $privacy->displayForPatient('allergies', $patient?->allergies, $patient));
    $chronicDisplay = $chronicCanEdit
        ? old('chronic_conditions', $patient?->chronic_conditions)
        : ($isCreate ? __('patients.privacy.clinical_sensitive_hidden') : $privacy->displayForPatient('chronic_conditions', $patient?->chronic_conditions, $patient));
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-report-medical me-1"></i>{{ __('patients.medical_notes') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('patients.known_allergies') }}</label>
                <textarea @if($allergiesCanEdit) name="allergies" @endif class="form-control @error('allergies') is-invalid @enderror" rows="3" @if($showPlaceholders && $allergiesCanEdit) placeholder="{{ __('patients.known_allergies_ph') }}" @endif @unless($allergiesCanEdit) disabled @endunless>{{ $allergiesDisplay }}</textarea>
                @unless($allergiesCanEdit)<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('allergies')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">{{ __('patients.chronic_conditions') }}</label>
                <textarea @if($chronicCanEdit) name="chronic_conditions" @endif class="form-control @error('chronic_conditions') is-invalid @enderror" rows="3" @if($showPlaceholders && $chronicCanEdit) placeholder="{{ __('patients.chronic_conditions_ph') }}" @endif @unless($chronicCanEdit) disabled @endunless>{{ $chronicDisplay }}</textarea>
                @unless($chronicCanEdit)<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('chronic_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
