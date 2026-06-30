@extends('layouts.app')
@section('title', __('admin.departments'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Departments
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $departments->total() }}</span>
        </h4>
    </div>
    <div>
        @can('departments.create')
        <button class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="ti ti-plus me-1"></i>Add Department
        </button>
        @endcan
    </div>
</div>

<!-- Departments Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('common.code') }}</th>
                        <th>{{ __('common.name') }}</th>
                        <th>{{ __('common.type') }}</th>
                        <th>{{ __('admin.result_type') }}</th>
                        <th class="text-center">{{ __('admin.stock_managed') }}</th>
                        <th>{{ __('admin.users') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
                    <tr>
                        <td><span class="fw-medium badge bg-light text-dark">{{ $dept->code }}</span></td>
                        <td class="fw-medium">{{ $dept->name }}</td>
                        <td>
                            @if($dept->type)
                                <span class="badge bg-{{ $dept->type->color() }}">
                                    {{ $dept->type->translatedLabel() }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($dept->result_type && $dept->result_type->value !== 'none')
                                <span class="badge bg-{{ $dept->result_type->color() }}">
                                    <i class="ti {{ $dept->result_type->icon() }} me-1"></i>{{ $dept->result_type->translatedLabel() }}
                                </span>
                            @else
                                <span class="text-muted small">{{ __('departments.result_types.none') }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($dept->is_stock_managed)
                                <span class="badge bg-success"><i class="ti ti-check"></i> Yes</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td><span class="badge bg-soft-primary">{{ $dept->users_count }}</span></td>
                        <td>
                            <span class="badge bg-{{ $dept->status === 'active' ? 'success' : 'danger' }}">
                                {{ ucfirst($dept->status) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="Actions" title="Actions" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('departments.edit')
                                    <li>
                                        <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editDeptModal-{{ $dept->id }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </button>
                                    </li>
                                    @endcan
                                    @can('departments.delete')
                                    <li>
                                        <x-confirm-form :action="route('admin.departments.destroy', $dept)" method="DELETE"
                                            button-label="Delete" button-class="dropdown-item text-danger" icon="ti-trash"
                                            :confirm-title="__('admin.delete_department_confirm')" :confirm-text="__('common.action_cannot_be_undone')" :confirm-button="__('common.yes')" />
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="8"><x-empty-state message="No departments found" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($departments->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $departments->links() }}
</div>
@endif

@foreach($departments as $dept)
<!-- Edit Department Modal -->
<div class="modal fade" id="editDeptModal-{{ $dept->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.departments.update', $dept) }}">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $dept->name }}" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ $dept->code }}" required maxlength="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="">— Select Type —</option>
                                @foreach($departmentTypes as $type)
                                    <option value="{{ $type->value }}" {{ $dept->type?->value === $type->value ? 'selected' : '' }}>
                                        {{ $type->translatedLabel() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Result Type <span class="text-danger">*</span></label>
                        <select name="result_type" class="form-select" required>
                            @foreach($resultTypes as $rt)
                            <option value="{{ $rt->value }}" {{ $dept->result_type?->value === $rt->value ? 'selected' : '' }}>
                                {{ $rt->translatedLabel() }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Determines what type of report/result this department sends back.</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_stock_managed" value="1" id="stockEdit{{ $dept->id }}" {{ $dept->is_stock_managed ? 'checked' : '' }}>
                            <label class="form-check-label" for="stockEdit{{ $dept->id }}">
                                <strong>{{ __('admin.store_manages_stock') }}</strong>
                                <div class="text-muted small">Items from this department appear in Procurement & Stock Transfers.</div>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ $dept->description }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $dept->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $dept->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('journey.supervisor.supervisor') }}</label>
                        <select name="supervisor_user_id" class="form-select">
                            <option value="">{{ __('journey.supervisor.none') }}</option>
                            @foreach($supervisorCandidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ (int) $dept->supervisor_user_id === $candidate->id ? 'selected' : '' }}>{{ trim($candidate->first_name.' '.$candidate->last_name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('journey.supervisor.escalation_user') }}</label>
                        <select name="escalation_user_id" class="form-select">
                            <option value="">{{ __('journey.supervisor.none') }}</option>
                            @foreach($supervisorCandidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ (int) $dept->escalation_user_id === $candidate->id ? 'selected' : '' }}>{{ trim($candidate->first_name.' '.$candidate->last_name) }}</option>
                            @endforeach
                        </select>
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
@endforeach

<!-- Add Department Modal -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.departments.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Outpatient Department" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. OPD" required maxlength="10">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="">— Select Type —</option>
                                @foreach($departmentTypes as $type)
                                    <option value="{{ $type->value }}">{{ $type->translatedLabel() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-medium">Result Type <span class="text-danger">*</span></label>
                        <select name="result_type" class="form-select" required>
                            @foreach($resultTypes as $rt)
                            <option value="{{ $rt->value }}" {{ $rt->value === 'none' ? 'selected' : '' }}>{{ $rt->translatedLabel() }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Determines what type of report/result this department sends back.</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_stock_managed" value="1" id="stockAdd">
                            <label class="form-check-label" for="stockAdd">
                                <strong>{{ __('admin.store_manages_stock') }}</strong>
                                <div class="text-muted small">Items from this department appear in Procurement & Stock Transfers.</div>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Brief description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
