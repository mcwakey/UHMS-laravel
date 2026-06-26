{{-- Finance control room: money KPIs, revenue trend first, reconciliation table. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        <h6 class="fw-bold text-muted text-uppercase small mb-2"><i class="ti ti-cash-register me-1"></i>{{ __('departments.sections.cashier_sessions') }}</h6>
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-12'])
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @foreach($restricted ?? [] as $restrictedCard)
            @include('admin.dashboards.department.partials.restricted-card', ['card' => $restrictedCard])
        @endforeach
    </div>
</div>
