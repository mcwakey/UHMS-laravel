@extends('layouts.app')
@section('title', 'Payment Method Settings')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('settings.title') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">{{ __('settings.payment_methods_breadcrumb') }}</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-lg-3">
        <div class="card">
            <div class="card-body p-0">
                @include('settings.partials.sidebar')
            </div>
        </div>
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('settings.payment_configuration') }}</h5>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.payment-methods.update') }}">
                    @csrf
                    @method('PUT')

                    <h6 class="fw-bold mb-3">{{ __('settings.enabled_payment_methods') }}</h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="cash_enabled" value="1"
                                       id="cashEnabled" {{ old('cash_enabled', $settings['cash_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="cashEnabled"><i class="ti ti-cash me-1"></i>{{ __('settings.cash') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="momo_enabled" value="1"
                                       id="momoEnabled" {{ old('momo_enabled', $settings['momo_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="momoEnabled"><i class="ti ti-device-mobile me-1"></i>{{ __('settings.mobile_money') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="card_enabled" value="1"
                                       id="cardEnabled" {{ old('card_enabled', $settings['card_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="cardEnabled"><i class="ti ti-credit-card me-1"></i>{{ __('settings.card_payment') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="insurance_enabled" value="1"
                                       id="insuranceEnabled" {{ old('insurance_enabled', $settings['insurance_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="insuranceEnabled"><i class="ti ti-heart-handshake me-1"></i>{{ __('settings.insurance_settlements') }}</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="bank_transfer_enabled" value="1"
                                       id="bankEnabled" {{ old('bank_transfer_enabled', $settings['bank_transfer_enabled'] ?? '') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="bankEnabled"><i class="ti ti-building-bank me-1"></i>{{ __('settings.bank_transfer') }}</label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-bold mb-3">{{ __('settings.momo_settings') }}</h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('settings.merchant_id') }}</label>
                            <input type="text" name="momo_merchant_id" class="form-control"
                                   value="{{ old('momo_merchant_id', $settings['momo_merchant_id'] ?? '') }}">
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-bold mb-3">{{ __('settings.bank_transfer_details') }}</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.bank_name') }}</label>
                            <input type="text" name="bank_name" class="form-control"
                                   value="{{ old('bank_name', $settings['bank_name'] ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.account_number') }}</label>
                            <input type="text" name="bank_account_number" class="form-control"
                                   value="{{ old('bank_account_number', $settings['bank_account_number'] ?? '') }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">{{ __('settings.branch') }}</label>
                            <input type="text" name="bank_branch" class="form-control"
                                   value="{{ old('bank_branch', $settings['bank_branch'] ?? '') }}">
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('settings.save_changes') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
