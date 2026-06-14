@extends('layouts.app')
@section('title', __('accounting.accounting_settings'))

@section('content')
<x-page-header :title="__('accounting.accounting_settings')" icon="ti-settings-dollar" />

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.accounting.settings.update') }}">
            @csrf
            @method('PUT')
            <div class="row g-3">
                @foreach($settings as $setting)
                    <div class="col-md-6">
                        <label class="form-label">{{ $setting->description }}</label>
                        @if(str_ends_with($setting->key, '_account_id'))
                            <select name="{{ $setting->key }}" class="form-select select2">
                                <option value="">{{ __('accounting.not_set') }}</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" @selected($setting->account_id == $account->id)>{{ $account->display_name }}</option>
                                @endforeach
                            </select>
                        @else
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="{{ $setting->key }}" value="1" @checked(in_array((string) $setting->value, ['1', 'true', 'yes', 'on'], true))>
                                <span class="form-check-label">Enabled</span>
                            </div>
                        @endif
                        <div class="form-text">{{ $setting->key }}</div>
                    </div>
                @endforeach
            </div>
            @can('accounting.settings.manage')
                <div class="mt-3">
                    <button class="btn btn-primary" type="submit"><i class="ti ti-device-floppy me-1"></i>Save Settings</button>
                </div>
            @endcan
        </form>
    </div>
</div>
@endsection
