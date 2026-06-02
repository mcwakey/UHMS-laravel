@extends('layouts.app')
@section('title', 'Statistical Dashboard')

@php
    $fmt = function ($value, $format) {
        return match ($format) {
            'currency' => 'GHS '.number_format((float) $value, 2),
            'percent' => rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%',
            default => is_numeric($value) ? number_format((float) $value) : $value,
        };
    };
@endphp

@section('content')
<x-page-header title="Statistical Dashboard" icon="ti-chart-histogram"
    :description="'Hospital-wide KPIs for '.$filters['from'].' → '.$filters['to'].'. Click a card to drill into its statistics.'" />

@include('statistics._nav')

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Apply</button></div>
            <div class="col-md-3">
                <div class="btn-group w-100">
                    <a href="{{ route('admin.statistics.dashboard', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm">Today</a>
                    <a href="{{ route('admin.statistics.dashboard', ['date_from' => now()->startOfWeek()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm">Week</a>
                    <a href="{{ route('admin.statistics.dashboard', ['date_from' => now()->startOfMonth()->toDateString(), 'date_to' => now()->toDateString()]) }}" class="btn btn-outline-secondary btn-sm">Month</a>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    @foreach($kpis as $kpi)
        <div class="col-6 col-md-4 col-xl-3">
            @php($card = '<div class="card h-100 border-start border-'.($kpi['color'] ?? 'primary').' border-3"><div class="card-body py-3"><div class="small text-muted">'.e($kpi['label']).'</div><div class="h3 mb-0 text-dark">'.e($fmt($kpi['value'], $kpi['format'] ?? 'number')).'</div></div></div>')
            @if(!empty($kpi['route']) && Route::has($kpi['route']))
                <a href="{{ route($kpi['route'], $filters) }}" class="text-decoration-none">{!! $card !!}</a>
            @else
                {!! $card !!}
            @endif
        </div>
    @endforeach
</div>

<div class="row g-3">
    @foreach($charts as $chart)
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="card-title mb-0">{{ $chart['title'] }}</h6></div>
                <div class="card-body">
                    @if(empty($chart['labels']))
                        <p class="text-muted text-center my-4">No data for this period.</p>
                    @else
                        <div style="position:relative;height:300px;"><canvas id="{{ $chart['id'] }}"></canvas></div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
(function () {
    const charts = @json($charts);
    const palette = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6610f2','#fd7e14','#20c997'];
    charts.forEach(function (cfg) {
        const el = document.getElementById(cfg.id);
        if (!el || !cfg.labels || cfg.labels.length === 0 || typeof Chart === 'undefined') return;
        new Chart(el, {
            type: cfg.type,
            data: {
                labels: cfg.labels,
                datasets: cfg.datasets.map((ds, idx) => ({
                    label: ds.label, data: ds.data,
                    backgroundColor: palette[idx % palette.length],
                    borderColor: palette[idx % palette.length], fill: false, tension: 0.3,
                })),
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
        });
    });
})();
</script>
@endpush
