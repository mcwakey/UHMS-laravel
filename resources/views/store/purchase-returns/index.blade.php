@extends('layouts.app')
@section('title', 'Purchase Returns')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Purchase Returns
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $purchaseReturns->total() }}</span>
        </h4>
    </div>
    <a href="{{ route('admin.store.purchase-returns.create') }}" class="btn btn-primary btn-md fs-13">
        <i class="ti ti-plus me-1"></i>New Return
    </a>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.purchase-returns.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search return # or supplier" value="{{ request('search') }}"></div>
            <div class="col-md-2">
                <select name="supplier_id" class="form-select">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}@if($product->code) ({{ $product->code }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
            <div class="col-md-1"><input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
            <div class="col-md-1"><button aria-label="Search" title="Search" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
            @if(request()->hasAny(['search', 'supplier_id', 'status', 'product_id', 'date_from', 'date_to']))
                <div class="col-md-1"><a aria-label="Close" title="Close" href="{{ route('admin.store.purchase-returns.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Return #</th>
                    <th>Supplier</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th class="text-center">Items</th>
                    <th class="text-end">Value</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($purchaseReturns as $purchaseReturn)
                <tr>
                    <td><a href="{{ route('admin.store.purchase-returns.show', $purchaseReturn) }}" class="fw-medium text-primary">{{ $purchaseReturn->return_number }}</a></td>
                    <td>{{ $purchaseReturn->supplier?->name ?? '-' }}</td>
                    <td>{{ $purchaseReturn->return_date?->format('d M Y') }}</td>
                    <td>{{ $purchaseReturn->stockLocation?->name ?? '-' }}</td>
                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $purchaseReturn->items_count }}</span></td>
                    <td class="text-end fw-medium">GHS {{ number_format((float) $purchaseReturn->total_amount, 2) }}</td>
                    <td><span class="badge bg-{{ $purchaseReturn->status->color() }}">{{ $purchaseReturn->status->label() }}</span></td>
                    <td class="text-end"><a aria-label="View" title="View" href="{{ route('admin.store.purchase-returns.show', $purchaseReturn) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty-state message="No purchase returns found." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $purchaseReturns->links() }}</div>
</div>
@endsection
