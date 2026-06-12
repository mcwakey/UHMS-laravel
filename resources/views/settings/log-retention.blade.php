@extends('layouts.app')
@section('title', 'Log Retention')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.log_retention_title') }}</h4>
        <small class="text-muted">{{ __('settings.retention_default_hint', ['days' => $default]) }}</small>
    </div>
    <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('settings.back_to_logs') }}</a>
</div>

<form method="POST" action="{{ route('admin.log-retention.update') }}">
    @csrf @method('PUT')
    <div class="card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('settings.module_label') }}</th>
                        <th style="width:160px">{{ __('settings.retention_days_col') }}</th>
                        <th>{{ __('settings.reason_col') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $i => $mod)
                        @php $ov = $overrides->get($mod->value); @endphp
                        <tr>
                            <td>
                                {{ $mod->label() }}
                                <input type="hidden" name="overrides[{{ $i }}][module]" value="{{ $mod->value }}">
                            </td>
                            <td>
                                <input type="number" min="1" max="36500"
                                       name="overrides[{{ $i }}][retention_days]"
                                       value="{{ $ov?->retention_days }}"
                                       placeholder="{{ $default }}"
                                       class="form-control form-control-sm">
                            </td>
                            <td>
                                <input type="text" maxlength="500"
                                       name="overrides[{{ $i }}][reason]"
                                       value="{{ $ov?->reason }}"
                                       class="form-control form-control-sm">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <button class="btn btn-primary btn-sm">{{ __('settings.save_retention') }}</button>
        </div>
    </div>
</form>
@endsection
