@extends('layouts.app')
@section('title', 'Designations')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Designations
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $designations->total() }}</span>
        </h4>
    </div>
    <div>
        @can('departments.create')
        <button class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addDesigModal">
            <i class="ti ti-plus me-1"></i>Add Designation
        </button>
        @endcan
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.designations.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search designation..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary btn-md me-1"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.designations.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Designations Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Description</th>
                        <th>Users</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($designations as $desig)
                    <tr>
                        <td class="fw-medium">{{ $desig->name }}</td>
                        <td>
                            <span class="badge bg-soft-info">{{ $desig->department?->name ?? 'N/A' }}</span>
                        </td>
                        <td>{{ Str::limit($desig->description, 50) ?? '-' }}</td>
                        <td><span class="badge bg-soft-primary">{{ $desig->users_count }}</span></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('departments.edit')
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editDesigModal-{{ $desig->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    @endcan
                                    @can('departments.delete')
                                    <li>
                                        <form method="POST" action="{{ route('admin.designations.destroy', $desig) }}" onsubmit="return confirm('Delete this designation?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="ti ti-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editDesigModal-{{ $desig->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.designations.update', $desig) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Designation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $desig->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Department <span class="text-danger">*</span></label>
                                            <select name="department_id" class="form-select" required>
                                                @foreach($departments as $dept)
                                                    <option value="{{ $dept->id }}" {{ $desig->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="3">{{ $desig->description }}</textarea>
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
                        <td colspan="5" class="text-center text-muted py-4">No designations found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($designations->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $designations->links() }}
</div>
@endif

<!-- Add Designation Modal -->
<div class="modal fade" id="addDesigModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.designations.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Senior Medical Officer" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department <span class="text-danger">*</span></label>
                        <select name="department_id" class="form-select" required>
                            <option value="">Select Department</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Designation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
