@extends('layouts.app')

@section('title', __('payments.gateway.payment_reconciliation'))

@php
    $cards = [
        ['key' => 'pending', 'label' => __('payments.gateway.pending_transactions'), 'variant' => 'warning'],
        ['key' => 'stale_pending', 'label' => __('payments.gateway.stale_pending_transactions'), 'variant' => 'danger'],
        ['key' => 'verified_not_linked', 'label' => __('payments.gateway.verified_not_linked'), 'variant' => 'info'],
        ['key' => 'paid_linked', 'label' => __('payments.gateway.paid_linked'), 'variant' => 'success'],
        ['key' => 'failed', 'label' => __('payments.gateway.payment_failed'), 'variant' => 'danger'],
        ['key' => 'amount_mismatch', 'label' => __('payments.gateway.amount_mismatch'), 'variant' => 'danger'],
        ['key' => 'unknown_callbacks', 'label' => __('payments.gateway.unknown_callback'), 'variant' => 'secondary'],
        ['key' => 'callbacks_awaiting_verification', 'label' => __('payments.gateway.callbacks_awaiting_verification'), 'variant' => 'warning'],
        ['key' => 'provider_errors', 'label' => __('payments.gateway.provider_errors'), 'variant' => 'danger'],
    ];
@endphp

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.payment_reconciliation')" :description="__('payments.gateway.payment_gateway')" icon="ti-arrows-diff" />

        <div class="row g-2 mb-3">
            @foreach($cards as $card)
                <div class="col-6 col-md-4 col-xl-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body py-2">
                            <div class="fs-3 fw-bold text-{{ $card['variant'] }}">{{ $metrics[$card['key']] ?? 0 }}</div>
                            <div class="text-muted" style="font-size:.78rem;">{{ $card['label'] }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <form method="GET" class="card border-0 shadow-sm mb-3">
            <div class="card-body row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ __('integrations.status') }}</label>
                    <input type="text" name="status" value="{{ request('status') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">{{ __('payments.gateway.payment_reference') }}</label>
                    <input type="text" name="reference" value="{{ request('reference') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ __('payments.gateway.payer_phone') }}</label>
                    <input type="text" name="payer_phone" value="{{ request('payer_phone') }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">{{ __('payments.gateway.linked_payment') }}</label>
                    <select name="linked" class="form-select form-select-sm">
                        <option value="">—</option>
                        <option value="linked" @selected(request('linked')==='linked')>{{ __('integrations.yes') }}</option>
                        <option value="unlinked" @selected(request('linked')==='unlinked')>{{ __('integrations.no') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($transactions->isEmpty())
                    <x-empty-state icon="ti-arrows-diff" :message="__('payments.gateway.no_transactions')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('payments.gateway.payment_reference') }}</th>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('payments.gateway.amount') }}</th>
                                    <th>{{ __('integrations.status') }}</th>
                                    <th>{{ __('payments.gateway.linked_payment') }}</th>
                                    <th class="text-end">{{ __('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    <tr>
                                        <td><a href="{{ route('admin.integrations.payments.transactions.show', $txn) }}"><code>{{ $txn->payment_reference }}</code></a></td>
                                        <td>{{ $txn->provider?->name ?? $txn->provider_code }}</td>
                                        <td>{{ $txn->currency }} {{ number_format((float) $txn->amount, 2) }}</td>
                                        <td><x-status-badge :status="$txn->status" domain="payment_transaction" /></td>
                                        <td>{{ $txn->uhms_payment_id ? '#'.$txn->uhms_payment_id : '—' }}</td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @can('integrations.payments.reconciliation.verify')
                                                    @unless($txn->isSuccessful())
                                                        <x-confirm-form :action="route('admin.integrations.payments.reconciliation.recheck', $txn)"
                                                            buttonClass="btn btn-sm btn-outline-primary" icon="ti-refresh"
                                                            :buttonLabel="__('payments.gateway.recheck')" :confirmTitle="__('payments.gateway.manual_recheck')" :confirmText="__('payments.gateway.manual_recheck')" />
                                                    @endunless
                                                @endcan
                                                @can('integrations.payments.reconciliation.expire')
                                                    @unless($txn->isSuccessful() || $txn->hasUhmsPayment())
                                                        <x-confirm-form :action="route('admin.integrations.payments.transactions.expire', $txn)"
                                                            buttonClass="btn btn-sm btn-outline-secondary" icon="ti-clock-off"
                                                            :buttonLabel="__('payments.gateway.payment_expired')" :confirmTitle="__('payments.gateway.payment_expired')" :confirmText="__('payments.gateway.payment_expired')" />
                                                    @endunless
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="card-footer bg-transparent">{{ $transactions->links() }}</div>
        </div>
    </div>
</div>
@endsection
