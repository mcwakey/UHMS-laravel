{{-- Safe invoice summary — no internal ids, no clinical data. --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="text-muted small">{{ __('payments.gateway.pay_invoice') }}</div>
            @isset($summary['invoice_number'])
                <div class="small">{{ $summary['invoice_number'] }}</div>
            @endisset
        </div>
        <div class="text-center my-3">
            <div class="text-muted small">{{ __('payments.gateway.amount_due') }}</div>
            <div class="display-6 fw-bold">{{ $summary['currency'] }} {{ $summary['amount_due'] }}</div>
        </div>
        <dl class="row mb-0 small">
            @if($summary['payer_name'])
                <dt class="col-5 text-muted">{{ __('payments.gateway.payer_name') }}</dt>
                <dd class="col-7">{{ $summary['payer_name'] }}</dd>
            @endif
            @if($summary['expires_at'])
                <dt class="col-5 text-muted">{{ __('payments.gateway.expires_at') }}</dt>
                <dd class="col-7">{{ $summary['expires_at'] }}</dd>
            @endif
        </dl>
    </div>
</div>
