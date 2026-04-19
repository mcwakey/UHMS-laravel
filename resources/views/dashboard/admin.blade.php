@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Admin Dashboard</h4>
    </div>
</div>

<!-- Key Metrics Row -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-01.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-primary rounded-circle"><i class="ti ti-user-heart fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Total Patients</p>
                        <h3 class="fw-bold mb-0">{{ $totalPatients ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-02.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-success rounded-circle"><i class="ti ti-currency-dollar fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Month Revenue</p>
                        <h3 class="fw-bold mb-0">₵{{ number_format($dashboardStats['month_revenue'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-03.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-warning rounded-circle"><i class="ti ti-stethoscope fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Active Doctors</p>
                        <h3 class="fw-bold mb-0">{{ $dashboardStats['active_doctors'] ?? 0 }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm">
            <img src="{{ URL::asset('build/img/bg/bg-04.svg') }}" alt="img" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-danger rounded-circle"><i class="ti ti-receipt fs-24"></i></span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="mb-1">Outstanding Balance</p>
                        <h3 class="fw-bold mb-0">₵{{ number_format($dashboardStats['outstanding_balance'] ?? 0, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Visits Stats -->
<div class="row mb-3">
    <div class="col-12">
        <h6 class="fw-bold text-muted mb-3">Today's Visits</h6>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Total</p>
                <h4 class="fw-bold mb-0">{{ $visitStats['total'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Waiting</p>
                <h4 class="fw-bold mb-0">{{ $visitStats['waiting'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-info border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Consulting</p>
                <h4 class="fw-bold mb-0">{{ $visitStats['consulting'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Completed</p>
                <h4 class="fw-bold mb-0">{{ $visitStats['completed'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Emergency</p>
                <h4 class="fw-bold mb-0">{{ $visitStats['emergency'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-secondary border-3">
            <div class="card-body py-3 px-3">
                <p class="text-muted mb-1 small">Today Revenue</p>
                <h4 class="fw-bold mb-0">₵{{ number_format($dashboardStats['today_revenue'] ?? 0, 2) }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Revenue & Visit Trends (Last 7 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Department Load (Today)</h5>
            </div>
            <div class="card-body">
                @if(!empty($departmentLoad))
                    <canvas id="deptChart" height="200"></canvas>
                @else
                    <p class="text-center text-muted py-4">No visits today</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Alerts Row -->
<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-alert-triangle text-warning me-1"></i> Low Stock Alerts</h5>
                <span class="badge bg-warning">{{ $dashboardStats['low_stock_alerts'] ?? 0 }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Drug</th>
                                <th>Qty</th>
                                <th>Reorder</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems ?? [] as $stock)
                            <tr>
                                <td>{{ $stock->drug->brand_name ?? $stock->drug->generic_name }}</td>
                                <td><span class="text-danger fw-bold">{{ $stock->quantity }}</span></td>
                                <td>{{ $stock->reorder_level }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No low stock items</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ti ti-flask text-info me-2"></i>Pending Lab Requests</span>
                        <span class="badge bg-info rounded-pill">{{ $dashboardStats['pending_lab'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ti ti-users-group text-primary me-2"></i>Total Users</span>
                        <span class="badge bg-primary rounded-pill">{{ $totalUsers ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ti ti-user-check text-success me-2"></i>Active Users</span>
                        <span class="badge bg-success rounded-pill">{{ $activeUsers ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ti ti-building-bank text-warning me-2"></i>Departments</span>
                        <span class="badge bg-warning rounded-pill">{{ $totalDepartments ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span><i class="ti ti-shield-lock text-secondary me-2"></i>Roles</span>
                        <span class="badge bg-secondary rounded-pill">{{ $totalRoles ?? 0 }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Patients</h5>
                @can('patients.view')
                <a href="{{ route('admin.patients.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentPatients ?? [] as $patient)
                            <tr>
                                <td><a href="{{ route('admin.patients.show', $patient) }}" class="text-primary">{{ $patient->patient_number }}</a></td>
                                <td>{{ $patient->full_name }}</td>
                                <td>{{ $patient->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No patients yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Visits Table -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Today's Visits</h5>
                @can('visits.view')
                <a href="{{ route('admin.visits.index', ['today' => 1]) }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Visit #</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentVisits ?? [] as $visit)
                            <tr>
                                <td><a href="{{ route('admin.visits.show', $visit) }}" class="text-primary fw-medium">{{ $visit->visit_number }}</a></td>
                                <td>{{ $visit->patient->full_name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $visit->visit_type->label() }}</span></td>
                                <td><span class="badge bg-{{ $visit->priority->color() }}">{{ $visit->priority->label() }}</span></td>
                                <td>—</td>
                                <td>{{ $visit->assignedDoctor?->full_name ?? '—' }}</td>
                                <td><span class="badge bg-{{ $visit->status->color() }}">{{ $visit->status->label() }}</span></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No visits today</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue & Visit Trend Chart
    const revenueTrend = @json($revenueTrend ?? []);
    const visitTrend = @json($visitTrend ?? []);

    const allDays = [...new Set([...Object.keys(revenueTrend), ...Object.keys(visitTrend)])].sort();
    const labels = allDays.map(d => {
        const date = new Date(d + 'T00:00:00');
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    });

    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue (₵)',
                        data: allDays.map(d => parseFloat(revenueTrend[d] || 0)),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Visits',
                        data: allDays.map(d => parseInt(visitTrend[d] || 0)),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.1)',
                        fill: true,
                        tension: 0.3,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'Revenue (₵)' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Visits' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    // Department Load Chart
    const deptLoad = @json($departmentLoad ?? []);
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx && Object.keys(deptLoad).length > 0) {
        const colors = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997'];
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(deptLoad),
                datasets: [{
                    data: Object.values(deptLoad),
                    backgroundColor: colors.slice(0, Object.keys(deptLoad).length),
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
            }
        });
    }
});
</script>
@endpush
