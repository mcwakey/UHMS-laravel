@extends('layouts.app')
@section('title', __('roles.title'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('roles.title') }}</h4>
    </div>
    <div>
        <button class="btn btn-primary btn-md fs-13" data-bs-toggle="modal" data-bs-target="#addRoleModal">
            <i class="ti ti-plus me-1"></i>{{ __('roles.add_role') }}
        </button>
    </div>
</div>

<!-- Roles Grid -->
<div class="row">
    @foreach($roles as $role)
    <div class="col-xl-4 col-md-6 mb-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title mb-0">{{ $role->name }}</h5>
                    <div class="dropdown">
                        @if(!in_array($role->name, ['Super Admin', 'Admin']))
                        <button aria-label="{{ __('common.actions') }}" title="{{ __('common.actions') }}" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                            <i class="ti ti-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editRoleModal-{{ $role->id }}">
                                    <i class="ti ti-edit me-1"></i>{{ __('roles.edit_name') }}
                                </button>
                            </li>
                            <li>
                                <x-confirm-form :action="route('admin.roles.destroy', $role)" method="DELETE"
                                    button-label="{{ __('roles.delete_role') }}" button-class="dropdown-item text-danger" icon="ti-trash"
                                    confirm-title="{{ __('roles.delete_role') }}?" confirm-text="{{ __('roles.cannot_delete') }}" confirm-button="{{ __('common.yes') }}, {{ strtolower(__('common.delete')) }}" />
                            </li>
                        </ul>
                        @endif
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="badge bg-soft-primary"><i class="ti ti-shield-lock me-1"></i>{{ $role->permissions_count }} {{ __('roles.permissions') }}</span>
                    <span class="badge bg-soft-info"><i class="ti ti-users me-1"></i>{{ $role->users_count }} {{ __('roles.users_count') }}</span>
                </div>
                <a href="{{ route('admin.roles.permissions', $role) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="ti ti-settings me-1"></i>{{ __('roles.manage_permissions') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal-{{ $role->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('roles.edit_role') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">{{ __('roles.role_name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('roles.update_role') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('roles.add_new_role') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('roles.role_name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Lab Technician" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('roles.create_role') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
