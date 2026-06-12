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
<h6 class="fw-bold mb-2"><i class="ti ti-shield-lock me-1"></i>Verification (optional)</h6>
<p class="text-muted small mb-3">
    Configure how membership is verified at the visit desk. Leave the driver blank to mark this provider as
    <em>not requiring</em> verification.
</p>

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label" for="ver_driver_{{ $idSuffix }}">Verification Driver</label>
        <select name="verification_driver" id="ver_driver_{{ $idSuffix }}" class="form-select verification-driver-select" data-suffix="{{ $idSuffix }}">
            <option value="">— Not required —</option>
            @foreach ($drivers as $d)
                <option value="{{ $d }}" {{ old('verification_driver', $current) === $d ? 'selected' : '' }}>{{ __('statuses.default.' . $d) }}</option>
            @endforeach
        </select>
        <div class="form-text">Manual asks the visit desk to enter a reference code; Code validates a captured code; API calls an endpoint.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ver_method_{{ $idSuffix }}">Method</label>
        <input type="text" name="verification_method" id="ver_method_{{ $idSuffix }}" class="form-control"
               value="{{ old('verification_method', $provider?->verification_method) }}"
               placeholder="e.g. biometric, code, portal_lookup" maxlength="60">
    </div>
    <div class="col-md-4">
        <label class="form-label" for="ver_channel_{{ $idSuffix }}">Channel</label>
        <input type="text" name="verification_channel" id="ver_channel_{{ $idSuffix }}" class="form-control"
               value="{{ old('verification_channel', $provider?->verification_channel) }}"
               placeholder="e.g. desk, mobile_app, ussd, portal" maxlength="60">
    </div>
</div>

<div class="row mb-3 verification-api-row" data-suffix="{{ $idSuffix }}"
     style="display: {{ ($current === 'api' || old('verification_driver') === 'api') ? '' : 'none' }};">
    <div class="col-md-6">
        <label class="form-label" for="ver_credkey_{{ $idSuffix }}">Credentials Key</label>
        <input type="text" name="verification_credentials_key" id="ver_credkey_{{ $idSuffix }}" class="form-control"
               value="{{ old('verification_credentials_key', $provider?->verification_credentials_key) }}"
               placeholder="e.g. nhia, acme_health" maxlength="60" pattern="[A-Za-z0-9_\-]+">
        <div class="form-text">
            Looked up at <code>config('services.insurance_verification.&lt;key&gt;')</code>. Add the matching block in
            <code>config/services.php</code> + <code>.env</code>.
        </div>
    </div>
</div>

<div class="mb-2 verification-config-row" data-suffix="{{ $idSuffix }}"
     style="display: {{ (in_array($current, ['code', 'api']) || in_array(old('verification_driver'), ['code', 'api'])) ? '' : 'none' }};">
    <label class="form-label" for="ver_config_{{ $idSuffix }}">Driver Config (JSON)</label>
    <textarea name="verification_config" id="ver_config_{{ $idSuffix }}" rows="5"
              class="form-control font-monospace small"
              placeholder='{
  "code_pattern": "/^[A-Z]{3}-\d{6}$/"
}'>{{ old('verification_config_raw', $configJson) }}</textarea>
    <div class="form-text">
        Non-secret driver config. Examples:
        <code>code</code> driver &rarr; <code>{"code_pattern": "..."}</code>;
        <code>api</code> driver &rarr; <code>{"endpoint": "...", "method": "POST", "auth": "bearer", "response": {...}}</code>.
        Secrets must live in <code>.env</code> only.
    </div>
    @error('verification_config')<div class="text-danger small">{{ $message }}</div>@enderror
</div>
