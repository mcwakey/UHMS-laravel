@extends('layouts.app')
@section('title', __('users.title'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">{{ __('users.title') }}
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">{{ __('common.total') }}: {{ $users->total() }}</span>
        </h4>
    </div>
    <div class="text-end">
        @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>{{ __('users.add_user') }}
        </a>
        @endcan
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <input type="text" name="search" class="form-control" placeholder="{{ __('users.search_placeholder') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2 col-sm-6">
                <select name="role" class="form-select">
                    <option value="">{{ __('users.all_roles') }}</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <select name="department" class="form-select">
                    <option value="">{{ __('users.all_departments') }}</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <select name="status" class="form-select">
                    <option value="">{{ __('users.all_status') }}</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('users.active') }}</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('users.inactive') }}</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>{{ __('users.suspended') }}</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-12">
                <button type="submit" class="btn btn-outline-primary btn-md me-1"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-md">{{ __('common.clear') }}</a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('users.employee_id') }}</th>
                        <th>{{ __('users.name') }}</th>
                        <th>{{ __('users.email') }}</th>
                        <th>{{ __('users.phone') }}</th>
                        <th>{{ __('users.role') }}</th>
                        <th>{{ __('users.department') }}</th>
                        <th>{{ __('users.status') }}</th>
                        <th class="text-end">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td><span class="fw-medium">{{ $user->employee_id ?? __('users.na') }}</span></td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($user->avatar)
                                    <img src="{{ asset('storage/' . $user->avatar) }}" class="avatar avatar-sm rounded-circle me-2" alt="">
                                @else
                                    <span class="avatar avatar-sm rounded-circle bg-primary text-white me-2 d-flex align-items-center justify-content-center">
                                        {{ strtoupper(substr($user->first_name, 0, 1)) }}
                                    </span>
                                @endif
                                {{ $user->full_name }}
                            </div>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?? __('users.na') }}</td>
                        <td><span class="badge bg-soft-primary">{{ $user->roles->first()?->name ?? __('users.na') }}</span></td>
                        <td>{{ $user->department?->name ?? __('users.na') }}</td>
                        <td>
                            <x-status-badge :status="$user->status->value" />
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button aria-label="{{ __('common.actions') }}" title="{{ __('common.actions') }}" class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('users.edit')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                                            <i class="ti ti-edit me-1"></i>{{ __('common.edit') }}
                                        </a>
                                    </li>
                                    @endcan
                                    @can('users.disable')
                                    <li>
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-toggle-{{ $user->status->value === 'active' ? 'right' : 'left' }} me-1"></i>
                                                {{ $user->status->value === 'active' ? __('users.deactivate') : __('users.activate') }}
                                            </button>
                                        </form>
                                    </li>
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8"><x-empty-state message="{{ __('users.no_users_found') }}" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
@if($users->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $users->withQueryString()->links() }}
</div>
@endif
@endsection
