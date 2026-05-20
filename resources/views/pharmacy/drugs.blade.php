@extends('layouts.app')
@section('title', 'Pharmacy Drug Catalogue')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-pill me-2"></i>Pharmacy Drug Catalogue</h4>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search product name or code" value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.pharmacy.drugs.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Product Type</th>
                        <th>Unit</th>
                        <th class="text-end">Available in Pharmacy</th>
                        <th class="text-end">Reorder Level</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($drugs as $product)
                        @php
                            $qty = (float) ($product->available_in_pharmacy ?? 0);
                            $reorder = (float) ($product->reorder_level ?? 0);
                            $low = $reorder > 0 && $qty <= $reorder;
                            $type = $product->product_type;
                            $typeLabel = $type instanceof \App\Enums\ProductType ? $type->label() : ucfirst(str_replace('_', ' ', (string) $type));
                        @endphp
                        <tr class="{{ $low ? 'table-warning' : '' }}">
                            <td>
                                <span class="fw-medium">{{ $product->name }}</span>
                                @if($product->code)
                                    <br><small class="text-muted"><code>{{ $product->code }}</code></small>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $typeLabel }}</span></td>
                            <td>{{ $product->unit ?? 'unit' }}</td>
                            <td class="text-end fw-semibold">{{ rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.') }}</td>
                            <td class="text-end text-muted">{{ $reorder > 0 ? rtrim(rtrim(number_format($reorder, 4, '.', ''), '0'), '.') : '—' }}</td>
                            <td>
                                @if($qty <= 0)
                                    <span class="badge bg-danger">Out of stock</span>
                                @elseif($low)
                                    <span class="badge bg-warning text-dark">Low</span>
                                @else
                                    <span class="badge bg-success">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No pharmacy products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($drugs->hasPages())
        <div class="card-footer">{{ $drugs->links() }}</div>
    @endif
</div>
@endsection
