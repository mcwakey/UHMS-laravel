@extends('layouts.app')
@section('title', 'Stock Requisitions')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Department Stock Requisitions
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $stockRequisitions->total() }}</span>
        </h4>
    </div>
    <a href="{{ route('admin.store.stock-requisitions.create') }}" class="btn btn-primary btn-md fs-13">
        <i class="ti ti-plus me-1"></i>New Requisition
    </a>
</div>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.stock-requisitions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="Search requisition #" value="{{ request('search') }}"></div>
            <div class="col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
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
            <div class="col-md-3">
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}@if($product->code) ({{ $product->code }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><button class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
            @if(request()->hasAny(['search', 'department_id', 'status', 'product_id']))
                <div class="col-md-1"><a href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Requisition #</th>
                    <th>Department</th>
                    <th>Requested</th>
                    <th>Requested By</th>
                    <th class="text-center">Items</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($stockRequisitions as $stockRequisition)
                <tr>
                    <td><a href="{{ route('admin.store.stock-requisitions.show', $stockRequisition) }}" class="fw-medium text-primary">{{ $stockRequisition->requisition_number }}</a></td>
                    <td>{{ $stockRequisition->department?->name ?? '-' }}</td>
                    <td>{{ $stockRequisition->requested_at?->format('d M Y H:i') ?? '-' }}</td>
                    <td>{{ $stockRequisition->requestedByUser?->name ?: '-' }}</td>
                    <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $stockRequisition->items_count }}</span></td>
                    <td><span class="badge bg-{{ $stockRequisition->status->color() }}">{{ $stockRequisition->status->label() }}</span></td>
                    <td class="text-end"><a href="{{ route('admin.store.stock-requisitions.show', $stockRequisition) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No stock requisitions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $stockRequisitions->links() }}</div>
</div>
@endsection
