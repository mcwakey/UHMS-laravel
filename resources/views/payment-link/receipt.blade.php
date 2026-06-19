@extends('layouts.public')

@section('title', __('payments.receipt'))

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="text-center mb-3">
                <div class="text-success mb-1"><i class="ti ti-circle-check" style="font-size:2.5rem;"></i></div>
                <h6 class="fw-bold mb-0">{{ __('payments.receipt') }}</h6>
            </div>
            <dl class="row mb-0 small">
                <dt class="col-6 text-muted">{{ __('payments.receipt_number') }}</dt>
                <dd class="col-6 text-end">{{ $receipt['receipt_number'] ?? '—' }}</dd>
                <dt class="col-6 text-muted">{{ __('payments.gateway.amount') }}</dt>
                <dd class="col-6 text-end fw-bold">{{ $receipt['currency'] }} {{ $receipt['amount_paid'] }}</dd>
                @if($receipt['invoice_number'])
                    <dt class="col-6 text-muted">{{ __('invoices.invoice') }}</dt>
                    <dd class="col-6 text-end">{{ $receipt['invoice_number'] }}</dd>
                @endif
                <dt class="col-6 text-muted">{{ __('payments.payment_date') }}</dt>
                <dd class="col-6 text-end">{{ $receipt['paid_at'] ?? '—' }}</dd>
                <dt class="col-6 text-muted">{{ __('payments.gateway.facility_label') }}</dt>
                <dd class="col-6 text-end">{{ $receipt['facility'] }}</dd>
            </dl>
        </div>
    </div>
    <p class="text-muted small text-center mt-3">{{ __('payments.thank_you_short') }}</p>
@endsection
