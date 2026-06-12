@extends('layouts.app')
@section('title', __('stock.stock_movement_title'))

@php use App\Enums\StockMovementDirection; @endphp
@php $isIn = $movement->movement_type->direction() === StockMovementDirection::IN; @endphp

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-0">{{ __('stock.stock_movement_title') }} #{{ $movement->id }}</h4>
        <div class="text-muted small">{{ $movement->movement_date?->format('d M Y H:i') }}</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.type') }}</small><span class="badge {{ $isIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-13">{{ $movement->movement_type->translatedLabel() }}</span></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.direction_label') }}</small><strong class="{{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? 'IN' : 'OUT' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.quantity') }}</small><strong class="{{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? '+' : '−' }}{{ rtrim(rtrim(number_format((float) $movement->quantity, 4, '.', ''), '0'), '.') }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.line_value') }}</small><strong>GH₵ {{ number_format((float) $movement->quantity * (float) $movement->unit_cost, 2) }}</strong></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0">{{ __('stock.movement_detail') }}</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.item') }}</small><strong>{{ $movement->product?->name ?? $movement->drug?->name ?? '—' }}</strong>@if($movement->product?->code) <span class="text-muted">({{ $movement->product->code }})</span>@elseif($movement->drug?->unit) <span class="text-muted">({{ $movement->drug->unit }})</span>@endif</div>
            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.location') }}</small><strong>{{ $movement->location?->name ?? '—' }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.performed_by') }}</small><strong>{{ trim(($movement->performedBy?->first_name ?? '') . ' ' . ($movement->performedBy?->last_name ?? '')) ?: '—' }}</strong></div>

            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.unit_cost') }}</small><strong>GH₵ {{ number_format((float) $movement->unit_cost, 2) }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.batch_no') }}</small><strong>{{ $movement->batch_no ?: '—' }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.expiry_date_label') }}</small><strong>{{ $movement->expiry_date?->format('d M Y') ?? '—' }}</strong></div>

            <div class="col-md-4"><small class="text-muted d-block">{{ __('stock.date_time') }}</small><strong>{{ $movement->movement_date?->format('d M Y H:i') ?? '—' }}</strong></div>
            <div class="col-md-8"><small class="text-muted d-block">{{ __('stock.source_label') }}</small><strong>{{ $movement->source_type ? class_basename($movement->source_type) . ($movement->source_id ? ' #' . $movement->source_id : '') : __('stock.manual_entry') }}</strong></div>

            <div class="col-12"><small class="text-muted d-block">{{ __('stock.notes') }}</small>{{ $movement->notes ?: '—' }}</div>
        </div>
    </div>
</div>

{{-- Phase 6: inventory accounting (cost gated by permission) --}}
<div class="card mt-3">
    <div class="card-header"><h6 class="mb-0"><i class="ti ti-report-money me-1"></i>{{ __('stock.inventory_accounting') }}</h6></div>
    <div class="card-body">
        <div class="row g-3">
            @can('inventory.cost.view')
                <div class="col-md-3"><small class="text-muted d-block">{{ __('stock.total_cost') }}</small><strong>GH₵ {{ number_format((float) $movement->total_cost, 2) }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">{{ __('stock.valuation_method') }}</small><strong>{{ $movement->valuation_method ? ucwords(str_replace('_',' ',$movement->valuation_method)) : '—' }}</strong></div>
            @endcan
            @php $acct = $movement->accounting_status; $acctVar = match($acct){ 'posted'=>'success','failed'=>'danger','reversed'=>'secondary','not_applicable'=>'light', default=>'warning' }; @endphp
            <div class="col-md-3"><small class="text-muted d-block">{{ __('stock.accounting_status') }}</small><span class="badge bg-{{ $acctVar }}-subtle text-{{ $acctVar === 'light' ? 'muted' : $acctVar }}">{{ $acct ? ucwords(str_replace('_',' ',$acct)) : __('stock.pending') }}</span></div>
            <div class="col-md-3"><small class="text-muted d-block">{{ __('stock.journal_entry') }}</small><strong>{{ $movement->journal_entry_id ? '#'.$movement->journal_entry_id : '—' }}</strong></div>
            @if($movement->accounting_error)
                <div class="col-12"><small class="text-muted d-block">{{ __('stock.posting_error') }}</small><span class="text-danger small">{{ $movement->accounting_error }}</span></div>
            @endif
        </div>
    </div>
</div>
@endsection
