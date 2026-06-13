@extends('layouts.app')
@section('title', __('store.suppliers'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Suppliers
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $suppliers->total() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.store.suppliers.index') }}" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Search suppliers..." value="{{ request('search') }}" style="width:200px;">
            <button aria-label="Search" title="Search" type="submit" class="btn btn-outline-primary"><i class="ti ti-search"></i></button>
            @if(request('search'))
                <a aria-label="Close" title="Close" href="{{ route('admin.store.suppliers.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
            @endif
        </form>
        @can('store.purchase.create')
        <button class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="ti ti-plus me-1"></i>Add Supplier
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

<!-- Suppliers Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.name') }}</th>
                        <th>{{ __('store.contact_person') }}</th>
                        <th>{{ __('common.phone') }}</th>
                        <th>{{ __('common.email') }}</th>
                        <th>{{ __('store.pos') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td class="fw-medium">{{ $supplier->name }}</td>
                        <td>{{ $supplier->contact_person ?? '-' }}</td>
                        <td>{{ $supplier->phone ?? '-' }}</td>
                        <td>{{ $supplier->email ?? '-' }}</td>
                        <td><span class="badge bg-soft-info">{{ $supplier->purchase_orders_count }}</span></td>
                        <td>
                            <span class="badge bg-{{ $supplier->is_active ? 'success' : 'danger' }}">
                                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="Actions" title="Actions" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.store.suppliers.ledger', $supplier) }}">
                                            <i class="ti ti-notebook me-1"></i>Ledger / Statement
                                        </a>
                                    </li>
                                    @can('store.purchase.create')
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editSupplierModal-{{ $supplier->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.store.suppliers.toggle', $supplier) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-{{ $supplier->is_active ? 'ban' : 'check' }} me-1"></i>
                                                {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editSupplierModal-{{ $supplier->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.store.suppliers.update', $supplier) }}">
                                    @csrf @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Supplier</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $supplier->name }}" required>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Contact Person</label>
                                                <input type="text" name="contact_person" class="form-control" value="{{ $supplier->contact_person }}">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Phone</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $supplier->phone }}">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="{{ $supplier->email }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" class="form-control" rows="2">{{ $supplier->address }}</textarea>
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
                            <i class="ti ti-truck-off fs-2 d-block mb-2"></i>
                            No suppliers found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($suppliers->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $suppliers->withQueryString()->links() }}
</div>
@endif

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.store.suppliers.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. MedSupply Ghana Ltd" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" placeholder="Contact name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="Phone number">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Supplier address..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
