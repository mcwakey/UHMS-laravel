{{-- Generic department layout (fallback): balanced KPI → chart → side panels. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')
@include('admin.dashboards.department.partials.layouts._secondary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        <div class="row g-3">
            <div class="col-lg-6">@include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['queue_status_breakdown'] ?? [], 'theme' => $theme])</div>
            <div class="col-lg-6">@include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['request_status_breakdown'] ?? [], 'theme' => $theme])</div>
        </div>
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
        @foreach($restricted ?? [] as $restrictedCard)
            @include('admin.dashboards.department.partials.restricted-card', ['card' => $restrictedCard])
        @endforeach
    </div>
</div>
