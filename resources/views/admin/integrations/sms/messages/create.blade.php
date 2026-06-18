@extends('layouts.app')

@section('title', __('sms.manual_sms'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('sms.manual_sms')" :description="__('sms.send_test_sms')" icon="ti-send"
            :breadcrumbs="[
                ['label' => __('sms.sms_messages'), 'url' => route('admin.integrations.sms.messages.index')],
                ['label' => __('sms.manual_sms')],
            ]" />

        @unless($hasProvider)
            <div class="alert alert-warning"><i class="ti ti-alert-triangle me-1"></i>{{ __('sms.gateway_disabled') }}</div>
        @endunless

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.integrations.sms.messages.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">{{ __('sms.recipients') }} <span class="text-danger">*</span></label>
                        <textarea name="recipients" rows="2" class="form-control @error('recipients') is-invalid @enderror"
                                  placeholder="0241234567, 0209876543" required>{{ old('recipients') }}</textarea>
                        <div class="form-text">{{ __('sms.recipients_hint') }}</div>
                        @error('recipients')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if($templates->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label">{{ __('sms.sms_template') }}</label>
                            <select name="template_id" class="form-select">
                                <option value="">—</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">{{ __('sms.message_body') }} <span class="text-danger">*</span></label>
                        <textarea name="message_body" rows="4" maxlength="1000" class="form-control @error('message_body') is-invalid @enderror" required>{{ old('message_body') }}</textarea>
                        <div class="form-text text-warning"><i class="ti ti-shield-lock me-1"></i>{{ __('sms.phi_warning') }}</div>
                        @error('message_body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('sms.sender_id') }}</label>
                        <input type="text" name="sender_id" value="{{ old('sender_id') }}" class="form-control" maxlength="60">
                    </div>

                    <button type="submit" class="btn btn-primary" @disabled(! $hasProvider)>
                        <i class="ti ti-send me-1"></i>{{ __('sms.send_sms') }}
                    </button>
                    <a href="{{ route('admin.integrations.sms.messages.index') }}" class="btn btn-light">{{ __('integrations.cancel') }}</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
