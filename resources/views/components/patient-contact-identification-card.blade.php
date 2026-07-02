@props([
    'patient' => null,
    'phonePattern' => null,
    'phonePlaceholder' => '',
])

@php
    $privacy = app(\App\Services\PatientPrivacyService::class);
    $isCreate = $patient === null;
    $fieldState = function (string $field) use ($privacy, $patient, $isCreate) {
        if ($isCreate) {
            $canEdit = $privacy->canCaptureOnCreate($field);
            return [
                'can_edit' => $canEdit,
                'restricted' => ! $canEdit,
                'readonly' => false,
                'value' => old($field),
                'display' => $canEdit ? old($field) : __('patients.privacy.hidden_sensitive_patient_data'),
            ];
        }

        return $privacy->editFieldState($field, old($field, $patient?->{$field}), $patient);
    };
    $phoneState = $fieldState('phone');
    $secondaryPhoneState = $fieldState('phone_secondary');
    $emailState = $fieldState('email');
    $idState = $fieldState('ghana_card_number');
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-phone me-1"></i>{{ __('patients.contact_identification') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.phone_number') }} <span class="text-danger">*</span></label>
                <input type="tel" @if($phoneState['can_edit']) name="phone" @endif class="form-control js-phone-mask @error('phone') is-invalid @enderror" value="{{ $phoneState['can_edit'] ? $phoneState['value'] : $phoneState['display'] }}" placeholder="{{ $phonePlaceholder }}" inputmode="tel" @if($phonePattern && $phoneState['can_edit']) pattern="{{ $phonePattern }}" @endif @if($phoneState['can_edit']) required @else disabled @endif>
                @unless($phoneState['can_edit'])<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.secondary_phone') }}</label>
                <input type="tel" @if($secondaryPhoneState['can_edit']) name="phone_secondary" @endif class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ $secondaryPhoneState['can_edit'] ? $secondaryPhoneState['value'] : $secondaryPhoneState['display'] }}" placeholder="+000000000000" inputmode="tel" @unless($secondaryPhoneState['can_edit']) disabled @endunless>
                @unless($secondaryPhoneState['can_edit'])<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.email_address') }}</label>
                <input type="email" @if($emailState['can_edit']) name="email" @endif class="form-control js-email-input @error('email') is-invalid @enderror" value="{{ $emailState['can_edit'] ? $emailState['value'] : $emailState['display'] }}" autocomplete="email" inputmode="email" @if($emailState['can_edit']) pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$" @else disabled @endif>
                @unless($emailState['can_edit'])<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.id_card_number') }}</label>
                <input type="text" @if($idState['can_edit']) name="ghana_card_number" @endif class="form-control js-id-card-input @error('ghana_card_number') is-invalid @enderror" value="{{ $idState['can_edit'] ? $idState['value'] : $idState['display'] }}" placeholder="{{ __('patients.id_card_number') }}" inputmode="text" maxlength="30" @unless($idState['can_edit']) disabled @endunless>
                @unless($idState['can_edit'])<small class="text-muted">{{ __('patients.privacy.masked_value_not_submitted') }}</small>@endunless
                @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
