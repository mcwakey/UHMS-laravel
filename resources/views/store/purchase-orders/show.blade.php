@extends('layouts.app')
@section('title', 'PO ' . $purchaseOrder->po_number)

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            {{ $purchaseOrder->po_number }}
            <span class="badge bg-{{ $purchaseOrder->status->color() }} ms-2">{{ $purchaseOrder->status->label() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        @if($purchaseOrder->is_editable)
            @can('store.purchase.create')
            <form method="POST" action="{{ route('admin.store.purchase-orders.submit', $purchaseOrder) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary btn-md fs-13" onclick="return confirm('Submit this PO for approval?')">
                    <i class="ti ti-send me-1"></i>Submit
                </button>
            </form>
            @endcan
        @endif

        @if($purchaseOrder->status === \App\Enums\PurchaseOrderStatus::SUBMITTED)
            @can('store.purchase.approve')
            <form method="POST" action="{{ route('admin.store.purchase-orders.approve', $purchaseOrder) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-md fs-13" onclick="return confirm('Approve this purchase order?')">
                    <i class="ti ti-check me-1"></i>Approve
                </button>
            </form>
            @endcan
        @endif

        @if($purchaseOrder->status->canTransitionTo(\App\Enums\PurchaseOrderStatus::CANCELLED))
            <form method="POST" action="{{ route('admin.store.purchase-orders.cancel', $purchaseOrder) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-md fs-13" onclick="return confirm('Cancel this PO?')">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
            </form>
        @endif

        <a href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="row">
    <!-- PO Info -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Order Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">PO #</td>
                        <td class="fw-medium">{{ $purchaseOrder->po_number }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status</td>
                        <td><span class="badge bg-{{ $purchaseOrder->status->color() }}">{{ $purchaseOrder->status->label() }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Order Date</td>
                        <td>{{ $purchaseOrder->order_date->format('d M Y') }}</td>
                    </tr>
                    @if($purchaseOrder->expected_date)
                    <tr>
                        <td class="text-muted">Expected</td>
                        <td>{{ $purchaseOrder->expected_date->format('d M Y') }}</td>
                    </tr>
                    @endif
                    @if($purchaseOrder->received_date)
                    <tr>
                        <td class="text-muted">Received</td>
                        <td>{{ $purchaseOrder->received_date->format('d M Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Total Amount</td>
                        <td class="fw-bold">GH₵ {{ number_format($purchaseOrder->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Created By</td>
                        <td>{{ $purchaseOrder->createdByUser->name ?? 'N/A' }}</td>
                    </tr>
                    @if($purchaseOrder->approvedByUser)
                    <tr>
                        <td class="text-muted">Approved By</td>
                        <td>{{ $purchaseOrder->approvedByUser->name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted">Created</td>
                        <td>{{ $purchaseOrder->created_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Supplier Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Supplier</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-1">{{ $purchaseOrder->supplier->name }}</h6>
                @if($purchaseOrder->supplier->contact_person)
                    <div><small class="text-muted"><i class="ti ti-user me-1"></i>{{ $purchaseOrder->supplier->contact_person }}</small></div>
                @endif
                @if($purchaseOrder->supplier->phone)
                    <div><small class="text-muted"><i class="ti ti-phone me-1"></i>{{ $purchaseOrder->supplier->phone }}</small></div>
                @endif
                @if($purchaseOrder->supplier->email)
                    <div><small class="text-muted"><i class="ti ti-mail me-1"></i>{{ $purchaseOrder->supplier->email }}</small></div>
                @endif
            </div>
        </div>

        @if($purchaseOrder->notes)
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Notes</h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $purchaseOrder->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- PO Items -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Order Items ({{ $purchaseOrder->items->count() }})</h5>
                @if($purchaseOrder->is_editable)
                    @can('store.purchase.create')
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="ti ti-plus me-1"></i>Add Item
                    </button>
                    @endcan
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center">Type</th>
                                <th class="text-center">Ordered</th>
                                <th class="text-center">Received</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Total</th>
                                @if($purchaseOrder->is_editable)
                                <th class="text-end">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td class="fw-medium">{{ $item->item_name }}</td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark text-uppercase">{{ $item->item_type ?? 'drug' }}</span>
                                </td>
                                <td class="text-center">{{ $item->quantity_ordered }}</td>
                                <td class="text-center">
                                    @if($item->quantity_received > 0)
                                        <span class="badge bg-{{ $item->is_fully_received ? 'success' : 'warning' }}">
                                            {{ $item->quantity_received }}
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">GH₵ {{ number_format($item->unit_cost, 2) }}</td>
                                <td class="text-end">GH₵ {{ number_format($item->total_cost, 2) }}</td>
                                @if($purchaseOrder->is_editable)
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.store.purchase-orders.remove-item', $item) }}" class="d-inline" onsubmit="return confirm('Remove this item?')">
                                        @csrf @method('DELETE')
                                        <button aria-label="Delete" title="Delete" type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end">Total:</td>
                                <td class="text-end">GH₵ {{ number_format($purchaseOrder->total_amount, 2) }}</td>
                                @if($purchaseOrder->is_editable)
                                <td></td>
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Receive Items -->
        @if($purchaseOrder->is_receivable)
        @can('store.purchase.approve')
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-package me-1"></i>Receive Items</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.store.purchase-orders.receive', $purchaseOrder) }}">
                    @csrf
                    <div class="table-responsive mb-3">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th style="width:80px;">Ordered</th>
                                    <th style="width:80px;">Already</th>
                                    <th style="width:80px;">Remaining</th>
                                    <th style="width:100px;">Receiving</th>
                                    <th style="width:130px;">Batch #</th>
                                    <th style="width:140px;">Expiry</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $idx => $item)
                                @if(!$item->is_fully_received)
                                <tr>
                                    <td class="fw-medium">{{ $item->item_name }}</td>
                                    <td class="text-center">{{ $item->quantity_ordered }}</td>
                                    <td class="text-center">{{ $item->quantity_received }}</td>
                                    <td class="text-center fw-medium">{{ $item->remaining_quantity }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                        <input type="number" name="items[{{ $idx }}][quantity_received]" class="form-control form-control-sm" value="0" min="0" max="{{ $item->remaining_quantity }}">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="{{ $item->batch_number }}" placeholder="Batch">
                                    </td>
                                    <td>
                                        <input type="date" name="items[{{ $idx }}][expiry_date]" class="form-control form-control-sm" value="{{ $item->expiry_date?->format('Y-m-d') }}">
                                    </td>
                                </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-success" onclick="return confirm('Confirm receiving these items?')">
                        <i class="ti ti-package me-1"></i>Receive Items
                    </button>
                </form>
            </div>
        </div>
        @endcan
        @endif
    </div>
</div>

<!-- Add Item Modal -->
@if($purchaseOrder->is_editable)
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.store.purchase-orders.add-item', $purchaseOrder) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Item to PO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select select2-modal" required>
                            <option value="">Select Product...</option>
                            @foreach(\App\Models\Product::query()->active()->orderBy('name')->get() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}@if($product->code) — {{ $product->code }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity_ordered" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Cost (GH₵) <span class="text-danger">*</span></label>
                            <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

