{{-- Diagnostic workbench: request pipeline + results, services/stock in side column. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 text-muted text-uppercase small"><i class="ti ti-flask me-1"></i>{{ __('departments.sections.samples') }}</h6>
        </div>
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        <div class="row g-3">
            <div class="col-lg-6">@include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['request_status_breakdown'] ?? [], 'theme' => $theme])</div>
            <div class="col-lg-6">@include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])</div>
        </div>
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
    </div>
</div>
