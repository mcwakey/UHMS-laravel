@extends('layouts.app')
@section('title', __('journey.analytics.title'))

@php
    use Illuminate\Support\Str;
    $fmtMin = fn ($m) => intdiv((int) $m, 60).'h '.((int) $m % 60).'m';
    $causeLabel = fn ($c) => \App\Enums\JourneyDelayCause::tryFrom($c)?->translatedLabel() ?? Str::headline($c);
    $deptLabel = fn ($t) => $t ? Str::headline($t) : '—';
    $dir = fn ($d) => $d === 'up' ? 'ti-arrow-up-right' : ($d === 'down' ? 'ti-arrow-down-right' : 'ti-minus');
    $trendMax = collect($trend)->max('handoff_count') ?: 1;
    $causeMax = collect($causes)->max('handoff_count') ?: 1;
@endphp

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="ti ti-chart-histogram me-1"></i>{{ __('journey.analytics.title') }}</h4>
        <small class="text-muted">{{ $isOversight ? __('journey.analytics.scope_hospital') : __('journey.analytics.scope_department') }}
            · {{ $filters['date_from'] }} → {{ $filters['date_to'] }}</small>
    </div>
    <a href="{{ route('admin.journey.analytics.export', array_merge(request()->query(), ['dataset' => 'matrix'])) }}" class="btn btn-outline-secondary btn-sm">
        <i class="ti ti-download me-1"></i>{{ __('journey.analytics.export') }}
    </a>
</div>

{{-- Filters --}}
<form method="GET" class="card shadow-sm mb-3"><div class="card-body d-flex flex-wrap gap-2 align-items-end">
    <div><label class="form-label small mb-1">{{ __('journey.analytics.date_from') }}</label>
        <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm"></div>
    <div><label class="form-label small mb-1">{{ __('journey.analytics.date_to') }}</label>
        <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm"></div>
    <div><label class="form-label small mb-1">{{ __('journey.worklist.col_cause') }}</label>
        <select name="cause" class="form-select form-select-sm" style="max-width: 200px;">
            <option value="">{{ __('journey.worklist.all_causes') }}</option>
            @foreach($causeOptions as $c)
                <option value="{{ $c->value }}" @selected(($filters['cause'] ?? null) === $c->value)>{{ $c->translatedLabel() }}</option>
            @endforeach
        </select></div>
    <button type="submit" class="btn btn-primary btn-sm">{{ __('journey.worklist.apply') }}</button>
    <a href="{{ route('admin.journey.analytics') }}" class="btn btn-outline-secondary btn-sm">{{ __('journey.worklist.reset') }}</a>
</div></form>

@if($summary['handoff_volume'] === 0 && empty($summary['resolved_count']))
    <div class="card shadow-sm"><div class="card-body text-center text-muted py-5">
        <i class="ti ti-chart-dots fs-1 d-block mb-2"></i>{{ __('journey.analytics.no_data') }}
    </div></div>
@else
{{-- Summary cards --}}
<div class="row g-2 mb-3">
    @php
        $cards = [
            ['handoff_volume', $summary['handoff_volume'], 'primary', 'count'],
            ['breach_rate', $summary['breach_rate'], 'danger', 'percent'],
            ['critical_breaches', $summary['critical_breaches'], 'danger', 'count'],
            ['time_to_acknowledge_avg', $summary['time_to_acknowledge_avg'], 'info', 'minutes'],
            ['time_to_resolve_avg', $summary['time_to_resolve_avg'], 'info', 'minutes'],
            ['resolution_rate', $summary['resolution_rate'], 'success', 'percent'],
            ['unassigned_count', $summary['unassigned_count'], 'warning', 'count'],
        ];
    @endphp
    @foreach($cards as [$key, $value, $variant, $fmt])
        <div class="col-6 col-md"><div class="card border-start border-{{ $variant }} border-3 shadow-sm h-100"><div class="card-body py-2">
            <div class="text-muted small text-uppercase">{{ __('journey.analytics.metric.'.$key) }}</div>
            <div class="h5 fw-bold mb-0 text-{{ $variant }}">
                @if($fmt === 'percent'){{ $value }}%@elseif($fmt === 'minutes'){{ $fmtMin($value) }}@else{{ $value }}@endif
            </div>
            @if(isset($comparison[$key]))
                <div class="small text-muted"><i class="ti {{ $dir($comparison[$key]['direction']) }}"></i>
                    @if($comparison[$key]['delta_pct'] !== null){{ abs($comparison[$key]['delta_pct']) }}%@else{{ __('journey.analytics.not_enough_data') }}@endif
                </div>
            @endif
        </div></div></div>
    @endforeach
</div>

{{-- Risk forecast (Phase 9.9) — live, aggregate-only, clearly an estimate --}}
@if(!empty($forecast) && (($forecast['likely'] ?? 0) + ($forecast['critical'] ?? 0)) > 0)
    <div class="card shadow-sm mb-3 border-start border-warning border-3">
        <div class="card-header py-2 fw-bold d-flex align-items-center gap-2">
            <i class="ti ti-chart-dots"></i>{{ __('journey.risk.forecast') }}
            <span class="badge bg-secondary-subtle text-secondary fw-normal">{{ __('journey.risk.estimates_note') }}</span>
        </div>
        <div class="card-body row g-2">
            <div class="col-6 col-md-3"><div class="text-muted small text-uppercase">{{ __('journey.risk.cards.likely_breaches') }}</div><div class="h5 fw-bold mb-0 text-warning">{{ $forecast['likely'] }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small text-uppercase">{{ __('journey.risk.cards.high_risk') }}</div><div class="h5 fw-bold mb-0">{{ $forecast['high'] }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small text-uppercase">{{ __('journey.risk.cards.critical_risk') }}</div><div class="h5 fw-bold mb-0 text-danger">{{ $forecast['critical'] }}</div></div>
            <div class="col-6 col-md-3"><div class="text-muted small text-uppercase">{{ __('journey.risk.cards.avg_remaining') }}</div><div class="h5 fw-bold mb-0">{{ intdiv((int) $forecast['avg_remaining'], 60) }}h {{ (int) $forecast['avg_remaining'] % 60 }}m</div></div>
            @if($forecast['top_cause'])
                <div class="col-12"><span class="text-muted small">{{ __('journey.risk.cards.top_risk_cause') }}:</span>
                    <strong>{{ \App\Enums\JourneyDelayCause::tryFrom($forecast['top_cause'])?->translatedLabel() ?? $forecast['top_cause'] }}</strong>
                    @if($forecast['top_department']) · <span class="text-muted small">{{ __('journey.risk.cards.top_risk_department') }}:</span> <strong>{{ Str::headline($forecast['top_department']) }}</strong>@endif
                </div>
            @endif
        </div>
    </div>
@endif

{{-- Prediction accuracy (Phase 9.10) — aggregate-only, gated by journey.predictions.view --}}
@if(!empty($canPredict) && !empty($accuracy))
    <div class="card shadow-sm mb-3">
        <div class="card-header py-2 fw-bold"><i class="ti ti-target-arrow me-1"></i>{{ __('journey.accuracy.title') }}</div>
        <div class="card-body">
            @if(empty($accuracy['has_data']))
                <div class="text-muted text-center py-3">{{ __('journey.accuracy.no_data') }}</div>
            @else
                <div class="row g-2 mb-2">
                    @php $accCards = [['precision', $accuracy['precision'], 'success', 'percent'], ['recall', $accuracy['recall'], 'info', 'percent'], ['false_alarm_rate', $accuracy['false_alarm_rate'], 'warning', 'percent'], ['miss_rate', $accuracy['miss_rate'], 'danger', 'percent'], ['eta_error', $accuracy['eta_error_avg'], 'secondary', 'minutes'], ['overall', $accuracy['overall_accuracy'], 'primary', 'percent']]; @endphp
                    @foreach($accCards as [$k, $v, $variant, $fmt])
                        @php $cmpKey = $k === 'eta_error' ? 'eta_error_avg' : ($k === 'overall' ? null : $k); @endphp
                        <div class="col-6 col-md-2">
                            <div class="text-muted small text-uppercase">{{ __('journey.accuracy.'.$k) }}</div>
                            <div class="h6 fw-bold mb-0 text-{{ $variant }}">
                                @if($v === null)—@elseif($fmt === 'percent'){{ $v }}%@else{{ $fmtMin((int) $v) }}@endif
                                @if($cmpKey && isset($accuracyComparison[$cmpKey]))<i class="ti {{ $dir($accuracyComparison[$cmpKey]['direction']) }} small text-muted"></i>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="small text-muted">{{ __('journey.accuracy.evaluated', ['count' => $accuracy['evaluated']]) }} · TP {{ $accuracy['true_positive'] }} · FP {{ $accuracy['false_positive'] }} · FN {{ $accuracy['false_negative'] }}</div>
                @if(!empty($worstPaths))
                    <div class="mt-2"><div class="fw-bold small mb-1">{{ __('journey.accuracy.worst_paths') }}</div>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach(array_slice($worstPaths, 0, 6) as $wp)
                                <span class="badge bg-light text-dark border">{{ Str::headline($wp['from']) }} → {{ Str::headline($wp['to']) }} · {{ __('journey.accuracy.errors') }} {{ $wp['errors'] }}/{{ $wp['evaluated'] }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endif

<div class="row g-3">
    {{-- SLA trend --}}
    <div class="col-lg-7"><div class="card shadow-sm h-100">
        <div class="card-header py-2 fw-bold">{{ __('journey.analytics.sla_trend') }}</div>
        <div class="card-body">
            @forelse($trend as $point)
                <div class="d-flex align-items-center gap-2 mb-1 small">
                    <span class="text-muted" style="width: 90px;">{{ $point['date'] }}</span>
                    <div class="progress flex-grow-1" style="height: 14px;">
                        <div class="progress-bar bg-primary" style="width: {{ round($point['handoff_count'] / $trendMax * 100) }}%"></div>
                        <div class="progress-bar bg-danger" style="width: {{ round($point['breached_count'] / $trendMax * 100) }}%"></div>
                    </div>
                    <span style="width: 60px;" class="text-end">{{ $point['handoff_count'] }}</span>
                </div>
            @empty
                <div class="text-muted text-center py-3">{{ __('journey.analytics.no_data') }}</div>
            @endforelse
        </div>
    </div></div>

    {{-- Top causes --}}
    <div class="col-lg-5"><div class="card shadow-sm h-100">
        <div class="card-header py-2 fw-bold">{{ __('journey.analytics.top_causes') }}</div>
        <div class="card-body">
            @forelse(array_slice($causes, 0, 6) as $c)
                <div class="d-flex align-items-center gap-2 mb-1 small">
                    <a href="{{ route('admin.journey.worklist', ['tab' => 'sla_breaches', 'cause' => $c['cause']]) }}" style="width: 150px;" class="text-truncate text-decoration-none" title="{{ __('journey.analytics.matrix') }}">{{ $causeLabel($c['cause']) }}</a>
                    <div class="progress flex-grow-1" style="height: 14px;">
                        <div class="progress-bar bg-warning" style="width: {{ round($c['handoff_count'] / $causeMax * 100) }}%"></div>
                    </div>
                    <span style="width: 50px;" class="text-end">{{ $c['handoff_count'] }}</span>
                </div>
            @empty
                <div class="text-muted text-center py-3">{{ __('journey.analytics.no_data') }}</div>
            @endforelse
        </div>
    </div></div>

    {{-- Blocking departments --}}
    <div class="col-lg-6"><div class="card shadow-sm h-100">
        <div class="card-header py-2 fw-bold">{{ __('journey.analytics.top_blocking') }}</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>{{ __('journey.handoff.col_to') }}</th><th class="text-end">{{ __('journey.worklist.total') }}</th><th class="text-end">{{ __('journey.sla.breached') }}</th><th class="text-end">{{ __('journey.analytics.metric.breach_rate') }}</th></tr></thead>
            <tbody>
                @forelse(array_slice($blocking, 0, 8) as $row)
                    <tr><td>{{ $deptLabel($row['type']) }}</td><td class="text-end">{{ $row['handoff_count'] }}</td><td class="text-end text-danger">{{ $row['breached_count'] }}</td><td class="text-end">{{ $row['breach_rate'] }}%</td></tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">{{ __('journey.analytics.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div></div>

    {{-- Waiting departments --}}
    <div class="col-lg-6"><div class="card shadow-sm h-100">
        <div class="card-header py-2 fw-bold">{{ __('journey.analytics.top_waiting') }}</div>
        <div class="table-responsive"><table class="table table-sm mb-0">
            <thead><tr><th>{{ __('journey.handoff.col_from') }}</th><th class="text-end">{{ __('journey.worklist.total') }}</th><th class="text-end">{{ __('journey.analytics.metric.avg_wait_minutes') }}</th></tr></thead>
            <tbody>
                @forelse(array_slice($waiting, 0, 8) as $row)
                    <tr><td>{{ $deptLabel($row['type']) }}</td><td class="text-end">{{ $row['handoff_count'] }}</td><td class="text-end">{{ $fmtMin($row['avg_wait']) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-3">{{ __('journey.analytics.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div></div>

    {{-- Handoff matrix --}}
    <div class="col-12"><div class="card shadow-sm">
        <div class="card-header py-2 fw-bold"><i class="ti ti-arrows-transfer-down me-1"></i>{{ __('journey.analytics.matrix') }}</div>
        <div class="table-responsive"><table class="table table-sm table-hover mb-0">
            <thead><tr>
                <th>{{ __('journey.handoff.col_from') }}</th><th>{{ __('journey.handoff.col_to') }}</th>
                <th class="text-end">{{ __('journey.worklist.total') }}</th><th class="text-end">{{ __('journey.sla.breached') }}</th>
                <th class="text-end">{{ __('journey.escalation.critical') }}</th><th class="text-end">{{ __('journey.analytics.metric.breach_rate') }}</th>
                <th class="text-end">{{ __('journey.analytics.metric.avg_wait_minutes') }}</th><th class="text-end">{{ __('journey.analytics.metric.time_to_acknowledge_avg') }}</th>
                <th class="text-end">{{ __('journey.analytics.metric.time_to_resolve_avg') }}</th>
            </tr></thead>
            <tbody>
                @forelse(array_slice($matrix, 0, 25) as $row)
                    <tr>
                        <td>{{ $deptLabel($row['from']) }}</td><td><i class="ti ti-arrow-right text-muted me-1"></i>{{ $deptLabel($row['to']) }}</td>
                        <td class="text-end">{{ $row['handoff_count'] }}</td><td class="text-end text-danger">{{ $row['breached_count'] }}</td>
                        <td class="text-end">{{ $row['critical_count'] }}</td><td class="text-end">{{ $row['breach_rate'] }}%</td>
                        <td class="text-end">{{ $fmtMin($row['avg_wait']) }}</td><td class="text-end">{{ $row['avg_ack'] ? $fmtMin($row['avg_ack']) : '—' }}</td>
                        <td class="text-end">{{ $row['avg_resolve'] ? $fmtMin($row['avg_resolve']) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-3">{{ __('journey.analytics.no_data') }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div></div>
</div>
@endif
@endsection
