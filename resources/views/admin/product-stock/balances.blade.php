@extends('layouts.app')

@section('title', 'Stock Balances')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0"><i class="ti ti-database"></i> Stock Balances</h4>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#receiveStockModal"><i class="ti ti-arrow-down"></i> Receive</button>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#transferStockModal"><i class="ti ti-transfer"></i> Transfer</button>
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#adjustStockModal"><i class="ti ti-adjustments"></i> Adjust</button>
            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#returnStockModal"><i class="ti ti-arrow-back-up"></i> Return</button>
            <a href="{{ route('admin.product-stock.ledger') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-list"></i> Ledger</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <form method="GET" class="card card-body mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-1">Location</label>
                <select name="location_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Locations</option>
                    @foreach($locations as $l)
                        <option value="{{ $l->id }}" @selected($locationId == $l->id)>
                            {{ $l->name }} @if($l->is_main) (Main) @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Name or code">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Product Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($typeOptions as $val => $label)
                        <option value="{{ $val }}" @selected($type === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <label class="form-check me-2">
                    <input type="checkbox" class="form-check-input" name="low_only" value="1" @checked($lowOnly)>
                    <span class="form-check-label">Low only</span>
                </label>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button class="btn btn-sm btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover mb-0">
                <thead>
                    <tr>
                        <th>Product</th><th>Type</th><th>Location</th><th>Department</th>
                        <th class="text-end">Quantity on Hand</th>
                        <th class="text-end">Reorder Level</th>
                        <th>Status</th>
                        <th>Last Movement</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($balances as $b)
                    @php
                        $qty = (float) $b->quantity_on_hand;
                        $reorder = (float) ($b->product?->reorder_level ?? 0);
                        $low = $reorder > 0 && $qty <= $reorder;
                        $type = $b->product?->product_type;
                        $typeLabel = $type instanceof \App\Enums\ProductType ? $type->label() : ucfirst(str_replace('_', ' ', (string) $type));
                        $productDepartments = $b->product?->departments?->pluck('name')->filter()->join(', ');
                        $department = $b->location?->department?->name ?? ($productDepartments ?: '—');
                    @endphp
                    <tr class="{{ $low ? 'table-warning' : '' }}">
                        <td>{{ $b->product->name ?? '—' }}<br><small class="text-muted"><code>{{ $b->product->code ?? '—' }}</code></small></td>
                        <td><span class="badge bg-secondary">{{ $typeLabel ?: '—' }}</span></td>
                        <td>{{ $b->location->name ?? '—' }}</td>
                        <td>{{ $department ?: '—' }}</td>
                        <td class="text-end"><strong>{{ rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.') }}</strong></td>
                        <td class="text-end">{{ $reorder > 0 ? rtrim(rtrim(number_format($reorder, 4, '.', ''), '0'), '.') : '—' }}</td>
                        <td>
                            @if($qty <= 0)
                                <span class="badge bg-danger">Out of stock</span>
                            @elseif($low)
                                <span class="badge bg-warning text-dark">Low</span>
                            @else
                                <span class="badge bg-success">OK</span>
                            @endif
                        </td>
                        <td>{{ optional($b->last_movement_at)->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No balances yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-end">{{ $balances->links() }}</div>
    </div>
</div>

<div class="modal fade" id="receiveStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.product-stock.receive') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-arrow-down me-1"></i>Receive Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="stock_location_id" value="{{ $mainStore->id }}">
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" value="{{ $mainStore->name }} (Main Store)" disabled>
                </div>
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label">Product *</label>
                        <select name="items[0][product_id]" class="form-select" required>
                            <option value="">Select product</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Qty *</label>
                        <input type="number" step="0.0001" min="0.0001" name="items[0][quantity]" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Unit Cost</label>
                        <input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Batch No.</label>
                        <input type="text" name="items[0][batch_no]" class="form-control" maxlength="100">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="items[0][expiry_date]" class="form-control">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" maxlength="500">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Receive</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="transferStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.product-stock.transfer') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-transfer me-1"></i>Transfer Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">From *</label>
                        <select name="from_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected($location->is_main)>{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To *</label>
                        <select name="to_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">Product *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Quantity *</label>
                    <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Transfer</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.product-stock.adjust') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-adjustments me-1"></i>Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label">Location *</label>
                    <select name="stock_location_id" class="form-select" required>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Product *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-select" required>
                            @foreach($adjustmentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Quantity *</label>
                        <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" required>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">Reason *</label>
                    <textarea name="reason" rows="2" class="form-control" maxlength="500" required></textarea>
                </div>
                <label class="form-check">
                    <input type="checkbox" name="allow_negative" value="1" class="form-check-input">
                    <span class="form-check-label">Allow negative balance</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning">Adjust</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="returnStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.product-stock.return') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-arrow-back-up me-1"></i>Return Stock To Main Store</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">From *</label>
                        <select name="from_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To *</label>
                        <select name="to_location_id" class="form-select" required>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected($mainStore->id === $location->id)>{{ $location->name }} @if($location->is_main) (Main) @endif</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">Product *</label>
                    <select name="product_id" class="form-select" required>
                        <option value="">Select product</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} @if($product->code) ({{ $product->code }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Quantity *</label>
                    <input type="number" step="0.0001" min="0.0001" name="quantity" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="2" class="form-control" maxlength="500"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-secondary">Return</button>
            </div>
        </form>
    </div>
</div>
@endsection
