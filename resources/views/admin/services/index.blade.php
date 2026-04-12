@extends('layouts.app')
@section('title', 'Service Catalog')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-list-details me-2"></i>Service Catalog</h4>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addServiceModal">
            <i class="ti ti-plus me-1"></i>Add Service
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.services.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search service name or code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.services.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Services Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-nowrap mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Service Name</th>
                        <th>Category</th>
                        <th class="text-end">Price (&#8373;)</th>
                        <th class="text-end">NHIS Price (&#8373;)</th>
                        <th class="text-center">NHIS Covered</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $service)
                    <tr>
                        <td class="fw-medium">{{ $service->code }}</td>
                        <td>{{ $service->name }}</td>
                        <td><span class="badge bg-soft-info">{{ ucfirst($service->category) }}</span></td>
                        <td class="text-end fw-medium">&#8373;{{ number_format($service->price, 2) }}</td>
                        <td class="text-end">{{ $service->nhis_price ? '₵' . number_format($service->nhis_price, 2) : '—' }}</td>
                        <td class="text-center">
                            @if($service->is_nhis_covered)
                            <span class="badge bg-success">Yes</span>
                            @else
                            <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($service->is_active)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editServiceModal-{{ $service->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.services.toggle', $service) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item {{ $service->is_active ? 'text-danger' : 'text-success' }}">
                                                <i class="ti ti-{{ $service->is_active ? 'x' : 'check' }} me-1"></i>
                                                {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editServiceModal-{{ $service->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.services.update', $service) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Service</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
                                            <input type="text" name="code" class="form-control" value="{{ $service->code }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                                            <select name="category" class="form-select" required>
                                                @foreach($categories as $cat)
                                                <option value="{{ $cat }}" {{ $service->category === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium">Price (&#8373;) <span class="text-danger">*</span></label>
                                                <input type="number" name="price" class="form-control" value="{{ $service->price }}" step="0.01" min="0" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-medium">NHIS Price (&#8373;)</label>
                                                <input type="number" name="nhis_price" class="form-control" value="{{ $service->nhis_price }}" step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="is_nhis_covered" class="form-check-input" value="1" id="editNhis{{ $service->id }}" {{ $service->is_nhis_covered ? 'checked' : '' }}>
                                            <label class="form-check-label" for="editNhis{{ $service->id }}">NHIS Covered</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Service</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="ti ti-list-details fs-1 d-block mb-2"></i>
                                No services found. Add your first service.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($services->hasPages())
    <div class="card-footer">
        {{ $services->links() }}
    </div>
    @endif
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.services.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. General Consultation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. CONSULT-001" required>
                        <small class="text-muted">Unique code, auto-uppercased</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="">Select category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">Price (&#8373;) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-medium">NHIS Price (&#8373;)</label>
                            <input type="number" name="nhis_price" class="form-control" step="0.01" min="0">
                            <small class="text-muted">NHIS-approved price</small>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_nhis_covered" class="form-check-input" value="1" id="addNhisCovered">
                        <label class="form-check-label" for="addNhisCovered">NHIS Covered</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Service</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
