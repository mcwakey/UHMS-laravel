@extends('layouts.app')
@section('title', 'Investigation Results')

@section('content')
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-report-medical me-2"></i>Investigation Results</h4>
        <small class="text-muted">Billed or accepted investigation requests ready for result entry.</small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="ti ti-check me-1"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="ti ti-alert-circle me-1"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search patient, request #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Result State</label>
                <select name="status" class="form-select">
                    <option value="">All Billed</option>
                    <option value="pending_result" {{ request('status') === 'pending_result' ? 'selected' : '' }}>Pending Result</option>
                    <option value="entered" {{ request('status') === 'entered' ? 'selected' : '' }}>Result Entered</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Urgency</label>
                <select name="urgency" class="form-select">
                    <option value="">All Urgency</option>
                    <option value="routine" {{ request('urgency') === 'routine' ? 'selected' : '' }}>Routine</option>
                    <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="emergency" {{ request('urgency') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.lab.results.index') }}" class="btn btn-outline-secondary btn-md"><i class="ti ti-x me-1"></i>Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Patient</th>
                        <th>Department</th>
                        <th>Items</th>
                        <th>Progress</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $labRequest)
                    @php
                        $acceptedItems = $labRequest->items->filter(fn ($item) => $item->isAccepted());
                        $billedCount = $acceptedItems->filter(fn ($item) => $item->billed_at || $item->invoice_item_id)->count();
                        $resultCount = $acceptedItems->filter(fn ($item) => $item->result)->count();
                        $verifiedCount = $acceptedItems->filter(fn ($item) => $item->result?->is_verified)->count();
                        $pendingResultCount = $acceptedItems
                            ->filter(fn ($item) => in_array($item->status, ['accepted', 'processing']) && ! $item->result)
                            ->count();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.lab.results.show', $labRequest) }}" class="fw-medium text-primary">
                                {{ $labRequest->request_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $labRequest->patient?->full_name ?? $labRequest->external_party_name ?? 'Unknown' }}</div>
                            <small class="text-muted">{{ $labRequest->patient?->patient_number ?? ($labRequest->external_party_name ? 'Walk-in' : '') }}</small>
                        </td>
                        <td>
                            @if($labRequest->targetDepartment)
                                <span class="fw-medium">{{ $labRequest->targetDepartment->name }}</span>
                            @else
                                <span class="text-muted">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            <div class="small">
                                <span class="badge bg-primary-subtle text-primary">{{ $acceptedItems->count() }} accepted</span>
                                <span class="badge bg-info-subtle text-info">{{ $billedCount }} billed</span>
                                <span class="badge bg-warning-subtle text-warning">{{ $pendingResultCount }} pending</span>
                            </div>
                            <small class="text-muted">{{ $resultCount }} entered, {{ $verifiedCount }} verified</small>
                        </td>
                        <td>
                            <div class="progress" style="height: 6px; width: 100px;">
                                <div class="progress-bar bg-success" style="width: {{ $labRequest->completion_percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $labRequest->completion_percentage }}%</small>
                        </td>
                        <td><span class="badge bg-{{ $labRequest->urgency_color }}">{{ ucfirst($labRequest->urgency) }}</span></td>
                        <td><span class="badge bg-{{ $labRequest->status_color }}">{{ $labRequest->status_label }}</span></td>
                        <td>
                            <small>{{ $labRequest->created_at?->format('d M Y') }}</small><br>
                            <small class="text-muted">{{ $labRequest->created_at?->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.lab.results.show', $labRequest) }}" class="btn btn-sm {{ $pendingResultCount ? 'btn-outline-primary' : 'btn-outline-secondary' }}">
                                <i class="ti {{ $pendingResultCount ? 'ti-edit' : 'ti-eye' }} me-1"></i>{{ $pendingResultCount ? 'Enter Results' : 'View Results' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ti ti-report-medical fs-1 d-block mb-2"></i>
                            No billed investigation requests found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($requests->hasPages())
<div class="d-flex justify-content-end mt-3">
    {{ $requests->links() }}
</div>
@endif
@endsection
