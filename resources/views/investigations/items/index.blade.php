@extends('layouts.app')
@section('title', 'Investigation Consumables')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Investigation Consumables</h4>
        <p class="text-muted small mb-0">
            Filtered view of <strong>products</strong> linked to the Investigation / Laboratory / Radiology departments.
            Quantities below reflect the on-hand balance at the <strong>Laboratory stock location</strong>.
            New products are added from <em>Store &rsaquo; Products</em>.
        </p>
    </div>
</div>

{{-- Stats --}}
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

{{-- Filters --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search product name or code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <select name="product_type" class="form-select form-select-sm">
                    <option value="">All Product Types</option>
                    @foreach($allowedTypes as $type)
                    <option value="{{ $type }}" {{ request('product_type') === $type ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', strtolower($type))) }}
                    </option>
                    @endforeach
                    {{-- $allowedTypes contains ->value strings already --}}
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.investigations.items.index') }}" class="btn btn-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Unit</th>
                        <th class="text-center">Department Available Qty</th>
                        <th class="text-center">Main Stock Qty</th>
                        <th class="text-center">Reorder Level</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $qty = (float) ($product->available_in_lab ?? 0);
                            $mainQty = (float) ($product->available_in_main_store ?? 0);
                            $reorder = (float) ($product->reorder_level ?? 0);
                            $qtyDisplay = rtrim(rtrim(number_format($qty, 4), '0'), '.');
                            $mainQtyDisplay = rtrim(rtrim(number_format($mainQty, 4), '0'), '.');
                            $labStatus = $product->lab_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
                            $mainStatus = $product->main_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
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
                                    {{ ucwords(str_replace('_', ' ', strtolower(is_string($product->product_type) ? $product->product_type : $product->product_type->value))) }}
                                </span>
                            </td>
                            <td>{{ $product->unit ?? 'unit' }}</td>
                            <td class="text-center">
                                <span class="fw-bold">{{ $qtyDisplay ?: '0' }}</span>
                                <span class="badge bg-{{ $labStatus['class'] }} ms-1">{{ $labStatus['label'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold">{{ $mainQtyDisplay ?: '0' }}</span>
                                <span class="badge bg-{{ $mainStatus['class'] }} ms-1">{{ $mainStatus['label'] }}</span>
                            </td>
                            <td class="text-center text-muted">{{ rtrim(rtrim(number_format($reorder, 4), '0'), '.') }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $labStatus['class'] }}">{{ $labStatus['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No products are linked to the Investigation / Laboratory department yet.
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
