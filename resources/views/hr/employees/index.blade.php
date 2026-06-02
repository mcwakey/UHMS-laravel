@extends('layouts.app')
@section('title', 'Employees')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Employees
            <span class="badge badge-soft-primary border border-primary fs-13 fw-medium ms-2">Total: {{ $employees->total() }}</span>
        </h4>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" action="{{ route('admin.hr.employees.index') }}" class="d-flex gap-2">
            <select name="department_id" class="form-select" style="width:150px;" onchange="this.form.submit()">
                <option value="">All Departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select" style="width:130px;" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}" style="width:160px;">
            <button type="submit" class="btn btn-outline-primary"><i class="ti ti-search"></i></button>
        </form>
        @can('hr.employees.create')
        <a href="{{ route('admin.hr.employees.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>Add Employee
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Emp #</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Phone</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $emp)
                    <tr>
                        <td><span class="badge bg-light text-dark">{{ $emp->employee_number }}</span></td>
                        <td class="fw-medium">{{ $emp->full_name }}</td>
                        <td>{{ $emp->department?->name ?? '-' }}</td>
                        <td>{{ $emp->position }}</td>
                        <td>{{ $emp->phone }}</td>
                        <td>{{ $emp->hire_date->format('d M Y') }}</td>
                        <td><span class="badge bg-{{ $emp->status->color() }}">{{ $emp->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.hr.employees.show', $emp) }}" class="btn btn-sm btn-outline-info" title="View"><i class="ti ti-eye"></i></a>
                            @can('hr.employees.edit')
                            <a href="{{ route('admin.hr.employees.edit', $emp) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="ti ti-edit"></i></a>
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8"><x-empty-state message="No employees found" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">{{ $employees->withQueryString()->links() }}</div>
@endsection
