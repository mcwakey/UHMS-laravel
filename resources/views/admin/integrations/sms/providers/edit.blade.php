@extends('layouts.app')

@section('title', __('integrations.edit_provider'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="$provider->name" :description="__('sms.sms_provider')" icon="ti-message-2"
            :breadcrumbs="[
                ['label' => __('sms.sms_providers'), 'url' => route('admin.integrations.sms.providers.index')],
                ['label' => $provider->name],
            ]">
            <x-slot:actions>
                <x-status-badge :status="$provider->status" domain="integration_provider" />
                @if($provider->is_active)<span class="badge bg-success"><i class="ti ti-circle-check me-1"></i>{{ __('integrations.active_provider') }}</span>@endif
            </x-slot:actions>
        </x-page-header>

        <div class="row g-3">
            {{-- Provider details --}}
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent"><strong>{{ __('integrations.provider') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.integrations.sms.providers.update', $provider) }}">
                            @csrf
                            @method('PUT')
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('integrations.provider_code') }}</label>
                                    <input type="text" class="form-control" value="{{ $provider->code }}" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('integrations.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" value="{{ old('name', $provider->name) }}" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('integrations.environment') }}</label>
                                    <select name="environment" class="form-select">
                                        <option value="sandbox" @selected($provider->environment==='sandbox')>{{ __('integrations.sandbox') }}</option>
                                        <option value="live" @selected($provider->environment==='live')>{{ __('integrations.live') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('sms.sender_id') }}</label>
                                    <input type="text" name="sender_id" value="{{ old('sender_id', $provider->sender_id) }}" class="form-control" maxlength="60">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('integrations.base_url') }}</label>
                                    <input type="url" name="base_url" value="{{ old('base_url', $provider->base_url) }}" class="form-control">
                                    <div class="form-text">{{ __('integrations.base_url_hint') }}</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('integrations.callback_url') }}</label>
                                    <input type="url" name="callback_url" value="{{ old('callback_url', $provider->callback_url) }}" class="form-control">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">{{ __('integrations.description') }}</label>
                                    <textarea name="description" rows="2" class="form-control">{{ old('description', $provider->description) }}</textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3"><i class="ti ti-check me-1"></i>{{ __('integrations.save') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Credentials --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent"><strong>{{ __('integrations.credentials') }}</strong></div>
                    <div class="card-body">
                        @can('integrations.sms.credentials.manage')
                            <p class="text-muted small">{{ __('integrations.leave_blank_keep') }} {{ __('integrations.credentials_never_shown') }}</p>
                            <form method="POST" action="{{ route('admin.integrations.sms.providers.credentials.update', $provider) }}">
                                @csrf
                                @method('PUT')
                                @forelse($credentialKeys as $key)
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">{{ $key }}
                                            @if(in_array($key, $configuredKeys, true))<i class="ti ti-lock text-success" title="{{ __('integrations.configured_credentials') }}"></i>@endif
                                        </label>
                                        <input type="password" autocomplete="new-password" name="credentials[{{ $key }}]" class="form-control form-control-sm"
                                               placeholder="{{ in_array($key, $configuredKeys, true) ? '••••••••' : '' }}">
                                    </div>
                                @empty
                                    <p class="text-muted">{{ __('integrations.no_credentials') }}</p>
                                @endforelse
                                @if(count($credentialKeys))
                                    <button type="submit" class="btn btn-outline-primary btn-sm mt-2"><i class="ti ti-key me-1"></i>{{ __('integrations.update_credentials') }}</button>
                                @endif
                            </form>
                        @else
                            <p class="text-muted">{{ __('integrations.masked_credentials') }}</p>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
