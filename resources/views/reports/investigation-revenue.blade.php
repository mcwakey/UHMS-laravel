@extends('layouts.app')
@section('title', 'Investigation Revenue')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Investigation Revenue</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Investigation Revenue</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.investigation-revenue', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Revenue</p>
                <h4 class="fw-bold mb-0 text-success">₵{{ number_format($stats['total_revenue'], 2) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Completed Requests</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_requests']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Departments</p>
                <h4 class="fw-bold mb-0">{{ $stats['departments'] }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Revenue by Department -->
@if($departmentRevenue->count())
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Revenue by Requesting Department</h6></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Department</th><th class="text-end">Tests</th><th class="text-end">Revenue</th></tr>
            </thead>
            <tbody>
                @foreach($departmentRevenue as $dept)
                <tr>
                    <td>{{ $dept->name }}</td>
                    <td class="text-end">{{ number_format($dept->test_count) }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($dept->total_revenue, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.investigation-revenue') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.investigation-revenue') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Request #</th>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Department</th>
                    <th>Tests</th>
                    <th class="text-end">Revenue</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td><code>{{ $req->request_number }}</code></td>
                    <td>{{ $req->created_at->format('d/m/Y') }}</td>
                    <td>{{ $req->patient?->full_name ?? '—' }}</td>
                    <td>{{ $req->department?->name ?? '—' }}</td>
                    <td>{{ $req->items->count() }}</td>
                    <td class="text-end fw-semibold">₵{{ number_format($req->items->sum(fn($i) => $i->labTest->price ?? 0), 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6"><x-empty-state message="No completed requests found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="card-footer">{{ $requests->links() }}</div>
    @endif
</div>
@endsection
