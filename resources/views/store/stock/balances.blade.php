@extends('layouts.app')
@section('title', 'Stock On Hand')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Stock On Hand</h4>
        <small class="text-muted">Current cached balances (read from <code>stock_balances</code>).</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.store.stock.ledger') }}" class="btn btn-outline-primary"><i class="ti ti-list me-1"></i>View Ledger</a>
        @can('store.purchase.create')
        <a href="{{ route('admin.store.stock.adjustments.create') }}" class="btn btn-warning"><i class="ti ti-adjustments me-1"></i>Adjust Stock</a>
        <a href="{{ route('admin.store.stock.returns.create') }}" class="btn btn-info text-white"><i class="ti ti-rotate me-1"></i>Record Return</a>
        <a href="{{ route('admin.store.stock.locations.index') }}" class="btn btn-outline-secondary"><i class="ti ti-building me-1"></i>Locations</a>
        @endcan
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Drug ID</label>
                <input type="number" name="drug_id" value="{{ request('drug_id') }}" class="form-control" placeholder="Optional drug filter">
            </div>
            <div class="col-md-3">
                <div class="form-check mt-4">
                    <input type="checkbox" class="form-check-input" id="lowOnly" name="low_only" value="1" {{ request('low_only') ? 'checked' : '' }}>
                    <label for="lowOnly" class="form-check-label">Low stock only (≤ reorder level)</label>
                </div>
            </div>
            <div class="col-md-3 text-end">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Drug</th>
                        <th>Unit</th>
                        <th>Location</th>
                        <th class="text-end">Quantity on Hand</th>
                        <th class="text-end">Reorder Level</th>
                        <th>Last Movement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $b)
                    @php $low = (float) $b->quantity_on_hand <= (float) ($b->drug->reorder_level ?? 0); @endphp
                    <tr class="{{ $low ? 'table-warning' : '' }}">
                        <td>{{ $b->drug?->name ?? '—' }}</td>
                        <td>{{ $b->drug?->unit ?? '—' }}</td>
                        <td>{{ $b->location?->name ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format((float)$b->quantity_on_hand, 4), '0'), '.') }}</td>
                        <td class="text-end text-muted">{{ rtrim(rtrim(number_format((float)($b->drug->reorder_level ?? 0), 4), '0'), '.') }}</td>
                        <td>{{ $b->last_movement_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No stock balances yet. Create opening stock or receive a purchase order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($balances->hasPages())
    <div class="card-footer">{{ $balances->links() }}</div>
    @endif
</div>
@endsection
