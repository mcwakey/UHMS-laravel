@extends('layouts.app')
@section('title', __('wards.wards'))

@section('content')
<x-page-header :title="__('wards.wards')" icon="ti-bed">
    <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">{{ __('wards.total') }}: {{ $wards->total() }}</span>
    <x-slot:actions>
        @can('ward.manage')
        <button type="button" class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addWardModal"><i class="ti ti-plus me-1"></i>{{ __('wards.new_ward') }}</button>
        @endcan
        <a href="{{ $workspaceRoutes->route('admin.wards.beds') }}" class="btn btn-outline-info btn-md fs-13"><i class="ti ti-bed me-1"></i>{{ __('wards.manage_beds') }}</a>
        <a href="{{ $workspaceRoutes->route('admin.wards.bed-map') }}" class="btn btn-outline-success btn-md fs-13"><i class="ti ti-map me-1"></i>{{ __('wards.bed_map') }}</a>
    </x-slot:actions>
</x-page-header>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ $workspaceRoutes->route('admin.wards.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="{{ __('wards.search_ward_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="is_active" class="form-select">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                    <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ $workspaceRoutes->route('admin.wards.index') }}" class="btn btn-outline-secondary btn-md ms-1"><i class="ti ti-x me-1"></i>{{ __('common.clear') }}</a>
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
                        <th>{{ __('common.code') }}</th>
                        <th>{{ __('wards.ward_name') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('wards.floor') }}</th>
                        <th>{{ __('wards.capacity') }}</th>
                        <th>{{ __('wards.beds_total') }}</th>
                        <th>{{ __('wards.available') }}</th>
                        <th>{{ __('wards.occupied') }}</th>
                        <th>{{ __('wards.occupancy') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wards as $ward)
                    <tr>
                        <td><span class="fw-medium">{{ $ward->code }}</span></td>
                        <td><a href="{{ $workspaceRoutes->route('admin.wards.show', $ward) }}" class="fw-semibold text-decoration-none" title="{{ __('wards.view_ward') }}">{{ $ward->name }}</a></td>
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
                                <small class="text-muted">{{ __('wards.no_beds') }}</small>
                            @endif
                        </td>
                        <td>
                            @if($ward->is_active)
                                <span class="badge badge-soft-success">{{ __('common.active') }}</span>
                            @else
                                <span class="badge badge-soft-danger">{{ __('common.inactive') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @can('ward.manage')
                            <div class="dropdown">
                                <button aria-label="{{ __('common.actions') }}" title="{{ __('common.actions') }}" class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editWardModal{{ $ward->id }}">
                                            <i class="ti ti-pencil me-1"></i>{{ __('common.edit') }}
                                        </a>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ $workspaceRoutes->route('admin.wards.toggle', $ward) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-{{ $ward->is_active ? 'eye-off' : 'eye' }} me-1"></i>{{ $ward->is_active ? __('common.deactivate') : __('common.activate') }}
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
                            {{ __('wards.no_wards_found') }}
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
        <form method="POST" action="{{ $workspaceRoutes->route('admin.wards.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('wards.add_new_ward') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('wards.ward_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required placeholder="{{ __('wards.code_placeholder') }}" value="{{ old('code') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.department') }}</label>
                        <select name="department_id" class="form-select">
                            <option value="">{{ __('wards.none_option') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.capacity') }} <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" required min="1" value="{{ old('capacity', 10) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.floor') }}</label>
                            <input type="text" name="floor" class="form-control" placeholder="{{ __('wards.floor_placeholder') }}" value="{{ old('floor') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.description') }}</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('wards.create_ward') }}</button>
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
        <form method="POST" action="{{ $workspaceRoutes->route('admin.wards.update', $ward) }}">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('wards.edit_ward_title', ['name' => $ward->name]) }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('wards.ward_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ $ward->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.code') }} <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required value="{{ $ward->code }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.department') }}</label>
                        <select name="department_id" class="form-select">
                            <option value="">{{ __('wards.none_option') }}</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $ward->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.capacity') }} <span class="text-danger">*</span></label>
                            <input type="number" name="capacity" class="form-control" required min="1" value="{{ $ward->capacity }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('wards.floor') }}</label>
                            <input type="text" name="floor" class="form-control" value="{{ $ward->floor }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.description') }}</label>
                        <textarea name="description" class="form-control" rows="2">{{ $ward->description }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>{{ __('wards.update_ward') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endforeach
@endcan
@endsection
