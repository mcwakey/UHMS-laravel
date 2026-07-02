@extends('layouts.app')

@section('title', __('payments.gateway.payment_transaction'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="$transaction->payment_reference" :description="__('payments.gateway.payment_transaction')" icon="ti-cash-register"
            :breadcrumbs="[
                ['label' => __('payments.gateway.payment_transactions'), 'url' => route('admin.integrations.payments.transactions.index')],
                ['label' => $transaction->payment_reference],
            ]">
            <x-slot:actions>
                <x-status-badge :status="$transaction->status" domain="payment_transaction" />
                @can('integrations.payments.transactions.verify')
                    @unless($transaction->isSuccessful())
                        <x-confirm-form :action="route('admin.integrations.payments.transactions.verify', $transaction)"
                            buttonClass="btn btn-outline-primary btn-sm" icon="ti-refresh"
                            :buttonLabel="__('payments.gateway.manual_verify')"
                            :confirmTitle="__('payments.gateway.verify_payment')" :confirmText="__('payments.gateway.manual_verify')" />
                    @endunless
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5">{{ __('payments.gateway.amount') }}</dt>
                            <dd class="col-7 fw-semibold">{{ $transaction->currency }} {{ number_format((float) $transaction->amount, 2) }}</dd>
                            <dt class="col-5">{{ __('integrations.provider') }}</dt><dd class="col-7">{{ $transaction->provider?->name ?? $transaction->provider_code }}</dd>
                            <dt class="col-5">{{ __('payments.gateway.method') }}</dt><dd class="col-7">{{ $transaction->payment_method ?? '—' }}</dd>
                            <dt class="col-5">{{ __('payments.gateway.provider_transaction_id') }}</dt><dd class="col-7">{{ $transaction->provider_transaction_id ?? '—' }}</dd>
                            <dt class="col-5">{{ __('invoices.invoice') }}</dt><dd class="col-7">{{ $transaction->invoice?->invoice_number ?? '—' }}</dd>
                            <dt class="col-5">{{ __('payments.gateway.linked_payment') }}</dt>
                            <dd class="col-7">{{ $transaction->payment?->payment_number ?? '—' }}</dd>
                            <dt class="col-5">{{ __('payments.gateway.payer_phone') }}</dt><dd class="col-7"><x-patient-protected-field field="phone" :value="$transaction->payer_phone" /></dd>
                            <dt class="col-5">{{ __('sms.created_at') }}</dt><dd class="col-7">{{ $transaction->created_at?->format('Y-m-d H:i') }}</dd>
                        </dl>
                        @if($transaction->error_message)
                            <div class="alert alert-danger mt-3 mb-0 py-2 small">{{ $transaction->error_message }}</div>
                        @endif
                        @php $instructions = data_get($transaction->metadata_snapshot, 'instructions'); @endphp
                        @if($instructions)
                            <div class="alert alert-info mt-3 mb-0 py-2 small">{{ $instructions }}</div>
                        @endif
                    </div>
                </div>

                @can('integrations.payments.refunds.manage')
                    @if($transaction->hasUhmsPayment())
                        <div class="card border-0 shadow-sm mt-3">
                            <div class="card-header bg-transparent"><strong>{{ __('payments.gateway.request_refund') }}</strong></div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.integrations.payments.refunds.store', $transaction) }}">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small">{{ __('payments.gateway.amount') }}</label>
                                        <input type="number" step="0.01" min="0.01" max="{{ (float) $transaction->amount }}" name="amount" class="form-control form-control-sm" value="{{ (float) $transaction->amount }}">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small">{{ __('payments.gateway.reason') }}</label>
                                        <input type="text" name="reason" class="form-control form-control-sm" maxlength="500">
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ti ti-receipt-refund me-1"></i>{{ __('payments.gateway.request_refund') }}</button>
                                </form>
                            </div>
                        </div>
                    @endif
                @endcan
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent"><strong>{{ __('payments.gateway.attempts') }}</strong></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('sms.type') }}</th>
                                        <th>{{ __('integrations.status') }}</th>
                                        <th>HTTP</th>
                                        <th>{{ __('sms.created_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transaction->attempts as $attempt)
                                        <tr>
                                            <td><span class="badge badge-soft-secondary">{{ $attempt->attempt_type }}</span></td>
                                            <td><x-status-badge :status="$attempt->status" domain="default" size="sm" /></td>
                                            <td class="small">{{ $attempt->http_status ?? '—' }}</td>
                                            <td class="small text-muted">{{ $attempt->created_at?->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted py-3">—</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if($transaction->refunds->isNotEmpty())
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-transparent"><strong>{{ __('payments.gateway.refunds') }}</strong></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('payments.gateway.provider_reference') }}</th>
                                            <th>{{ __('payments.gateway.amount') }}</th>
                                            <th>{{ __('integrations.status') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transaction->refunds as $refund)
                                            <tr>
                                                <td><code>{{ $refund->refund_reference }}</code></td>
                                                <td>{{ $refund->currency }} {{ number_format((float) $refund->amount, 2) }}</td>
                                                <td><x-status-badge :status="$refund->status" domain="payment_refund" size="sm" /></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
