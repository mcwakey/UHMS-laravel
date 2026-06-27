{{-- Stores showcase — stock-alert donut + stock usage first, then movements. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Stock alert donut. --}}
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['stock_status_breakdown'] ?? null, 'theme' => $theme])
    </div>
    {{-- Stock usage + stat tiles. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-xl-6 col-6'])
    </div>
</div>

<div class="row g-3">
    {{-- Stock movements / requests queue. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
    </div>
</div>

@include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
