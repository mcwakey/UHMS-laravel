@extends('layouts.app')

@section('title', __('payments.gateway.payment_request_links'))

@section('content')
<div class="page-wrapper">
    <div class="content">

        <x-page-header :title="__('payments.gateway.payment_request_links')" :description="__('payments.gateway.payment_gateway')" icon="ti-link" />

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent"><strong>{{ __('payments.gateway.create_payment_link') }}</strong></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.integrations.payments.request-links.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">{{ __('invoices.invoice') }} ID</label>
                                <input type="number" name="invoice_id" value="{{ old('invoice_id') }}" class="form-control form-control-sm">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">{{ __('payments.gateway.amount') }} <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">{{ __('payments.gateway.expires_at') }}</label>
                                <input type="date" name="expires_at" value="{{ old('expires_at') }}" class="form-control form-control-sm">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="ti ti-plus me-1"></i>{{ __('payments.gateway.create_payment_link') }}</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if($requestLinks->isEmpty())
                            <x-empty-state icon="ti-link" :message="__('payments.gateway.no_transactions')" />
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{ __('payments.gateway.payment_request_link') }}</th>
                                            <th>{{ __('invoices.invoice') }}</th>
                                            <th>{{ __('payments.gateway.amount') }}</th>
                                            <th>{{ __('payments.gateway.link_status') }}</th>
                                            <th>{{ __('payments.gateway.expires_at') }}</th>
                                            <th class="text-end">{{ __('common.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($requestLinks as $link)
                                            <tr>
                                                <td class="small"><code>{{ $link->link_uuid }}</code></td>
                                                <td>{{ $link->invoice?->invoice_number ?? '—' }}</td>
                                                <td>{{ $link->currency }} {{ number_format((float) $link->amount, 2) }}</td>
                                                <td><span class="badge bg-{{ $link->status === 'active' ? 'success' : 'secondary' }}">{{ $link->status }}</span></td>
                                                <td class="small text-muted">{{ $link->expires_at?->format('Y-m-d') ?? '—' }}</td>
                                                <td class="text-end">
                                                    @if($link->status === 'active')
                                                        <x-confirm-form :action="route('admin.integrations.payments.request-links.expire', $link)"
                                                            buttonClass="btn btn-sm btn-outline-secondary" icon="ti-clock-off"
                                                            :buttonLabel="__('payments.gateway.expire_payment_link')" :confirmTitle="__('payments.gateway.expire_payment_link')" :confirmText="__('payments.gateway.expire_payment_link')" />
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent">{{ $requestLinks->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
