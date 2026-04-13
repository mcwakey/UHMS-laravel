@extends('layouts.app')
@section('title', 'Specialties')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2"></i>Specialties
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $specialties->total() }}</span>
        </h4>
    </div>
    <div>
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addSpecialtyModal">
            <i class="ti ti-plus me-1"></i>Add Specialty
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.specialties.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search specialty name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary"><i class="ti ti-search me-1"></i>Search</button>
                @if(request('search'))
                <a href="{{ route('admin.specialties.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Specialties Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-center">Doctors</th>
                        <th class="text-center">Services</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($specialties as $specialty)
                    <tr>
                        <td class="fw-medium">{{ $specialty->name }}</td>
                        <td class="text-muted">{{ Str::limit($specialty->description, 60) ?? '—' }}</td>
                        <td class="text-center"><span class="badge bg-soft-info">{{ $specialty->doctors_count }}</span></td>
                        <td class="text-center"><span class="badge bg-soft-primary">{{ $specialty->services_count }}</span></td>
                        <td class="text-center">
                            <span class="badge bg-{{ $specialty->is_active ? 'success' : 'danger' }}">{{ $specialty->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editSpecialtyModal-{{ $specialty->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.specialties.toggle', $specialty) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item {{ $specialty->is_active ? 'text-danger' : 'text-success' }}">
                                                <i class="ti ti-{{ $specialty->is_active ? 'eye-off' : 'eye' }} me-1"></i>
                                                {{ $specialty->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editSpecialtyModal-{{ $specialty->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.specialties.update', $specialty) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Specialty</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $specialty->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3">{{ $specialty->description }}</textarea>
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="ti ti-stethoscope fs-1 d-block mb-2"></i>No specialties found. Add your first specialty.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($specialties->hasPages())
    <div class="card-footer">{{ $specialties->links() }}</div>
    @endif
</div>

<!-- Add Specialty Modal -->
<div class="modal fade" id="addSpecialtyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.specialties.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New Specialty</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. General Surgery, Cardiology..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this specialty..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Specialty</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
