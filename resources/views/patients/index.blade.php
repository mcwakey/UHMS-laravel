@extends('layouts.app')
@section('title', 'Patients')

@section('content')
<x-page-header title="Patients" icon="ti-users">
    <span class="badge badge-soft-primary fw-medium border py-1 px-2 border-primary fs-13 ms-1">Total: {{ $patients->total() }}</span>
    <x-slot:actions>
        @can('patients.merge.view')
        <a href="{{ route('admin.patients.merge.index') }}" class="btn btn-outline-primary btn-md fs-13"><i class="ti ti-git-merge me-1"></i>Folder Merge</a>
        @endcan
        @can('patients.create')
        <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-plus me-1"></i>New Patient</a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.patients.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search by name, phone, Ghana Card, insurance card, or emergency contact..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Insurance Provider</label>
                <select name="insurance_provider_id" class="form-select">
                    <option value="">All Insurances</option>
                    @foreach($insuranceProviders as $provider)
                        <option value="{{ $provider->id }}" {{ request('insurance_provider_id') == $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="deceased" {{ request('status') == 'deceased' ? 'selected' : '' }}>Deceased</option>
                </select>
            </div>
            {{-- <div class="d-flex align-items-center col-md-3"> --}}
                <div class="col-md-2">
                    <label class="form-label small">Last Visit From</label>
                    <input type="date" name="visit_from" class="form-control" value="{{ request('visit_from') }}">
                </div>
                {{-- <div class="col-md-1">
                </div> --}}
                <div class="col-md-2">
                    <label class="form-label small">Last Visit To</label>
                    <input type="date" name="visit_to" class="form-control" value="{{ request('visit_to') }}">
                </div>
            {{-- </div> --}}
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary btn-md"><i class="ti ti-filter me-1"></i>Filter</button>
                <a aria-label="Close" title="Close" href="{{ route('admin.patients.index') }}" class="btn btn-outline-secondary btn-md ms-1"><i class="ti ti-x me-1"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Patients Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Patient ID</th>
                        <th>Patient Name</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Date of Birth</th>
                        <th>City</th>
                        <th>Insurance</th>
                        <th>Status</th>
                        <th>Last Visit</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td>
                            <a href="{{ route('admin.patients.show', $patient) }}" class="text-primary fw-medium">{{ $patient->patient_number }}</a>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-md rounded-circle bg-light text-dark me-2 flex-shrink-0">
                                    @if($patient->avatar)
                                        <img src="{{ Storage::url($patient->avatar) }}" alt="{{ $patient->full_name }}" class="rounded-circle">
                                    @else
                                        {{ strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)) }}
                                    @endif
                                </span>
                                <div>
                                    <a href="{{ route('admin.patients.show', $patient) }}" class="fw-medium text-dark">{{ $patient->full_name }}</a>
                                    @if($patient->email)
                                    <br><small class="text-muted">{{ $patient->email }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>{{ $patient->phone }}</td>
                        <td>{{ $patient->gender?->label() }}</td>
                        {{-- <td>{{ $patient->age }} yrs</td> --}}
                        <td>{{ $patient->date_of_birth ? \Carbon\Carbon::parse($patient->date_of_birth)->format('d M Y') : '—' }}</td>
                        <td>{{ $patient->city ?? '—' }}</td>
                        <td>
                            @if($patient->primaryInsurance?->insuranceProvider)
                                <span class="badge bg-{{ $patient->primaryInsurance->insuranceProvider->type?->color() ?? 'secondary' }}">
                                    {{ $patient->primaryInsurance->insuranceProvider->name }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            @if($patient->isMerged())
                                <span class="badge badge-soft-dark">Merged</span>
                            @elseif($patient->status === 'active')
                                <span class="badge badge-soft-success">Active</span>
                            @elseif($patient->status === 'inactive')
                                <span class="badge badge-soft-warning">Inactive</span>
                            @else
                                <span class="badge badge-soft-dark">Deceased</span>
                            @endif
                        </td>
                        <td>{{ $patient->last_visit_date ? \Carbon\Carbon::parse($patient->last_visit_date)->format('d M Y') : '—' }}</td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                @can('visits.create')
                                @if($patient->isMerged() || $patient->status === 'deceased')
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="Merged folder cannot receive new visits" disabled>
                                    <i class="ti ti-lock"></i>
                                </button>
                                @else
                                <a href="{{ route('admin.visits.create', ['patient_id' => $patient->id]) }}" class="btn btn-sm btn-outline-success" title="New Visit">
                                    <i class="ti ti-stethoscope"></i>
                                </a>
                                @endif
                                @endcan
                                <div class="dropdown">
                                    <a aria-label="Actions" title="Actions" href="javascript:void(0);" class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots-vertical"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('admin.patients.show', $patient) }}"><i class="ti ti-eye me-2"></i>View Profile</a></li>
                                        @can('patients.edit')
                                        @if(!$patient->isMerged() && $patient->status !== 'deceased')
                                        <li><a class="dropdown-item" href="{{ route('admin.patients.edit', $patient) }}"><i class="ti ti-edit me-2"></i>Edit</a></li>
                                        <li>
                                            <form method="POST" action="{{ route('admin.patients.toggle-status', $patient) }}" class="d-inline">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="ti ti-toggle-{{ $patient->status === 'active' ? 'right' : 'left' }} me-2"></i>
                                                    {{ $patient->status === 'active' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </li>
                                        @endif
                                        @endcan
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="ti ti-user-off fs-1 d-block mb-2"></i>
                            No patients found.
                            @can('patients.create')
                            <br><a href="{{ route('admin.patients.create') }}">Register a new patient</a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
@if($patients->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $patients->withQueryString()->links() }}
</div>
@endif
@endsection
