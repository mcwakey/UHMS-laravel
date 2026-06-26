{{-- Surgery board: pre-op → in-progress → post-op schedule emphasis. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')
@include('admin.dashboards.department.partials.layouts._secondary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="ti ti-clipboard-heart me-1"></i>{{ __('departments.sections.surgery_schedule') }}</h6>
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
    </div>
</div>
