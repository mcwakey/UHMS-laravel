@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Admin Dashboard</h4>
    </div>
</div>

<!-- Stats Row -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-01.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-primary rounded-circle"><i class="ti ti-users-group fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Total Users</p>
                        <h3 class="fw-bold mb-0">{{ $totalUsers ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-02.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-danger rounded-circle"><i class="ti ti-building-bank fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Departments</p>
                        <h3 class="fw-bold mb-0">{{ $totalDepartments ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-03.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-warning rounded-circle"><i class="ti ti-shield-lock fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Roles</p>
                        <h3 class="fw-bold mb-0">{{ $totalRoles ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-04.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-success rounded-circle"><i class="ti ti-user-heart fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Total Patients</p>
                        <h3 class="fw-bold mb-0">{{ $totalPatients ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Tables -->
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Users</h5>
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentUsers ?? [] as $user)
                            <tr>
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
                                <td><span class="badge bg-soft-primary">{{ $user->roles->first()?->name ?? 'N/A' }}</span></td>
                                <td>{{ $user->department?->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->status->value === 'active' ? 'success' : 'danger' }}">
                                        {{ ucfirst($user->status->value) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No users found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Patients</h5>
                @can('patients.view')
                <a href="{{ route('admin.patients.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Patient ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPatients ?? [] as $patient)
                            <tr>
                                <td><a href="{{ route('admin.patients.show', $patient) }}" class="text-primary">{{ $patient->patient_number }}</a></td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->phone }}</td>
                                <td>{{ $patient->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No patients registered yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
