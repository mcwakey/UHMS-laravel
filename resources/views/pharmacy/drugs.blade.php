@extends('layouts.app')
@section('title', 'Drug Catalog')

@section('content')
<!-- Flash Messages (success / error / validation) -->
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
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="ti ti-alert-circle me-1"></i>Please fix the following:</strong>
    <ul class="mb-0 mt-1 small">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-pill me-2"></i>Drug Catalog</h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-md" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="ti ti-folder-plus me-1"></i>Add Category
        </button>
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addDrugModal">
            <i class="ti ti-plus me-1"></i>Link Pharmacy Product
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- Categories Sidebar -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h6 class="fw-bold mb-0"><i class="ti ti-folders me-1"></i>Categories</h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @forelse($categories as $cat)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-medium">{{ $cat->name }}</span>
                            <span class="badge bg-soft-primary ms-1">{{ $cat->drugs_count }}</span>
                            @if(!$cat->is_active)
                                <span class="badge bg-danger ms-1">Inactive</span>
                            @endif
                            @if($cat->description)
                                <br><small class="text-muted">{{ Str::limit($cat->description, 40) }}</small>
                            @endif
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                <i class="ti ti-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editCategoryModal-{{ $cat->id }}">
                                        <i class="ti ti-edit me-1"></i>Edit
                                    </button>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('admin.pharmacy.drug-categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="ti ti-trash me-1"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Edit Category Modal -->
                    <div class="modal fade" id="editCategoryModal-{{ $cat->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.pharmacy.drug-categories.update', $cat) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Category</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3">{{ $cat->description }}</textarea>
                                        </div>
                                        <div class="form-check">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="catActive-{{ $cat->id }}" {{ $cat->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="catActive-{{ $cat->id }}">Active</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="list-group-item text-center text-muted py-4">
                        No categories yet. Create one to get started.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Drugs Table -->
    <div class="col-md-9">
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search drugs..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
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
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Form</th>
                                <th>Strength</th>
                                <th>Unit</th>
                                <th>Price (GHS)</th>
                                <th>Available in Pharmacy</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($drugs as $drug)
                            <tr class="{{ !$drug->is_active ? 'table-secondary' : '' }}">
                                <td>
                                    <span class="fw-medium">{{ $drug->product?->name ?? $drug->name }}</span>
                                    @if($drug->generic_name_id || $drug->generic_name)
                                        <br><small class="text-muted">Generic: {{ $drug->genericName?->name ?? $drug->generic_name }}</small>
                                    @endif
                                    @if($drug->brand_name)
                                        <br><small class="text-info">Brand: {{ $drug->brand_name }}</small>
                                    @endif
                                    @if($drug->requires_prescription)
                                        <span class="badge bg-soft-warning ms-1" title="Requires Prescription"><i class="ti ti-prescription"></i></span>
                                    @endif
                                </td>
                                <td>{{ $drug->category->name ?? '-' }}</td>
                                <td>{{ $drug->dosage_form }}</td>
                                <td>{{ $drug->strength ?? '-' }}</td>
                                <td>{{ $drug->unit }}</td>
                                <td>{{ number_format($drug->price, 2) }}</td>
                                <td>
                                    @php
                                        $stock = (float) $drug->total_stock;
                                        $stockDisplay = rtrim(rtrim(number_format($stock, 4), '0'), '.');
                                    @endphp
                                    <span class="badge bg-{{ $stock > 10 ? 'success' : ($stock > 0 ? 'warning' : 'danger') }}">
                                        {{ $stockDisplay }}
                                    </span>
                                    @if($drug->is_low_stock)
                                        <small class="text-danger d-block"><i class="ti ti-alert-triangle"></i> Low</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $drug->is_active ? 'success' : 'secondary' }}">
                                        {{ $drug->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.pharmacy.drugs.history', $drug) }}" class="btn btn-outline-info" title="Drug History">
                                            <i class="ti ti-history"></i>
                                        </a>
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editDrugModal-{{ $drug->id }}" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.pharmacy.drugs.toggle', $drug) }}" class="d-inline">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-outline-{{ $drug->is_active ? 'warning' : 'success' }}" title="{{ $drug->is_active ? 'Deactivate' : 'Activate' }}">
                                                <i class="ti ti-{{ $drug->is_active ? 'eye-off' : 'eye' }}"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Drug Modal -->
                            <div class="modal fade" id="editDrugModal-{{ $drug->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.pharmacy.drugs.update', $drug) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Drug: {{ $drug->product?->name ?? $drug->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label">Linked Product <span class="text-danger">*</span></label>
                                                        <select name="product_id" class="form-select" required>
                                                            <option value="">— Select product —</option>
                                                            @foreach($pharmacyProducts ?? [] as $p)
                                                                <option value="{{ $p->id }}" @selected($drug->product_id == $p->id)>{{ $p->name }}@if($p->code) ({{ $p->code }})@endif</option>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted">The product's name is used as the drug name.</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Category <span class="text-danger">*</span></label>
                                                        <select name="category_id" class="form-select" required>
                                                            @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}" @selected($drug->category_id == $cat->id)>{{ $cat->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Generic Name</label>
                                                        <select name="generic_name_id" class="form-select">
                                                            <option value="">— None —</option>
                                                            @foreach($generics ?? [] as $g)
                                                                <option value="{{ $g->id }}" @selected($drug->generic_name_id == $g->id)>{{ $g->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">Brand Name</label>
                                                        <input type="text" name="brand_name" class="form-control" value="{{ $drug->brand_name }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Dosage Form <span class="text-danger">*</span></label>
                                                        <select name="dosage_form" class="form-select" required>
                                                            @foreach(['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Ointment', 'Drops', 'Inhaler', 'Suppository', 'Suspension', 'Powder', 'Solution', 'Spray', 'Gel', 'Patch', 'Other'] as $form)
                                                            <option value="{{ $form }}" {{ $drug->dosage_form == $form ? 'selected' : '' }}>{{ $form }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Strength</label>
                                                        <input type="text" name="strength" class="form-control" value="{{ $drug->strength }}" placeholder="e.g. 500mg">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Unit <span class="text-danger">*</span></label>
                                                        <select name="unit" class="form-select" required>
                                                            @foreach(['Tablet', 'Capsule', 'Bottle', 'Ampoule', 'Tube', 'Sachet', 'Vial', 'Pack', 'Strip', 'Piece', 'ml', 'mg', 'g'] as $unit)
                                                            <option value="{{ $unit }}" {{ $drug->unit == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Price (GHS) <span class="text-danger">*</span></label>
                                                        <input type="number" name="price" class="form-control" value="{{ $drug->price }}" step="0.01" min="0" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-check mt-4">
                                                            <input type="hidden" name="requires_prescription" value="0">
                                                            <input type="checkbox" name="requires_prescription" value="1" class="form-check-input" id="editRxReq-{{ $drug->id }}" {{ $drug->requires_prescription ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="editRxReq-{{ $drug->id }}">Requires Prescription</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-check mt-4">
                                                            <input type="hidden" name="is_active" value="0">
                                                            <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editDrugActive-{{ $drug->id }}" {{ $drug->is_active ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="editDrugActive-{{ $drug->id }}">Active</label>
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label">Description</label>
                                                        <textarea name="description" class="form-control" rows="2">{{ $drug->description }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update Drug</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="ti ti-pill fs-1 d-block mb-2"></i>
                                    No drugs found. Add your first drug to get started.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @if($drugs->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $drugs->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.pharmacy.drug-categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Drug Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Drug Modal -->
<div class="modal fade" id="addDrugModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.pharmacy.drugs.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add New Drug</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Drug (from Products) <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select" required>
                                <option value="">Select product...</option>
                                @foreach($pharmacyProducts ?? [] as $p)
                                    <option value="{{ $p->id }}" data-name="{{ $p->name }}" data-unit="{{ $p->unit }}">{{ $p->name }}@if($p->code) ({{ $p->code }})@endif</option>
                                @endforeach
                            </select>
                            <small class="text-muted">
                                The product's name is used as the drug name.
                                @if(($pharmacyProducts ?? collect())->isEmpty())
                                    <span class="text-warning">No pharmacy drug products found — create them under
                                        <a href="{{ route('admin.products.index') }}">Store / Products</a> first.</span>
                                @endif
                            </small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Generic Name</label>
                            <select name="generic_name_id" class="form-select">
                                <option value="">— None —</option>
                                @foreach($generics ?? [] as $g)
                                    <option value="{{ $g->id }}">{{ $g->name }}@if($g->therapeutic_class) — <span>{{ $g->therapeutic_class }}</span>@endif</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Pick from the seeded WHO/essential drugs list.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Brand Name</label>
                            <input type="text" name="brand_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Dosage Form <span class="text-danger">*</span></label>
                            <select name="dosage_form" class="form-select" required>
                                <option value="">Select form...</option>
                                @foreach(['Tablet', 'Capsule', 'Syrup', 'Injection', 'Cream', 'Ointment', 'Drops', 'Inhaler', 'Suppository', 'Suspension', 'Powder', 'Solution', 'Spray', 'Gel', 'Patch', 'Other'] as $form)
                                <option value="{{ $form }}">{{ $form }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Strength</label>
                            <input type="text" name="strength" class="form-control" placeholder="e.g. 500mg">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" required>
                                <option value="">Select unit...</option>
                                @foreach(['Tablet', 'Capsule', 'Bottle', 'Ampoule', 'Tube', 'Sachet', 'Vial', 'Pack', 'Strip', 'Piece', 'ml', 'mg', 'g'] as $unit)
                                <option value="{{ $unit }}">{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (GHS) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Opening Stock</label>
                            <input type="number" name="opening_stock" class="form-control" step="0.0001" min="0" value="0">
                            <small class="text-muted">Creates an OPENING_STOCK movement in Main Store.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" step="0.0001" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input type="hidden" name="requires_prescription" value="0">
                                <input type="checkbox" name="requires_prescription" value="1" class="form-check-input" id="newRxReq" checked>
                                <label class="form-check-label" for="newRxReq">Requires Prescription</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Link Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
