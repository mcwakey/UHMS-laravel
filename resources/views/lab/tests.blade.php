@extends('layouts.app')
@section('title', 'Lab Test Catalog')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-flask me-2"></i>Lab Test Catalog</h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-md" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="ti ti-folder-plus me-1"></i>Add Category
        </button>
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addTestModal">
            <i class="ti ti-plus me-1"></i>Add Test
        </button>
    </div>
</div>

<div class="row g-3">
    <!-- Categories Sidebar -->
    <div class="col-md-4">
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
                            <span class="badge bg-soft-primary ms-1">{{ $cat->tests_count }}</span>
                            @if(!$cat->is_active)
                                <span class="badge bg-danger ms-1">Inactive</span>
                            @endif
                            @if($cat->description)
                                <br><small class="text-muted">{{ Str::limit($cat->description, 50) }}</small>
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
                                    <form method="POST" action="{{ route('admin.lab.categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category?')">
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
                                <form method="POST" action="{{ route('admin.lab.categories.update', $cat) }}">
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

    <!-- Tests Table -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="ti ti-flask me-1"></i>Lab Tests</h6>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search tests..." value="{{ request('search') }}" style="width: 150px;">
                        <select name="category_id" class="form-select form-select-sm" style="width: 150px;" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Normal Range</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tests as $test)
                            <tr>
                                <td><span class="badge bg-light text-dark fw-medium">{{ $test->code }}</span></td>
                                <td class="fw-medium">{{ $test->name }}</td>
                                <td>{{ $test->category->name ?? '-' }}</td>
                                <td><small>{{ $test->normal_range ?? '-' }} {{ $test->unit ?? '' }}</small></td>
                                <td>{{ $test->price ? 'GH₵ ' . number_format($test->price, 2) : '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $test->is_active ? 'success' : 'danger' }}">
                                        {{ $test->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                            <i class="ti ti-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editTestModal-{{ $test->id }}">
                                                    <i class="ti ti-edit me-1"></i>Edit
                                                </button>
                                            </li>
                                            <li>
                                                <form method="POST" action="{{ route('admin.lab.tests.toggle', $test) }}">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="ti ti-{{ $test->is_active ? 'eye-off' : 'eye' }} me-1"></i>
                                                        {{ $test->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Test Modal -->
                            <div class="modal fade" id="editTestModal-{{ $test->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('admin.lab.tests.update', $test) }}">
                                            @csrf @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Lab Test</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Category <span class="text-danger">*</span></label>
                                                    <select name="category_id" class="form-select" required>
                                                        @foreach($categories as $cat)
                                                            <option value="{{ $cat->id }}" {{ $test->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-md-8">
                                                        <label class="form-label">Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control" value="{{ $test->name }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Code <span class="text-danger">*</span></label>
                                                        <input type="text" name="code" class="form-control" value="{{ $test->code }}" required>
                                                    </div>
                                                </div>
                                                <div class="row g-2 mt-1">
                                                    <div class="col-md-5">
                                                        <label class="form-label">Normal Range</label>
                                                        <input type="text" name="normal_range" class="form-control" value="{{ $test->normal_range }}" placeholder="e.g. 4.5-11.0">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label">Unit</label>
                                                        <input type="text" name="unit" class="form-control" value="{{ $test->unit }}" placeholder="e.g. g/dL">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">Price (GH₵)</label>
                                                        <input type="number" name="price" class="form-control" value="{{ $test->price }}" step="0.01" min="0">
                                                    </div>
                                                </div>
                                                <div class="form-check mt-3">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="testActive-{{ $test->id }}" {{ $test->is_active ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="testActive-{{ $test->id }}">Active</label>
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
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="ti ti-flask fs-1 d-block mb-2"></i>
                                    No lab tests found. Create a category first, then add tests.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @if($tests->hasPages())
        <div class="d-flex justify-content-end mt-3">
            {{ $tests->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.lab.categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Haematology" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description..."></textarea>
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

<!-- Add Test Modal -->
<div class="modal fade" id="addTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.lab.tests.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Lab Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Full Blood Count" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. FBC" required>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-md-5">
                            <label class="form-label">Normal Range</label>
                            <input type="text" name="normal_range" class="form-control" placeholder="e.g. 4.5-11.0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g. g/dL">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (GH₵)</label>
                            <input type="number" name="price" class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Test</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
