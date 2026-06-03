@extends('layouts.app')
@section('title', 'Procedure Catalog')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-stethoscope me-2"></i>Procedure Catalog</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.procedures.schedule') }}" class="btn btn-outline-primary btn-md">
            <i class="ti ti-calendar me-1"></i>Scheduled Procedures
        </a>
        @can('procedures.create')
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addProcedureModal">
            <i class="ti ti-plus me-1"></i>Add Procedure
        </button>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Stats -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['total_procedures'] }}</div>
                        <small>Active Procedures</small>
                    </div>
                    <i class="ti ti-stethoscope fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['scheduled_today'] }}</div>
                        <small>Scheduled Today</small>
                    </div>
                    <i class="ti ti-calendar fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['completed_today'] }}</div>
                        <small>Completed Today</small>
                    </div>
                    <i class="ti ti-check fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fs-5 fw-bold">{{ $stats['total_icd_codes'] }}</div>
                        <small>ICD-10 Codes</small>
                    </div>
                    <i class="ti ti-medical-cross fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.procedures.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search procedure..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $id => $name)
                    <option value="{{ $id }}" {{ request('department_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a aria-label="Close" title="Close" href="{{ route('admin.procedures.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Procedures Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th class="text-end">Price (₵)</th>
                        <th class="text-end">Insurance (₵)</th>
                        <th class="text-center">Consent</th>
                        <th class="text-center">Status</th>
                        <th style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($procedures as $procedure)
                    <tr>
                        <td class="fw-semibold">{{ $procedure->name }}</td>
                        <td><code>{{ $procedure->code ?? '—' }}</code></td>
                        <td>
                            <span class="badge bg-{{ $procedure->category === 'surgical' ? 'danger' : ($procedure->category === 'diagnostic' ? 'info' : ($procedure->category === 'therapeutic' ? 'success' : 'secondary')) }}">
                                {{ ucfirst($procedure->category) }}
                            </span>
                        </td>
                        <td>{{ $procedure->department->name ?? '—' }}</td>
                        <td class="text-end">{{ $procedure->formatted_price }}</td>
                        <td class="text-end">{{ $procedure->formatted_nhis_price ?? '—' }}</td>
                        <td class="text-center">
                            @if($procedure->requires_consent)
                            <i class="ti ti-alert-triangle text-warning" title="Requires consent"></i>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $procedure->is_active ? 'success' : 'secondary' }}">{{ $procedure->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td>
                            @can('procedures.edit')
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProcedureModal{{ $procedure->id }}" aria-label="Edit" title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.procedures.toggle', $procedure) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-{{ $procedure->is_active ? 'warning' : 'success' }}" title="{{ $procedure->is_active ? 'Deactivate' : 'Activate' }}">
                                    <i class="ti ti-{{ $procedure->is_active ? 'ban' : 'check' }}"></i>
                                </button>
                            </form>
                            @endcan
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editProcedureModal{{ $procedure->id }}" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.procedures.update', $procedure) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Procedure</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-8">
                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="{{ $procedure->name }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Code</label>
                                                <input type="text" name="code" class="form-control" value="{{ $procedure->code }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                                <select name="category" class="form-select" required>
                                                    @foreach($categories as $cat)
                                                    <option value="{{ $cat }}" {{ $procedure->category === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Department</label>
                                                <select name="department_id" class="form-select">
                                                    <option value="">None</option>
                                                    @foreach($departments as $id => $name)
                                                    <option value="{{ $id }}" {{ $procedure->department_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="2">{{ $procedure->description }}</textarea>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Default Price (₵) <span class="text-danger">*</span></label>
                                                <input type="number" name="default_price" class="form-control" step="0.01" value="{{ $procedure->default_price }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Insurance Price (₵)</label>
                                                <input type="number" name="nhis_price" class="form-control" step="0.01" value="{{ $procedure->nhis_price }}">
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <div class="form-check">
                                                    <input type="hidden" name="requires_consent" value="0">
                                                    <input class="form-check-input" type="checkbox" name="requires_consent" value="1" id="editConsent{{ $procedure->id }}" {{ $procedure->requires_consent ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="editConsent{{ $procedure->id }}">Requires Consent</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ti ti-stethoscope fs-1 d-block mb-2"></i>
                            No procedures found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($procedures->hasPages())
    <div class="card-footer">
        {{ $procedures->links() }}
    </div>
    @endif
</div>

<!-- Add Procedure Modal -->
<div class="modal fade" id="addProcedureModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.procedures.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Procedure</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Appendectomy" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g., PROC-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">None</option>
                                @foreach($departments as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Procedure description..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Default Price (₵) <span class="text-danger">*</span></label>
                            <input type="number" name="default_price" class="form-control" step="0.01" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Insurance Price (₵)</label>
                            <input type="number" name="nhis_price" class="form-control" step="0.01">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="requires_consent" value="0">
                                <input class="form-check-input" type="checkbox" name="requires_consent" value="1" id="addConsent">
                                <label class="form-check-label" for="addConsent">Requires Consent</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Procedure</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
