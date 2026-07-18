@extends('layouts.app')
@section('title', __('investigations.dashboard.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('investigations.dashboard.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($statusColor = fn ($s) => match ((string) $s) {
    'completed' => 'success',
    'processing' => 'info',
    'cancelled' => 'danger',
    default => 'warning',
})
@php($urgencyColor = fn ($u) => match ((string) $u) {
    'emergency' => 'danger',
    'urgent' => 'warning',
    default => 'secondary',
})

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        @can('lab.requests.view')
            <a href="{{ route('investigations.lab.requests.index') }}" class="btn btn-primary"><i class="ti ti-test-pipe me-1"></i>{{ $t('open_requests') }}</a>
        @endcan
        @can('lab.samples.view')
            <a href="{{ route('investigations.lab.samples.index') }}" class="btn btn-outline-dark"><i class="ti ti-droplet me-1"></i>{{ $t('open_specimens') }}</a>
        @endcan
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('pending_requests'), 'value' => $kpis['pending']['value'], 'icon' => 'ti-hourglass-high', 'color' => 'warning', 'caption' => $t('awaiting_acceptance')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('in_process_requests'), 'value' => $kpis['processing']['value'], 'icon' => 'ti-microscope', 'color' => 'info', 'caption' => $t('results_in_progress')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('completed_today'), 'value' => $kpis['completed_today']['value'], 'icon' => 'ti-report-medical', 'color' => 'success', 'caption' => $t('resulted_and_closed')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('abnormal_today'), 'value' => $kpis['abnormal_today']['value'], 'icon' => 'ti-alert-triangle', 'color' => 'danger', 'caption' => $t('flagged_results')])
    </div>
</div>

{{-- Volume trend + specimen status --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('volume_trend') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-primary"></i>{{ $t('received') }}</span>
                    <span><i class="ti ti-point-filled text-success"></i>{{ $t('completed') }}</span>
                </div>
            </div>
            <div class="card-body"><div id="volumeChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('specimen_status') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="specimenChart"></div></div>
        </div>
    </div>
</div>

{{-- Worklist + verification queue --}}
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('active_worklist') }}</h5>
                @can('lab.requests.view')
                    <a href="{{ route('investigations.lab.requests.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $tc('patient') }}</th><th>{{ $t('tests') }}</th><th>{{ $t('urgency') }}</th><th>{{ $tc('status') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($recentRequests as $request)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('dashboards.partials._avatar', ['name' => $request->patient?->full_name ?? $request->external_party_name, 'color' => 'primary'])
                                        <div>
                                            <span class="fw-semibold d-block">{{ $request->patient?->full_name ?? $request->external_party_name ?? '—' }}</span>
                                            <span class="text-muted small">{{ $request->request_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $request->items->first()?->name ?? '—' }}@if($request->items->count() > 1) <span class="text-muted small">+{{ $request->items->count() - 1 }}</span>@endif</td>
                                <td><span class="badge badge-soft-{{ $urgencyColor($request->urgency) }}">{{ __('lab.'.$request->urgency) }}</span></td>
                                <td><span class="badge badge-soft-{{ $statusColor($request->status) }}">{{ ucfirst((string) $request->status) }}</span></td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('investigations.lab.requests.show', $request) }}" class="btn btn-sm {{ $request->status === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">
                                        {{ $request->status === 'pending' ? $t('process') : $tc('view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ $tc('no_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('awaiting_verification_title') }}</h5>
                @can('lab.results.view')
                    <a href="{{ route('investigations.lab.results.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body">
                @forelse($awaitingVerification as $result)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $result->requestItem?->name ?? $result->labRequest?->request_number }}</span>
                            <span class="text-muted small">{{ $result->labRequest?->patient?->full_name ?? '—' }}</span>
                        </div>
                        @if($result->is_abnormal)
                            <span class="badge badge-soft-danger">{{ $t('abnormal') }}</span>
                        @else
                            <span class="badge badge-soft-warning">{{ $t('unverified') }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_unverified_results') }}</p>
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
    new ApexCharts(document.querySelector('#volumeChart'), {
        chart: { height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('investigations.dashboard.received')), type: 'column', data: @json($volumeTrend['received']) },
            { name: @json(__('investigations.dashboard.completed')), type: 'line', data: @json($volumeTrend['completed']) },
        ],
        xaxis: { categories: @json($volumeTrend['labels']) },
        colors: ['#3538CD', '#0E9384'],
        stroke: { curve: 'smooth', width: [0, 2] },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const specimenStatuses = @json($sampleStatuses);
    new ApexCharts(document.querySelector('#specimenChart'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: Object.values(specimenStatuses),
        labels: Object.keys(specimenStatuses),
        colors: ['#F7C325', '#3538CD', '#0E9384', '#E91E63', '#98A2B3'],
        legend: { position: 'bottom' },
        dataLabels: { formatter: (v) => Math.round(v) + '%' },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: {
            show: true, label: @json(__('investigations.dashboard.total_specimens')),
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
        } } } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
