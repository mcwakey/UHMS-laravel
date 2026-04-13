@extends('layouts.app')
@section('title', 'Expired Stock Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Expired & Expiring Stock</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Expired Stock</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.expired-stock', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Expired Items</p>
                <h4 class="fw-bold mb-0 text-danger">{{ number_format($stats['expired_count']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Expiring Soon</p>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($stats['expiring_count']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Expired Value (Cost)</p>
                <h4 class="fw-bold mb-0 text-danger">₵{{ number_format($stats['expired_value'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Expiring Value (Cost)</p>
                <h4 class="fw-bold mb-0 text-warning">₵{{ number_format($stats['expiring_value'], 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.expired-stock') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">All</option>
                    <option value="expired" {{ ($filters['type'] ?? '') === 'expired' ? 'selected' : '' }}>Expired Only</option>
                    <option value="expiring" {{ ($filters['type'] ?? '') === 'expiring' ? 'selected' : '' }}>Expiring Soon (90 days)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <select name="location" class="form-select">
                    <option value="">All Locations</option>
                    @foreach(\App\Enums\StockLocation::cases() as $loc)
                    <option value="{{ $loc->value }}" {{ ($filters['location'] ?? '') == $loc->value ? 'selected' : '' }}>{{ $loc->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search Drug</label>
                <input type="text" name="search" class="form-control" placeholder="Drug name..." value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.expired-stock') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Drug</th>
                    <th>Batch</th>
                    <th>Location</th>
                    <th>Expiry Date</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Cost Value</th>
                    <th>Status</th>
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
                            <span class="badge bg-danger">Expired</span>
                        @else
                            <span class="badge bg-warning">Expiring Soon</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No expired or expiring stock found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($stocks->hasPages())
    <div class="card-footer">{{ $stocks->links() }}</div>
    @endif
</div>
@endsection
