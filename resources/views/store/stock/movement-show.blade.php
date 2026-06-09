@extends('layouts.app')
@section('title', 'Stock Movement')

@php use App\Enums\StockMovementDirection; @endphp
@php $isIn = $movement->movement_type->direction() === StockMovementDirection::IN; @endphp

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-0">Stock Movement #{{ $movement->id }}</h4>
        <div class="text-muted small">{{ $movement->movement_date?->format('d M Y H:i') }}</div>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>Back</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Type</small><span class="badge {{ $isIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} fs-13">{{ $movement->movement_type->label() }}</span></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Direction</small><strong class="{{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? 'IN' : 'OUT' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Quantity</small><strong class="{{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? '+' : '−' }}{{ rtrim(rtrim(number_format((float) $movement->quantity, 4, '.', ''), '0'), '.') }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">Line Value</small><strong>GH₵ {{ number_format((float) $movement->quantity * (float) $movement->unit_cost, 2) }}</strong></div></div></div>
</div>

<div class="card">
    <div class="card-header"><h6 class="mb-0">Movement Detail</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><small class="text-muted d-block">Item</small><strong>{{ $movement->product?->name ?? $movement->drug?->name ?? '—' }}</strong>@if($movement->product?->code) <span class="text-muted">({{ $movement->product->code }})</span>@elseif($movement->drug?->unit) <span class="text-muted">({{ $movement->drug->unit }})</span>@endif</div>
            <div class="col-md-4"><small class="text-muted d-block">Location</small><strong>{{ $movement->location?->name ?? '—' }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Performed By</small><strong>{{ trim(($movement->performedBy?->first_name ?? '') . ' ' . ($movement->performedBy?->last_name ?? '')) ?: '—' }}</strong></div>

            <div class="col-md-4"><small class="text-muted d-block">Unit Cost</small><strong>GH₵ {{ number_format((float) $movement->unit_cost, 2) }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Batch No</small><strong>{{ $movement->batch_no ?: '—' }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Expiry Date</small><strong>{{ $movement->expiry_date?->format('d M Y') ?? '—' }}</strong></div>

            <div class="col-md-4"><small class="text-muted d-block">Date / Time</small><strong>{{ $movement->movement_date?->format('d M Y H:i') ?? '—' }}</strong></div>
            <div class="col-md-8"><small class="text-muted d-block">Source</small><strong>{{ $movement->source_type ? class_basename($movement->source_type) . ($movement->source_id ? ' #' . $movement->source_id : '') : 'Manual entry' }}</strong></div>

            <div class="col-12"><small class="text-muted d-block">Notes</small>{{ $movement->notes ?: '—' }}</div>
        </div>
    </div>
</div>
@endsection
