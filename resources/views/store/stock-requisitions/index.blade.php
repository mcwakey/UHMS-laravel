@extends('layouts.app')
@section('title', __('stock.dept_stock_requisitions'))

@section('content')
<x-page-header title="{{ __('stock.dept_stock_requisitions') }}" icon="ti-clipboard-list">
    <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('stock.total') }}: {{ $stockRequisitions->total() }}</span>
    <x-slot:actions>
        <a href="{{ route('admin.store.stock-requisitions.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('stock.new_requisition') }}
        </a>
    </x-slot:actions>
</x-page-header>

@if(session('success'))<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger alert-dismissible fade show">{{ $errors->first() }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.stock-requisitions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><input type="text" name="search" class="form-control" placeholder="{{ __('stock.search_requisition') }}" value="{{ request('search') }}"></div>
            <div class="col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">{{ __('stock.all_departments') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('stock.all_status') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="product_id" class="form-select">
                    <option value="">{{ __('stock.all_products') }}</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}@if($product->code) ({{ $product->code }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                @include('partials.date-range-filter', ['name' => 'date_range', 'value' => request('date_range'), 'label' => 'Date Range', 'labelClass' => 'small text-muted mb-1'])
            </div>
            <div class="col-md-1"><button aria-label="Search" title="Search" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button></div>
            @if(request()->hasAny(['search', 'department_id', 'status', 'product_id', 'date_range']))
                <div class="col-md-1"><a aria-label="Close" title="Close" href="{{ route('admin.store.stock-requisitions.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a></div>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>{{ __('stock.requisition_no') }}</th>
                    <th>{{ __('stock.department') }}</th>
                    <th>{{ __('stock.requested') }}</th>
                    <th>{{ __('stock.requested_by') }}</th>
                    <th class="text-center">{{ __('stock.items') }}</th>
                    <th>{{ __('stock.status') }}</th>
                    <th class="text-end">{{ __('stock.actions') ?? 'Actions' }}</th>
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
                    <td><x-status-badge :status="$stockRequisition->status" /></td>
                    <td class="text-end"><a aria-label="View" title="View" href="{{ route('admin.store.stock-requisitions.show', $stockRequisition) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="7"><x-empty-state icon="ti-clipboard-off" title="{{ __('stock.no_requisitions') }}" message="{{ __('stock.no_requisitions_message') }}" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-end">{{ $stockRequisitions->links() }}</div>
</div>
@endsection

@push('scripts')
@include('partials.date-range-filter-scripts')
@endpush
