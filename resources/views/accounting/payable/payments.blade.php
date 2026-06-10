@extends('layouts.app')
@section('title', 'Supplier Payments')

@php $acctVariant = fn ($s) => match ($s) { 'posted' => 'success', 'failed' => 'danger', 'reversed' => 'secondary', default => 'warning' }; @endphp

@section('content')
<x-page-header title="Supplier Payments" icon="ti-cash" description="Payments made to suppliers (Dr Supplier Payables / Cr Cash·Bank).">
    <x-slot:actions>
        <a href="{{ route('admin.accounts-payable.payables') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-file-dollar me-1"></i>Payables</a>
        @can('supplier_payments.create')
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recordPaymentModal"><i class="ti ti-plus me-1"></i>Record Payment</button>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <select name="supplier_id" class="form-select"><option value="">All Suppliers</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
    </form>
</div></div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>Payment #</th><th>Supplier</th><th>Date</th><th class="text-end">Amount</th><th>Method</th><th>Accounting</th><th>By</th><th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($payments as $p)
            <tr @class(['opacity-50' => $p->isReversed()])>
                <td class="fw-medium">{{ $p->payment_number }}</td>
                <td><a href="{{ route('admin.accounts-payable.statement', $p->supplier_id) }}" class="text-primary">{{ $p->supplier?->name ?? '—' }}</a></td>
                <td><small>{{ optional($p->payment_date)->format('d M Y') }}</small></td>
                <td class="text-end fw-semibold">{{ number_format($p->amount, 2) }}</td>
                <td><span class="badge bg-light text-dark border">{{ ucwords(str_replace('_',' ',$p->payment_method)) }}</span></td>
                <td><span class="badge bg-{{ $acctVariant($p->accounting_status) }}-subtle text-{{ $acctVariant($p->accounting_status) }}">{{ ucfirst($p->accounting_status ?? 'pending') }}</span>@if($p->isReversed())<span class="badge bg-secondary ms-1">Reversed</span>@endif</td>
                <td><small>{{ trim(($p->createdByUser?->first_name ?? '').' '.($p->createdByUser?->last_name ?? '')) ?: '—' }}</small></td>
                <td class="text-end">
                    @can('supplier_payments.reverse')
                        @if(! $p->isReversed())
                        <x-confirm-form :action="route('admin.accounts-payable.payments.reverse', $p)" method="POST"
                            button-label="Reverse" button-class="btn btn-sm btn-outline-danger" icon="ti-arrow-back-up"
                            confirm-title="Reverse this payment?" confirm-text="A reversal journal entry will be created and the supplier balance restored."
                            confirm-button="Reverse" :require-reason="true" reason-name="reason" reason-placeholder="Reason for reversal" />
                        @endif
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="8"><x-empty-state icon="ti-cash" title="No payments" message="No supplier payments recorded yet." /></td></tr>
        @endforelse
        </tbody>
    </table>
</div><div class="card-footer d-flex justify-content-end">{{ $payments->links() }}</div></div>

@can('supplier_payments.create')
<div class="modal fade" id="recordPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.accounts-payable.payments.store') }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Record Supplier Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select select2" required><option value="">Select supplier</option>
                        @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">Amount <span class="text-danger">*</span></label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount') }}" required></div>
                    <div class="col-6"><label class="form-label">Method <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option><option value="bank">Bank</option><option value="mobile_money">Mobile Money</option>
                        </select>
                    </div>
                    <div class="col-6"><label class="form-label">Date</label><input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', now()->toDateString()) }}"></div>
                    <div class="col-6"><label class="form-label">Reference</label><input name="reference" class="form-control" value="{{ old('reference') }}" placeholder="Cheque/txn no."></div>
                    <div class="col-12"><label class="form-label">Notes</label><input name="notes" class="form-control" maxlength="1000" value="{{ old('notes') }}"></div>
                </div>
                <small class="text-muted d-block mt-2"><i class="ti ti-info-circle me-1"></i>Payment cannot exceed the supplier's outstanding balance.</small>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Record Payment</button></div>
        </form>
    </div>
</div>
@endcan
@endsection
