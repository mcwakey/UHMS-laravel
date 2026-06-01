@extends('layouts.app')
@section('title', 'Complaint Catalogue')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Complaint Catalogue</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Complaint Catalogue</li>
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">Add Complaint</h5></div>
            <div class="card-body">
                @can('complaints.catalogue.create')
                <form method="POST" action="{{ route('admin.complaints.catalogue.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control" value="{{ old('category') }}" list="complaintCategoryList">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Body System</label>
                            <input type="text" name="body_system" class="form-control" value="{{ old('body_system') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keywords</label>
                        <textarea name="keywords" class="form-control" rows="2" placeholder="Comma-separated synonyms">{{ old('keywords') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order') }}">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>Add</button>
                </form>
                @else
                    <p class="text-muted mb-0">You can view the catalogue but cannot add entries.</p>
                @endcan
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name, category, keyword">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All</option>
                            @foreach($categories as $category)
                                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="active" class="form-select">
                            <option value="">All</option>
                            <option value="1" @selected(($filters['active'] ?? '') === '1')>Active</option>
                            <option value="0" @selected(($filters['active'] ?? '') === '0')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary"><i class="ti ti-search me-1"></i>Filter</button>
                    </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Keywords</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($complaints as $complaint)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $complaint->name }}</div>
                                @if($complaint->description)<small class="text-muted">{{ Str::limit($complaint->description, 90) }}</small>@endif
                            </td>
                            <td>{{ $complaint->category ?: '-' }}</td>
                            <td><small class="text-muted">{{ implode(', ', $complaint->keywords ?: []) }}</small></td>
                            <td><span class="badge bg-{{ $complaint->is_active ? 'success' : 'secondary' }}">{{ $complaint->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                @can('complaints.catalogue.update')
                                <button class="btn btn-xs btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editComplaint{{ $complaint->id }}">
                                    <i class="ti ti-edit"></i>
                                </button>
                                @endcan
                                @can('complaints.catalogue.deactivate')
                                <form method="POST" action="{{ route('admin.complaints.catalogue.toggle', $complaint) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-xs btn-outline-secondary" title="Toggle status"><i class="ti ti-power"></i></button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                        @can('complaints.catalogue.update')
                        <tr class="collapse" id="editComplaint{{ $complaint->id }}">
                            <td colspan="5">
                                <form method="POST" action="{{ route('admin.complaints.catalogue.update', $complaint) }}" class="row g-2 align-items-end">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-md-3"><label class="form-label small">Name</label><input name="name" class="form-control form-control-sm" value="{{ $complaint->name }}" required></div>
                                    <div class="col-md-2"><label class="form-label small">Category</label><input name="category" class="form-control form-control-sm" value="{{ $complaint->category }}" list="complaintCategoryList"></div>
                                    <div class="col-md-2"><label class="form-label small">Body System</label><input name="body_system" class="form-control form-control-sm" value="{{ $complaint->body_system }}"></div>
                                    <div class="col-md-3"><label class="form-label small">Keywords</label><input name="keywords" class="form-control form-control-sm" value="{{ implode(', ', $complaint->keywords ?: []) }}"></div>
                                    <div class="col-md-1"><label class="form-label small">Order</label><input name="sort_order" type="number" min="0" class="form-control form-control-sm" value="{{ $complaint->sort_order }}"></div>
                                    <div class="col-md-1 d-grid"><button class="btn btn-sm btn-primary">Save</button></div>
                                    <div class="col-12"><label class="form-label small">Description</label><textarea name="description" class="form-control form-control-sm" rows="2">{{ $complaint->description }}</textarea></div>
                                    <input type="hidden" name="is_active" value="{{ $complaint->is_active ? 1 : 0 }}">
                                </form>
                            </td>
                        </tr>
                        @endcan
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No complaint catalogue entries found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($complaints->hasPages())
                <div class="card-footer">{{ $complaints->links() }}</div>
            @endif
        </div>
    </div>
</div>

<datalist id="complaintCategoryList">
    @foreach($categories as $category)
        <option value="{{ $category }}">
    @endforeach
</datalist>
@endsection