{{-- Dispensing + stock control: prescription queue first, stock alerts prominent. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="ti ti-prescription me-1"></i>{{ __('departments.sections.dispensing_queue') }}</h6>
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
    </div>
</div>
