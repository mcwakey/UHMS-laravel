{{-- Emergency showcase — alert-first triage command view (Preclinic style).
     Bespoke per-type layout; reuses shared partials. --}}
@php
    $critical = collect($primary_cards)->firstWhere('key', 'critical_cases')['value'] ?? null;
    $active = collect($primary_cards)->firstWhere('key', 'active_cases')['value'] ?? null;
@endphp

{{-- Priority alert banner. --}}
@if(is_numeric($critical) || is_numeric($active))
<div class="card border-0 shadow-sm mb-3 department-emergency-alert">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 text-white">
        <div class="d-flex align-items-center gap-3">
            <span class="avatar avatar-lg rounded-circle bg-white text-danger flex-shrink-0"><i class="ti ti-urgent fs-28"></i></span>
            <div>
                <div class="text-uppercase small fw-semibold opacity-75">{{ __('departments.sections.priority_alerts') }}</div>
                <h5 class="mb-0 fw-bold">{{ __('departments.sections.triage_status') }}</h5>
            </div>
        </div>
        <div class="d-flex gap-4 text-center">
            @if(is_numeric($active))
                <div><div class="h3 fw-bold mb-0">{{ $active }}</div><div class="small opacity-75">{{ __('dashboards.department.metrics.active_cases') }}</div></div>
            @endif
            @if(is_numeric($critical))
                <div><div class="h3 fw-bold mb-0">{{ $critical }}</div><div class="small opacity-75">{{ __('dashboards.department.metrics.critical_cases') }}</div></div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- KPI row: active cases, critical cases, visits today (sparkline), revenue. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Left: live triage / emergency queue as a rich avatar list. --}}
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>

    {{-- Right: activity trend (gradient area) + mid-row stat tiles. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-xl-6 col-6'])
    </div>
</div>

<div class="row g-3">
    {{-- Triage status donut (centre total). --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['queue_status_breakdown'] ?? null, 'theme' => $theme])
    </div>
    {{-- Rapid actions. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
    </div>
    {{-- Department services. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
    </div>
</div>

{{-- Recent department activity. --}}
@include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])

@once
@push('styles')
<style>
    .department-emergency-alert { background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); }
</style>
@endpush
@endonce
