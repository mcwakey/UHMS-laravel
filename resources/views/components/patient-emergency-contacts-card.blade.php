@props([
    'emergencyContacts' => null,
    'phonePattern' => null,
    'phonePlaceholder' => '',
    'relationships' => ['Spouse', 'Parent', 'Child', 'Sibling', 'Relative', 'Friend', 'Other'],
])

@php
    $privacy = app(\App\Services\PatientPrivacyService::class);
    $canEditEmergencyContact = $privacy->canCaptureOnCreate('emergency_contact_phone');
    $oldContacts = old('emergency_contacts');
    $contacts = collect($oldContacts ?? $emergencyContacts ?? [[]])->values();
    if ($contacts->isEmpty()) {
        $contacts = collect([[]]);
    }
@endphp

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0"><i class="ti ti-urgent me-1"></i>{{ __('patients.emergency_contacts') }}</h5>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-ec-btn" @unless($canEditEmergencyContact) disabled @endunless>
            <i class="ti ti-plus me-1"></i>{{ __('patients.add_another_ec') }}
        </button>
    </div>
    <div class="card-body">
        <div id="ec-wrapper">
            @foreach($contacts as $index => $contact)
                @php
                    $nameError = 'emergency_contacts.'.$index.'.name';
                    $phoneError = 'emergency_contacts.'.$index.'.phone';
                    $relationshipValue = data_get($contact, 'relationship');
                    $nameValue = data_get($contact, 'name');
                    $phoneValue = data_get($contact, 'phone');
                    $secondaryPhoneValue = data_get($contact, 'phone_secondary');
                @endphp
                <div class="ec-row border rounded p-3 mb-2" data-index="{{ $index }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-medium small text-muted ec-label">
                            {{ __('patients.contact_name') }} #{{ $index + 1 }}
                            @if($index === 0)
                                <span class="badge bg-primary ms-1">{{ __('patients.primary_contact_badge') }}</span>
                            @endif
                        </span>
                        <button aria-label="Delete" title="Delete" type="button" class="btn btn-sm btn-outline-danger remove-ec {{ $index === 0 ? 'd-none' : '' }}"><i class="ti ti-trash"></i></button>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ __('common.name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="emergency_contacts[{{ $index }}][name]" class="form-control form-control-sm @error($nameError) is-invalid @enderror" value="{{ $nameValue }}" placeholder="{{ __('common.name') }}">
                            @error($nameError)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ __('common.phone') }} <span class="text-danger">*</span></label>
                            <input type="tel" @if($canEditEmergencyContact) name="emergency_contacts[{{ $index }}][phone]" @endif class="form-control form-control-sm js-phone-mask @error($phoneError) is-invalid @enderror" value="{{ $canEditEmergencyContact ? $phoneValue : __('patients.privacy.hidden_sensitive_patient_data') }}" placeholder="{{ $phonePlaceholder }}" inputmode="tel" @if($phonePattern && $canEditEmergencyContact) pattern="{{ $phonePattern }}" @endif @unless($canEditEmergencyContact) disabled @endunless>
                            @error($phoneError)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ __('patients.secondary_phone') }}</label>
                            <input type="tel" @if($canEditEmergencyContact) name="emergency_contacts[{{ $index }}][phone_secondary]" @endif class="form-control form-control-sm" value="{{ $canEditEmergencyContact ? $secondaryPhoneValue : __('patients.privacy.hidden_sensitive_patient_data') }}" placeholder="{{ __('common.optional') }}" inputmode="tel" @unless($canEditEmergencyContact) disabled @endunless>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ __('patients.relationship') }}</label>
                            <select name="emergency_contacts[{{ $index }}][relationship]" class="form-select form-select-sm">
                                <option value="">{{ __('patients.select') }}</option>
                                @foreach($relationships as $rel)
                                    <option value="{{ $rel }}" {{ $relationshipValue == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <small class="text-muted"><i class="ti ti-info-circle me-1"></i>{{ $canEditEmergencyContact ? __('patients.ec_first_contact_info') : __('patients.privacy.masked_value_not_submitted') }}</small>
    </div>
</div>
