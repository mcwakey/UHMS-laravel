@extends('layouts.app')
@section('title', __('stock.product_stock_balances'))

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('stock.product_stock_balances') }}</h4>
        <small class="text-muted">{{ __('stock.balances_description') }}</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.store.stock.ledger') }}" class="btn btn-outline-primary"><i class="ti ti-list me-1"></i>{{ __('stock.view_ledger') }}</a>
        @can('store.purchase.create')
        <a href="{{ route('admin.store.stock.adjustments.create') }}" class="btn btn-warning"><i class="ti ti-adjustments me-1"></i>{{ __('stock.adjust_stock') }}</a>
        <a href="{{ route('admin.store.stock.returns.create') }}" class="btn btn-info text-white"><i class="ti ti-rotate me-1"></i>{{ __('stock.record_return') }}</a>
        <a href="{{ route('admin.store.stock.locations.index') }}" class="btn btn-outline-secondary"><i class="ti ti-building me-1"></i>{{ __('stock.locations') }}</a>
        @endcan
    </div>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">{{ __('stock.location') }}</label>
                <select name="location_id" class="form-select">
                    <option value="">{{ __('stock.all_locations') }}</option>
                    @foreach($allLocations as $loc)
                    <option value="{{ $loc->id }}" {{ request('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('stock.product_type') }}</label>
                <select name="product_type" class="form-select">
                    <option value="">{{ __('stock.all_types') }}</option>
                    @foreach($productTypes as $pt)
                    <option value="{{ $pt->value }}" {{ request('product_type') === $pt->value ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', strtolower($pt->value))) }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('stock.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('stock.search_name_code') }}">
            </div>
            <div class="col-md-3 text-end">
                <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>{{ __('stock.filter') }}</button>
                <a href="{{ route('admin.store.stock.balances') }}" class="btn btn-light">{{ __('stock.reset') }}</a>
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
                        <th>{{ __('stock.product') }}</th>
                        <th>{{ __('stock.code') }}</th>
                        <th>{{ __('stock.type') }}</th>
                        <th>{{ __('stock.unit') }}</th>
                        @foreach($locations as $location)
                            <th class="text-end text-nowrap">{{ $location->name }}</th>
                        @endforeach
                        <th class="text-end">{{ __('stock.total') }}</th>
                        <th>{{ __('stock.status') }}</th>
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
                    <tr><td colspan="{{ 6 + $locations->count() }}" class="text-center text-muted py-4">{{ __('stock.no_products_found') }}</td></tr>
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
