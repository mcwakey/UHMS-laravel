@extends('layouts.app')
@section('title', __('role_dashboards.receptionist.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('role_dashboards.receptionist.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($statusColor = fn ($s) => match ((string) $s) {
    'checked_in', 'completed', 'confirmed' => 'success',
    'scheduled' => 'info',
    'in_progress', 'serving' => 'primary',
    'waiting' => 'warning',
    'cancelled', 'no_show' => 'danger',
    default => 'secondary',
})

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        @can('appointments.create')
            <a href="{{ route('admin.appointments.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ $t('new_appointment') }}</a>
        @endcan
        @can('patients.create')
            <a href="{{ route('admin.patients.create') }}" class="btn btn-outline-dark"><i class="ti ti-user-plus me-1"></i>{{ $t('register_patient') }}</a>
        @endcan
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('todays_appointments'), 'value' => $kpis['appointments']['value'], 'trend' => $kpis['appointments']['trend'], 'icon' => 'ti-calendar-heart', 'color' => 'primary', 'caption' => $t('scheduled_for_today')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('patients_checked_in'), 'value' => $kpis['checked_in']['value'], 'trend' => $kpis['checked_in']['trend'], 'icon' => 'ti-user-check', 'color' => 'success', 'caption' => $t('so_far_today')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('waiting_in_queue'), 'value' => $kpis['waiting']['value'], 'badge' => $tc('live'), 'badgeColor' => 'warning', 'icon' => 'ti-list-numbers', 'color' => 'warning', 'caption' => $t('currently_in_queue')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('pending_requests'), 'value' => $kpis['pending_requests']['value'], 'icon' => 'ti-clock-exclamation', 'color' => 'danger', 'caption' => $t('appointment_requests')])
    </div>
</div>

{{-- Footfall + channel donut --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('daily_footfall') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-primary"></i>{{ $t('checked_in') }}</span>
                    <span><i class="ti ti-point-filled text-success"></i>{{ $t('completed') }}</span>
                </div>
            </div>
            <div class="card-body"><div id="footfallChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('appointments_by_channel') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="channelChart"></div></div>
        </div>
    </div>
</div>

{{-- Today's appointments + live queue --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('todays_appointments') }}</h5>
                <span class="badge bg-light text-dark border">{{ $tc('today') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $tc('patient') }}</th><th>{{ $tc('doctor') }}</th><th>{{ $tc('time') }}</th><th>{{ $tc('status') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($todaysAppointments as $appointment)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('dashboards.partials._avatar', ['name' => $appointment->patient?->full_name, 'color' => 'primary'])
                                        <div>
                                            <span class="fw-semibold d-block">{{ $appointment->patient?->full_name }}</span>
                                            <span class="text-muted small">{{ $appointment->patient?->patient_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $appointment->doctor?->name ?? '—' }}</td>
                                <td>{{ $appointment->start_time ? \Illuminate\Support\Carbon::parse($appointment->start_time)->format('h:i A') : '—' }}</td>
                                <td><span class="badge badge-soft-{{ $statusColor($appointment->status?->value) }}">{{ $tc('statuses.'.($appointment->status?->value ?? 'scheduled')) }}</span></td>
                                <td class="text-end pe-3"><a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view') }}</a></td>
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
                <h5 class="fw-bold mb-0">{{ $t('live_queue') }}</h5>
                <span class="badge badge-soft-success"><i class="ti ti-point-filled"></i>{{ $tc('live') }}</span>
            </div>
            <div class="card-body">
                @forelse($liveQueue as $entry)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $t('token') }} #{{ str_pad((string) $entry->queue_number, 3, '0', STR_PAD_LEFT) }}</span>
                            <span class="text-muted small">{{ $entry->visit?->patient?->full_name ?? '—' }}</span>
                        </div>
                        <span class="badge badge-soft-{{ $statusColor($entry->status) }}">{{ $tc('statuses.'.$entry->status) }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $tc('no_data') }}</p>
                @endforelse
                <a href="{{ route('admin.visits.index') }}" class="btn btn-light w-100 mt-2">{{ $tc('view_full_queue') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- Payments + appointment requests --}}
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('payment_collection') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="paymentChart"></div></div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('requests_title') }}</h5>
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $tc('patient') }}</th><th>{{ $t('preferred_doctor') }}</th><th>{{ $t('requested_time') }}</th><th>{{ $t('channel') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($appointmentRequests as $request)
                            <tr>
                                <td class="fw-semibold">{{ $request->patient?->full_name }}</td>
                                <td>{{ $request->doctor?->name ?? '—' }}</td>
                                <td>
                                    {{ $request->appointment_date?->isToday() ? $tc('today') : $request->appointment_date?->format('d M') }},
                                    {{ $request->start_time ? \Illuminate\Support\Carbon::parse($request->start_time)->format('h:i A') : '' }}
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ ucfirst(str_replace('_', ' ', (string) ($request->consultation_mode?->value ?? 'in_person'))) }}</span></td>
                                <td class="text-end pe-3"><a href="{{ route('admin.appointments.index') }}" class="btn btn-sm btn-primary">{{ $tc('view') }}</a></td>
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
</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#footfallChart'), {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('role_dashboards.receptionist.checked_in')), data: @json($footfall['checked_in']) },
            { name: @json(__('role_dashboards.receptionist.completed')), data: @json($footfall['completed']) },
        ],
        xaxis: { categories: @json($footfall['labels']), axisBorder: { show: false } },
        colors: ['#3538CD', '#0E9384'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.25, opacityTo: 0.02 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const channels = @json($channels);
    new ApexCharts(document.querySelector('#channelChart'), {
        chart: { type: 'donut', height: 280, fontFamily: 'inherit' },
        series: Object.values(channels),
        labels: Object.keys(channels),
        colors: ['#3538CD', '#0E9384', '#98A2B3', '#E04F16', '#F7C325'],
        legend: { position: 'bottom' },
        dataLabels: { formatter: (v) => Math.round(v) + '%' },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: {
            show: true, label: @json(__('role_dashboards.common.total')),
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0),
        } } } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();

    const payments = @json($payments['methods']);
    new ApexCharts(document.querySelector('#paymentChart'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: Object.values(payments),
        labels: Object.keys(payments),
        colors: ['#0E9384', '#3538CD', '#F7C325', '#E04F16', '#98A2B3'],
        legend: { position: 'bottom' },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '74%', labels: { show: true, total: {
            show: true, label: @json(__('role_dashboards.receptionist.collected')),
            formatter: () => @json(number_format($payments['total'], 2)),
        } } } } },
        tooltip: { y: { formatter: (v) => Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
