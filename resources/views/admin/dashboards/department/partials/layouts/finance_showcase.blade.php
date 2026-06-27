{{-- Finance showcase — revenue trend first, transactions list, restricted cards.
     Shared by billing / accounting / claims (different personality, same shape). --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Revenue / activity trend (gradient area). --}}
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-6'])
    </div>
</div>

<div class="row g-3">
    {{-- Transactions / financial queue as a rich list. --}}
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
    </div>
</div>

@include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
