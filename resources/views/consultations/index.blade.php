@extends('layouts.app')
@section('title', 'Consultations')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Consultations</h4>
        <small class="text-muted">Manage active patient consultations</small>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Patient name, visit number..." value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Visit Type</label>
                <select name="visit_type" class="form-select">
                    <option value="">All Types</option>
                    @foreach(\App\Enums\VisitType::cases() as $type)
                        <option value="{{ $type->value }}" {{ ($filters['visit_type'] ?? '') == $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="my_patients" value="1" id="myPatients" {{ request('my_patients') ? 'checked' : '' }}>
                    <label class="form-check-label" for="myPatients">My Patients Only</label>
                </div>
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="ti ti-search me-1"></i>Filter</button>
                <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary ms-1">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Visits List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Visit #</th>
                        <th>Patient</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Doctor</th>
                        <th>Chief Complaint</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                    <tr>
                        <td>
                            <span class="fw-medium">{{ $visit->visit_number }}</span>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $visit->patient->full_name }}</div>
                            <small class="text-muted">{{ $visit->patient->patient_number }} &middot; {{ $visit->patient->age }}y {{ $visit->patient->gender->value }}</small>
                        </td>
                        <td><span class="badge bg-{{ $visit->status->color() }}">{{ $visit->status->label() }}</span></td>
                        <td><span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span></td>
                        <td>{{ $visit->assignedDoctor ? 'Dr. ' . $visit->assignedDoctor->full_name : '—' }}</td>
                        <td><small>{{ Str::limit($visit->chief_complaint, 40) ?? '—' }}</small></td>
                        <td>
                            <a href="{{ route('admin.consultations.show', $visit) }}" class="btn btn-sm btn-primary">
                                <i class="ti ti-stethoscope me-1"></i>Consult
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="ti ti-stethoscope fs-1 d-block mb-2"></i>
                            No active consultations at the moment.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center mt-3">
    {{ $visits->withQueryString()->links() }}
</div>
@endsection
