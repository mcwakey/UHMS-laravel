@extends('layouts.app')
@section('title', __('accounting.supplier_payables'))

@php
    $variant = fn ($s) => match ($s) {
        'paid' => 'success', 'partially_paid' => 'info', 'pending' => 'warning',
        'overdue' => 'danger', 'cancelled', 'written_off' => 'secondary', default => 'secondary',
    };
    $acctVariant = fn ($s) => match ($s) { 'posted' => 'success', 'failed' => 'danger', 'reversed' => 'secondary', default => 'warning' };
@endphp

@section('content')
<x-page-header :title="__('accounting.supplier_payables')" icon="ti-file-dollar" description="Amounts the facility owes suppliers (Accounts Payable).">
    <x-slot:actions>
        <a href="{{ route('admin.accounts-payable.aging') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-clock-dollar me-1"></i>{{ __('accounting.ap_aging') }}</a>
        <a href="{{ route('admin.accounts-payable.payments') }}" class="btn btn-primary btn-sm"><i class="ti ti-cash me-1"></i>{{ __('accounting.supplier_payments') }}</a>
    </x-slot:actions>
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3"><div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
            <select name="supplier_id" class="form-select"><option value="">All Suppliers</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)request('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select"><option value="">Outstanding</option>
                @foreach($statuses as $st)<option value="{{ $st }}" @selected(request('status')===$st)>{{ __('statuses.default.' . $st) }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
        @if(request()->hasAny(['supplier_id','status']))<div class="col-md-1"><a href="{{ route('admin.accounts-payable.payables') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>@endif
    </form>
</div></div>

<div class="card"><div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead class="table-light"><tr>
            <th>Supplier</th><th>Reference</th><th class="text-end">Original</th><th class="text-end">Paid</th>
            <th class="text-end">Returns/Credits</th><th class="text-end">Balance</th><th>Due</th><th>Status</th><th>Accounting</th>
        </tr></thead>
        <tbody>
        @forelse($payables as $p)
            <tr>
                <td><a href="{{ route('admin.accounts-payable.statement', $p->supplier_id) }}" class="fw-medium text-primary">{{ $p->supplier?->name ?? '—' }}</a></td>
                <td><small>{{ $p->goodsReceivedNote?->grn_number ?? '—' }}@if($p->purchaseOrder) · {{ $p->purchaseOrder->po_number }}@endif</small></td>
                <td class="text-end">{{ number_format($p->original_amount, 2) }}</td>
                <td class="text-end">{{ number_format($p->paid_amount, 2) }}</td>
                <td class="text-end">{{ number_format((float)$p->return_amount + (float)$p->credit_note_amount + (float)$p->adjustment_amount, 2) }}</td>
                <td class="text-end fw-semibold">{{ number_format($p->balance, 2) }}</td>
                <td><small>{{ optional($p->due_date)->format('d M Y') ?? '—' }}</small></td>
                <td><span class="badge bg-{{ $variant($p->status) }}-subtle text-{{ $variant($p->status) }}">{{ __('statuses.default.' . $p->status) }}</span></td>
                <td><span class="badge bg-{{ $acctVariant($p->accounting_status) }}-subtle text-{{ $acctVariant($p->accounting_status) }}">{{ __('statuses.default.' . ($p->accounting_status ?? 'pending')) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="9"><x-empty-state icon="ti-file-dollar" :title="__('accounting.no_payables')" message="No outstanding supplier payables." /></td></tr>
        @endforelse
        </tbody>
    </table>
</div><div class="card-footer d-flex justify-content-end">{{ $payables->links() }}</div></div>
@endsection
