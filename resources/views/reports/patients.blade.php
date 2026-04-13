@extends('layouts.app')
@section('title', 'Patient Report')

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Patient Report</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Patient Report</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route('admin.reports.patients', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>Export PDF
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Total Patients</p>
                <h4 class="fw-bold mb-0">{{ $stats['total_patients'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">New This Month</p>
                <h4 class="fw-bold mb-0">{{ $stats['new_this_month'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Male / Female</p>
                <h4 class="fw-bold mb-0">{{ $stats['male_patients'] ?? 0 }} / {{ $stats['female_patients'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Charts + Filter -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Monthly Registration Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="registrationChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Gender Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="genderChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.patients') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name or ID..." value="{{ $filters['search'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">All</option>
                    <option value="male" {{ ($filters['gender'] ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ ($filters['gender'] ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Patient Records</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Patient ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>DOB</th>
                        <th>Phone</th>
                        <th>Visits</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                    <tr>
                        <td><a href="{{ route('admin.patients.show', $patient) }}" class="text-primary fw-medium">{{ $patient->patient_number }}</a></td>
                        <td>{{ $patient->full_name }}</td>
                        <td>{{ ucfirst($patient->gender?->value ?? '—') }}</td>
                        <td>{{ $patient->date_of_birth?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $patient->phone }}</td>
                        <td><span class="badge bg-primary rounded-pill">{{ $patient->visits_count }}</span></td>
                        <td>{{ $patient->created_at->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No patients found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($patients->hasPages())
    <div class="card-footer">
        {{ $patients->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Registration Trend
    const trend = @json($registrationTrend ?? []);
    const regCtx = document.getElementById('registrationChart');
    if (regCtx && Object.keys(trend).length) {
        new Chart(regCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(trend).map(m => {
                    const [y, mo] = m.split('-');
                    return new Date(y, mo-1).toLocaleDateString('en-GB', {month:'short', year:'numeric'});
                }),
                datasets: [{
                    label: 'Registrations',
                    data: Object.values(trend),
                    backgroundColor: '#0d6efd',
                    borderRadius: 4,
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    // Gender Chart
    const genderCtx = document.getElementById('genderChart');
    if (genderCtx) {
        new Chart(genderCtx, {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{
                    data: [{{ $stats['male_patients'] ?? 0 }}, {{ $stats['female_patients'] ?? 0 }}],
                    backgroundColor: ['#0d6efd', '#e83e8c'],
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }
});
</script>
@endpush
