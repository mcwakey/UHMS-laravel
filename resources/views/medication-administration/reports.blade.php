@extends('layouts.app')
@section('title', 'Medication Administration Reports')

@section('content')
<div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Medication Administration Reports</h4>
        <p class="text-muted mb-0">Administration, overdue, missed, held, refused, and nurse activity foundation.</p>
    </div>
</div>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach(['GIVEN','MISSED','HELD','REFUSED','SKIPPED'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3"><button class="btn btn-primary w-100">Apply Filters</button></div>
        </div>
    </div>
</form>

<div class="card mb-3">
    <div class="card-header"><h5 class="card-title mb-0">Medication Administration Report</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr><th>Date</th><th>Patient</th><th>Medication</th><th>Status</th><th>Nurse</th><th>Reason / Reaction</th></tr>
                </thead>
                <tbody>
                    @forelse($administrations as $record)
                    <tr>
                        <td>{{ $record->administered_at?->format('d M Y H:i') }}</td>
                        <td>{{ $record->patient->full_name ?? '—' }}</td>
                        <td>{{ $record->medicationOrder->display_name ?? 'Medication' }}</td>
                        <td><span class="badge badge-soft-{{ match($record->status){'GIVEN'=>'success','HELD'=>'warning','REFUSED'=>'warning','MISSED'=>'danger','SKIPPED'=>'secondary',default=>'secondary'} }}">{{ $record->status }}</span></td>
                        <td>{{ $record->administeredBy->name ?? '—' }}</td>
                        <td>{{ $record->reason_not_given ?: $record->reaction ?: $record->notes ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No administration records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($administrations->hasPages())
    <div class="card-footer">{{ $administrations->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0 text-danger">Overdue Medication Report</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light"><tr><th>Due</th><th>Patient</th><th>Ward</th><th>Medication</th><th>Escalation</th></tr></thead>
                <tbody>
                    @forelse($overdueTasks as $task)
                    <tr>
                        <td class="text-danger fw-semibold">{{ $task->due_at?->format('d M Y H:i') }}</td>
                        <td>{{ $task->patient->full_name ?? '—' }}</td>
                        <td>{{ $task->admission->bed->ward->name ?? 'Emergency / OPD' }}</td>
                        <td>{{ $task->schedule->medicationOrder->display_name ?? $task->title }}</td>
                        <td><span class="badge bg-danger">{{ $task->escalation_level }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No overdue medication tasks.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
