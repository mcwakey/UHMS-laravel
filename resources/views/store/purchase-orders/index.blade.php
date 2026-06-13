@extends('layouts.app')
@section('title', __('store.purchase_orders'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('store.purchase_orders') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('common.total') }}: {{ $purchaseOrders->total() }}</span>
        </h4>
    </div>
    <div>
        @can('store.purchase.create')
        <a href="{{ route('admin.store.purchase-orders.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>New Purchase Order
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Stats Cards -->
<div class="row mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning bg-opacity-10 rounded me-3">
                        <i class="ti ti-file-text fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['draft'] ?? 0 }}</h4>
                        <small class="text-muted">Draft</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-info bg-opacity-10 rounded me-3">
                        <i class="ti ti-clock fs-4 text-info"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['pending_pos'] ?? 0 }}</h4>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-check fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['partially_received_pos'] ?? 0 }}</h4>
                        <small class="text-muted">Partially Received</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-currency-dollar fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">GH₵ {{ number_format($stats['outstanding_value'] ?? 0, 2) }}</h4>
                        <small class="text-muted">Outstanding Value</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-header py-2">
        <div class="row g-2 text-center">
            <div class="col-md-4"><small class="text-muted d-block">Total Ordered Value</small><strong>GH₵ {{ number_format($stats['total_ordered_value'] ?? 0, 2) }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Total Received Value</small><strong>GH₵ {{ number_format($stats['total_received_value'] ?? 0, 2) }}</strong></div>
            <div class="col-md-4"><small class="text-muted d-block">Outstanding Value</small><strong>GH₵ {{ number_format($stats['outstanding_value'] ?? 0, 2) }}</strong></div>
        </div>
    </div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.store.purchase-orders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search PO #, supplier..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->translatedLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="supplier_id" class="form-select">
                    <option value="">{{ __('stock.all_suppliers') }}</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                @include('partials.date-range-filter', ['name' => 'date_range', 'value' => request('date_range'), 'label' => __('store.date_range'), 'labelClass' => 'small text-muted mb-1'])
            </div>
            <div class="col-md-2">
                <select name="product_id" class="form-select">
                    <option value="">{{ __('stock.all_products') }}</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>{{ $product->name }}@if($product->code) ({{ $product->code }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button aria-label="Search" title="Search" type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search"></i></button>
            </div>
            @if(request()->hasAny(['search', 'status', 'supplier_id', 'date_range', 'date_from', 'date_to', 'product_id']))
            <div class="col-md-1">
                <a aria-label="Close" title="Close" href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-outline-secondary w-100"><i class="ti ti-x"></i></a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Purchase Orders Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('store.po_number_short') }}</th>
                        <th>{{ __('stock.supplier') }}</th>
                        <th>{{ __('stock.order_date') }}</th>
                        <th>{{ __('store.expected') }}</th>
                        <th class="text-center">{{ __('stock.items') }}</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                    <tr>
                        <td>
                            <a href="{{ route('admin.store.purchase-orders.show', $po) }}" class="fw-medium text-primary">
                                {{ $po->po_number }}
                            </a>
                        </td>
                        <td>{{ $po->supplier->name }}</td>
                        <td>{{ $po->order_date->format('d M Y') }}</td>
                        <td>{{ $po->expected_date?->format('d M Y') ?? '-' }}</td>
                        <td class="text-center"><span class="badge bg-soft-info">{{ $po->items_count }}</span></td>
                        <td class="text-end fw-medium">GH₵ {{ number_format($po->total_amount, 2) }}</td>
                        <td><x-status-badge :status="$po->status" /></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="Actions" title="Actions" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a href="{{ route('admin.store.purchase-orders.show', $po) }}" class="dropdown-item">
                                            <i class="ti ti-eye me-1"></i>View
                                        </a>
                                    </li>
                                    @if($po->is_editable)
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.purchase-orders.submit', $po) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-send me-1"></i>Submit
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                    @if($po->status === \App\Enums\PurchaseOrderStatus::SUBMITTED)
                                    @can('store.purchase.approve')
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.purchase-orders.approve', $po) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-check me-1"></i>Approve
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                    @endif
                                    @if($po->is_editable || $po->status === \App\Enums\PurchaseOrderStatus::SUBMITTED)
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.purchase-orders.cancel', $po) }}" onsubmit="return confirm('{{ __('store.cancel_po_confirm') }}')">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="ti ti-x me-1"></i>Cancel
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="ti ti-file-off fs-2 d-block mb-2"></i>
                            No purchase orders found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($purchaseOrders->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $purchaseOrders->withQueryString()->links() }}
</div>
@endif
@endsection

@push('scripts')
@include('partials.date-range-filter-scripts')
@endpush
