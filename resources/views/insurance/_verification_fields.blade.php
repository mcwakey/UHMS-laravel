{{--
    Reusable verification fields fragment for the insurance provider Add/Edit modals.
    Variables expected:
        $provider (App\Models\InsuranceProvider|null)  — null when adding
        $idSuffix  (string)                             — appended to all element ids to keep them unique
--}}
@php
    $idSuffix = $idSuffix ?? 'add';
    $drivers  = array_keys((array) config('insurance_verification.drivers', []));
    $current  = $provider?->verification_driver;
    $configJson = $provider && $provider->verification_config
        ? json_encode($provider->verification_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        : '';
@endphp

<hr class="my-3">
<h6 class="fw-bold mb-2"><i class="ti ti-shield-lock me-1"></i>{{ __('claims.verification_optional') }}</h6>
<p class="text-muted small mb-3">
    {{ __('claims.verification_help') }}
</p>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label" for="ver_driver_{{ $idSuffix }}">{{ __('claims.verification_driver') }}</label>
        <select name="verification_driver" id="ver_driver_{{ $idSuffix }}" class="form-select verification-driver-select" data-suffix="{{ $idSuffix }}">
            <option value="">{{ __('claims.verification_not_required') }}</option>
            @foreach ($drivers as $d)
                <option value="{{ $d }}" {{ old('verification_driver', $current) === $d ? 'selected' : '' }}>{{ __('statuses.default.' . $d) }}</option>
            @endforeach
        </select>
        <div class="form-text">{{ __('claims.verification_driver_help') }}</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ver_method_{{ $idSuffix }}">{{ __('claims.verification_method') }}</label>
        <input type="text" name="verification_method" id="ver_method_{{ $idSuffix }}" class="form-control"
               value="{{ old('verification_method', $provider?->verification_method) }}"
               placeholder="{{ __('claims.method_placeholder') }}" maxlength="60">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ver_channel_{{ $idSuffix }}">{{ __('claims.verification_channel') }}</label>
        <input type="text" name="verification_channel" id="ver_channel_{{ $idSuffix }}" class="form-control"
               value="{{ old('verification_channel', $provider?->verification_channel) }}"
               placeholder="{{ __('claims.channel_placeholder') }}" maxlength="60">
    </div>
</div>

<div class="row mb-3 verification-api-row" data-suffix="{{ $idSuffix }}"
     style="display: {{ ($current === 'api' || old('verification_driver') === 'api') ? '' : 'none' }};">
    <div class="col-md-6">
        <label class="form-label" for="ver_credkey_{{ $idSuffix }}">{{ __('claims.credentials_key') }}</label>
        <input type="text" name="verification_credentials_key" id="ver_credkey_{{ $idSuffix }}" class="form-control"
               value="{{ old('verification_credentials_key', $provider?->verification_credentials_key) }}"
               placeholder="{{ __('claims.credentials_key_placeholder') }}" maxlength="60" pattern="[A-Za-z0-9_\-]+">
        <div class="form-text">
            {{ __('claims.credentials_key_help') }}
        </div>
    </div>
</div>

<div class="mb-2 verification-config-row" data-suffix="{{ $idSuffix }}"
     style="display: {{ (in_array($current, ['code', 'api']) || in_array(old('verification_driver'), ['code', 'api'])) ? '' : 'none' }};">
    <label class="form-label" for="ver_config_{{ $idSuffix }}">{{ __('claims.driver_config_json') }}</label>
    <textarea name="verification_config" id="ver_config_{{ $idSuffix }}" rows="5"
              class="form-control font-monospace small"
              placeholder='{
  "code_pattern": "/^[A-Z]{3}-\d{6}$/"
}'>{{ old('verification_config_raw', $configJson) }}</textarea>
    <div class="form-text">
        {{ __('claims.driver_config_help') }}
    </div>
    @error('verification_config')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
