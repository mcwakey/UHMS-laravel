@extends('layouts.app')
@section('title', 'Blood Bank Reports')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div><h4 class="fw-bold mb-1">Blood Bank Reports</h4><p class="text-muted mb-0">Inventory, request, issue, transfusion, expiry, and wastage foundation.</p></div>
    <a href="{{ route('admin.blood-bank.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end">
            <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Group</label><select name="blood_group" class="form-select"><option value="">All</option>@foreach(['O-','O+','A-','A+','B-','B+','AB-','AB+'] as $g)<option value="{{ $g }}" @selected(($filters['blood_group'] ?? '') === $g)>{{ $g }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Component</label><input name="component_type" value="{{ $filters['component_type'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label">Status</label><input name="status" value="{{ $filters['status'] ?? '' }}" class="form-control"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Run Report</button></div>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white"><h5 class="card-title mb-0">Inventory Report</h5></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
        <thead class="bg-light"><tr><th>Unit</th><th>Group</th><th>Component</th><th>Status</th><th>Screening</th><th>Expiry</th><th>Storage</th></tr></thead>
        <tbody>@forelse($inventory as $unit)<tr><td>{{ $unit->unit_number }}</td><td>{{ $unit->blood_group }}</td><td>{{ $unit->component_type }}</td><td>{{ $unit->status }}</td><td>{{ $unit->screening_status }}</td><td>{{ $unit->expiry_date?->format('d M Y') }}</td><td>{{ $unit->storageLocation->name ?? '—' }}</td></tr>@empty<tr><td colspan="7" class="text-center text-muted py-4">No inventory records.</td></tr>@endforelse</tbody>
    </table></div></div>
    @if($inventory->hasPages())<div class="card-footer">{{ $inventory->links() }}</div>@endif
</div>

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header bg-white"><h5 class="card-title mb-0">Request Report</h5></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead class="bg-light"><tr><th>Request</th><th>Patient</th><th>Blood</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>@forelse($requests as $request)<tr><td>{{ $request->request_number }}</td><td>{{ $request->patient->full_name ?? '—' }}</td><td>{{ $request->blood_group }} x{{ $request->units_requested }}</td><td>{{ $request->status }}</td><td>{{ $request->requested_at?->format('d M Y') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No requests.</td></tr>@endforelse</tbody>
            </table></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header bg-white"><h5 class="card-title mb-0">Issue / Transfusion Report</h5></div>
            <div class="card-body p-0"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
                <thead class="bg-light"><tr><th>Issue</th><th>Unit</th><th>Patient</th><th>Status</th><th>Issued</th></tr></thead>
                <tbody>@forelse($issues as $issue)<tr><td>{{ $issue->issue_number }}</td><td>{{ $issue->unit->unit_number ?? '—' }}</td><td>{{ $issue->patient->full_name ?? '—' }}</td><td>{{ $issue->transfusion_status }}</td><td>{{ $issue->issued_at?->format('d M Y H:i') }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-4">No issues.</td></tr>@endforelse</tbody>
            </table></div></div>
        </div>
    </div>
</div>
@endsection
