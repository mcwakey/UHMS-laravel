@extends('layouts.app')
@section('title', 'Admissions')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 pb-3 mb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Admissions <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total: {{ $admissions->total() }}</span></h4>
    </div>
    <div class="text-end d-flex gap-2">
        @can('ward.admit')
        <a href="{{ route('admin.admissions.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>New Admission</a>
        @endcan
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-primary bg-opacity-10 rounded me-3">
                        <i class="ti ti-bed fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['total_admitted'] }}</h4>
                        <small class="text-muted">Currently Admitted</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-success bg-opacity-10 rounded me-3">
                        <i class="ti ti-login fs-4 text-success"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['admitted_today'] }}</h4>
                        <small class="text-muted">Admitted Today</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body py-3">
                <div class="d-flex align-items-center">
                    <div class="avatar avatar-md bg-warning bg-opacity-10 rounded me-3">
                        <i class="ti ti-logout fs-4 text-warning"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">{{ $stats['discharged_today'] }}</h4>
                        <small class="text-muted">Discharged Today</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.admissions.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search patient name, admission #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach(\App\Enums\AdmissionStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ request('status') == $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="ward_id" class="form-select">
                    <option value="">All Wards</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" {{ request('ward_id') == $ward->id ? 'selected' : '' }}>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md ms-1"><i class="ti ti-x me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Admissions Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Admission #</th>
                        <th>Patient</th>
                        <th>Ward / Bed</th>
                        <th>Admitted On</th>
                        <th>Days</th>
                        <th>Admitted By</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admissions as $admission)
                    <tr>
                        <td>
                            <a href="{{ route('admin.admissions.show', $admission) }}" class="fw-medium text-decoration-none">
                                {{ $admission->admission_number }}
                            </a>
                        </td>
                        <td>
                            <div>
                                <a href="{{ route('admin.patients.show', $admission->patient) }}" class="text-decoration-none">
                                    {{ $admission->patient->full_name }}
                                </a>
                            </div>
                            <small class="text-muted">{{ $admission->patient->patient_number }}</small>
                        </td>
                        <td>
                            <div>{{ $admission->bed->ward->name }}</div>
                            <small class="text-muted">Bed: {{ $admission->bed->bed_number }}</small>
                        </td>
                        <td>{{ $admission->admission_date->format('d M Y, H:i') }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $admission->length_of_stay }} day(s)</span></td>
                        <td>{{ $admission->admittedBy->name ?? '—' }}</td>
                        <td><span class="badge badge-soft-{{ $admission->status->color() }}">{{ $admission->status->label() }}</span></td>
                        <td class="text-end">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admissions.show', $admission) }}">
                                            <i class="ti ti-eye me-1"></i>View Details
                                        </a>
                                    </li>
                                    @if($admission->status->value === 'admitted')
                                    @can('ward.discharge')
                                    <li>
                                        <a class="dropdown-item" href="{{ route('admin.admissions.discharge', $admission) }}">
                                            <i class="ti ti-logout me-1"></i>Discharge
                                        </a>
                                    </li>
                                    @endcan
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="ti ti-bed-off fs-1 d-block mb-2"></i>
                            No admissions found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($admissions->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $admissions->withQueryString()->links() }}
</div>
@endif
@endsection
