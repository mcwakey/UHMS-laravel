@extends('layouts.app')
@section('title', __('reports.stock.title'))

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ __('reports.stock.expired_stock') }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('reports.stock.expired_stock') }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.expired-stock', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('reports.export.label_excel') }}
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.expired_items') }}</p>
                <h4 class="fw-bold mb-0 text-danger">{{ number_format($stats['expired_count']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.expiring_soon') }}</p>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($stats['expiring_count']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.expired_value') }}</p>
                <h4 class="fw-bold mb-0 text-danger">₵{{ number_format($stats['expired_value'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.kpi.expiring_value') }}</p>
                <h4 class="fw-bold mb-0 text-warning">₵{{ number_format($stats['expiring_value'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.expired-stock') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.type') }}</label>
                <select name="type" class="form-select">
                    <option value="">{{ __('reports.filters.all_types') }}</option>
                    <option value="expired" {{ ($filters['type'] ?? '') === 'expired' ? 'selected' : '' }}>{{ __('reports.filters.expired_only') }}</option>
                    <option value="expiring" {{ ($filters['type'] ?? '') === 'expiring' ? 'selected' : '' }}>{{ __('reports.filters.expiring_soon_days') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.location') }}</label>
                <select name="location" class="form-select">
                    <option value="">{{ __('reports.filters.all_locations') }}</option>
                    @foreach(\App\Enums\StockLocation::cases() as $loc)
                    <option value="{{ $loc->value }}" {{ ($filters['location'] ?? '') == $loc->value ? 'selected' : '' }}>{{ $loc->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.filters.search_drug') }}</label>
                <input type="text" name="search" class="form-control" placeholder="{{ __('reports.filters.drug_placeholder') }}" value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-3 d-flex gap-2 align-items-end">
                <button class="btn btn-primary flex-fill">{{ __('reports.actions.apply_filters') }}</button>
                <a href="{{ route('admin.reports.expired-stock') }}" class="btn btn-outline-secondary">{{ __('reports.actions.clear_filters') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('reports.columns.drug') }}</th>
                    <th>{{ __('reports.columns.batch') }}</th>
                    <th>{{ __('reports.columns.location') }}</th>
                    <th>{{ __('reports.columns.expiry_date') }}</th>
                    <th class="text-end">{{ __('reports.columns.qty') }}</th>
                    <th class="text-end">{{ __('reports.columns.cost_value') }}</th>
                    <th>{{ __('reports.columns.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $stock)
                <tr>
                    <td>{{ $stock->drug?->name ?? '—' }}</td>
                    <td><code>{{ $stock->batch_number }}</code></td>
                    <td><span class="badge bg-light text-dark">{{ $stock->location instanceof \App\Enums\StockLocation ? $stock->location->label() : ucfirst($stock->location ?? '') }}</span></td>
                    <td>{{ $stock->expiry_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-end">{{ number_format($stock->quantity) }}</td>
                    <td class="text-end">₵{{ number_format($stock->quantity * $stock->cost_price, 2) }}</td>
                    <td>
                        @if($stock->expiry_date?->isPast())
                            <span class="badge bg-danger">{{ __('reports.statuses.expired') }}</span>
                        @else
                            <span class="badge bg-warning">{{ __('reports.statuses.expiring_soon') }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><x-empty-state :message="__('reports.empty.no_stock')" /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
    <div class="card-footer">{{ $stocks->links() }}</div>
    @endif
</div>
@endsection
