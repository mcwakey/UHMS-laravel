@extends('layouts.app')
@section('title', 'Stock Batch ' . $batch->batch_number)

@php use App\Enums\StockMovementDirection; @endphp

@php
    $backRoute = match ($batch->type) {
        \App\Models\StockBatch::TYPE_RETURN => 'admin.store.stock.returns.index',
        \App\Models\StockBatch::TYPE_TRANSFER => 'admin.store.stock.transfers.index',
        default => 'admin.store.stock.adjustments.index',
    };
@endphp

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-0">{{ $batch->translatedTypeLabel() }} {{ $batch->batch_number }}</h4>
        <div class="text-muted small">{{ $batch->created_at?->format('d M Y H:i') }}</div>
    </div>
    <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i>{{ __('stock.back') }}</a>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.type') }}</small><strong>{{ $batch->translatedTypeLabel() }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.from') }}</small><strong>{{ $batch->sourceLocation?->name ?? '—' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.to') }}</small><strong>{{ $batch->destLocation?->name ?? '—' }}</strong></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><small class="text-muted d-block">{{ __('stock.recorded_by') }}</small><strong>{{ trim(($batch->createdBy?->first_name ?? '') . ' ' . ($batch->createdBy?->last_name ?? '')) ?: '—' }}</strong></div></div></div>
</div>

@if($batch->reason || $batch->notes)
<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-6"><small class="text-muted d-block">{{ __('stock.reason') }}</small>{{ $batch->reason ?: '—' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">{{ __('stock.notes') }}</small>{{ $batch->notes ?: '—' }}</div>
    </div>
</div></div>
@endif

<div class="card">
    <div class="card-header"><h6 class="mb-0">{{ __('stock.line_items') }}</h6></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('stock.product') }}</th>
                    <th>{{ __('stock.location') }}</th>
                    <th>{{ __('stock.movement') }}</th>
                    <th class="text-end">{{ __('stock.quantity') }}</th>
                    <th>{{ __('stock.batch_number') }}</th>
                    <th>{{ __('stock.expiry_date') }}</th>
                    <th class="text-end">{{ __('stock.unit_cost') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($batch->movements as $movement)
                @php $isIn = $movement->movement_type->direction() === StockMovementDirection::IN; @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.store.stock.movements.show', $movement) }}" class="fw-medium text-primary">{{ $movement->product?->name ?? $movement->drug?->name ?? '—' }}</a>
                        @if($movement->product?->code)<small class="text-muted">({{ $movement->product->code }})</small>@endif
                    </td>
                    <td>{{ $movement->location?->name ?? '—' }}</td>
                    <td><span class="badge {{ $isIn ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">{{ $movement->movement_type->translatedLabel() }}</span></td>
                    <td class="text-end fw-medium {{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? '+' : '−' }}{{ rtrim(rtrim(number_format((float) $movement->quantity, 4, '.', ''), '0'), '.') }}</td>
                    <td>{{ $movement->batch_no ?: '—' }}</td>
                    <td>{{ $movement->expiry_date?->format('d M Y') ?? '—' }}</td>
                    <td class="text-end">GH₵ {{ number_format((float) $movement->unit_cost, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('stock.no_batch_movements') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
