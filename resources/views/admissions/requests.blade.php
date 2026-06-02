@extends('layouts.app')
@section('title', 'Admission Requests')

@section('content')
<x-page-header title="Admission Requests" icon="ti-bed" description="Visits where the doctor has ordered admission and the patient is awaiting bed assignment.">
    @if($totalPending > 0)
        <span class="badge bg-warning text-dark fw-medium border py-1 px-2 border-warning fs-13 ms-1">{{ $totalPending }} pending</span>
    @else
        <span class="badge badge-soft-secondary fw-medium border py-1 px-2 fs-13 ms-1">0 pending</span>
    @endif
    <x-slot:actions>
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-outline-secondary btn-md fs-13">
            <i class="ti ti-bed me-1"></i>All Admissions
        </a>
        @can('ward.admit')
        <a href="{{ route('admin.admissions.create') }}" class="btn btn-primary btn-md fs-13">
            <i class="ti ti-plus me-1"></i>New Admission
        </a>
        @endcan
    </x-slot:actions>
</x-page-header>

<!-- Search Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.admissions.requests') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by patient name, visit number..."
                       value="{{ $searchQuery }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md">
                    <i class="ti ti-search me-1"></i>Search
                </button>
                @if($searchQuery)
                    <a href="{{ route('admin.admissions.requests') }}" class="btn btn-outline-secondary btn-md ms-1">
                        <i class="ti ti-x me-1"></i>Clear
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-body p-0">
        @if($visits->isEmpty())
            <div class="text-center py-5">
                <i class="ti ti-circle-check fs-1 text-success d-block mb-3"></i>
                <h5 class="text-muted">No pending admission requests</h5>
                <p class="text-muted small">All patients referred for admission have been assigned a bed.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-nowrap align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Patient</th>
                            <th>Visit No.</th>
                            <th>Type</th>
                            <th>Department</th>
                            <th>Doctor</th>
                            <th>Waiting Since</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visits as $visit)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm bg-warning bg-opacity-15 rounded-circle flex-shrink-0">
                                            <span class="fs-12 fw-bold text-warning">
                                                {{ strtoupper(substr($visit->patient?->full_name ?? '?', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $visit->patient?->full_name ?? '—' }}</div>
                                            <small class="text-muted">{{ $visit->patient?->patient_number ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted fs-13">{{ $visit->visit_number ?? '—' }}</span>
                                </td>
                                <td>
                                    @if($visit->visit_type)
                                        <span class="badge badge-soft-info">{{ $visit->visit_type->label() }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-muted fs-13">{{ $visit->department?->name ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="text-muted fs-13">
                                        {{ $visit->activeConsultationRoute?->doctor?->full_name ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $since = $visit->updated_at;
                                        $diff  = $since?->diffForHumans() ?? '—';
                                        $isUrgent = $since && $since->diffInHours(now()) >= 2;
                                    @endphp
                                    <span class="fs-13 {{ $isUrgent ? 'text-danger fw-semibold' : 'text-muted' }}"
                                          title="{{ $since?->format('d M Y H:i') }}">
                                        {{ $diff }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    @can('ward.admit')
                                    <a href="{{ route('admin.admissions.create', ['visit_id' => $visit->id]) }}"
                                       class="btn btn-sm btn-warning fw-semibold">
                                        <i class="ti ti-bed me-1"></i>Admit Now
                                    </a>
                                    @else
                                    <span class="text-muted small">No permission</span>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($visits->hasPages())
                <div class="px-3 py-2 border-top">
                    {{ $visits->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
