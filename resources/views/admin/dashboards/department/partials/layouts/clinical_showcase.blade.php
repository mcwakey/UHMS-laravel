{{-- Consultation showcase — fully-realised Preclinic-style clinical command view.
     Reference layout for per-type bespoke dashboards; reuses shared partials. --}}

{{-- KPI row: value + delta badge + sparkline. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Left: live patient queue as a rich avatar list. --}}
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>

    {{-- Right: appointments trend (gradient area) + mid-row stat tiles. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-xl-4 col-6'])
    </div>
</div>

<div class="row g-3">
    {{-- Status donut with centre total. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['queue_status_breakdown'] ?? null, 'theme' => $theme])
    </div>
    {{-- Department services. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
    </div>
    {{-- Clinical quick actions. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
    </div>
</div>

{{-- Recent department activity. --}}
@include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
