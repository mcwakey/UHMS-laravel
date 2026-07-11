@extends('layouts.app')
@section('title', __('payment_timing.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('menu.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('payment_timing.title') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card"><div class="card-body p-0">@include('settings.partials.sidebar')</div></div>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('payment_timing.title') }}</h5></div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                <form method="POST" action="{{ route('admin.settings.payment-timing.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="enabled" value="1" id="paymentTimingEnabled"
                               @checked(old('enabled', $settings['enabled']))>
                        <label class="form-check-label fw-semibold" for="paymentTimingEnabled">{{ __('payment_timing.enable') }}</label>
                    </div>
                    <p class="text-muted small">{{ __('payment_timing.enable_help') }}</p>

                    <hr class="my-4">
                    <h6 class="fw-bold">{{ __('payment_timing.global_default') }}</h6>
                    <div class="mb-4">
                        <select name="default_policy" class="form-select @error('default_policy') is-invalid @enderror">
                            @foreach($globalPolicies as $policy)
                                <option value="{{ $policy->value }}" @selected(old('default_policy', $settings['default_policy']) === $policy->value)>{{ $policy->label() }}</option>
                            @endforeach
                        </select>
                        @foreach($globalPolicies as $policy)
                            <div class="form-text"><strong>{{ $policy->label() }}:</strong> {{ $policy->description() }}</div>
                        @endforeach
                    </div>

                    <h6 class="fw-bold">{{ __('payment_timing.visit_type_defaults') }}</h6>
                    <div class="row mb-3">
                        @foreach($visitTypes as $visitType)
                            @php($key = $visitType->value.'_policy')
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="{{ $key }}">{{ $visitType->translatedLabel() }}</label>
                                <select id="{{ $key }}" name="{{ $key }}" class="form-select @error($key) is-invalid @enderror">
                                    @foreach($visitPolicies as $policy)
                                        <option value="{{ $policy->value }}" @selected(old($key, $settings[$key]) === $policy->value)>{{ $policy->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold">{{ __('payment_timing.emergency_protection') }}</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="emergency_never_block_stabilisation" value="1" id="neverBlockEmergency"
                               @checked(old('emergency_never_block_stabilisation', $settings['emergency_never_block_stabilisation']))>
                        <label class="form-check-label" for="neverBlockEmergency">{{ __('payment_timing.never_block_stabilisation') }}</label>
                    </div>
                    <p class="text-muted small">{{ __('payment_timing.emergency_help') }}</p>

                    <hr class="my-4">
                    <h6 class="fw-bold">{{ __('payment_timing.financial_closure') }}</h6>
                    @foreach([
                        'require_settlement_for_pay_after_services' => 'payment_timing.require_pay_after_settlement',
                        'require_settlement_for_running_bill' => 'payment_timing.require_running_bill_settlement',
                        'allow_outstanding_balance_override' => 'payment_timing.allow_outstanding_override',
                    ] as $key => $label)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="{{ $key }}" value="1" id="{{ $key }}" @checked(old($key, $settings[$key]))>
                            <label class="form-check-label" for="{{ $key }}">{{ __($label) }}</label>
                        </div>
                    @endforeach
                    <p class="text-muted small">{{ __('payment_timing.financial_closure_help') }}</p>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('settings.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
