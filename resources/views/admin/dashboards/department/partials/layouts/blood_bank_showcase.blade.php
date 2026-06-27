{{-- Blood bank showcase — unit stock + requests. Stock-alert donut prominent. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Requests pipeline. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>
    {{-- Unit stock status donut. --}}
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['stock_status_breakdown'] ?? null, 'theme' => $theme])
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">@include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])</div>
    <div class="col-xl-4">@include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-6'])</div>
</div>

<div class="row g-3">
    <div class="col-xl-6">@include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])</div>
    <div class="col-xl-6">@include('admin.dashboards.department.partials.services-card', ['services' => $services])</div>
</div>

@include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
