@extends('layouts.app')
@section('title', $title)

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ $title }}</h4>
        <p class="text-muted small mb-0">
            Filtered view of <strong>products</strong> linked to {{ $departmentLabel }}.
            Department quantities reflect on-hand balance at the <strong>{{ $locationLabel }}</strong>.
            New products are added from <em>Store &rsaquo; Products</em>.
        </p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-start border-primary border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Total Items</p>
                <h4 class="fw-bold mb-0">{{ $products->total() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-warning border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Low / Out of Stock (page)</p>
                <h4 class="fw-bold mb-0">{{ $lowStockCount }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route($routeName) }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="{{ __('stock.search_product_name_code') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="product_type" class="form-select form-select-sm">
                    <option value="">{{ __('stock.all_product_types') }}</option>
                    @foreach($allowedTypes as $type)
                        <option value="{{ $type }}" {{ request('product_type') === $type ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', strtolower($type))) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('stock.filter') }}</button>
                <a href="{{ route($routeName) }}" class="btn btn-secondary btn-sm">{{ __('stock.reset') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('stock.product') }}</th>
                        <th>{{ __('stock.code') }}</th>
                        <th>{{ __('stock.type') }}</th>
                        <th>{{ __('stock.unit') }}</th>
                        <th class="text-center">{{ __('theatre.department_available_qty') }}</th>
                        <th class="text-center">{{ __('theatre.main_stock_qty') }}</th>
                        <th class="text-center">{{ __('stock.reorder_level') }}</th>
                        <th class="text-center">{{ __('stock.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $qty = (float) ($product->department_available_quantity ?? 0);
                            $mainQty = (float) ($product->available_in_main_store ?? 0);
                            $reorder = (float) ($product->reorder_level ?? 0);
                            $qtyDisplay = rtrim(rtrim(number_format($qty, 4), '0'), '.');
                            $mainQtyDisplay = rtrim(rtrim(number_format($mainQty, 4), '0'), '.');
                            $departmentStatus = $product->department_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                            $mainStatus = $product->main_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                            $type = is_string($product->product_type) ? $product->product_type : $product->product_type->value;
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-medium">{{ $product->name }}</span>
                                @if($product->description)
                                    <br><small class="text-muted">{{ Str::limit($product->description, 60) }}</small>
                                @endif
                            </td>
                            <td><code>{{ $product->code ?? '—' }}</code></td>
                            <td>
                                <span class="badge bg-soft-info text-info">
                                    {{ ucwords(str_replace('_', ' ', strtolower($type))) }}
                                </span>
                            </td>
                            <td>{{ $product->unit ?? 'unit' }}</td>
                            <td class="text-center">
                                <span class="fw-bold">{{ $qtyDisplay ?: '0' }}</span>
                                <span class="badge bg-{{ $departmentStatus['class'] }} ms-1">{{ $departmentStatus['label'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold">{{ $mainQtyDisplay ?: '0' }}</span>
                                <span class="badge bg-{{ $mainStatus['class'] }} ms-1">{{ $mainStatus['label'] }}</span>
                            </td>
                            <td class="text-center text-muted">{{ rtrim(rtrim(number_format($reorder, 4), '0'), '.') }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $departmentStatus['class'] }}">{{ $departmentStatus['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                {{ $emptyMessage }}
                                Link a product from <em>Store &rsaquo; Products</em> first.
                            </td>
                        </tr>
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