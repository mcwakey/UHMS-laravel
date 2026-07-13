@extends('layouts.app')
@section('title', __('role_dashboards.nurse.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('role_dashboards.nurse.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($vitalBadge = fn ($s) => match ($s) { 'critical' => 'danger', 'monitor' => 'warning', default => 'success' })

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.admissions.index') }}" class="btn btn-primary"><i class="ti ti-bed me-1"></i>{{ $t('view_admissions') }}</a>
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-dark"><i class="ti ti-list-numbers me-1"></i>{{ $tc('view_full_queue') }}</a>
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('patients_under_care'), 'value' => $kpis['under_care']['value'], 'trend' => null, 'icon' => 'ti-user-heart', 'color' => 'primary', 'caption' => $t('currently_assigned')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('critical_alerts'), 'value' => $kpis['critical']['value'], 'badge' => $tc('live'), 'badgeColor' => 'danger', 'icon' => 'ti-alert-triangle', 'color' => 'danger', 'caption' => $t('need_immediate_action')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('medications_due'), 'value' => $kpis['meds_due']['value'], 'badge' => $t('next_2h'), 'badgeColor' => 'warning', 'icon' => 'ti-pill', 'color' => 'warning', 'caption' => $t('scheduled_doses')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('vitals_recorded'), 'value' => $kpis['vitals_today']['value'], 'trend' => $kpis['vitals_today']['trend'], 'icon' => 'ti-device-heart-monitor', 'color' => 'success', 'caption' => $t('across_all_wards')])
    </div>
</div>

{{-- Vitals trend + ward occupancy --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('vitals_trend') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-danger"></i>{{ $t('heart_rate') }}</span>
                    <span><i class="ti ti-point-filled text-primary"></i>SpO2</span>
                </div>
            </div>
            <div class="card-body"><div id="vitalsChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('ward_occupancy') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="occupancyChart"></div></div>
        </div>
    </div>
</div>

{{-- Live vitals + medication schedule --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('live_patient_vitals') }}</h5>
                <span class="badge badge-soft-danger"><i class="ti ti-point-filled"></i>{{ $tc('live') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $tc('patient') }}</th><th>{{ $t('bed') }}</th><th>{{ $t('heart_rate') }}</th><th>{{ $t('blood_pressure') }}</th><th>SpO2</th><th>{{ $tc('status') }}</th>
                        </tr></thead>
                        <tbody>
                        @forelse($liveVitals as $row)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('dashboards.partials._avatar', ['name' => $row['patient']?->full_name, 'color' => $vitalBadge($row['status'])])
                                        <div>
                                            <span class="fw-semibold d-block">{{ $row['patient']?->full_name }}</span>
                                            <span class="text-muted small">{{ $row['ward'] ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $row['bed'] ?? '—' }}</td>
                                <td class="fw-semibold {{ $row['status'] === 'critical' ? 'text-danger' : ($row['status'] === 'monitor' ? 'text-warning' : '') }}">
                                    {{ $row['heart_rate'] !== null ? $row['heart_rate'].' bpm' : '—' }}
                                </td>
                                <td>{{ $row['bp'] ?? '—' }}</td>
                                <td>{{ $row['spo2'] !== null ? $row['spo2'].'%' : '—' }}</td>
                                <td><span class="badge bg-{{ $vitalBadge($row['status']) }}">{{ $t($row['status']) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ $t('no_admitted_vitals') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('medication_schedule') }}</h5></div>
            <div class="card-body">
                @forelse($medicationSchedule as $dose)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $dose->patient?->full_name }}</span>
                            <span class="text-muted small">{{ $dose->medicationOrder?->drug_name ?? '—' }}@if($dose->dose) — {{ $dose->dose }}{{ $dose->dose_unit }}@endif</span>
                        </div>
                        <span class="badge {{ $dose->scheduled_at?->isPast() ? 'bg-danger' : 'bg-primary' }}">{{ $dose->scheduled_at?->format('h:i A') }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_meds_due') }}</p>
                @endforelse
                <a href="{{ route('admin.admissions.index') }}" class="btn btn-light w-100 mt-2">{{ $tc('view_full_schedule') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- Weekly activity heatmap + handover notes --}}
<div class="row g-3">
    <div class="col-xl-7">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('weekly_ward_activity') }}</h5></div>
            <div class="card-body"><div id="activityHeatmap"></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('shift_handover_notes') }}</h5>
                <a href="{{ route('admin.admissions.index') }}" class="btn btn-sm btn-outline-secondary">{{ $t('add_note') }}</a>
            </div>
            <div class="card-body">
                @forelse($handoverNotes as $note)
                    <div class="d-flex gap-3 {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <span class="avatar avatar-md bg-soft-info text-info rounded-circle flex-shrink-0"><i class="ti ti-notes"></i></span>
                        <div>
                            <p class="mb-1">{{ \Illuminate\Support\Str::limit($note->note, 110) }}</p>
                            <span class="text-muted small">
                                {{ $t('handed_over_by') }} {{ $note->nurse?->name ?? '—' }}
                                <i class="ti ti-point-filled mx-1"></i>{{ $note->observed_at?->format('h:i A') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_notes') }}</p>
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
    new ApexCharts(document.querySelector('#vitalsChart'), {
        chart: { type: 'line', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('role_dashboards.nurse.heart_rate')), data: @json($vitalsTrend['heart_rate']) },
            { name: 'SpO2', data: @json($vitalsTrend['spo2']) },
        ],
        xaxis: { categories: @json($vitalsTrend['labels']) },
        colors: ['#EF4444', '#3538CD'],
        stroke: { curve: 'smooth', width: 2 },
        markers: { size: 0 },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const occupancy = @json($wardOccupancy);
    new ApexCharts(document.querySelector('#occupancyChart'), {
        chart: { type: 'radialBar', height: 320, fontFamily: 'inherit' },
        series: occupancy.wards.map(w => w.pct),
        labels: occupancy.wards.map(w => w.name),
        colors: ['#EF4444', '#3538CD', '#0E9384'],
        plotOptions: { radialBar: {
            hollow: { size: '42%' },
            track: { margin: 8 },
            dataLabels: {
                name: { fontSize: '13px' },
                value: { fontSize: '15px', formatter: (v) => v + '%' },
                total: { show: true, label: @json(__('role_dashboards.nurse.avg_occupancy')), formatter: () => occupancy.average + '%' },
            },
        } },
        legend: { show: true, position: 'bottom' },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();

    const heat = @json($weeklyActivity);
    const shiftNames = @json(__('role_dashboards.nurse.shifts'));
    new ApexCharts(document.querySelector('#activityHeatmap'), {
        chart: { type: 'heatmap', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        series: heat.series.map(s => ({ name: shiftNames[s.name] ?? s.name, data: s.data.map((v, i) => ({ x: heat.labels[i], y: v })) })),
        colors: ['#3538CD'],
        dataLabels: { enabled: false },
        plotOptions: { heatmap: { radius: 4, shadeIntensity: 0.6 } },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3' },
    }).render();
});
</script>
@endpush
