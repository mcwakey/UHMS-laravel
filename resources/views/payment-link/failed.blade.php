@extends('layouts.public')

@section('title', __('payments.gateway.payment_unsuccessful'))

@section('content')
    @include('payment-link._summary')

    <div class="card border-0 shadow-sm text-center">
        <div class="card-body py-4">
            <div class="text-danger mb-2"><i class="ti ti-circle-x" style="font-size:2.5rem;"></i></div>
            <h6 class="fw-bold">{{ __('payments.gateway.payment_unsuccessful') }}</h6>
            <p class="text-muted small">{{ __('payments.gateway.failed_instructions') }}</p>

            <a href="{{ route('public.payments.show', $token) }}" class="btn btn-outline-primary w-100">
                <i class="ti ti-refresh me-1"></i>{{ __('payments.gateway.try_again') }}
            </a>
        </div>
    </div>
@endsection
