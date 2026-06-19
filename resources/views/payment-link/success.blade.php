@extends('layouts.public')

@section('title', __('payments.gateway.payment_successful'))

@section('content')
    <div class="card border-0 shadow-sm text-center mb-3">
        <div class="card-body py-4">
            <div class="text-success mb-2"><i class="ti ti-circle-check" style="font-size:3rem;"></i></div>
            <h5 class="fw-bold">{{ __('payments.gateway.payment_successful') }}</h5>
            <div class="display-6 fw-bold my-2">{{ $summary['currency'] }} {{ $summary['amount_due'] }}</div>
            @if($summary['invoice_number'])<div class="text-muted small">{{ $summary['invoice_number'] }}</div>@endif
        </div>
    </div>

    <a href="{{ route('public.payments.receipt', $token) }}" class="btn btn-primary w-100">
        <i class="ti ti-receipt me-1"></i>{{ __('payments.gateway.view_receipt') }}
    </a>
@endsection
