@extends('layouts.app')
@section('title', __('maternity.labor_episode'))
@section('content')
<x-page-header :title="__('maternity.labor_episode')" :subtitle="$episode->patient?->full_name" icon="ti-baby-carriage">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.labor.index') }}" class="btn btn-outline-secondary btn-md fs-13"><i class="ti ti-arrow-left me-1"></i>{{ __('common.back') }}</a>
        @can('maternity.labor.update')
        <a href="{{ route('admin.maternity.labor.edit', $episode) }}" class="btn btn-primary btn-md fs-13"><i class="ti ti-edit me-1"></i>{{ __('common.edit') }}</a>
        @endcan
    </x-slot:actions>
</x-page-header>

@if(! empty($laborOverview['warnings']) || ! empty($riskAssessment['warnings']))
<div class="alert alert-warning">
    <strong><i class="ti ti-alert-triangle me-1"></i>{{ __('maternity.risk_assessment') }}</strong>
    <ul class="mb-0 mt-1">
        @foreach(array_unique(array_merge($laborOverview['warnings'], $riskAssessment['warnings'])) as $warning)<li>{{ $warning }}</li>@endforeach
    </ul>
</div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"><h5 class="card-title mb-0">{{ __('maternity.labor_episode') }}</h5><span class="badge bg-{{ $episode->status?->color() ?? 'secondary' }}">{{ $episode->status?->label() }}</span></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.patient') }}</small><strong>{{ $episode->patient?->full_name }}</strong><div class="small text-muted">{{ $episode->patient?->patient_number }}</div></div>
                    <div class="col-12">
                        @include('partials.visit-insurance-strip', ['visit' => $episode->visit ?? $episode->admission?->visit])
                    </div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.labor_stage') }}</small><strong>{{ $episode->labor_stage?->label() }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.risk_level') }}</small><span class="badge badge-soft-{{ $episode->risk_level?->color() ?? 'secondary' }}">{{ $episode->risk_level?->label() ?? __('common.none') }}</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.started_at') }}</small><strong>{{ $episode->started_at?->format('d M Y H:i') ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.membranes_status') }}</small><strong>{{ $episode->membranes_status?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.liquor_colour') }}</small><strong>{{ $episode->liquor_colour?->label() ?? __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.presentation') }}</small><strong>{{ $episode->presentation ?: __('common.none') }}</strong></div>
                    <div class="col-md-3"><small class="text-muted d-block">{{ __('maternity.linked_admission') }}</small><strong>{{ $episode->admission?->admission_number ?? __('common.none') }}</strong>@if($episode->admission?->bed)<div class="small text-muted">{{ $episode->admission->bed?->ward?->name }} / {{ $episode->admission->bed?->bed_number }}</div>@endif</div>
                    <div class="col-md-6"><small class="text-muted d-block">{{ __('maternity.initial_assessment') }}</small>{{ $episode->initial_assessment ?: __('common.none') }}</div>
                    <div class="col-md-6"><small class="text-muted d-block">{{ __('maternity.clinical_summary') }}</small>{{ $episode->clinical_summary ?: __('common.none') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-chart-line me-1"></i>{{ __('maternity.labor_observations') }}</h5>
                @can('maternity.labor.observe')
                <a href="{{ route('admin.maternity.labor.observations.create', $episode) }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('maternity.record_observation') }}</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light"><tr><th>{{ __('maternity.observed_at') }}</th><th>{{ __('maternity.labor_stage') }}</th><th>{{ __('maternity.cervical_dilation') }}</th><th>{{ __('maternity.fetal_heart_rate') }}</th><th>{{ __('maternity.blood_pressure') }}</th><th>{{ __('maternity.danger_signs') }}</th><th>{{ __('maternity.status') }}</th><th></th></tr></thead>
                        <tbody>
                            @forelse($observations as $observation)
                            <tr>
                                <td>{{ $observation->observed_at?->format('d M Y H:i') }}</td>
                                <td>{{ $observation->labor_stage?->label() ?? __('common.none') }}</td>
                                <td>{{ $observation->cervical_dilation_cm !== null ? $observation->cervical_dilation_cm.' cm' : __('common.none') }}</td>
                                <td>{{ $observation->fetal_heart_rate ?? __('common.none') }}</td>
                                <td>{{ $observation->blood_pressure_systolic && $observation->blood_pressure_diastolic ? $observation->blood_pressure_systolic.'/'.$observation->blood_pressure_diastolic : __('common.none') }}</td>
                                <td><span class="badge bg-{{ count($observation->danger_signs ?? []) ? 'danger' : 'secondary' }}">{{ count($observation->danger_signs ?? []) }}</span></td>
                                <td><span class="badge bg-{{ $observation->status?->color() ?? 'secondary' }}">{{ $observation->status?->label() }}</span></td>
                                <td class="text-end"><a href="{{ route('admin.maternity.labor.observations.show', $observation) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="8"><x-empty-state icon="ti-chart-line" :title="__('maternity.no_observations_yet')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($observations->hasPages())<div class="card-footer">{{ $observations->links() }}</div>@endif
        </div>

        <div class="card mb-3" id="partographCard">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-chart-dots me-1"></i>{{ __('maternity.partograph') }}</h5>
                @if($partograph['has_data'])<small class="text-muted">{{ __('maternity.partograph_since', ['time' => $partograph['started_at']]) }}</small>@endif
            </div>
            <div class="card-body">
                @if($partograph['has_data'])
                    <p class="text-muted small mb-3"><i class="ti ti-info-circle me-1"></i>{{ __('maternity.partograph_axis_note') }}</p>
                    <div class="mb-1 fw-semibold small text-muted">{{ __('maternity.fetal_heart_rate') }}</div>
                    <div id="partoFhr"></div>
                    <div class="mb-1 mt-3 fw-semibold small text-muted">{{ __('maternity.partograph_cervicograph') }}</div>
                    <div id="partoCervix"></div>
                    <div class="mb-1 mt-3 fw-semibold small text-muted">{{ __('maternity.contractions_per_10_min') }}</div>
                    <div id="partoContractions"></div>
                    <div class="mb-1 mt-3 fw-semibold small text-muted">{{ __('maternity.partograph_maternal_vitals') }}</div>
                    <div id="partoVitals"></div>
                @else
                    <div class="alert alert-info mb-0"><i class="ti ti-chart-dots me-1"></i>{{ __('maternity.partograph_no_data') }}</div>
                @endif
            </div>
        </div>
        @include('maternity.partials.billing-preview')
    </div>

    <div class="col-xl-4">
        @can('maternity.labor.update')
        <form method="POST" action="{{ route('admin.maternity.labor.stage', $episode) }}" class="card mb-3">
            @csrf
            @method('PATCH')
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.labor_stage') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><select name="labor_stage" class="form-select">@foreach(\App\Enums\LaborStage::cases() as $stage)<option value="{{ $stage->value }}" @selected($episode->labor_stage?->value === $stage->value)>{{ $stage->label() }}</option>@endforeach</select></div>
                <select name="status" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach(\App\Enums\LaborEpisodeStatus::cases() as $status)<option value="{{ $status->value }}" @selected($episode->status?->value === $status->value)>{{ $status->label() }}</option>@endforeach</select>
            </div>
            <div class="card-footer text-end"><button class="btn btn-primary">{{ __('common.update') }}</button></div>
        </form>
        @endcan

        @can('maternity.delivery.record')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.delivery_record') }}</h5></div>
            <div class="card-body">
                @forelse($episode->deliveryRecords as $record)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2"><span>{{ $record->delivery_at?->format('d M Y H:i') ?? __('common.none') }} <span class="badge bg-{{ $record->status?->color() }}">{{ $record->status?->label() }}</span></span><a href="{{ route('admin.maternity.deliveries.show', $record) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a></div>
                @empty
                <p class="text-muted mb-0">{{ __('common.none') }}</p>
                @endforelse
            </div>
            <div class="card-footer text-end"><a href="{{ route('admin.maternity.labor.delivery.create', $episode) }}" class="btn btn-success"><i class="ti ti-plus me-1"></i>{{ __('maternity.create_delivery_record') }}</a></div>
        </div>
        @endcan

        @can('maternity.newborn.view')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-baby-bottle me-1"></i>{{ __('maternity.newborn_records') }}</h5></div>
            <div class="card-body">
                @forelse($laborOverview['newborn_records'] as $newborn)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span>{{ __('maternity.birth_order') }} {{ $newborn->birth_order }} · {{ $newborn->outcome?->label() ?? __('common.none') }}</span>
                    <a href="{{ route('admin.maternity.newborns.show', $newborn) }}" class="btn btn-sm btn-outline-primary">{{ __('common.view') }}</a>
                </div>
                @empty
                <p class="text-muted mb-0">{{ __('maternity.no_newborn_records_yet') }}</p>
                @endforelse
            </div>
        </div>
        @endcan

        @can('maternity.labor.escalate')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.theatre_escalation') }} / {{ __('maternity.emergency_escalation') }}</h5></div>
            <div class="card-body text-muted">{{ __('maternity.escalation_hooks_deferred') }}</div>
            <div class="card-footer d-flex gap-2 justify-content-end">
                <form method="POST" action="{{ route('admin.maternity.labor.theatre-escalation', $episode) }}">@csrf<button class="btn btn-outline-warning">{{ __('maternity.theatre_escalation') }}</button></form>
                <form method="POST" action="{{ route('admin.maternity.labor.emergency-escalation', $episode) }}">@csrf<button class="btn btn-outline-danger">{{ __('maternity.emergency_escalation') }}</button></form>
            </div>
        </div>
        @endcan

        @can('maternity.labor.admission.request')
        @unless($episode->admission_id)
        <form method="POST" action="{{ route('admin.maternity.labor.admission-request', $episode) }}" class="card mb-3">
            @csrf
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.create_admission_request') }}</h5></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label">{{ __('maternity.requested_ward') }}</label><select name="requested_ward_id" class="form-select"><option value="">{{ __('common.none') }}</option>@foreach($wards as $ward)<option value="{{ $ward->id }}">{{ $ward->name }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.priority') }}</label><input name="priority" class="form-control" value="urgent"></div>
                <div class="mb-3"><label class="form-label">{{ __('maternity.provisional_diagnosis') }}</label><input name="provisional_diagnosis" class="form-control" value="{{ __('maternity.labor_default_admission_diagnosis') }}"></div>
                <label class="form-label">{{ __('maternity.clinical_summary') }}</label><textarea name="clinical_summary" rows="3" class="form-control">{{ $episode->clinical_summary }}</textarea>
            </div>
            <div class="card-footer text-end"><button class="btn btn-warning">{{ __('maternity.request_admission') }}</button></div>
        </form>
        @endunless
        @endcan

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">{{ __('maternity.future_workflows') }}</h5></div>
            <div class="card-body">
                <div class="mb-2"><i class="ti ti-baby-bottle me-1"></i>{{ __('maternity.newborn_records_placeholder') }}</div>
                <div><i class="ti ti-heart-handshake me-1"></i>{{ __('maternity.postnatal_transition_placeholder') }}</div>
            </div>
        </div>
    </div>
</div>

@if($partograph['has_data'])
@push('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') return;

    var parto = @json($partograph);
    var maxHour = parto.max_hour || 12;
    var series = parto.series || {};

    var sharedX = {
        type: 'numeric',
        min: 0,
        max: maxHour,
        tickAmount: Math.min(maxHour, 24),
        title: { text: @json(__('maternity.partograph_hours_axis')) },
        labels: { formatter: function (v) { return Math.round(v); } },
    };

    function baseChart(height) {
        return {
            chart: { height: height, group: 'partograph', fontFamily: 'inherit', toolbar: { show: false }, animations: { enabled: false } },
            grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
            stroke: { curve: 'straight', width: 2 },
            markers: { size: 4 },
            dataLabels: { enabled: false },
            xaxis: sharedX,
            tooltip: { x: { formatter: function (v) { return v + ' ' + @json(__('maternity.partograph_hour_short')); } } },
            legend: { position: 'top', horizontalAlign: 'left' },
        };
    }

    // Fetal heart rate ────────────────────────────────────────────────
    if ((series.fhr || []).length) {
        new ApexCharts(document.querySelector('#partoFhr'), Object.assign(baseChart(220), {
            series: [{ name: @json(__('maternity.fetal_heart_rate')), data: series.fhr }],
            colors: ['#7C3AED'],
            yaxis: { min: 80, max: 200, tickAmount: 6, title: { text: 'bpm' } },
            annotations: { yaxis: [
                { y: 110, y2: 160, fillColor: '#D1FAE5', opacity: 0.4, label: { text: @json(__('maternity.partograph_fhr_normal')), style: { fontSize: '10px' } } },
            ] },
        })).render();
    }

    // Cervicograph: dilation + descent + WHO alert/action lines ────────
    var cervixSeries = [];
    if ((series.dilation || []).length) {
        cervixSeries.push({ name: @json(__('maternity.cervical_dilation')), type: 'line', data: series.dilation });
    }
    if (parto.alert_line) {
        cervixSeries.push({ name: @json(__('maternity.partograph_alert_line')), type: 'line', data: parto.alert_line });
    }
    if (parto.action_line) {
        cervixSeries.push({ name: @json(__('maternity.partograph_action_line')), type: 'line', data: parto.action_line });
    }
    if ((series.descent || []).length) {
        cervixSeries.push({ name: @json(__('maternity.partograph_descent')), type: 'line', data: series.descent });
    }
    if (cervixSeries.length) {
        var hasDescent = (series.descent || []).length > 0;
        var cervixColors = [];
        if ((series.dilation || []).length) cervixColors.push('#2563EB');
        if (parto.alert_line) cervixColors.push('#F59E0B');
        if (parto.action_line) cervixColors.push('#DC2626');
        if (hasDescent) cervixColors.push('#0E9384');

        var dashArray = cervixSeries.map(function (s) {
            return (s.name === @json(__('maternity.partograph_alert_line')) || s.name === @json(__('maternity.partograph_action_line'))) ? 6
                : (s.name === @json(__('maternity.partograph_descent')) ? 4 : 0);
        });

        var yAxes = [{ seriesName: @json(__('maternity.cervical_dilation')), min: 0, max: 10, tickAmount: 5, title: { text: @json(__('maternity.partograph_dilation_axis')) } }];
        // alert & action lines share the dilation (cm) axis
        if (parto.alert_line) yAxes.push({ seriesName: @json(__('maternity.cervical_dilation')), show: false, min: 0, max: 10 });
        if (parto.action_line) yAxes.push({ seriesName: @json(__('maternity.cervical_dilation')), show: false, min: 0, max: 10 });
        if (hasDescent) yAxes.push({ seriesName: @json(__('maternity.partograph_descent')), opposite: true, reversed: true, min: 0, max: 5, tickAmount: 5, title: { text: @json(__('maternity.partograph_descent_axis')) } });

        new ApexCharts(document.querySelector('#partoCervix'), Object.assign(baseChart(300), {
            series: cervixSeries,
            colors: cervixColors,
            stroke: { curve: 'straight', width: 2, dashArray: dashArray },
            markers: { size: dashArray.map(function (d) { return d === 0 ? 5 : 0; }) },
            yaxis: yAxes,
        })).render();
    }

    // Contractions per 10 min ─────────────────────────────────────────
    if ((series.contractions || []).length) {
        new ApexCharts(document.querySelector('#partoContractions'), Object.assign(baseChart(200), {
            chart: Object.assign(baseChart(200).chart, { type: 'bar' }),
            series: [{ name: @json(__('maternity.contractions_per_10_min')), data: series.contractions }],
            colors: ['#3538CD'],
            plotOptions: { bar: { columnWidth: '35%', borderRadius: 3 } },
            markers: { size: 0 },
            yaxis: { min: 0, max: 5, tickAmount: 5, title: { text: '/10 min' } },
        })).render();
    }

    // Maternal vitals: BP, pulse, temperature ─────────────────────────
    var vitalsSeries = [];
    if ((series.systolic || []).length) vitalsSeries.push({ name: @json(__('maternity.bp_systolic')), type: 'line', data: series.systolic });
    if ((series.diastolic || []).length) vitalsSeries.push({ name: @json(__('maternity.bp_diastolic')), type: 'line', data: series.diastolic });
    if ((series.pulse || []).length) vitalsSeries.push({ name: @json(__('maternity.maternal_pulse')), type: 'line', data: series.pulse });
    if ((series.temperature || []).length) vitalsSeries.push({ name: @json(__('maternity.temperature')), type: 'line', data: series.temperature });
    if (vitalsSeries.length) {
        var vitalsColors = [];
        if ((series.systolic || []).length) vitalsColors.push('#DC2626');
        if ((series.diastolic || []).length) vitalsColors.push('#2563EB');
        if ((series.pulse || []).length) vitalsColors.push('#0E9384');
        if ((series.temperature || []).length) vitalsColors.push('#F59E0B');

        var vitalsY = [{ seriesName: [@json(__('maternity.bp_systolic')), @json(__('maternity.bp_diastolic')), @json(__('maternity.maternal_pulse'))], min: 40, max: 200, tickAmount: 8, title: { text: 'mmHg / bpm' } }];
        if ((series.temperature || []).length) vitalsY.push({ seriesName: @json(__('maternity.temperature')), opposite: true, min: 34, max: 42, tickAmount: 8, title: { text: '°C' } });

        new ApexCharts(document.querySelector('#partoVitals'), Object.assign(baseChart(240), {
            series: vitalsSeries,
            colors: vitalsColors,
            yaxis: vitalsY,
        })).render();
    }
});
</script>
@endpush
@endif

{{-- Phase 14R.5.1 — explicit Emergency escalation (dark by default). --}}
@include('maternity.partials.emergency-handoff-panel', [
    'actions' => $emergencyHandoffActions ?? [],
])

@endsection
