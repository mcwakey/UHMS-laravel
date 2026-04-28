@extends('layouts.app')
@section('title', 'Investigation Item Catalog')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Investigation Item Catalog</h4>
        <p class="text-muted small mb-0">Manage reagents, test kits, consumables, radiology & imaging supplies</p>
    </div>
    @can('lab.tests.manage')
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
        <i class="ti ti-plus me-1"></i>Add Item
    </button>
    @endcan
</div>

{{-- Stats --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
        <div class="card border-start border-primary border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Total Items</p>
                <h4 class="fw-bold mb-0">{{ $items->total() }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-start border-warning border-3 shadow-sm">
            <div class="card-body py-3">
                <p class="text-muted small mb-1">Low Stock</p>
                <h4 class="fw-bold mb-0">{{ $lowStockCount }}</h4>
            </div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                    placeholder="Search name or code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                        {{ $cat->label() }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('admin.investigations.items.index') }}" class="btn btn-secondary btn-sm">Reset</a>
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
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th class="text-center">Total Stock</th>
                        <th class="text-center">Reorder Level</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $item->name }}</span>
                            @if($item->description)
                            <br><small class="text-muted">{{ Str::limit($item->description, 60) }}</small>
                            @endif
                        </td>
                        <td><code>{{ $item->code ?? '—' }}</code></td>
                        <td>
                            <span class="badge bg-{{ $item->category->color() }}">{{ $item->category->label() }}</span>
                        </td>
                        <td>{{ $item->unit }}</td>
                        <td class="text-center">
                            @php $stock = $item->total_stock ?? 0; @endphp
                            <span class="fw-bold {{ $stock <= $item->reorder_level ? 'text-danger' : 'text-success' }}">
                                {{ $stock }}
                            </span>
                        </td>
                        <td class="text-center text-muted">{{ $item->reorder_level }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('lab.tests.manage')
                            <button class="btn btn-sm btn-outline-primary edit-btn"
                                data-id="{{ $item->id }}"
                                data-name="{{ $item->name }}"
                                data-code="{{ $item->code }}"
                                data-category="{{ $item->category->value }}"
                                data-unit="{{ $item->unit }}"
                                data-reorder="{{ $item->reorder_level }}"
                                data-description="{{ $item->description }}"
                                data-bs-toggle="modal" data-bs-target="#editItemModal">
                                <i class="ti ti-edit"></i>
                            </button>
                            <form method="POST"
                                action="{{ route('admin.investigations.items.toggle', $item) }}"
                                class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-{{ $item->is_active ? 'warning' : 'success' }}" type="submit">
                                    <i class="ti ti-{{ $item->is_active ? 'ban' : 'check' }}"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No investigation items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($items->hasPages())
    <div class="card-footer">{{ $items->links() }}</div>
    @endif
</div>

{{-- Add Modal --}}
@can('lab.tests.manage')
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.investigations.items.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Investigation Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. RGT-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" value="unit" required>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="10" min="0">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editItemModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editItemForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Investigation Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editName" class="form-control" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Code</label>
                            <input type="text" name="code" id="editCode" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" id="editUnit" class="form-control" required>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                            <select name="category" id="editCategory" class="form-select" required>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Reorder Level</label>
                            <input type="number" name="reorder_level" id="editReorder" class="form-control" min="0">
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label fw-medium">Description</label>
                        <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Item</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const form = document.getElementById('editItemForm');
            form.action = `/admin/investigations/items/${id}`;
            document.getElementById('editName').value        = this.dataset.name;
            document.getElementById('editCode').value        = this.dataset.code || '';
            document.getElementById('editUnit').value        = this.dataset.unit;
            document.getElementById('editCategory').value   = this.dataset.category;
            document.getElementById('editReorder').value    = this.dataset.reorder;
            document.getElementById('editDescription').value = this.dataset.description || '';
        });
    });
});
</script>
@endpush
