@extends('layouts.app')
@section('title', __('reports.stock.stock_valuation'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.stock.stock_valuation') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.stock.stock_valuation') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.stock-valuation', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.actions.excel') }}
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.stock_balances') }}</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_items']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.cost_value') }}</p>
                <h4 class="fw-bold mb-0 text-success">GHS {{ number_format($stats['total_cost_value'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.selling_value') }}</p>
                <h4 class="fw-bold mb-0 text-info">GHS {{ number_format($stats['total_sell_value'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.potential_margin') }}</p>
                <h4 class="fw-bold mb-0 text-warning">GHS {{ number_format($stats['total_sell_value'] - $stats['total_cost_value'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.stock-valuation') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">{{ __('reports.stock.location') }}</label>
                <select name="location" class="form-select">
                    <option value="">{{ __('reports.filters.all_locations') }}</option>
                    @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected(($filters['location'] ?? '') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('reports.filters.search_product') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('reports.filters.product_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary">{{ __('reports.filter') }}</button>
                <a href="{{ route('admin.reports.stock-valuation') }}" class="btn btn-outline-secondary">{{ __('reports.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.stock.product') }}</th>
                    <th>{{ __('reports.stock.type') }}</th>
                    <th>{{ __('reports.stock.location') }}</th>
                    <th class="text-end">{{ __('reports.columns.qty') }}</th>
                    <th class="text-end">{{ __('reports.columns.cost_price') }}</th>
                    <th class="text-end">{{ __('reports.columns.selling_price') }}</th>
                    <th class="text-end">{{ __('reports.columns.cost_value') }}</th>
                    <th class="text-end">{{ __('reports.columns.sell_value') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                @php
                    $product = $stock->product;
                    $costPrice = (float) ($product?->default_cost ?? 0);
                    $sellPrice = (float) ($product?->base_price ?? 0);
                    $quantity = (float) $stock->quantity_on_hand;
                @endphp
                <tr>
                    <td>{{ $product?->name ?? '-' }}</td>
                    <td>{{ $product?->product_type?->label() ?? '-' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $stock->location?->name ?? '-' }}</span></td>
                    <td class="text-end">{{ number_format($quantity, 2) }}</td>
                    <td class="text-end">GHS {{ number_format($costPrice, 2) }}</td>
                    <td class="text-end">GHS {{ number_format($sellPrice, 2) }}</td>
                    <td class="text-end">GHS {{ number_format($quantity * $costPrice, 2) }}</td>
                    <td class="text-end fw-semibold">GHS {{ number_format($quantity * $sellPrice, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="8"><x-empty-state message="{{ __('reports.empty.no_stock') }}" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
    <div class="card-footer">{{ $stocks->links() }}</div>
    @endif
</div>
@endsection
