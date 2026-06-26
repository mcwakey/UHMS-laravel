{{-- Imaging workbench: schedule/volume-first, imaging queue, radiology services side. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 text-muted text-uppercase small"><i class="ti ti-device-ipad-horizontal me-1"></i>{{ __('departments.sections.imaging_schedule') }}</h6>
        </div>
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['request_status_breakdown'] ?? [], 'theme' => $theme])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
    </div>
</div>
