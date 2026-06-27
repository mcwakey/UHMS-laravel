{{-- Records / reception showcase — folder requests & activity first. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Folder requests / activity as a rich list. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
    </div>
</div>

@include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-xl-4 col-6'])

<div class="row g-3">
    <div class="col-xl-4">@include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['queue_status_breakdown'] ?? null, 'theme' => $theme])</div>
    <div class="col-xl-4">@include('admin.dashboards.department.partials.services-card', ['services' => $services])</div>
    <div class="col-xl-4">@include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])</div>
</div>

@include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
