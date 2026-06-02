@extends('layouts.app')
@section('title', 'Wards')

@section('content')
<x-page-header title="Wards" icon="ti-bed">
    <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total: {{ $wards->total() }}</span>
    <x-slot:actions>
        @can('ward.manage')
        <button type="button" class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addWardModal"><i class="ti ti-plus me-1"></i>New Ward</button>
        @endcan
        <a href="{{ route('admin.wards.beds') }}" class="btn btn-outline-info btn-md fs-13"><i class="ti ti-bed me-1"></i>Manage Beds</a>
        <a href="{{ route('admin.wards.bed-map') }}" class="btn btn-outline-success btn-md fs-13"><i class="ti ti-map me-1"></i>Bed Map</a>
    </x-slot:actions>
</x-page-header>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.wards.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search ward name or code..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select">
                    <option value="">All Status</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md ms-1"><i class="ti ti-x me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Wards Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Code</th>
                        <th>Ward Name</th>
                        <th>Department</th>
                        <th>Floor</th>
                        <th>Capacity</th>
                        <th>Beds (Total)</th>
                        <th>Available</th>
                        <th>Occupied</th>
                        <th>Occupancy</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wards as $ward)
                    <tr>
                        <td><span class="fw-medium">{{ $ward->code }}</span></td>
                        <td>{{ $ward->name }}</td>
                        <td>{{ $ward->department?->name ?? '—' }}</td>
                        <td>{{ $ward->floor ?? '—' }}</td>
                        <td>{{ $ward->capacity }}</td>
                        <td>{{ $ward->beds_count }}</td>
                        <td><span class="badge badge-soft-success">{{ $ward->available_beds_count }}</span></td>
                        <td><span class="badge badge-soft-danger">{{ $ward->occupied_beds_count }}</span></td>
                        <td>
                            @if($ward->beds_count > 0)
                                <div class="progress" style="height: 6px; width: 60px;">
                                    <div class="progress-bar bg-{{ $ward->occupied_beds_count / $ward->beds_count > 0.8 ? 'danger' : ($ward->occupied_beds_count / $ward->beds_count > 0.5 ? 'warning' : 'success') }}" style="width: {{ $ward->beds_count > 0 ? round(($ward->occupied_beds_count / $ward->beds_count) * 100) : 0 }}%"></div>
                                </div>
                                <small class="text-muted">{{ $ward->beds_count > 0 ? round(($ward->occupied_beds_count / $ward->beds_count) * 100) : 0 }}%</small>
                            @else
                                <small class="text-muted">No beds</small>
                            @endif
                        </td>
                        <td>
                            @if($ward->is_active)
                                <span class="badge badge-soft-success">Active</span>
                            @else
                                <span class="badge badge-soft-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('ward.manage')
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editWardModal{{ $ward->id }}">
                                            <i class="ti ti-pencil me-1"></i>Edit
                                        </a>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('admin.wards.toggle', $ward) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-{{ $ward->is_active ? 'eye-off' : 'eye' }} me-1"></i>{{ $ward->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="ti ti-building-hospital fs-1 d-block mb-2"></i>
                            No wards found. Create a ward to get started.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($wards->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $wards->withQueryString()->links() }}
</div>
@endif

<!-- Add Ward Modal -->
@can('ward.manage')
<div class="modal fade" id="addWardModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.wards.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Ward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g., MW, FW, ICU" value="{{ old('code') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" required min="1" value="{{ old('capacity', 10) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Floor</label>
                            <input type="text" name="floor" class="form-control" placeholder="e.g., Ground, 1st" value="{{ old('floor') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Create Ward</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

<!-- Edit Ward Modals -->
@can('ward.manage')
@foreach($wards as $ward)
<div class="modal fade" id="editWardModal{{ $ward->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.wards.update', $ward) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Ward — {{ $ward->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ward Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ $ward->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required value="{{ $ward->code }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $ward->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Capacity <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" required min="1" value="{{ $ward->capacity }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Floor</label>
                            <input type="text" name="floor" class="form-control" value="{{ $ward->floor }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $ward->description }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Update Ward</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endcan
@endsection
