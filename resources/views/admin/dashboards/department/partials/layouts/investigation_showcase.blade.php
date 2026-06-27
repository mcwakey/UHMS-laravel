{{-- Investigation / diagnostic showcase — samples & requests pipeline first,
     request-status donut, turnaround trend. Bespoke per-type layout. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Sample / request pipeline as a rich avatar list. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>
    {{-- Request status donut (centre total). --}}
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['request_status_breakdown'] ?? null, 'theme' => $theme])
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-6'])
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-6">@include('admin.dashboards.department.partials.services-card', ['services' => $services])</div>
    <div class="col-xl-6">@include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])</div>
</div>

@include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
