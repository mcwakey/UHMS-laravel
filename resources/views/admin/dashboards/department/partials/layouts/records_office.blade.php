{{-- Records office: folder/file requests and recent activity emphasis. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="ti ti-folders me-1"></i>{{ __('departments.sections.folder_requests') }}</h6>
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
    </div>
</div>
