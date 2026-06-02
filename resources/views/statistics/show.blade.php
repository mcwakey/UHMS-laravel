@extends('layouts.app')
@section('title', $title)

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
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti {{ $meta['icon'] ?? 'ti-chart-bar' }} me-2 text-primary"></i>{{ $title }}</h4>
        <p class="text-muted mb-0">{{ $filters['from'] }} → {{ $filters['to'] }} · figures use the selected period.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.statistics.dashboard', $filters) }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-layout-dashboard me-1"></i>Dashboard</a>
        @if($canExport ?? false)
            <a href="{{ route('admin.statistics.'.$key, array_merge($filters, ['export' => 'csv'])) }}" class="btn btn-outline-success btn-sm"><i class="ti ti-download me-1"></i>Export CSV</a>
        @endif
    </div>
</div>

@include('statistics._nav')

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}"></div>
            <div class="col-md-3"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="ti ti-filter me-1"></i>Apply</button></div>
            <div class="col-md-3"><a href="{{ route('admin.statistics.'.$key) }}" class="btn btn-outline-secondary w-100">Reset</a></div>
        </div>
    </div>
</form>

{{-- KPI cards --}}
<div class="row g-3 mb-3">
    @foreach($kpis as $kpi)
        <div class="col-6 col-xl-3">
            @php($card = '<div class="card h-100 border-start border-'.($kpi['color'] ?? 'primary').' border-3"><div class="card-body py-3"><div class="small text-muted">'.e($kpi['label']).'</div><div class="h3 mb-0 text-dark">'.e($fmt($kpi['value'], $kpi['format'] ?? 'number')).'</div></div></div>')
            @if(!empty($kpi['route']) && Route::has($kpi['route']))
                <a href="{{ route($kpi['route'], $filters) }}" class="text-decoration-none">{!! $card !!}</a>
            @else
                {!! $card !!}
            @endif
        </div>
    @endforeach
</div>

{{-- Charts --}}
@if(!empty($charts))
<div class="row g-3 mb-3">
    @foreach($charts as $chart)
        <div class="col-xl-{{ $chart['type'] === 'line' ? 12 : 6 }}">
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
@endif

{{-- Ranked / grouped lists with drill-down --}}
<div class="row g-3">
    @foreach($lists as $list)
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">{{ $list['title'] }}</h6>
                    @if(!empty($list['drilldown']) && Route::has($list['drilldown']['route']))
                        <a href="{{ route($list['drilldown']['route'], $list['drilldown']['params'] ?? $filters) }}" class="btn btn-sm btn-outline-primary">{{ $list['drilldown']['label'] ?? 'Details' }}</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="bg-light"><tr>@foreach($list['columns'] as $i => $col)<th class="{{ $i > 0 ? 'text-end' : '' }}">{{ $col }}</th>@endforeach</tr></thead>
                            <tbody>
                                @forelse($list['rows'] as $row)
                                    <tr>@foreach($row['cells'] as $i => $cell)<td class="{{ $i > 0 ? 'text-end' : '' }}">{{ $cell }}</td>@endforeach</tr>
                                @empty
                                    <tr><td colspan="{{ count($list['columns']) }}" class="text-center text-muted py-4">No data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
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
    const palette = ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6610f2','#fd7e14','#20c997','#6f42c1','#d63384','#495057','#212529'];

    charts.forEach(function (cfg) {
        const el = document.getElementById(cfg.id);
        if (!el || !cfg.labels || cfg.labels.length === 0 || typeof Chart === 'undefined') return;

        const isPie = cfg.type === 'doughnut';
        const baseColors = (cfg.colors && cfg.colors.length) ? cfg.colors : palette;
        const datasets = cfg.datasets.map(function (ds, idx) {
            return {
                label: ds.label,
                data: ds.data,
                backgroundColor: isPie ? cfg.labels.map((_, i) => baseColors[i % baseColors.length]) : baseColors[idx % baseColors.length],
                borderColor: cfg.type === 'line' ? baseColors[idx % baseColors.length] : undefined,
                fill: false,
                tension: 0.3,
                borderWidth: cfg.type === 'line' ? 2 : 1,
            };
        });

        new Chart(el, {
            type: cfg.type,
            data: { labels: cfg.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: isPie, position: 'bottom' } },
                scales: isPie ? {} : { y: { beginAtZero: true } },
            },
        });
    });
})();
</script>
@endpush
