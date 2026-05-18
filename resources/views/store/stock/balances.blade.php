@extends('layouts.app')
@section('title', 'Product Stock Balances')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Product Stock Balances</h4>
        <small class="text-muted">Unified on-hand inventory across the hospital. One ledger, one truth — read from <code>product_stock_balances</code>.</small>
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
                <label class="form-label">Product Type</label>
                <select name="product_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach($productTypes as $pt)
                    <option value="{{ $pt->value }}" {{ request('product_type') === $pt->value ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', strtolower($pt->value))) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name or code…">
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
                        <th>Product</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Unit</th>
                        <th>Location</th>
                        <th>Department</th>
                        <th class="text-end">Quantity on Hand</th>
                        <th class="text-end">Reorder Level</th>
                        <th>Status</th>
                        <th>Last Movement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($balances as $b)
                    @php
                        $qty       = (float) $b->quantity_on_hand;
                        $reorder   = (float) ($b->product->reorder_level ?? 0);
                        $low       = $reorder > 0 && $qty <= $reorder;
                        $type      = $b->product?->product_type;
                        $typeLabel = $type instanceof \App\Enums\ProductType ? $type->value : ($type ?? '—');
                        $deptName  = $b->stockLocation?->department?->name
                                  ?? $b->product?->departments?->first()?->name
                                  ?? '—';
                    @endphp
                    <tr class="{{ $low ? 'table-warning' : '' }}">
                        <td>{{ $b->product?->name ?? '—' }}</td>
                        <td><code class="small">{{ $b->product?->code ?? '—' }}</code></td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', strtolower($typeLabel))) }}</span></td>
                        <td>{{ $b->product?->unit ?? '—' }}</td>
                        <td>{{ $b->stockLocation?->name ?? '—' }}</td>
                        <td class="small text-muted">{{ $deptName }}</td>
                        <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($qty, 4), '0'), '.') }}</td>
                        <td class="text-end text-muted">{{ rtrim(rtrim(number_format($reorder, 4), '0'), '.') }}</td>
                        <td>
                            @if($qty <= 0)
                                <span class="badge bg-danger">Out of stock</span>
                            @elseif($low)
                                <span class="badge bg-warning text-dark">Low</span>
                            @else
                                <span class="badge bg-success">OK</span>
                            @endif
                        </td>
                        <td class="small">{{ $b->last_movement_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No stock balances yet. Create opening stock or receive a purchase order.</td></tr>
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
