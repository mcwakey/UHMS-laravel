@extends('layouts.app')
@section('title', 'Patient Folder Merge')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">Patient Folder Merge</h4>
        <p class="text-muted mb-0">Search possible duplicates, compare folders, and consolidate safely.</p>
    </div>
    <a href="{{ route('admin.patients.merge.logs') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-history me-1"></i>Audit Logs</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@php
    $mainPatientNumber = request('main_patient_number');
    $duplicatePatientNumber = request('duplicate_patient_number');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.patients.merge.index') }}" class="row g-2 align-items-end">
            <input type="hidden" name="main_patient_number" value="{{ $mainPatientNumber }}">
            <input type="hidden" name="duplicate_patient_number" value="{{ $duplicatePatientNumber }}">
            <div class="col-md-9">
                <label class="form-label">Patient Search</label>
                <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name, patient number, old temporary number, phone, Ghana Card, or insurance number">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary flex-fill" type="submit"><i class="ti ti-search me-1"></i>Search</button>
                <a aria-label="Close" title="Close" class="btn btn-outline-secondary" href="{{ route('admin.patients.merge.index') }}"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Selected Folders</h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.patients.merge.compare') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Main Patient Number</label>
                <input type="text" name="main_patient_number" class="form-control" value="{{ $mainPatientNumber }}" placeholder="PT..." required>
                <div class="small text-muted mt-1">
                    @if($selectedMainPatient)
                        {{ $selectedMainPatient->full_name }} · {{ $selectedMainPatient->phone ?: 'No phone' }}
                    @elseif($mainPatientNumber)
                        No matching folder found yet.
                    @else
                        Select from search results or type the patient number.
                    @endif
                </div>
            </div>
            <div class="col-md-5">
                <label class="form-label">Duplicate Patient Number</label>
                <input type="text" name="duplicate_patient_number" class="form-control" value="{{ $duplicatePatientNumber }}" placeholder="PT..." required>
                <div class="small text-muted mt-1">
                    @if($selectedDuplicatePatient)
                        {{ $selectedDuplicatePatient->full_name }} · {{ $selectedDuplicatePatient->phone ?: 'No phone' }}
                    @elseif($duplicatePatientNumber)
                        No matching folder found yet.
                    @else
                        Select from search results or type the patient number.
                    @endif
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit"><i class="ti ti-git-compare me-1"></i>Preview</button>
            </div>
        </form>
    </div>
</div>

@if($patients->isNotEmpty())
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Search Results</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Patient Number</th>
                    <th>Patient</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Aliases</th>
                    <th class="text-end">Select</th>
                </tr>
            </thead>
            <tbody>
                @foreach($patients as $patient)
                    @php
                        $baseSelectionQuery = array_filter([
                            'search' => request('search'),
                            'main_patient_number' => $mainPatientNumber,
                            'duplicate_patient_number' => $duplicatePatientNumber,
                        ], fn ($value) => filled($value));

                        $asMainQuery = array_merge($baseSelectionQuery, ['main_patient_number' => $patient->patient_number]);
                        if (($asMainQuery['duplicate_patient_number'] ?? null) === $patient->patient_number) {
                            unset($asMainQuery['duplicate_patient_number']);
                        }

                        $asDuplicateQuery = array_merge($baseSelectionQuery, ['duplicate_patient_number' => $patient->patient_number]);
                        if (($asDuplicateQuery['main_patient_number'] ?? null) === $patient->patient_number) {
                            unset($asDuplicateQuery['main_patient_number']);
                        }
                    @endphp
                    <tr>
                        <td><span class="fw-semibold">{{ $patient->patient_number }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $patient->full_name }}</div>
                            @if($patient->is_temporary)
                                <span class="badge bg-warning-subtle text-warning mt-1">Temporary</span>
                            @endif
                        </td>
                        <td>{{ $patient->phone }}</td>
                        <td>
                            @if($patient->isMerged())
                                <span class="badge bg-dark">Merged</span>
                            @elseif($patient->status === 'active')
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($patient->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @forelse($patient->aliases->take(3) as $alias)
                                <span class="badge bg-light text-dark border">{{ $alias->alias_value }}</span>
                            @empty
                                <span class="text-muted">-</span>
                            @endforelse
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1 flex-wrap">
                                <a href="{{ route('admin.patients.merge.index', $asMainQuery) }}" class="btn btn-sm btn-outline-success" title="Select as main folder"><i class="ti ti-check me-1"></i>Main</a>
                                <a href="{{ route('admin.patients.merge.index', $asDuplicateQuery) }}" class="btn btn-sm btn-outline-warning" title="Select as duplicate folder"><i class="ti ti-copy me-1"></i>Duplicate</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Recent Merge Requests</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="bg-light">
                <tr>
                    <th>Request</th>
                    <th>Main Folder</th>
                    <th>Duplicate Folder</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentRequests as $mergeRequest)
                    <tr>
                        <td>{{ $mergeRequest->request_number }}</td>
                        <td>{{ $mergeRequest->mainPatient?->patient_number }}<br><small class="text-muted">{{ $mergeRequest->mainPatient?->full_name }}</small></td>
                        <td>{{ $mergeRequest->duplicatePatient?->patient_number }}<br><small class="text-muted">{{ $mergeRequest->duplicatePatient?->full_name }}</small></td>
                        <td><span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $mergeRequest->status) }}</span></td>
                        <td>{{ $mergeRequest->created_at?->format('d M Y H:i') }}</td>
                        <td class="text-end"><a href="{{ route('admin.patients.merge.requests.show', $mergeRequest) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6"><x-empty-state message="No merge requests yet." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection