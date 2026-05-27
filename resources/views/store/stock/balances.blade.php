@extends('layouts.app')
@section('title', 'Product Stock Balances')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Product Stock Balances</h4>
        <small class="text-muted">Unified on-hand inventory across the hospital. One ledger, one truth — read from <code>stock_balances</code>.</small>
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
                    @foreach($allLocations as $loc)
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
                        @foreach($locations as $location)
                            <th class="text-end text-nowrap">{{ $location->name }}</th>
                        @endforeach
                        <th class="text-end">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    @php
                        $product   = $row['product'];
                        $type      = $product?->product_type;
                        $typeLabel = $type instanceof \App\Enums\ProductType ? $type->value : ($type ?? '—');
                        $formatQty = fn ($qty) => rtrim(rtrim(number_format((float) $qty, 4), '0'), '.') ?: '0';
                    @endphp
                    <tr>
                        <td>{{ $product?->name ?? '—' }}</td>
                        <td><code class="small">{{ $product?->code ?? '—' }}</code></td>
                        <td><span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', strtolower($typeLabel))) }}</span></td>
                        <td>{{ $product?->unit ?? '—' }}</td>
                        @foreach($locations as $location)
                            @php $cell = $row['cells'][$location->id]; @endphp
                            <td class="text-end text-nowrap">
                                <span class="fw-semibold">{{ $formatQty($cell['quantity']) }}</span>
                                <span class="badge bg-{{ $cell['status']['class'] }} ms-1">{{ $cell['status']['label'] }}</span>
                            </td>
                        @endforeach
                        <td class="text-end fw-bold">{{ $formatQty($row['total']) }}</td>
                        <td>
                            <span class="badge bg-{{ $row['summary_class'] }}">{{ $row['summary'] }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="{{ 6 + $locations->count() }}" class="text-center text-muted py-4">No products found for the current filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($products->hasPages())
    <div class="card-footer">{{ $products->links() }}</div>
    @endif
</div>
@endsection
