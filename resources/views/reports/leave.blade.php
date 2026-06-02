@extends('layouts.app')
@section('title', 'Leave Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Leave Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Leave Report</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.leave', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Requests</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_requests']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Approved</p>
                <h4 class="fw-bold mb-0 text-success">{{ number_format($stats['approved']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Pending</p>
                <h4 class="fw-bold mb-0 text-warning">{{ number_format($stats['pending']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Rejected</p>
                <h4 class="fw-bold mb-0 text-danger">{{ number_format($stats['rejected']) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.leave') }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Leave Type</label>
                <select name="leave_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach(\App\Enums\LeaveType::cases() as $lt)
                    <option value="{{ $lt->value }}" {{ ($filters['leave_type'] ?? '') == $lt->value ? 'selected' : '' }}>{{ $lt->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach(\App\Enums\LeaveStatus::cases() as $ls)
                    <option value="{{ $ls->value }}" {{ ($filters['status'] ?? '') == $ls->value ? 'selected' : '' }}>{{ $ls->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.leave') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Type</th>
                    <th>From</th>
                    <th>To</th>
                    <th class="text-end">Days</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leaves as $leave)
                <tr>
                    <td>{{ $leave->employee?->user?->name ?? '—' }}</td>
                    <td>{{ $leave->employee?->department?->name ?? '—' }}</td>
                    <td><span class="badge bg-light text-dark">{{ $leave->leave_type instanceof \App\Enums\LeaveType ? $leave->leave_type->label() : ucfirst($leave->leave_type) }}</span></td>
                    <td>{{ $leave->start_date?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ $leave->end_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-end">{{ $leave->days ?? ($leave->start_date && $leave->end_date ? $leave->start_date->diffInDays($leave->end_date) + 1 : '—') }}</td>
                    <td>
                        @php
                            $statusColors = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
                            $sv = $leave->status instanceof \App\Enums\LeaveStatus ? $leave->status->value : $leave->status;
                        @endphp
                        <span class="badge bg-{{ $statusColors[$sv] ?? 'secondary' }}">{{ $leave->status instanceof \App\Enums\LeaveStatus ? $leave->status->label() : ucfirst($sv) }}</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><x-empty-state message="No leave requests found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leaves->hasPages())
    <div class="card-footer">{{ $leaves->links() }}</div>
    @endif
</div>
@endsection
