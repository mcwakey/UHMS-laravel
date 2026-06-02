@extends('layouts.app')
@section('title', 'Emergency Board')

@push('styles')
<style>
    .er-triage-strip { width: 6px; border-radius: 8px 0 0 8px; }
    .er-triage-RED { background: #dc3545; }
    .er-triage-ORANGE { background: #fd7e14; }
    .er-triage-YELLOW { background: #ffc107; }
    .er-triage-GREEN { background: #198754; }
    .er-triage-BLACK { background: #212529; }
    .er-alert-pill { min-width: 34px; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Emergency Board</h4>
        <p class="text-muted mb-0">Active emergency cases, triage urgency, bays, alerts, and quick actions.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="(window.UhmsInertia ? window.UhmsInertia.reload({ preserveScroll: true }) : location.reload())">
            <i class="ti ti-refresh me-1"></i>Refresh
        </button>
        @can('emergency.case.create')
            <a href="{{ route('admin.emergency.cases.create') }}" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>New Emergency Case
            </a>
        @endcan
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2"><div class="card border-0 bg-light"><div class="card-body py-3"><div class="text-muted small">Active</div><div class="h4 mb-0">{{ $counts['active'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-warning-subtle"><div class="card-body py-3"><div class="text-warning small">Waiting Triage</div><div class="h4 mb-0">{{ $counts['waiting_triage'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-danger text-white"><div class="card-body py-3"><div class="small opacity-75">Red / Critical</div><div class="h4 mb-0">{{ $counts['red'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-info-subtle"><div class="card-body py-3"><div class="text-info small">Under Care</div><div class="h4 mb-0">{{ $counts['under_care'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-primary-subtle"><div class="card-body py-3"><div class="text-primary small">Observation</div><div class="h4 mb-0">{{ $counts['observation'] ?? 0 }}</div></div></div></div>
    <div class="col-6 col-xl-2"><div class="card border-0 bg-success-subtle"><div class="card-body py-3"><div class="text-success small">Ready Disposition</div><div class="h4 mb-0">{{ $counts['ready_for_disposition'] ?? 0 }}</div></div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.emergency.board') }}">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="ER number, patient, complaint">
            </div>
            <div class="col-md-2">
                <label class="form-label">Triage</label>
                <select class="form-select" name="triage_category">
                    <option value="">All</option>
                    @foreach(['RED','ORANGE','YELLOW','GREEN','BLACK'] as $category)
                        <option value="{{ $category }}" @selected(($filters['triage_category'] ?? '') === $category)>{{ $category }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All active</option>
                    @foreach(['WAITING_TRIAGE','TRIAGED','UNDER_EMERGENCY_CARE','OBSERVATION','READY_FOR_DISPOSITION'] as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str_replace('_', ' ', $status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Bay</label>
                <select class="form-select" name="bay_id">
                    <option value="">All bays</option>
                    @foreach($bays as $bay)
                        <option value="{{ $bay->id }}" @selected((string)($filters['bay_id'] ?? '') === (string)$bay->id)>{{ $bay->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100" type="submit">Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('admin.emergency.board') }}">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Case</th>
                        <th>Patient</th>
                        <th>Triage</th>
                        <th>Arrival / Wait</th>
                        <th>Bay</th>
                        <th>Team</th>
                        <th>Status</th>
                        <th>Alerts</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        @php $triage = $case->triage_category ?? 'UNTRIAGED'; @endphp
                        <tr>
                            <td class="position-relative">
                                <span class="position-absolute top-0 bottom-0 start-0 er-triage-strip er-triage-{{ $case->triage_category ?? 'GREEN' }}"></span>
                                <div class="ps-2 fw-semibold">{{ $case->emergency_number }}</div>
                                <small class="ps-2 text-muted">{{ $case->visit->visit_number ?? 'No visit number' }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $case->patient->full_name ?? 'Unknown patient' }}</div>
                                <small class="text-muted">{{ $case->patient->patient_number ?? '' }}</small>
                                @if($case->patient?->is_temporary)
                                    <span class="badge bg-warning-subtle text-warning ms-1">Temporary</span>
                                @endif
                                @if($case->chief_complaint)
                                    <div class="small text-muted text-truncate" style="max-width: 220px;">{{ $case->chief_complaint }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $case->triage_badge_class }}">{{ $triage }}</span>
                                @if($case->triage_score !== null)
                                    <div class="small text-muted">Score {{ $case->triage_score }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $case->arrival_time?->format('d M H:i') }}</div>
                                <small class="text-muted">{{ $case->waiting_minutes }} min</small>
                            </td>
                            <td>{{ $case->bay->name ?? 'Unassigned' }}</td>
                            <td>
                                <div class="small">Dr: {{ $case->assignedDoctor->name ?? 'Unassigned' }}</div>
                                <div class="small text-muted">Nurse: {{ $case->assignedNurse->name ?? 'Unassigned' }}</div>
                            </td>
                            <td><x-status-badge :status="$case->emergency_status" domain="emergency" /></td>
                            <td>
                                <span class="badge rounded-pill bg-info er-alert-pill" title="Due or overdue tasks">{{ $case->due_tasks_count }}</span>
                                <span class="badge rounded-pill bg-danger er-alert-pill" title="Medication tasks">{{ $case->active_medication_tasks_count }}</span>
                                <span class="badge rounded-pill bg-warning text-dark er-alert-pill" title="Pending investigations">{{ $case->pending_investigations_count }}</span>
                                <span class="badge rounded-pill bg-secondary er-alert-pill" title="Pending procedures">{{ $case->pending_procedures_count }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a href="{{ route('admin.emergency.cases.show', $case) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                    @if($case->visit)
                                        <a href="{{ route('admin.emergency.mar-chart', $case->visit) }}" class="btn btn-sm btn-outline-danger">MAR</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">No active emergency cases found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($cases->hasPages())
        <div class="card-footer">{{ $cases->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
setTimeout(function () {
    if (window.UhmsInertia) {
        window.UhmsInertia.reload({ preserveScroll: true, preserveState: true });
    }
}, 60000);
</script>
@endpush
