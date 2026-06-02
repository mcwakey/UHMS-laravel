@extends('layouts.app')
@section('title', 'Users')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Users
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $users->total() }}</span>
        </h4>
    </div>
    <div class="text-end">
        @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>Add User
        </a>
        @endcan
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search name or email..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="department" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary btn-md me-1"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
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
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td><span class="fw-medium">{{ $user->employee_id ?? 'N/A' }}</span></td>
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
                        <td>{{ $user->phone ?? 'N/A' }}</td>
                        <td><span class="badge bg-soft-primary">{{ $user->roles->first()?->name ?? 'N/A' }}</span></td>
                        <td>{{ $user->department?->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $user->status->value === 'active' ? 'success' : ($user->status->value === 'suspended' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->status->value) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-white border dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @can('users.edit')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.users.edit', $user) }}">
                                            <i class="ti ti-edit me-1"></i>Edit
                                        </a>
                                    </li>
                                    @endcan
                                    @can('users.edit')
                                    <li>
                                        <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                <i class="ti ti-toggle-{{ $user->status->value === 'active' ? 'right' : 'left' }} me-1"></i>
                                                {{ $user->status->value === 'active' ? 'Deactivate' : 'Activate' }}
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
                        <td colspan="8"><x-empty-state message="No users found" /></td>
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
