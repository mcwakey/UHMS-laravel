@props([
    'patient' => null,
    'phonePattern' => null,
    'phonePlaceholder' => '',
])

<div class="card">
    <div class="card-header">
        <h5 class="fw-bold mb-0"><i class="ti ti-phone me-1"></i>{{ __('patients.contact_identification') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.phone_number') }} <span class="text-danger">*</span></label>
                <input type="tel" name="phone" class="form-control js-phone-mask @error('phone') is-invalid @enderror" value="{{ old('phone', $patient?->phone) }}" placeholder="{{ $phonePlaceholder }}" inputmode="tel" @if($phonePattern) pattern="{{ $phonePattern }}" @endif required>
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.secondary_phone') }}</label>
                <input type="tel" name="phone_secondary" class="form-control @error('phone_secondary') is-invalid @enderror" value="{{ old('phone_secondary', $patient?->phone_secondary) }}" placeholder="+000000000000" inputmode="tel">
                @error('phone_secondary')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.email_address') }}</label>
                <input type="email" name="email" class="form-control js-email-input @error('email') is-invalid @enderror" value="{{ old('email', $patient?->email) }}" autocomplete="email" inputmode="email" pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$">
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label">{{ __('patients.id_card_number') }}</label>
                <input type="text" name="ghana_card_number" class="form-control js-id-card-input @error('ghana_card_number') is-invalid @enderror" value="{{ old('ghana_card_number', $patient?->ghana_card_number) }}" placeholder="{{ __('patients.id_card_number') }}" inputmode="text" maxlength="30">
                @error('ghana_card_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
