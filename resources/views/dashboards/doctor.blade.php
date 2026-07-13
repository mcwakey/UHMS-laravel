@extends('layouts.app')
@section('title', __('role_dashboards.doctor.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('role_dashboards.doctor.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($routeStatusColor = fn ($s) => match ((string) $s) {
    'ACTIVE' => 'primary', 'PAUSED' => 'warning', default => 'info',
})
@php($priorityColor = fn ($p) => match (strtolower((string) $p)) {
    'emergency', 'critical', 'high' => 'danger', 'urgent' => 'warning', default => 'secondary',
})
@php($wait = function (?int $minutes) {
    if ($minutes === null) return '—';
    if ($minutes < 60) return $minutes.'m';
    if ($minutes < 1440) return intdiv($minutes, 60).'h '.($minutes % 60).'m';
    return intdiv($minutes, 1440).'d';
})

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
        @unless(auth()->id() === $doctor->id)
            <span class="text-muted small">{{ $t('viewing_as') }} <strong>{{ $doctor->name }}</strong></span>
        @endunless
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.consultations.index') }}" class="btn btn-primary"><i class="ti ti-stethoscope me-1"></i>{{ $t('open_workbench') }}</a>
        <a href="{{ route('admin.appointments.index') }}" class="btn btn-outline-dark"><i class="ti ti-calendar-cog me-1"></i>{{ $t('my_appointments') }}</a>
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row: queue (live) + 3 sparkline cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card border rounded-2 shadow-sm h-100 mb-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <p class="mb-1 text-muted small">{{ $t('patients_waiting') }}</p>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="fw-bold mb-0">{{ number_format($kpis['waiting']['value']) }}</h3>
                            <span class="badge badge-soft-warning rounded-pill fs-10"><i class="ti ti-point-filled"></i>{{ $tc('live') }}</span>
                        </div>
                    </div>
                    <span class="avatar avatar-lg bg-warning rounded-circle flex-shrink-0"><i class="ti ti-users fs-24"></i></span>
                </div>
                <p class="mb-0 mt-2 text-muted small">{{ $t('in_consultation_now', ['count' => $kpis['waiting']['active']]) }}</p>
            </div>
        </div>
    </div>
    @foreach([
        'consultations' => ['label' => $t('consultations_today'), 'caption' => $t('completed_encounters'), 'icon' => 'ti-stethoscope', 'color' => 'primary'],
        'results' => ['label' => $t('results_to_review'), 'caption' => $t('ready_for_review'), 'icon' => 'ti-flask', 'color' => 'danger'],
        'prescriptions' => ['label' => $t('prescriptions_today'), 'caption' => $t('written_today'), 'icon' => 'ti-prescription', 'color' => 'success'],
    ] as $key => $meta)
        <div class="col-xl-3 col-md-6">
            <div class="card border rounded-2 shadow-sm h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between mb-1">
                        <div>
                            <p class="mb-1 text-muted small">{{ $meta['label'] }}</p>
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="fw-bold mb-0">{{ number_format($kpis[$key]['value']) }}</h3>
                                @if(($kpis[$key]['trend'] ?? null) !== null)
                                    <span class="badge badge-soft-{{ $kpis[$key]['trend'] >= 0 ? 'success' : 'danger' }} rounded-pill fs-10">{{ $kpis[$key]['trend'] >= 0 ? '+' : '' }}{{ $kpis[$key]['trend'] }}%</span>
                                @endif
                            </div>
                        </div>
                        <span class="avatar avatar-md border rounded-2 text-{{ $meta['color'] }}"><i class="ti {{ $meta['icon'] }} fs-20"></i></span>
                    </div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div id="spark_{{ $key }}"></div>
                        <span class="text-muted small text-nowrap ms-2">{{ $meta['caption'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Next patient hero + clinical activity --}}
<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('next_patient') }}</h5>
                @if($nextPatient)
                    <span class="badge badge-soft-{{ $nextPatient['route']->status === 'PENDING' ? 'info' : 'primary' }}">
                        {{ $nextPatient['route']->status === 'PENDING' ? $t('up_next') : $t('in_progress') }}
                    </span>
                @endif
            </div>
            <div class="card-body">
                @if($nextPatient)
                    @php($routeModel = $nextPatient['route'])
                    <div class="d-flex align-items-center gap-2 mb-3">
                        @include('dashboards.partials._avatar', ['name' => $routeModel->patient?->full_name, 'color' => 'primary', 'size' => 'lg'])
                        <div>
                            <span class="fw-semibold d-block">{{ $routeModel->patient?->full_name }}</span>
                            <span class="text-muted small">{{ $routeModel->visit?->visit_number ?? $routeModel->patient?->patient_number }}</span>
                        </div>
                    </div>
                    <p class="fw-semibold mb-1">{{ \Illuminate\Support\Str::limit($routeModel->visit?->chief_complaint ?: $t('chief_complaint'), 70) }}</p>
                    <p class="text-muted small mb-3">
                        <i class="ti ti-building-hospital me-1"></i>{{ $routeModel->department?->name ?? '—' }}
                        <span class="badge badge-soft-{{ $priorityColor($routeModel->visit?->priority?->value) }} ms-2">{{ ucfirst((string) ($routeModel->visit?->priority?->value ?? 'normal')) }}</span>
                    </p>
                    <div class="d-flex align-items-center justify-content-between border rounded-2 px-3 py-2 mb-3 bg-light">
                        <span class="text-muted small">{{ $t('waiting_for') }}</span>
                        <span class="fw-bold">{{ $wait($nextPatient['waiting_minutes']) }}</span>
                    </div>
                    <p class="text-muted small mb-2">{{ $t('latest_vitals') }}</p>
                    @if($nextPatient['vitals'])
                        @php($v = $nextPatient['vitals'])
                        <div class="row g-2 mb-3 text-center">
                            @foreach([
                                ['ti-heartbeat', $v->heart_rate !== null ? $v->heart_rate.' bpm' : '—', 'danger'],
                                ['ti-activity', $v->blood_pressure_systolic ? $v->blood_pressure_systolic.'/'.$v->blood_pressure_diastolic : '—', 'primary'],
                                ['ti-temperature', $v->temperature !== null ? $v->temperature.'°C' : '—', 'warning'],
                                ['ti-lungs', $v->spo2 !== null ? $v->spo2.'%' : '—', 'info'],
                            ] as [$icon, $value, $color])
                                <div class="col-3">
                                    <div class="border rounded-2 py-2">
                                        <i class="ti {{ $icon }} text-{{ $color }} d-block"></i>
                                        <span class="small fw-semibold">{{ $value }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-3">{{ $t('no_vitals') }}</p>
                    @endif
                    <a href="{{ route('admin.consultations.index') }}" class="btn btn-primary w-100">
                        {{ $routeModel->status === 'PENDING' ? $t('start_consultation') : $t('resume_consultation') }}
                    </a>
                @else
                    <div class="text-center text-muted my-5">
                        <i class="ti ti-mood-smile fs-1 d-block mb-2 text-success"></i>
                        {{ $t('queue_empty') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('clinical_activity') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-primary"></i>{{ $t('consultations_completed') }}</span>
                    <span><i class="ti ti-point-filled text-success"></i>{{ $t('lab_requests_ordered') }}</span>
                </div>
            </div>
            <div class="card-body"><div id="activityChart"></div></div>
        </div>
    </div>
</div>

{{-- Mini stats --}}
<div class="row g-3 mb-4">
    @foreach([
        'patients' => ['label' => $t('total_patients'), 'icon' => 'ti-user-heart', 'color' => 'primary'],
        'consults_week' => ['label' => $t('consults_this_week'), 'icon' => 'ti-stethoscope', 'color' => 'success'],
        'avg_duration' => ['label' => $t('avg_consult_time'), 'icon' => 'ti-clock-play', 'color' => 'info'],
        'labs_week' => ['label' => $t('labs_this_week'), 'icon' => 'ti-flask', 'color' => 'warning'],
        'rx_week' => ['label' => $t('rx_this_week'), 'icon' => 'ti-prescription', 'color' => 'danger'],
        'appointments_today' => ['label' => $t('appointments_today'), 'icon' => 'ti-calendar-heart', 'color' => 'dark'],
    ] as $key => $meta)
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card border rounded-2 shadow-sm h-100 mb-0">
                <div class="card-body">
                    <span class="avatar avatar-md bg-{{ $meta['color'] }} rounded-2 mb-2"><i class="ti {{ $meta['icon'] }} fs-20"></i></span>
                    <p class="mb-1 text-muted small text-truncate">{{ $meta['label'] }}</p>
                    <h4 class="fw-bold mb-0">{{ is_numeric($miniStats[$key]['value']) ? number_format($miniStats[$key]['value']) : $miniStats[$key]['value'] }}</h4>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Consultation queue + results to review --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('my_consultation_queue') }}</h5>
                <span class="badge badge-soft-success"><i class="ti ti-point-filled"></i>{{ $tc('live') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>#</th><th>{{ $tc('patient') }}</th><th>{{ $t('complaint') }}</th><th>{{ $t('priority') }}</th><th>{{ $t('waiting') }}</th><th>{{ $tc('status') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($queue as $entry)
                            @php($since = $entry->visit?->checked_in_at ?? $entry->created_at)
                            <tr>
                                <td class="text-muted">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('dashboards.partials._avatar', ['name' => $entry->patient?->full_name, 'color' => $entry->status === 'ACTIVE' ? 'primary' : 'info'])
                                        <div>
                                            <span class="fw-semibold d-block">{{ $entry->patient?->full_name }}</span>
                                            <span class="text-muted small">{{ $entry->visit?->visit_number ?? $entry->patient?->patient_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted">{{ \Illuminate\Support\Str::limit($entry->visit?->chief_complaint ?? '—', 34) }}</td>
                                <td><span class="badge badge-soft-{{ $priorityColor($entry->visit?->priority?->value) }}">{{ ucfirst((string) ($entry->visit?->priority?->value ?? 'normal')) }}</span></td>
                                <td class="fw-semibold">{{ $since ? $wait((int) \Illuminate\Support\Carbon::parse($since)->diffInMinutes(now())) : '—' }}</td>
                                <td><span class="badge badge-soft-{{ $routeStatusColor($entry->status) }}">{{ $t('queue_statuses.'.$entry->status) }}</span></td>
                                <td class="text-end pe-3"><a href="{{ route('admin.consultations.index') }}" class="btn btn-sm {{ $entry->status === 'PENDING' ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $t('open') }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">{{ $t('queue_empty') }}</td></tr>
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
                <h5 class="fw-bold mb-0">{{ $t('results_panel') }}</h5>
                <span class="badge badge-soft-danger">{{ $resultsToReview->count() }}</span>
            </div>
            <div class="card-body">
                @forelse($resultsToReview as $request)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-md bg-soft-danger text-danger rounded-2"><i class="ti ti-flask"></i></span>
                            <div>
                                <span class="fw-semibold d-block">{{ $request->patient?->full_name }}</span>
                                <span class="text-muted small">{{ $request->request_number }} · {{ $t('tests_count', ['count' => $request->items_count]) }}</span>
                            </div>
                        </div>
                        <span class="badge bg-success">{{ $t('ready') }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_results') }}</p>
                @endforelse
                <a href="{{ route('admin.lab.results.index') }}" class="btn btn-light w-100 mt-2">{{ $t('view_all_results') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- Appointments (demoted) + outcomes donut + recent prescriptions --}}
<div class="row g-3">
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('todays_schedule') }}</h5>
                <span class="badge bg-light text-dark border">{{ $tc('today') }}</span>
            </div>
            <div class="card-body">
                @forelse($todaySchedule as $slot)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $slot->patient?->full_name }}</span>
                            <span class="text-muted small">{{ ucfirst(str_replace('_', ' ', (string) ($slot->consultation_mode?->value ?? 'in_person'))) }}</span>
                        </div>
                        <span class="text-muted small text-nowrap"><i class="ti ti-clock me-1"></i>{{ $slot->start_time ? \Illuminate\Support\Carbon::parse($slot->start_time)->format('h:i A') : '—' }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_schedule_today') }}</p>
                @endforelse
                <a href="{{ route('admin.appointments.index') }}" class="btn btn-light w-100 mt-3">{{ $tc('view_full_schedule') }}</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('consultation_outcomes') }}</h5>
                <span class="badge bg-light text-dark border">{{ $tc('weekly') }}</span>
            </div>
            <div class="card-body">
                <div id="outcomesChart"></div>
                <div class="d-flex justify-content-around text-center mt-2">
                    <div><i class="ti ti-point-filled text-success"></i><span class="small d-block text-muted">{{ $t('completed') }}</span><span class="fw-bold">{{ $outcomes['completed'] }}</span></div>
                    <div><i class="ti ti-point-filled text-primary"></i><span class="small d-block text-muted">{{ $t('in_progress') }}</span><span class="fw-bold">{{ $outcomes['in_progress'] }}</span></div>
                    <div><i class="ti ti-point-filled text-warning"></i><span class="small d-block text-muted">{{ $t('waiting_label') }}</span><span class="fw-bold">{{ $outcomes['waiting'] }}</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('recent_prescriptions') }}</h5></div>
            <div class="card-body">
                @forelse($recentPrescriptions as $prescription)
                    @php($rxStatus = strtolower((string) ($prescription->status?->value ?? $prescription->status ?? 'pending')))
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}">
                        <div class="d-flex align-items-center gap-2">
                            @include('dashboards.partials._avatar', ['name' => $prescription->patient?->full_name, 'color' => 'success'])
                            <div>
                                <span class="fw-semibold d-block">{{ $prescription->patient?->full_name }}</span>
                                <span class="text-muted small">{{ $prescription->prescription_number }} · {{ $t('items_count', ['count' => $prescription->items_count]) }}</span>
                            </div>
                        </div>
                        <span class="badge badge-soft-{{ in_array($rxStatus, ['dispensed', 'completed'], true) ? 'success' : ($rxStatus === 'cancelled' ? 'danger' : 'warning') }}">{{ ucfirst(str_replace('_', ' ', $rxStatus)) }}</span>
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
    const sparks = {
        consultations: { data: @json($kpis['consultations']['spark']), color: '#3538CD' },
        results: { data: @json($kpis['results']['spark']), color: '#E04F16' },
        prescriptions: { data: @json($kpis['prescriptions']['spark']), color: '#0E9384' },
    };
    Object.entries(sparks).forEach(([key, cfg]) => {
        new ApexCharts(document.querySelector('#spark_' + key), {
            chart: { type: 'bar', height: 40, width: 120, sparkline: { enabled: true } },
            series: [{ data: cfg.data }],
            colors: [cfg.color],
            plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
            tooltip: { enabled: false },
        }).render();
    });

    new ApexCharts(document.querySelector('#activityChart'), {
        chart: { height: 340, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('role_dashboards.doctor.consultations_completed')), type: 'column', data: @json($activity['consultations']) },
            { name: @json(__('role_dashboards.doctor.lab_requests_ordered')), type: 'area', data: @json($activity['labs']) },
        ],
        xaxis: { categories: @json($activity['labels']), tickAmount: 7 },
        colors: ['#3538CD', '#0E9384'],
        stroke: { curve: 'smooth', width: [0, 2] },
        plotOptions: { bar: { columnWidth: '35%', borderRadius: 3 } },
        fill: { type: ['solid', 'gradient'], gradient: { opacityFrom: 0.2, opacityTo: 0.02 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    new ApexCharts(document.querySelector('#outcomesChart'), {
        chart: { type: 'donut', height: 240, fontFamily: 'inherit' },
        series: @json(array_values($outcomes)),
        labels: [@json(__('role_dashboards.doctor.completed')), @json(__('role_dashboards.doctor.in_progress')), @json(__('role_dashboards.doctor.waiting_label'))],
        colors: ['#0E9384', '#3538CD', '#F7C325'],
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '78%' } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
