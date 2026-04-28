@extends('layouts.app')
@section('title', 'Investigation Requests')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-microscope me-2"></i>Investigation Requests</h4>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-warning">{{ $stats['pending'] }}</h3>
                <small class="text-muted">Pending</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-info">{{ $stats['processing'] }}</h3>
                <small class="text-muted">Processing</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-success">{{ $stats['completed_today'] }}</h3>
                <small class="text-muted">Completed Today</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body py-3 text-center">
                <h3 class="mb-0 text-primary">{{ $stats['total_tests'] }}</h3>
                <small class="text-muted">Active Tests</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search patient, request #..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
                <select name="urgency" class="form-select">
                    <option value="">All Urgency</option>
                    <option value="routine" {{ request('urgency') === 'routine' ? 'selected' : '' }}>Routine</option>
                    <option value="urgent" {{ request('urgency') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="emergency" {{ request('urgency') === 'emergency' ? 'selected' : '' }}>Emergency</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-md"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.lab.requests.index') }}" class="btn btn-outline-secondary btn-md">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Patient</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td>
                            <a href="{{ route('admin.lab.requests.show', $req) }}" class="fw-medium text-primary">
                                {{ $req->request_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $req->patient->full_name }}</div>
                            <small class="text-muted">{{ $req->patient->patient_number }}</small>
                        </td>
                        <td>
                            @if($req->targetDepartment)
                            <span class="fw-medium">{{ $req->targetDepartment->name }}</span>
                            @else <span class="text-muted">&mdash;</span> @endif
                        </td>
                        <td>
                            @php $rt = $req->result_type; @endphp
                            @if($rt && $rt->value !== 'none')
                            <span class="badge bg-{{ $rt->color() }}"><i class="ti {{ $rt->icon() }} me-1"></i>{{ $rt->label() }}</span>
                            @else <span class="text-muted">&mdash;</span> @endif
                        </td>
                        <td><span class="badge bg-{{ $req->urgency_color }}">{{ ucfirst($req->urgency) }}</span></td>
                        <td><span class="badge bg-{{ $req->status_color }}">{{ $req->status_label }}</span></td>
                        <td>
                            <div class="progress" style="height: 6px; width: 80px;">
                                <div class="progress-bar bg-success" style="width: {{ $req->completion_percentage }}%"></div>
                            </div>
                            <small class="text-muted">{{ $req->completion_percentage }}%</small>
                        </td>
                        <td>
                            <small>{{ $req->created_at->format('d M Y') }}</small><br>
                            <small class="text-muted">{{ $req->created_at->format('H:i') }}</small>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.lab.requests.show', $req) }}" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-eye me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ti ti-microscope fs-1 d-block mb-2"></i>
                            No investigation requests found.
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
