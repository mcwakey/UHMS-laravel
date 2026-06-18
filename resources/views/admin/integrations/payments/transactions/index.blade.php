@extends('layouts.app')

@section('title', __('payments.gateway.payment_transactions'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.payment_api_transactions')" :description="__('payments.gateway.payment_gateway')" icon="ti-cash-register">
            <x-slot:actions>
                @can('integrations.payments.transactions.initiate')
                    <a href="{{ route('admin.integrations.payments.transactions.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i>{{ __('payments.gateway.initiate_payment') }}
                    </a>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if($transactions->isEmpty())
                    <x-empty-state icon="ti-cash-register" :message="__('payments.gateway.no_transactions')" />
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('payments.gateway.payment_reference') }}</th>
                                    <th>{{ __('integrations.provider') }}</th>
                                    <th>{{ __('payments.gateway.amount') }}</th>
                                    <th>{{ __('invoices.invoice') }}</th>
                                    <th>{{ __('integrations.status') }}</th>
                                    <th>{{ __('sms.created_at') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactions as $txn)
                                    <tr>
                                        <td><code>{{ $txn->payment_reference }}</code></td>
                                        <td>{{ $txn->provider?->name ?? $txn->provider_code }}</td>
                                        <td class="fw-semibold">{{ $txn->currency }} {{ number_format((float) $txn->amount, 2) }}</td>
                                        <td>{{ $txn->invoice?->invoice_number ?? '—' }}</td>
                                        <td><x-status-badge :status="$txn->status" domain="payment_transaction" /></td>
                                        <td class="small text-muted">{{ $txn->created_at?->format('Y-m-d H:i') }}</td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.integrations.payments.transactions.show', $txn) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
