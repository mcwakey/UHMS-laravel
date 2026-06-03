@extends('layouts.app')
@section('title', 'Admin Dashboard')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Admin Dashboard</h4>
        <p class="text-muted mb-0 small">{{ now()->format('l, d F Y') }}</p>
    </div>
    <div class="d-flex gap-2">
        @can('patients.create')
        <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-sm">
            <i class="ti ti-user-plus me-1"></i>New Patient
        </a>
        @endcan
        @can('visits.create')
        <a href="{{ route('admin.visits.create') }}" class="btn btn-success btn-sm">
            <i class="ti ti-calendar-plus me-1"></i>New Visit
        </a>
        @endcan
        @can('appointments.create')
        <a href="{{ route('admin.appointments.create') }}" class="btn btn-info btn-sm text-white">
            <i class="ti ti-clock-plus me-1"></i>New Appointment
        </a>
        @endcan
    </div>
</div>

{{-- ROW 1: Primary KPI Cards --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm h-100">
            <img src="{{ URL::asset('build/img/bg/bg-01.svg') }}" alt="" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-primary rounded-circle"><i class="ti ti-user-heart fs-24"></i></span>
                    <a href="{{ route('admin.patients.index') }}" class="text-muted small">View all →</a>
                </div>
                <p class="mb-1 text-muted small">Total Patients</p>
                <h3 class="fw-bold mb-0">{{ number_format($totalPatients ?? 0) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm h-100">
            <img src="{{ URL::asset('build/img/bg/bg-02.svg') }}" alt="" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-success rounded-circle"><i class="ti ti-currency-dollar fs-24"></i></span>
                    <span class="text-muted small">This Month</span>
                </div>
                <p class="mb-1 text-muted small">Month Revenue</p>
                <h3 class="fw-bold mb-0">&#8373;{{ number_format($dashboardStats['month_revenue'] ?? 0, 2) }}</h3>
                <small class="text-muted">Today: &#8373;{{ number_format($dashboardStats['today_revenue'] ?? 0, 2) }}</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm h-100">
            <img src="{{ URL::asset('build/img/bg/bg-03.svg') }}" alt="" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-warning rounded-circle"><i class="ti ti-calendar-check fs-24"></i></span>
                    <a href="{{ route('admin.visits.index', ['today' => 1]) }}" class="text-muted small">View all →</a>
                </div>
                <p class="mb-1 text-muted small">Today's Visits</p>
                <h3 class="fw-bold mb-0">{{ $visitStats['total'] ?? 0 }}</h3>
                <small class="text-muted">{{ $visitStats['waiting'] ?? 0 }} waiting &bull; {{ $visitStats['consulting'] ?? 0 }} consulting</small>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="position-relative border card rounded-2 shadow-sm h-100">
            <img src="{{ URL::asset('build/img/bg/bg-04.svg') }}" alt="" class="position-absolute start-0 top-0">
            <div class="card-body">
                <div class="d-flex align-items-center mb-2 justify-content-between">
                    <span class="avatar bg-danger rounded-circle"><i class="ti ti-receipt fs-24"></i></span>
                    <a href="{{ route('admin.billing.invoices.index') }}" class="text-muted small">View all →</a>
                </div>
                <p class="mb-1 text-muted small">Outstanding Balance</p>
                <h3 class="fw-bold mb-0">&#8373;{{ number_format($dashboardStats['outstanding_balance'] ?? 0, 2) }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ROW 2: Secondary Stats --}}
<div class="row g-3 mb-3">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-info border-3 shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Today Appointments</p>
                <h4 class="fw-bold mb-0">{{ $dashboardStats['today_appointments'] ?? 0 }}</h4>
                <small class="text-info"><i class="ti ti-calendar-event me-1"></i>Scheduled</small>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-primary border-3 shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Active Admissions</p>
                <h4 class="fw-bold mb-0">{{ $dashboardStats['active_admissions'] ?? 0 }}</h4>
                <small class="text-primary"><i class="ti ti-bed me-1"></i>{{ $dashboardStats['occupied_beds'] ?? 0 }} beds used</small>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-warning border-3 shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Pending Prescriptions</p>
                <h4 class="fw-bold mb-0">{{ $dashboardStats['pending_prescriptions'] ?? 0 }}</h4>
                <small class="text-warning"><i class="ti ti-prescription me-1"></i>Awaiting dispensing</small>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-secondary border-3 shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Pending Lab</p>
                <h4 class="fw-bold mb-0">{{ $dashboardStats['pending_lab'] ?? 0 }}</h4>
                <small class="text-secondary"><i class="ti ti-test-pipe me-1"></i>Requests</small>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-danger border-3 shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Pending Claims</p>
                <h4 class="fw-bold mb-0">{{ $dashboardStats['pending_claims'] ?? 0 }}</h4>
                <small class="text-danger"><i class="ti ti-file-check me-1"></i>Under review</small>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card border-start border-success border-3 shadow-sm h-100">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">Active Doctors</p>
                <h4 class="fw-bold mb-0">{{ $dashboardStats['active_doctors'] ?? 0 }}</h4>
                <small class="text-success"><i class="ti ti-stethoscope me-1"></i>On staff</small>
            </div>
        </div>
    </div>
</div>

{{-- ROW 3: Charts --}}
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Revenue & Visit Trends <span class="text-muted fw-normal small">(Last 7 Days)</span></h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Department Load <span class="text-muted fw-normal small">(Today)</span></h5>
            </div>
            <div class="card-body">
                @if(!empty($departmentLoad))
                    <canvas id="deptChart" height="200"></canvas>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="ti ti-chart-donut fs-48 d-block mb-2"></i>No department activity today
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ROW 4: Alerts & Stock --}}
<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-alert-triangle text-warning me-1"></i>Low Stock Alerts</h5>
                <span class="badge bg-warning">{{ $dashboardStats['low_stock_alerts'] ?? 0 }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Drug</th><th class="text-center">Qty</th><th class="text-center">Reorder</th></tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems ?? [] as $stock)
                            <tr>
                                <td class="small">{{ $stock->name }}</td>
                                <td class="text-center"><span class="badge bg-danger">{{ number_format($stock->total_qty, 0) }}</span></td>
                                <td class="text-center text-muted small">{{ number_format($stock->reorder_level, 0) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3"><x-empty-state message="No low stock items" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0"><i class="ti ti-skull text-danger me-1"></i>Expired Stock</h5>
                <span class="badge bg-danger">{{ $dashboardStats['expired_stock_count'] ?? 0 }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Drug</th><th class="text-center">Qty</th><th>Expired</th></tr>
                        </thead>
                        <tbody>
                            @forelse($expiredStockItems ?? [] as $stock)
                            <tr>
                                <td class="small">{{ $stock->name }}</td>
                                <td class="text-center"><span class="badge bg-secondary">{{ number_format($stock->quantity, 0) }}</span></td>
                                <td class="small text-danger">{{ $stock->expiry_date?->format('M d, Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3"><x-empty-state message="No expired stock" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span class="small"><i class="ti ti-flask text-info me-2"></i>Today Admissions</span>
                        <span class="badge bg-info rounded-pill">{{ $dashboardStats['today_admissions'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span class="small"><i class="ti ti-users-group text-primary me-2"></i>Total Users</span>
                        <span class="badge bg-primary rounded-pill">{{ $totalUsers ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span class="small"><i class="ti ti-user-check text-success me-2"></i>Active Users</span>
                        <span class="badge bg-success rounded-pill">{{ $activeUsers ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span class="small"><i class="ti ti-building-bank text-warning me-2"></i>Departments</span>
                        <span class="badge bg-warning rounded-pill">{{ $totalDepartments ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span class="small"><i class="ti ti-calendar-check text-success me-2"></i>Today Completed</span>
                        <span class="badge bg-success rounded-pill">{{ $visitStats['completed'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span class="small"><i class="ti ti-ambulance text-danger me-2"></i>Emergency Visits</span>
                        <span class="badge bg-danger rounded-pill">{{ $visitStats['emergency'] ?? 0 }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ROW 5: Today Appointments & Recent Patients --}}
<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Today's Appointments</h5>
                @can('appointments.view')
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($todayAppointments ?? [] as $appt)
                            <tr>
                                <td class="small">{{ $appt->appointment_date?->format('H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.patients.show', $appt->patient_id) }}" class="text-primary small fw-medium">
                                        {{ $appt->patient->full_name ?? '—' }}
                                    </a>
                                </td>
                                <td class="small">{{ $appt->doctor->full_name ?? '—' }}</td>
                                <td><span class="badge bg-light text-dark">{{ $appt->type?->label() ?? '—' }}</span></td>
                                <td><span class="badge bg-{{ $appt->status?->color() ?? 'secondary' }}">{{ $appt->status?->label() ?? '—' }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="5"><x-empty-state message="No appointments today" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Recent Patients</h5>
                @can('patients.view')
                <a href="{{ route('admin.patients.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>ID</th><th>Name</th><th>Registered</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentPatients ?? [] as $patient)
                            <tr>
                                <td><a href="{{ route('admin.patients.show', $patient) }}" class="text-primary small">{{ $patient->patient_number }}</a></td>
                                <td class="small">{{ $patient->full_name }}</td>
                                <td class="small text-muted">{{ $patient->created_at->diffForHumans() }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3"><x-empty-state message="No patients yet" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ROW 6: Today's Visits Table --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card shadow-sm">
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
                                <th>Doctor</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentVisits ?? [] as $visit)
                            <tr>
                                <td><a href="{{ route('admin.visits.show', $visit) }}" class="text-primary fw-medium">{{ $visit->visit_number }}</a></td>
                                <td>{{ $visit->patient->full_name }}</td>
                                <td><span class="badge bg-light text-dark">{{ $visit->visit_type->label() }}</span></td>
                                <td><x-status-badge :status="$visit->priority" /></td>
                                <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                                <td><x-status-badge :status="$visit->status" /></td>
                                <td class="text-muted small">{{ $visit->created_at->format('H:i') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7"><x-empty-state message="No visits today" /></td></tr>
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
    const revenueTrend = @json($revenueTrend ?? []);
    const visitTrend   = @json($visitTrend ?? []);

    const allDays = [...new Set([...Object.keys(revenueTrend), ...Object.keys(visitTrend)])].sort();
    const labels  = allDays.map(d => {
        const date = new Date(d + 'T00:00:00');
        return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    });

    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Revenue (\u20b5)',
                        data: allDays.map(d => parseFloat(revenueTrend[d] || 0)),
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25,135,84,0.08)',
                        fill: true, tension: 0.3, yAxisID: 'y',
                    },
                    {
                        label: 'Visits',
                        data: allDays.map(d => parseInt(visitTrend[d] || 0)),
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13,110,253,0.08)',
                        fill: true, tension: 0.3, yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y:  { type: 'linear', display: true, position: 'left',  title: { display: true, text: 'Revenue (\u20b5)' } },
                    y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'Visits' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    const deptLoad = @json($departmentLoad ?? []);
    const deptCtx  = document.getElementById('deptChart');
    if (deptCtx && Object.keys(deptLoad).length > 0) {
        const colors = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6f42c1','#fd7e14','#20c997'];
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(deptLoad),
                datasets: [{ data: Object.values(deptLoad), backgroundColor: colors.slice(0, Object.keys(deptLoad).length) }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } }
            }
        });
    }
});
</script>
@endpush
