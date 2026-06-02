@extends('layouts.app')
@section('title', 'Emergency Reports')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1">Emergency Reports</h4>
        <p class="text-muted mb-0">Attendance and disposition foundation for emergency operations.</p>
    </div>
    <a href="{{ route('admin.emergency.board') }}" class="btn btn-outline-secondary btn-sm">Emergency Board</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2 align-items-end" method="GET" action="{{ route('admin.emergency.reports.index') }}">
            <div class="col-md-3">
                <label class="form-label">From</label>
                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">To</label>
                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
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
            <div class="col-md-2">
                <label class="form-label">Disposition</label>
                <select class="form-select" name="disposition">
                    <option value="">All</option>
                    @foreach(['ADMITTED','DISCHARGED','TRANSFERRED_TO_OPD','TRANSFERRED_TO_THEATRE','REFERRED_OUT','LEFT_AGAINST_MEDICAL_ADVICE','ABSCONDED','DIED','DEAD_ON_ARRIVAL'] as $disposition)
                        <option value="{{ $disposition }}" @selected(($filters['disposition'] ?? '') === $disposition)>{{ str_replace('_', ' ', $disposition) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Run Report</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    @forelse($dispositionCounts as $disposition => $total)
        <div class="col-6 col-xl-2">
            <div class="card border-0 bg-light">
                <div class="card-body py-3">
                    <div class="small text-muted">{{ str_replace('_', ' ', $disposition ?: 'Open') }}</div>
                    <div class="h4 mb-0">{{ $total }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-light mb-0">No disposition records for this period yet.</div>
        </div>
    @endforelse
</div>

<div class="card">
    <div class="card-header"><h5 class="card-title mb-0">Attendance</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Emergency Number</th>
                        <th>Patient</th>
                        <th>Arrival</th>
                        <th>Triage</th>
                        <th>Status</th>
                        <th>Disposition</th>
                        <th>Team</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        <tr>
                            <td><a href="{{ route('admin.emergency.cases.show', $case) }}">{{ $case->emergency_number }}</a></td>
                            <td>
                                <div class="fw-semibold">{{ $case->patient->full_name ?? 'Unknown patient' }}</div>
                                <small class="text-muted">{{ $case->patient->patient_number ?? '' }}</small>
                            </td>
                            <td>{{ $case->arrival_time?->format('d M Y H:i') }}</td>
                            <td><span class="badge {{ $case->triage_badge_class }}">{{ $case->triage_category ?? 'UNTRIAGED' }}</span></td>
                            <td>{{ str_replace('_', ' ', $case->emergency_status) }}</td>
                            <td>{{ $case->disposition ? str_replace('_', ' ', $case->disposition) : 'Open' }}</td>
                            <td>
                                <div class="small">Dr: {{ $case->assignedDoctor->name ?? 'Unassigned' }}</div>
                                <div class="small text-muted">Nurse: {{ $case->assignedNurse->name ?? 'Unassigned' }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><x-empty-state message="No emergency cases match this report." /></td></tr>
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
