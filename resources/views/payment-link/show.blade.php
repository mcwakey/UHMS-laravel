@extends('layouts.public')

@section('title', __('payments.gateway.pay_invoice'))

@section('content')
    @include('payment-link._summary')

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('public.payments.initiate', $token) }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('payments.gateway.payer_phone') }} <span class="text-danger">*</span></label>
                    <input type="tel" name="payer_phone" value="{{ old('payer_phone') }}" class="form-control form-control-lg @error('payer_phone') is-invalid @enderror" required>
                    @error('payer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('payments.gateway.payer_name') }}</label>
                    <input type="text" name="payer_name" value="{{ old('payer_name', $summary['payer_name']) }}" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary btn-lg w-100">
                    <i class="ti ti-device-mobile me-1"></i>{{ __('payments.gateway.pay_now') }}
                </button>
            </form>
        </div>
    </div>

    <p class="text-muted small text-center mt-3">{{ __('payments.gateway.public_instructions') }}</p>
@endsection
