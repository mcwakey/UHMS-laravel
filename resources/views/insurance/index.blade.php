@extends('layouts.app')
@section('title', 'Insurance Providers')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Insurance Providers
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $providers->total() }}</span>
        </h4>
    </div>
    <div>
        @can('claims.create')
        <button class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addProviderModal">
            <i class="ti ti-plus me-1"></i>Add Provider
        </button>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.insurance-providers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search name, short name..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    @foreach(\App\Enums\InsuranceType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('type') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="ti ti-search me-1"></i>Filter</button>
            </div>
            @if(request()->hasAny(['search', 'type']))
            <div class="col-md-2">
                <a href="{{ route('admin.insurance-providers.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
            </div>
            @endif
        </form>
    </div>
</div>

<!-- Providers Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Short Name</th>
                        <th>Type</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Contract #</th>
                        <th>Claims</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                    <tr>
                        <td class="fw-medium">{{ $provider->name }}</td>
                        <td><span class="badge bg-light text-dark">{{ $provider->short_name ?? '-' }}</span></td>
                        <td><span class="badge bg-{{ $provider->type->color() }}">{{ $provider->type->label() }}</span></td>
                        <td>{{ $provider->contact_phone ?? '-' }}</td>
                        <td>{{ $provider->contact_email ?? '-' }}</td>
                        <td>{{ $provider->contract_number ?? '-' }}</td>
                        <td><span class="badge bg-soft-info">{{ $provider->claims_count ?? 0 }}</span></td>
                        <td>
                            <span class="badge bg-{{ $provider->is_active ? 'success' : 'danger' }}">
                                {{ $provider->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('claims.create')
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editProviderModal-{{ $provider->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.insurance-providers.toggle', $provider) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-{{ $provider->is_active ? 'eye-off' : 'eye' }} me-1"></i>
                                                {{ $provider->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            @endcan
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editProviderModal-{{ $provider->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.insurance-providers.update', $provider) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Provider</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row mb-3">
                                            <div class="col-md-8">
                                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control" value="{{ $provider->name }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Short Name</label>
                                                <input type="text" name="short_name" class="form-control" value="{{ $provider->short_name }}" maxlength="20">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Type <span class="text-danger">*</span></label>
                                            <select name="type" class="form-select" required>
                                                @foreach(\App\Enums\InsuranceType::cases() as $type)
                                                    <option value="{{ $type->value }}" {{ $provider->type === $type ? 'selected' : '' }}>{{ $type->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Phone</label>
                                                <input type="text" name="contact_phone" class="form-control" value="{{ $provider->contact_phone }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="contact_email" class="form-control" value="{{ $provider->contact_email }}">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" class="form-control" rows="2">{{ $provider->address }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Contract Number</label>
                                            <input type="text" name="contract_number" class="form-control" value="{{ $provider->contract_number }}">
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
                        <td colspan="9" class="text-center text-muted py-4">No insurance providers found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($providers->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $providers->withQueryString()->links() }}
</div>
@endif

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.insurance-providers.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Insurance Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. NHIS Ghana" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Short Name</label>
                            <input type="text" name="short_name" class="form-control" placeholder="e.g. NHIS" maxlength="20">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach(\App\Enums\InsuranceType::cases() as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="+233...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="contact_email" class="form-control" placeholder="email@provider.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Provider address..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contract Number</label>
                        <input type="text" name="contract_number" class="form-control" placeholder="Contract/Agreement #">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
