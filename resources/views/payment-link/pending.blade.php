@extends('layouts.public')

@section('title', __('payments.gateway.payment_pending'))

@section('content')
    @include('payment-link._summary')

    <div class="card border-0 shadow-sm text-center">
        <div class="card-body py-4">
            <div class="text-warning mb-2"><i class="ti ti-clock-hour-4" style="font-size:2.5rem;"></i></div>
            <h6 class="fw-bold">{{ __('payments.gateway.payment_pending') }}</h6>
            <p class="text-muted small">{{ __('payments.gateway.pending_instructions') }}</p>

            <form method="POST" action="{{ route('public.payments.verify', $token) }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="ti ti-refresh me-1"></i>{{ __('payments.gateway.check_status') }}
                </button>
            </form>
        </div>
    </div>
@endsection
