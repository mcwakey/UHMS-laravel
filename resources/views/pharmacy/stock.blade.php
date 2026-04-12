@extends('layouts.app')
@section('title', 'Drug Stock Management')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-packages me-2"></i>Drug Stock Management</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.pharmacy.stock.alerts') }}" class="btn btn-outline-warning btn-md">
            <i class="ti ti-alert-triangle me-1"></i>Stock Alerts
        </a>
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addStockModal">
            <i class="ti ti-plus me-1"></i>Add Stock
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search drug or batch..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="drug_id" class="form-select">
                    <option value="">All Drugs</option>
                    @foreach($drugs as $drug)
                    <option value="{{ $drug->id }}" {{ request('drug_id') == $drug->id ? 'selected' : '' }}>{{ $drug->name }} {{ $drug->strength }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                    <option value="low" {{ request('status') == 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="expiring" {{ request('status') == 'expiring' ? 'selected' : '' }}>Expiring Soon</option>
                    <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.pharmacy.stock.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Stock Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Drug</th>
                        <th>Batch #</th>
                        <th>Qty</th>
                        <th>Unit Cost</th>
                        <th>Selling Price</th>
                        <th>Expiry Date</th>
                        <th>Supplier</th>
                        <th>Received</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stock as $entry)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $entry->drug->name ?? '-' }}</span>
                            @if($entry->drug)
                                <br><small class="text-muted">{{ $entry->drug->dosage_form }} {{ $entry->drug->strength }}</small>
                            @endif
                        </td>
                        <td><code>{{ $entry->batch_number }}</code></td>
                        <td>
                            <span class="fw-bold {{ $entry->quantity <= $entry->reorder_level ? 'text-danger' : 'text-success' }}">
                                {{ $entry->quantity }}
                            </span>
                            @if($entry->quantity <= $entry->reorder_level)
                                <br><small class="text-danger">Reorder: {{ $entry->reorder_level }}</small>
                            @endif
                        </td>
                        <td>{{ number_format($entry->unit_cost, 2) }}</td>
                        <td>{{ number_format($entry->selling_price, 2) }}</td>
                        <td>
                            <span class="text-{{ $entry->expiry_color }}">
                                {{ $entry->expiry_date->format('d M Y') }}
                            </span>
                            @if($entry->is_expired)
                                <br><span class="badge bg-danger">Expired</span>
                            @elseif($entry->is_expiring_soon)
                                <br><span class="badge bg-warning">Expiring Soon</span>
                            @endif
                        </td>
                        <td>{{ $entry->supplier ?? '-' }}</td>
                        <td>
                            <small>{{ $entry->received_date ? $entry->received_date->format('d M Y') : '-' }}</small>
                            @if($entry->receivedBy)
                                <br><small class="text-muted">by {{ $entry->receivedBy->name }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $entry->expiry_color }}">{{ $entry->expiry_status }}</span>
                        </td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editStockModal-{{ $entry->id }}" title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Edit Stock Modal -->
                    <div class="modal fade" id="editStockModal-{{ $entry->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.pharmacy.stock.update', $entry) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Stock: {{ $entry->drug->name ?? '' }} ({{ $entry->batch_number }})</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Batch Number <span class="text-danger">*</span></label>
                                                <input type="text" name="batch_number" class="form-control" value="{{ $entry->batch_number }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                                <input type="number" name="quantity" class="form-control" value="{{ $entry->quantity }}" min="0" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Unit Cost (GHS) <span class="text-danger">*</span></label>
                                                <input type="number" name="unit_cost" class="form-control" value="{{ $entry->unit_cost }}" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Selling Price (GHS) <span class="text-danger">*</span></label>
                                                <input type="number" name="selling_price" class="form-control" value="{{ $entry->selling_price }}" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                                                <input type="date" name="expiry_date" class="form-control" value="{{ $entry->expiry_date->format('Y-m-d') }}" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Reorder Level</label>
                                                <input type="number" name="reorder_level" class="form-control" value="{{ $entry->reorder_level }}" min="0">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Supplier</label>
                                                <input type="text" name="supplier" class="form-control" value="{{ $entry->supplier }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Stock</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ti ti-packages fs-1 d-block mb-2"></i>
                            No stock entries found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($stock->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $stock->links() }}
</div>
@endif

<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.pharmacy.stock.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Drug <span class="text-danger">*</span></label>
                            <select name="drug_id" class="form-select" required>
                                <option value="">Select drug...</option>
                                @foreach($drugs as $drug)
                                <option value="{{ $drug->id }}">{{ $drug->name }} {{ $drug->strength }} ({{ $drug->unit }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Batch Number <span class="text-danger">*</span></label>
                            <input type="text" name="batch_number" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit Cost (GHS) <span class="text-danger">*</span></label>
                            <input type="number" name="unit_cost" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Selling Price (GHS) <span class="text-danger">*</span></label>
                            <input type="number" name="selling_price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Received Date</label>
                            <input type="date" name="received_date" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="10" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Supplier</label>
                            <input type="text" name="supplier" class="form-control" placeholder="Supplier name">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
