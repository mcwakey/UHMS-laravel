@extends('layouts.app')
@section('title', __('journey.worklist.title'))

@php
    $tabs = [
        'my_actions'     => __('journey.handoff.tab_my_actions'),
        'owed_by'        => __('journey.handoff.tab_owed_by'),
        'owed_to'        => __('journey.handoff.tab_owed_to'),
        'sla_breaches'   => __('journey.handoff.tab_sla_breaches'),
        'assigned_to_me' => __('journey.handoff.tab_assigned'),
    ];
    if (!empty($canOversight)) {
        $tabs['oversight'] = __('journey.handoff.tab_oversight');
    }
    $isHandoffTab = $tab !== 'my_actions';
@endphp

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="ti ti-list-check me-1"></i>{{ __('journey.worklist.title') }}</h4>
        <small class="text-muted">{{ __('journey.worklist.subtitle') }}</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if(!empty($refreshConfig['enabled']))
            <span class="badge bg-success-subtle text-success"><i class="ti ti-broadcast me-1"></i>{{ __('journey.handoff.live_refresh') }}</span>
        @endif
        <span class="small text-muted">{{ __('journey.handoff.last_refreshed') }}: <span id="journey-last-refreshed">{{ now()->isoFormat('HH:mm') }}</span></span>
        <a href="{{ request()->fullUrl() }}" class="btn btn-outline-secondary btn-sm" data-manual-refresh><i class="ti ti-refresh me-1"></i>{{ __('journey.handoff.refresh') }}</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger py-2">{{ session('error') }}</div>@endif

{{-- Tabs --}}
<ul class="nav nav-tabs mb-3">
    @foreach($tabs as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ request()->fullUrlWithQuery(['tab' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>

{{-- Filters --}}
<form method="GET" class="card shadow-sm mb-3">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">{{ __('journey.worklist.col_severity') }}</label>
            <select name="severity" class="form-select form-select-sm">
                <option value="">{{ __('journey.worklist.all_severities') }}</option>
                <option value="critical" @selected(($filters['severity'] ?? null) === 'critical')>{{ __('journey.status.critical') }}</option>
                <option value="delayed" @selected(($filters['severity'] ?? null) === 'delayed')>{{ __('journey.status.delayed') }}</option>
            </select>
        </div>
        @if($isHandoffTab)
            <div>
                <label class="form-label small mb-1">{{ __('journey.sla.label') }}</label>
                <select name="sla_status" class="form-select form-select-sm">
                    <option value="">{{ __('journey.worklist.all_statuses') }}</option>
                    @foreach(['critical_breach', 'breached', 'near_breach'] as $s)
                        <option value="{{ $s }}" @selected(($filters['sla_status'] ?? null) === $s)>{{ __('journey.sla.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">{{ __('journey.assignment.col_status') }}</label>
                <select name="assignment_status" class="form-select form-select-sm">
                    <option value="">{{ __('journey.assignment.all_statuses') }}</option>
                    @foreach(['unassigned', 'assigned', 'acknowledged'] as $s)
                        <option value="{{ $s }}" @selected(($filters['assignment_status'] ?? null) === $s)>{{ __('journey.assignment.status.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label small mb-1">{{ __('journey.escalation.label') }}</label>
                <select name="escalation_level" class="form-select form-select-sm">
                    <option value="">{{ __('journey.escalation.all_levels') }}</option>
                    @foreach(['critical', 'supervisor', 'warning'] as $s)
                        <option value="{{ $s }}" @selected(($filters['escalation_level'] ?? null) === $s)>{{ __('journey.escalation.'.$s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-check mb-1">
                <input type="checkbox" class="form-check-input" id="mine_only" name="mine_only" value="1" @checked(!empty($filters['mine_only']))>
                <label class="form-check-label small" for="mine_only">{{ __('journey.assignment.mine_only') }}</label>
            </div>
            <div class="form-check mb-1">
                <input type="checkbox" class="form-check-input" id="unassigned_only" name="unassigned_only" value="1" @checked(!empty($filters['unassigned_only']))>
                <label class="form-check-label small" for="unassigned_only">{{ __('journey.assignment.unassigned_only') }}</label>
            </div>
            @if(!empty($canPredict))
                <div>
                    <label class="form-label small mb-1">{{ __('journey.risk.score') }}</label>
                    <select name="risk_level" class="form-select form-select-sm">
                        <option value="">{{ __('journey.risk.all_levels') }}</option>
                        @foreach(['critical', 'high', 'medium', 'low'] as $rl)
                            <option value="{{ $rl }}" @selected(($filters['risk_level'] ?? null) === $rl)>{{ __('journey.risk.level.'.$rl) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-check mb-1">
                    <input type="checkbox" class="form-check-input" id="sort_risk" name="sort" value="risk" @checked(request('sort') === 'risk')>
                    <label class="form-check-label small" for="sort_risk">{{ __('journey.risk.col_risk') }} ↓</label>
                </div>
            @endif
        @else
            <div>
                <label class="form-label small mb-1">{{ __('journey.action_status.open') }}</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('journey.worklist.all_statuses') }}</option>
                    @foreach(['actionable', 'open', 'blocked'] as $st)
                        <option value="{{ $st }}" @selected(($filters['status'] ?? null) === $st)>{{ __('journey.action_status.'.$st) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label class="form-label small mb-1">{{ __('journey.worklist.col_cause') }}</label>
            <select name="cause" class="form-select form-select-sm" style="max-width: 200px;">
                <option value="">{{ __('journey.worklist.all_causes') }}</option>
                @foreach($causes as $cause)
                    <option value="{{ $cause->value }}" @selected(($filters['cause'] ?? null) === $cause)>{{ $cause->translatedLabel() }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">{{ __('journey.worklist.apply') }}</button>
        <a href="{{ route('admin.journey.worklist', ['tab' => $tab]) }}" class="btn btn-outline-secondary btn-sm">{{ __('journey.worklist.reset') }}</a>
    </div>
</form>

{{-- Live-refreshable content --}}
<div id="journey-worklist"
     data-refresh-url="{{ route('admin.journey.worklist.refresh', request()->query()) }}"
     data-interval="{{ (int) ($refreshConfig['interval_seconds'] ?? 60) }}"
     data-enabled="{{ !empty($refreshConfig['enabled']) ? '1' : '0' }}">
    @include('admin.journey.partials.worklist-refresh')
</div>

<script>
(function () {
    var el = document.getElementById('journey-worklist');
    if (!el) return;
    var stamp = document.getElementById('journey-last-refreshed');

    function busy() {
        var a = document.activeElement;
        return a && el.contains(a) && /^(SELECT|INPUT|TEXTAREA|BUTTON)$/.test(a.tagName);
    }
    function refresh() {
        if (document.hidden || busy()) return Promise.resolve();
        return fetch(el.dataset.refreshUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
            .then(function (html) { el.innerHTML = html; if (stamp) { stamp.textContent = new Date().toLocaleTimeString(); } })
            .catch(function () { /* graceful: keep last data, manual button remains */ });
    }

    var btn = document.querySelector('[data-manual-refresh]');
    if (btn) { btn.addEventListener('click', function (e) { e.preventDefault(); refresh(); }); }

    if (el.dataset.enabled === '1') {
        var seconds = parseInt(el.dataset.interval || '60', 10);
        if (isNaN(seconds) || seconds < 15) { seconds = 60; }
        setInterval(refresh, seconds * 1000);
    }
})();
</script>
@endsection
