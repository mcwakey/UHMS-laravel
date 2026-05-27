@extends('layouts.app')
@section('title', 'Procedure Consumables')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Procedure Consumables</h4>
        <p class="text-muted small mb-0">
            Filtered view of <strong>products</strong> linked to the Theatre / Procedure department.
            Quantities show on-hand balance at the <strong>Theatre stock location</strong> and the <strong>Main Store</strong>.
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
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.theatre.consumables.index') }}" class="btn btn-secondary btn-sm">Reset</a>
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
                            $qty = (float) ($product->available_in_theatre ?? 0);
                            $mainQty = (float) ($product->available_in_main_store ?? 0);
                            $reorder = (float) ($product->reorder_level ?? 0);
                            $qtyDisplay = rtrim(rtrim(number_format($qty, 4), '0'), '.');
                            $mainQtyDisplay = rtrim(rtrim(number_format($mainQty, 4), '0'), '.');
                            $theatreStatus = $product->theatre_stock_status ?? ['label' => 'OUT', 'class' => 'danger'];
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
                                <span class="badge bg-{{ $theatreStatus['class'] }} ms-1">{{ $theatreStatus['label'] }}</span>
                            </td>
                            <td class="text-center">
                                <span class="fw-bold">{{ $mainQtyDisplay ?: '0' }}</span>
                                <span class="badge bg-{{ $mainStatus['class'] }} ms-1">{{ $mainStatus['label'] }}</span>
                            </td>
                            <td class="text-center text-muted">{{ rtrim(rtrim(number_format($reorder, 4), '0'), '.') }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $theatreStatus['class'] }}">{{ $theatreStatus['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No products are linked to the Theatre / Procedure department yet.
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
