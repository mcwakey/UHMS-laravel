@extends('layouts.app')
@section('title', 'Consultation Statistics')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Consultation Statistics</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Consultation Statistics</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.consultation-stats', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm">
            <i class="ti ti-file-spreadsheet me-1"></i>Excel
        </a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Consultations</p>
                <h4 class="fw-bold mb-0">{{ number_format($stats['total_consultations']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Doctors</p>
                <h4 class="fw-bold mb-0">{{ $stats['doctors'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Departments</p>
                <h4 class="fw-bold mb-0">{{ $stats['departments'] }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Avg / Doctor</p>
                <h4 class="fw-bold mb-0">{{ $stats['doctors'] > 0 ? number_format($stats['total_consultations'] / $stats['doctors'], 1) : 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Consultations by Doctor -->
@if($byDoctor->count())
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Consultations by Doctor</h6></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Doctor</th><th>Department</th><th class="text-end">Count</th></tr>
            </thead>
            <tbody>
                @foreach($byDoctor as $doc)
                <tr>
                    <td>{{ $doc->name }}</td>
                    <td>{{ $doc->department_name ?? '—' }}</td>
                    <td class="text-end fw-semibold">{{ number_format($doc->consultation_count) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.consultation-stats') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Doctor</label>
                <select name="doctor_id" class="form-select">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}" {{ ($filters['doctor_id'] ?? '') == $doc->id ? 'selected' : '' }}>{{ $doc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary">Filter</button>
                <a href="{{ route('admin.reports.consultation-stats') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Patient</th>
                    <th>Doctor</th>
                    <th>Department</th>
                    <th>Complaints</th>
                    <th>Visit</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td>{{ $record->created_at->format('d/m/Y') }}</td>
                    <td>{{ $record->visit?->patient?->full_name ?? '—' }}</td>
                    <td>{{ $record->doctor?->name ?? '—' }}</td>
                    <td>{{ $record->visit?->department?->name ?? '—' }}</td>
                    <td>
                        @foreach($record->complaints->take(2) as $c)
                        <span class="badge bg-light text-dark">{{ \Str::limit($c->complaint, 20) }}</span>
                        @endforeach
                        @if($record->complaints->count() > 2)
                        <span class="badge bg-secondary">+{{ $record->complaints->count() - 2 }}</span>
                        @endif
                    </td>
                    <td><code>{{ $record->visit?->visit_number ?? '—' }}</code></td>
                </tr>
                @empty
                <tr><td colspan="6"><x-empty-state message="No consultations found." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="card-footer">{{ $records->links() }}</div>
    @endif
</div>
@endsection
