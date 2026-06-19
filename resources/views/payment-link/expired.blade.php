@extends('layouts.public')

@section('title', __('payments.gateway.payment_link_expired'))

@section('content')
    <div class="card border-0 shadow-sm text-center">
        <div class="card-body py-4">
            <div class="text-muted mb-2"><i class="ti ti-link-off" style="font-size:2.5rem;"></i></div>
            <h6 class="fw-bold">{{ $state === 'cancelled' ? __('payments.gateway.payment_link_cancelled') : __('payments.gateway.payment_link_expired') }}</h6>
            <p class="text-muted small">{{ __('payments.gateway.expired_instructions') }}</p>
        </div>
    </div>
@endsection
