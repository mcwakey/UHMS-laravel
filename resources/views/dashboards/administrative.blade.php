@extends('layouts.app')
@section('title', __('administrative.dashboard.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('administrative.dashboard.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        @can('departments.view')
            <a href="{{ route('administrative.departments.index') }}" class="btn btn-primary"><i class="ti ti-building-hospital me-1"></i>{{ $t('open_departments') }}</a>
        @endcan
        @can('reports.view')
            <a href="{{ route('administrative.reports.index') }}" class="btn btn-outline-dark"><i class="ti ti-chart-bar me-1"></i>{{ $t('open_reports') }}</a>
        @endcan
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('active_departments'), 'value' => $kpis['departments']['value'], 'icon' => 'ti-building-hospital', 'color' => 'primary', 'caption' => $t('operational_units')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('active_staff'), 'value' => $kpis['staff']['value'], 'icon' => 'ti-users', 'color' => 'success', 'caption' => $t('enabled_accounts')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('visits_today'), 'value' => $kpis['visits_today']['value'], 'icon' => 'ti-calendar-check', 'color' => 'info', 'caption' => $t('registered_today')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('admissions_today'), 'value' => $kpis['admissions_today']['value'], 'icon' => 'ti-bed', 'color' => 'warning', 'caption' => $t('admitted_today')])
    </div>
</div>

{{-- Visit trend + departments by type --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('visit_trend') }}</h5>
                <span class="badge bg-light text-dark border">{{ $t('last_7_days') }}</span>
            </div>
            <div class="card-body"><div id="visitTrendChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('departments_by_type') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="departmentTypeChart"></div></div>
        </div>
    </div>
</div>

{{-- Busiest departments + audit trail --}}
<div class="row g-3">
    <div class="col-xl-5">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('busiest_departments') }}</h5></div>
            <div class="card-body">
                @php($max = max(1, (int) collect($busiestDepartments)->max('visit_count')))
                @forelse($busiestDepartments as $i => $row)
                    @php($colors = ['primary', 'success', 'warning', 'info', 'danger'])
                    <div class="{{ $loop->last ? '' : 'mb-3' }}">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold">{{ $row->name }}</span>
                            <span class="text-muted small">{{ $t('visits_count', ['count' => $row->visit_count]) }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $colors[$i % 5] }}" role="progressbar" style="width: {{ (int) round(($row->visit_count / $max) * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $tc('no_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('recent_activity') }}</h5>
                @can('logs.view')
                    <a href="{{ route('administrative.logs.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body">
                @forelse($recentActivity as $log)
                    <div class="d-flex align-items-start justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div class="me-2">
                            <span class="fw-semibold d-block">{{ \Illuminate\Support\Str::limit($log->description, 80) }}</span>
                            <span class="text-muted small">{{ $log->causer?->name ?? $t('system') }} · {{ $log->created_at?->diffForHumans() }}</span>
                        </div>
                        <span class="badge badge-soft-secondary text-uppercase">{{ $log->log_name }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $tc('no_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#visitTrendChart'), {
        chart: { height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: @json(__('administrative.dashboard.visits')), type: 'area', data: @json($visitTrend['visits']) }],
        xaxis: { categories: @json($visitTrend['labels']) },
        colors: ['#3538CD'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: .25, opacityTo: .02 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const departmentTypes = @json($departmentsByType);
    new ApexCharts(document.querySelector('#departmentTypeChart'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: Object.values(departmentTypes),
        labels: Object.keys(departmentTypes),
        colors: ['#3538CD', '#0E9384', '#F7C325', '#E91E63', '#98A2B3'],
        legend: { position: 'bottom' },
        dataLabels: { formatter: (v) => Math.round(v) + '%' },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: {
            show: true, label: @json(__('administrative.dashboard.total_departments')),
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
        } } } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
