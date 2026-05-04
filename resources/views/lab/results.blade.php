@extends('layouts.app')
@section('title', 'Lab Results')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-report-medical me-2"></i>Lab Results</h4>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search patient, request #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="verified" class="form-select">
                    <option value="">All Results</option>
                    <option value="yes" {{ request('verified') === 'yes' ? 'selected' : '' }}>Verified</option>
                    <option value="no" {{ request('verified') === 'no' ? 'selected' : '' }}>Unverified</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.lab.results.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Results Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Patient</th>
                        <th>Test</th>
                        <th>Result</th>
                        <th>Normal Range</th>
                        <th>Status</th>
                        <th>Performed By</th>
                        <th>Performed At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $result)
                    <tr class="{{ $result->is_abnormal ? 'table-danger' : '' }}">
                        <td>
                            <a href="{{ route('admin.lab.requests.show', $result->lab_request_id) }}" class="fw-medium text-primary">
                                {{ $result->labRequest->request_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $result->labRequest->patient->full_name ?? '-' }}</div>
                            <small class="text-muted">{{ $result->labRequest->patient->patient_number ?? '' }}</small>
                        </td>
                        <td>
                            <span class="fw-medium">{{ $result->requestItem->labTest->name ?? '-' }}</span>
                            <br><small class="text-muted">{{ $result->requestItem->labTest->code ?? '' }}</small>
                        </td>
                        <td>
                            <span class="{{ $result->is_abnormal ? 'text-danger fw-bold' : '' }}">
                                {{ $result->result_value }}
                            </span>
                            @if($result->is_abnormal)
                                <i class="ti ti-alert-triangle text-danger ms-1"></i>
                            @endif
                            @if($result->remarks)
                                <br><small class="text-muted">{{ $result->remarks }}</small>
                            @endif
                        </td>
                        <td>
                            @if($result->requestItem->labTest?->criteria?->isNotEmpty())
                                @foreach($result->requestItem->labTest->criteria as $criterion)
                                    <div><small><strong>{{ $criterion->name }}:</strong> {{ $criterion->normal_range ?? '-' }} {{ $criterion->unit ?? '' }}</small></div>
                                @endforeach
                            @else
                                <small>{{ $result->requestItem->labTest->normal_range ?? '-' }} {{ $result->requestItem->labTest->unit ?? '' }}</small>
                            @endif
                        </td>
                        <td>
                            @if($result->is_verified)
                                <span class="badge bg-success"><i class="ti ti-check me-1"></i>Verified</span>
                                <br><small class="text-muted">{{ $result->verifiedBy->name ?? '' }}</small>
                            @else
                                <span class="badge bg-warning">Unverified</span>
                            @endif
                        </td>
                        <td>{{ $result->performedBy->name ?? '-' }}</td>
                        <td>
                            <small>{{ $result->performed_at?->format('d M Y') }}</small><br>
                            <small class="text-muted">{{ $result->performed_at?->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            @if(!$result->is_verified)
                            @can('lab.results.create')
                            <form method="POST" action="{{ route('admin.lab.results.verify', $result) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Verify Result">
                                    <i class="ti ti-check me-1"></i>Verify
                                </button>
                            </form>
                            @endcan
                            @endif
                            <a href="{{ route('admin.lab.requests.show', $result->lab_request_id) }}" class="btn btn-sm btn-outline-primary" title="View Request">
                                <i class="ti ti-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ti ti-report-medical fs-1 d-block mb-2"></i>
                            No lab results found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($results->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $results->links() }}
</div>
@endif
@endsection
