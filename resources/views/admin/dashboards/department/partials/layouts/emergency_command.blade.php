{{-- Emergency command center: alert-first, triage/queue priority, rapid actions. --}}
@php
    $critical = collect($primary_cards)->firstWhere('key', 'critical_cases')['value'] ?? null;
    $active = collect($primary_cards)->firstWhere('key', 'active_cases')['value'] ?? null;
@endphp
@if(is_numeric($critical) || is_numeric($active))
<div class="alert alert-danger d-flex align-items-center justify-content-between gap-2 mb-3">
    <span class="fw-semibold"><i class="ti ti-urgent me-1"></i>{{ __('departments.sections.priority_alerts') }}</span>
    <span class="d-flex gap-3">
        @if(is_numeric($active))<span>{{ __('departments.sections.triage_status') }}: <strong>{{ $active }}</strong></span>@endif
        @if(is_numeric($critical))<span class="badge bg-white text-danger">{{ $critical }}</span>@endif
    </span>
</div>
@endif

@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        <div class="row g-3">
            <div class="col-lg-6">@include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['queue_status_breakdown'] ?? [], 'theme' => $theme])</div>
            <div class="col-lg-6">@include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])</div>
        </div>
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
    </div>
</div>
