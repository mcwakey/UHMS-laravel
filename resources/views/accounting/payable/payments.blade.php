@extends('layouts.app')
@section('title', __('accounting.supplier_payments'))

@php $acctVariant = fn ($s) => match ($s) { 'posted' => 'success', 'failed' => 'danger', 'reversed' => 'secondary', default => 'warning' }; @endphp

@section('content')
<x-page-header :title="__('accounting.supplier_payments')" icon="ti-cash" :description="__('accounting.supplier_payments_description')">
    <x-slot:actions>
        <a href="{{ route('admin.accounts-payable.payables') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-file-dollar me-1"></i>{{ __('accounting.payables') }}</a>
        @can('supplier_payments.create')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal"><i class="ti ti-plus me-1"></i>{{ __('accounting.record_payment') }}</button>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <select name="supplier_id" class="form-select"><option value="">{{ __('accounting.all_suppliers') }}</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>{{ __('accounting.payment_number') }}</th><th>{{ __('accounting.supplier') }}</th><th>{{ __('accounting.date') }}</th><th class="text-end">{{ __('accounting.amount') }}</th><th>{{ __('accounting.method') }}</th><th>{{ __('accounting.accounting') }}</th><th>{{ __('accounting.by') }}</th><th class="text-end">{{ __('accounting.actions') }}</th>
        </tr></thead>
        <tbody>
        @forelse($payments as $p)
            <tr @class(['opacity-50' => $p->isReversed()])>
                <td class="fw-medium">{{ $p->payment_number }}</td>
                <td><a href="{{ route('admin.accounts-payable.statement', $p->supplier_id) }}" class="text-primary">{{ $p->supplier?->name ?? '—' }}</a></td>
                <td><small>{{ optional($p->payment_date)->format('d M Y') }}</small></td>
                <td class="text-end fw-semibold">{{ number_format($p->amount, 2) }}</td>
                <td><span class="badge bg-light text-dark border">{{ __('statuses.default.' . $p->payment_method) }}</span></td>
                <td><span class="badge bg-{{ $acctVariant($p->accounting_status) }}-subtle text-{{ $acctVariant($p->accounting_status) }}">{{ __('statuses.default.' . ($p->accounting_status ?? 'pending')) }}</span>@if($p->isReversed())<span class="badge bg-secondary ms-1">{{ __('statuses.default.reversed') }}</span>@endif</td>
                <td><small>{{ trim(($p->createdByUser?->first_name ?? '').' '.($p->createdByUser?->last_name ?? '')) ?: '—' }}</small></td>
                <td class="text-end">
                    @can('supplier_payments.reverse')
                        @if(! $p->isReversed())
                        <x-confirm-form :action="route('admin.accounts-payable.payments.reverse', $p)" method="POST"
                            :button-label="__('accounting.reverse')" button-class="btn btn-sm btn-outline-danger" icon="ti-arrow-back-up"
                            :confirm-title="__('accounting.reverse_this_payment')" :confirm-text="__('accounting.supplier_balance_restored')"
                            :confirm-button="__('accounting.reverse')" :require-reason="true" reason-name="reason" :reason-placeholder="__('accounting.reason_for_reversal')" />
                        @endif
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="8"><x-empty-state icon="ti-cash" :title="__('accounting.no_payments')" :message="__('accounting.no_supplier_payments_recorded')" /></td></tr>
        @endforelse
        </tbody>
    </table>
</div><div class="card-footer d-flex justify-content-end">{{ $payments->links() }}</div></div>

@can('supplier_payments.create')
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.accounts-payable.payments.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">{{ __('accounting.record_supplier_payment') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">{{ __('accounting.supplier') }} <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select select2" required><option value="">{{ __('accounting.select_supplier') }}</option>
                        @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">{{ __('accounting.amount') }} <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required></div>
                    <div class="col-6"><label class="form-label">{{ __('accounting.method') }} <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">{{ __('accounting.cash') }}</option><option value="bank">{{ __('accounting.bank') }}</option><option value="mobile_money">{{ __('accounting.mobile_money') }}</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label">{{ __('accounting.date') }}</label><input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}"></div>
                    <div class="col-6"><label class="form-label">{{ __('accounting.reference') }}</label><input name="reference" class="form-control" value="{{ old('reference') }}" placeholder="{{ __('accounting.cheque_txn_no') }}"></div>
                    <div class="col-12"><label class="form-label">{{ __('accounting.notes') }}</label><input name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}"></div>
                </div>
                <small class="text-muted d-block mt-2"><i class="ti ti-info-circle me-1"></i>{{ __('accounting.payment_cannot_exceed_balance') }}</small>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button><button class="btn btn-primary">{{ __('accounting.record_payment') }}</button></div>
        </form>
    </div>
</div>
@endcan
@endsection
