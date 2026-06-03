@extends('layouts.app')
@section('title', 'ICD-10 Code Database')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-medical-cross me-2"></i>ICD-10 Code Database</h4>
        <small class="text-muted">{{ $codes->total() }} codes in database</small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-md" data-bs-toggle="modal" data-bs-target="#addCodeModal">
            <i class="ti ti-plus me-1"></i>Add Code
        </button>
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

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.icd-codes.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search code or description..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="chapter" class="form-select form-select-sm">
                    <option value="">All Chapters</option>
                    @foreach($chapters as $ch)
                    <option value="{{ $ch }}" {{ request('chapter') === $ch ? 'selected' : '' }}>Chapter {{ $ch }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a aria-label="Close" title="Close" href="{{ route('admin.icd-codes.index') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Codes Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:100px">Code</th>
                        <th>Description</th>
                        <th>Category</th>
                        <th style="width:80px">Chapter</th>
                        <th style="width:80px">Billable</th>
                        <th style="width:100px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($codes as $code)
                    <tr>
                        <td><code class="fw-bold">{{ $code->code }}</code></td>
                        <td>{{ $code->description }}</td>
                        <td><span class="badge bg-light text-dark">{{ $code->category ?? '—' }}</span></td>
                        <td class="text-center">{{ $code->chapter ?? '—' }}</td>
                        <td class="text-center">
                            @if($code->is_billable)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCodeModal{{ $code->id }}" aria-label="Edit" title="Edit">
                                <i class="ti ti-edit"></i>
                            </button>
                            <x-confirm-form :action="route('admin.icd-codes.destroy', $code)" method="DELETE"
                                button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-trash"
                                confirm-title="Delete this ICD code?" confirm-text="This action cannot be undone." confirm-button="Yes, delete" />
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editCodeModal{{ $code->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.icd-codes.update', $code) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit ICD-10 Code</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Code <span class="text-danger">*</span></label>
                                            <input type="text" name="code" class="form-control" value="{{ $code->code }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description <span class="text-danger">*</span></label>
                                            <input type="text" name="description" class="form-control" value="{{ $code->description }}" required>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Category</label>
                                                <input type="text" name="category" class="form-control" value="{{ $code->category }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Chapter</label>
                                                <input type="text" name="chapter" class="form-control" value="{{ $code->chapter }}">
                                            </div>
                                        </div>
                                        <div class="form-check mt-3">
                                            <input type="hidden" name="is_billable" value="0">
                                            <input class="form-check-input" type="checkbox" name="is_billable" value="1" id="editBillable{{ $code->id }}" {{ $code->is_billable ? 'checked' : '' }}>
                                            <label class="form-check-label" for="editBillable{{ $code->id }}">Billable</label>
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="ti ti-medical-cross fs-1 d-block mb-2"></i>
                            No ICD-10 codes found. Seed the database or add codes manually.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($codes->hasPages())
    <div class="card-footer">
        {{ $codes->links() }}
    </div>
    @endif
</div>

<!-- Add Code Modal -->
<div class="modal fade" id="addCodeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.icd-codes.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add ICD-10 Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., J06.9" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="e.g., Acute upper respiratory infection" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" placeholder="e.g., Respiratory diseases">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Chapter</label>
                            <input type="text" name="chapter" class="form-control" placeholder="e.g., X">
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input type="hidden" name="is_billable" value="0">
                        <input class="form-check-input" type="checkbox" name="is_billable" value="1" id="addBillable" checked>
                        <label class="form-check-label" for="addBillable">Billable</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Code</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
