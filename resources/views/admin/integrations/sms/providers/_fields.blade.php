{{-- Shared create fields for an SMS provider. --}}
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('integrations.provider_code') }} <span class="text-danger">*</span></label>
        <select name="code" class="form-select @error('code') is-invalid @enderror" required>
            <option value="">{{ __('integrations.select_provider_code') }}</option>
            @foreach($catalogue as $entry)
                <option value="{{ $entry['code'] }}" @selected(old('code') === $entry['code'])>
                    {{ $entry['label'] }} ({{ $entry['code'] }})
                </option>
            @endforeach
        </select>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('integrations.name') }} <span class="text-danger">*</span></label>
        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('integrations.environment') }}</label>
        <select name="environment" class="form-select">
            <option value="sandbox" @selected(old('environment','sandbox')==='sandbox')>{{ __('integrations.sandbox') }}</option>
            <option value="live" @selected(old('environment')==='live')>{{ __('integrations.live') }}</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('sms.sender_id') }}</label>
        <input type="text" name="sender_id" value="{{ old('sender_id') }}" class="form-control" maxlength="60">
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('integrations.base_url') }}</label>
        <input type="url" name="base_url" value="{{ old('base_url') }}" class="form-control @error('base_url') is-invalid @enderror">
        <div class="form-text">{{ __('integrations.base_url_hint') }}</div>
        @error('base_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">{{ __('integrations.callback_url') }}</label>
        <input type="url" name="callback_url" value="{{ old('callback_url') }}" class="form-control">
    </div>

    <div class="col-12">
        <label class="form-label">{{ __('integrations.description') }}</label>
        <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea>
    </div>
</div>
