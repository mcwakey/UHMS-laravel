@extends('layouts.app')
@section('title', 'Investigation Item Stock')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Investigation Item Stock</h4>
        <p class="text-muted small mb-0">Track reagents, test kits & consumables across all investigation departments</p>
    </div>
    @can('pharmacy.stock.manage')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStockModal">
        <i class="ti ti-plus me-1"></i>Add Stock
    </button>
    @endcan
</div>

{{-- Stats Row --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-start border-primary border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Active Batches</p>
                <h4 class="fw-bold mb-0">{{ $stats['total_batches'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-warning border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Low Stock</p>
                <h4 class="fw-bold mb-0 text-warning">{{ $stats['low_stock'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-danger border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Expired Batches</p>
                <h4 class="fw-bold mb-0 text-danger">{{ $stats['expired'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-info border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Expiring Soon</p>
                <h4 class="fw-bold mb-0 text-info">{{ $stats['expiring_soon'] }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search item..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="location" class="form-select form-select-sm">
                    <option value="">All Locations</option>
                    @foreach($locations as $loc)
                    <option value="{{ $loc->value }}" {{ request('location') === $loc->value ? 'selected' : '' }}>
                        {{ $loc->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Batches</option>
                    <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="expiring" {{ request('status') === 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.investigations.stock.index') }}" class="btn btn-secondary btn-sm">Reset</a>
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
                        <th>Item</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Batch</th>
                        <th class="text-center">Qty</th>
                        <th>Unit Cost</th>
                        <th>Expiry</th>
                        <th>Supplier</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($query as $stock)
                    @php
                        $status = $stock->expiry_status;
                        $rowClass = match($status) {
                            'expired'  => 'table-danger',
                            'expiring' => 'table-warning',
                            default    => '',
                        };
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="fw-medium">{{ $stock->item->name }}</td>
                        <td>
                            <span class="badge bg-{{ $stock->item->category->color() }}">
                                {{ $stock->item->category->label() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $stock->location->color() }}">{{ $stock->location->label() }}</span>
                        </td>
                        <td><code>{{ $stock->batch_number ?? '—' }}</code></td>
                        <td class="text-center">
                            <span class="fw-bold {{ $stock->quantity <= ($stock->reorder_level ?? 0) ? 'text-danger' : '' }}">
                                {{ $stock->quantity }} {{ $stock->item->unit }}
                            </span>
                        </td>
                        <td>₵{{ number_format($stock->unit_cost, 2) }}</td>
                        <td>
                            @if($stock->expiry_date)
                                <span class="text-{{ $status === 'expired' ? 'danger' : ($status === 'expiring' ? 'warning' : 'muted') }}">
                                    {{ $stock->expiry_date->format('M d, Y') }}
                                </span>
                            @else
                                <span class="text-muted">N/A</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $stock->supplier ?? '—' }}</td>
                        <td class="text-end">
                            @can('pharmacy.stock.manage')
                            <button class="btn btn-sm btn-outline-primary edit-stock-btn"
                                data-id="{{ $stock->id }}"
                                data-qty="{{ $stock->quantity }}"
                                data-cost="{{ $stock->unit_cost }}"
                                data-expiry="{{ $stock->expiry_date?->format('Y-m-d') }}"
                                data-reorder="{{ $stock->reorder_level }}"
                                data-bs-toggle="modal" data-bs-target="#editStockModal">
                                <i class="ti ti-edit"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9"><x-empty-state message="No stock records found." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($query->hasPages())
    <div class="card-footer">{{ $query->links() }}</div>
    @endif
</div>

{{-- Add Stock Modal --}}
@can('pharmacy.stock.manage')
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.investigations.stock.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Item <span class="text-danger">*</span></label>
                        <select name="investigation_item_id" class="form-select" required>
                            <option value="">Select item...</option>
                            @foreach($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Location <span class="text-danger">*</span></label>
                            <select name="location" class="form-select" required>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->value }}" {{ $loc->value === 'laboratory' ? 'selected' : '' }}>
                                    {{ $loc->label() }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Batch Number</label>
                            <input type="text" name="batch_number" class="form-control">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Unit Cost</label>
                            <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" min="0" value="10">
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">Select supplier...</option>
                                @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stock</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Stock Modal --}}
<div class="modal fade" id="editStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editStockForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="editQty" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Unit Cost</label>
                            <input type="number" name="unit_cost" id="editCost" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-medium">Reorder Level</label>
                            <input type="number" name="reorder_level" id="editReorder" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-medium">Expiry Date</label>
                        <input type="date" name="expiry_date" id="editExpiry" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.querySelectorAll('.edit-stock-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const form = document.getElementById('editStockForm');
        form.action = `/admin/investigations/stock/${id}`;
        document.getElementById('editQty').value     = this.dataset.qty;
        document.getElementById('editCost').value    = this.dataset.cost;
        document.getElementById('editExpiry').value  = this.dataset.expiry || '';
        document.getElementById('editReorder').value = this.dataset.reorder;
    });
});
</script>
@endpush
