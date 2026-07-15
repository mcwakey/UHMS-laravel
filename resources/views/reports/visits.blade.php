@extends('layouts.app')
@php
    $isRecordsAttendance = request()->routeIs('records.reports.attendance');
    $visitReportTitle = $isRecordsAttendance ? __('records.reports.attendance') : __('reports.visits.title');
    $visitReportRoute = $isRecordsAttendance ? 'records.reports.attendance' : $workspaceRoutes->routeName('admin.reports.visits');
@endphp
@section('title', $visitReportTitle)

@section('content')
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ $visitReportTitle }}</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ $workspaceRoutes->dashboard() }}">{{ $workspaceContext['workspaceKey'] === 'records' ? __('records.breadcrumbs.records') : __('common.dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ $visitReportTitle }}</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="{{ route($visitReportRoute, array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('reports.actions.export_pdf') }}
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-primary border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.visits.total_visits') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['total_visits'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-success border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.investigations.completed') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['completed'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-warning border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.investigations.in_progress') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['in_progress'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card border-start border-danger border-3">
            <div class="card-body py-3">
                <p class="text-muted mb-1 small">{{ __('reports.theatre.cancelled') }}</p>
                <h4 class="fw-bold mb-0">{{ $stats['cancelled'] ?? 0 }}</h4>
            </div>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('reports.charts.daily_visit_trend') }}</h5>
            </div>
            <div class="card-body">
                <canvas id="dailyTrendChart" height="100"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">{{ __('reports.charts.department_distribution') }}</h5>
            </div>
            <div class="card-body">
                @if(!empty($departmentLoad))
                    <canvas id="deptChart" height="200"></canvas>
                @else
                    <p class="text-center text-muted py-4">{{ __('reports.charts.no_data') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($visitReportRoute) }}" class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.filters.date_from') }}</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.filters.date_to') }}</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('reports.department') }}</label>
                <select name="department_id" class="form-select">
                    <option value="">{{ __('reports.all_departments') }}</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ ($filters['department_id'] ?? '') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">{{ __('reports.status') }}</label>
                <select name="status" class="form-select">
                    <option value="">{{ __('common.all') }}</option>
                    @foreach($visitStatuses as $vs)
                        <option value="{{ $vs->value }}" {{ ($filters['status'] ?? '') === $vs->value ? 'selected' : '' }}>
                            {{ $vs->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>{{ __('reports.filter') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('reports.visits.title') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('reports.visits.visit_number') }}</th>
                        <th>{{ __('reports.col_patient') }}</th>
                        <th>{{ __('reports.col_type') }}</th>
                        <th>{{ __('reports.col_priority') }}</th>
                        <th>{{ __('reports.col_department') }}</th>
                        <th>{{ __('reports.col_doctor') }}</th>
                        <th>{{ __('reports.col_status') }}</th>
                        <th>{{ __('reports.col_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                    <tr>
                        <td><a href="{{ $workspaceRoutes->route('admin.visits.show', $visit) }}" class="text-primary fw-medium">{{ $visit->visit_number }}</a></td>
                        <td>{{ $visit->patient->full_name }}</td>
                        <td><span class="badge bg-light text-dark">{{ $visit->visit_type->label() }}</span></td>
                        <td><x-status-badge :status="$visit->priority" /></td>
                        <td>—</td>
                        <td>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</td>
                        <td><x-status-badge :status="$visit->status" /></td>
                        <td>{{ $visit->visit_date->format('d M Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8"><x-empty-state message="{{ __('reports.empty.no_visits') }}" /></td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($visits->hasPages())
    <div class="card-footer">
        {{ $visits->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Trend
    const dailyTrend = @json($dailyTrend ?? []);
    const trendCtx = document.getElementById('dailyTrendChart');
    if (trendCtx && Object.keys(dailyTrend).length) {
        const labels = Object.keys(dailyTrend).map(d => {
            const date = new Date(d + 'T00:00:00');
            return date.toLocaleDateString('en-GB', { day:'2-digit', month:'short' });
        });
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: @json(__('reports.visits.title')),
                    data: Object.values(dailyTrend),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    // Department Distribution
    const deptLoad = @json($departmentLoad ?? []);
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx && Object.keys(deptLoad).length) {
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
            options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
        });
    }
});
</script>
@endpush
