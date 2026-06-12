@extends('layouts.app')
@section('title', __('stock.stock_requisition'))

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-0">{{ $stockRequisition->requisition_number }}</h4>
        <div class="text-muted small">{{ $stockRequisition->department?->name }} - {{ $stockRequisition->requested_at?->format('d M Y H:i') }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
        @if(in_array($stockRequisition->status, [\App\Enums\StockRequisitionStatus::SUBMITTED, \App\Enums\StockRequisitionStatus::APPROVED, \App\Enums\StockRequisitionStatus::PARTIALLY_APPROVED], true))
            <x-confirm-form :action="route('admin.store.stock-requisitions.cancel', $stockRequisition)" method="POST" button-label="Cancel" button-class="btn btn-outline-danger btn-sm" icon="ti-x" confirm-title="Cancel this requisition?" confirm-text="The requisition will be cancelled." confirm-button="Yes, cancel" />
        @endif
        @if(in_array($stockRequisition->status, [\App\Enums\StockRequisitionStatus::APPROVED, \App\Enums\StockRequisitionStatus::PARTIALLY_APPROVED], true))
            <x-confirm-form :action="route('admin.store.stock-requisitions.issue', $stockRequisition)" method="POST" button-label="Issue" button-class="btn btn-primary btn-sm" icon="ti-transfer-out" confirm-title="Issue approved quantities?" confirm-text="Approved quantities will be issued from Main Store." confirm-button="Yes, issue" />
        @endif
        @if($stockRequisition->status === \App\Enums\StockRequisitionStatus::AWAITING_ACKNOWLEDGEMENT)
            <x-confirm-form :action="route('admin.store.stock-requisitions.acknowledge', $stockRequisition)" method="POST" button-label="Acknowledge" button-class="btn btn-success btn-sm" icon="ti-check" confirm-title="Acknowledge receipt?" confirm-text="Items will be received into department stock." confirm-button="Yes, acknowledge" />
        @endif
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.status') }}</small><x-status-badge :status="$stockRequisition->status" /></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.requested_by') }}</small><strong>{{ $stockRequisition->requestedByUser?->name ?: '-' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.approved_by') }}</small><strong>{{ $stockRequisition->approvedByUser?->name ?: '-' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.issued_by') }}</small><strong>{{ $stockRequisition->issuedByUser?->name ?: '-' }}</strong></div></div></div>
</div>

@if($stockRequisition->status === \App\Enums\StockRequisitionStatus::SUBMITTED)
<form method="POST" action="{{ route('admin.store.stock-requisitions.approve', $stockRequisition) }}" class="card mb-3">
    @csrf
    <div class="card-header"><h6 class="mb-0">{{ __('stock.approve_quantities') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>{{ __('stock.product') }}</th><th class="text-end">{{ __('stock.requested_col') }}</th><th style="width: 180px;">{{ __('stock.approved_col') }}</th></tr></thead>
            <tbody>
            @foreach($stockRequisition->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? '-' }}<br><small class="text-muted">{{ $item->product?->code }}</small></td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity_requested, 4, '.', ''), '0'), '.') }}</td>
                    <td><input type="number" name="items[{{ $item->id }}][approved_quantity]" class="form-control form-control-sm" step="0.0001" min="0" max="{{ $item->quantity_requested }}" value="{{ old("items.{$item->id}.approved_quantity", rtrim(rtrim(number_format((float) $item->quantity_requested, 4, '.', ''), '0'), '.')) }}"></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end"><button class="btn btn-primary">{{ __('stock.approve') }}</button></div>
</form>
@endif

<div class="card">
    <div class="card-header"><h6 class="mb-0">{{ __('stock.requisition_items') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>{{ __('stock.product') }}</th><th class="text-end">{{ __('stock.requested_col') }}</th><th class="text-end">{{ __('stock.approved_col') }}</th><th class="text-end">{{ __('stock.issued_col') }}</th><th class="text-end">{{ __('stock.acknowledged_col') }}</th><th>{{ __('stock.notes') }}</th></tr>
            </thead>
            <tbody>
            @foreach($stockRequisition->items as $item)
                <tr>
                    <td>{{ $item->product?->name ?? '-' }}<br><small class="text-muted">{{ $item->product?->code }}</small></td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity_requested, 4, '.', ''), '0'), '.') }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity_approved, 4, '.', ''), '0'), '.') }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity_issued, 4, '.', ''), '0'), '.') }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format((float) $item->quantity_acknowledged, 4, '.', ''), '0'), '.') }}</td>
                    <td>{{ $item->notes ?: '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($stockRequisition->notes)
<div class="card mt-3"><div class="card-body"><small class="text-muted d-block">{{ __('stock.notes') }}</small>{{ $stockRequisition->notes }}</div></div>
@endif
@endsection
