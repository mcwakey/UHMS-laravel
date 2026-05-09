@extends('layouts.app')

@section('title', 'Investigation Catalogue')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><i class="ti ti-flask me-2"></i>Investigation Catalogue</h4>
            <small class="text-muted">Configure headers and result criteria for services offered by investigation-type departments.</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Service name or code...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Department</label>
                    <select name="department_id" class="form-select form-select-sm">
                        <option value="">All Investigation Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" @selected(request('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="active" class="form-select form-select-sm">
                        <option value="">Any</option>
                        <option value="1" @selected(request('active') === '1')>Active</option>
                        <option value="0" @selected(request('active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-primary btn-sm flex-grow-1"><i class="ti ti-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.investigation-catalogue.index') }}" class="btn btn-light btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Service</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th class="text-center">Headers</th>
                            <th class="text-center">Criteria</th>
                            <th class="text-end">Price</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr>
                                <td class="fw-medium">{{ $service->name }}</td>
                                <td><code class="small">{{ $service->code ?? '—' }}</code></td>
                                <td>{{ $service->department->name ?? '—' }} <span class="badge bg-light text-muted ms-1">{{ ucfirst($service->department->type?->value ?? '') }}</span></td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $service->investigation_headers_count > 0 ? 'info' : 'secondary' }}">{{ $service->investigation_headers_count }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $service->investigation_criteria_count > 0 ? 'primary' : 'secondary' }}">{{ $service->investigation_criteria_count }}</span>
                                </td>
                                <td class="text-end">{{ number_format((float) $service->price, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.investigation-catalogue.show', $service) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-settings me-1"></i>Configure
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="ti ti-flask-off fs-1 d-block mb-2"></i>
                                    No services found. Make sure investigation-type departments have services in the catalog.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($services->hasPages())
            <div class="card-footer">{{ $services->links() }}</div>
        @endif
    </div>
</div>
@endsection
