{{-- Pharmacy showcase — Preclinic-style dispensing & stock command view.
     Bespoke per-type layout; reuses shared partials + the new stock-alert donut. --}}

{{-- KPI row: pending Rx, dispensed today (sparkline), low stock, revenue. --}}
@include('admin.dashboards.department.partials.layouts._primary-cards')

<div class="row g-3">
    {{-- Left: dispensing queue as a rich avatar list. --}}
    <div class="col-xl-5">
        @include('admin.dashboards.department.partials._list-card', ['list' => $work_queue, 'theme' => $theme])
    </div>

    {{-- Right: dispensing trend (gradient area) + mid-row stat tiles. --}}
    <div class="col-xl-7">
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        @include('admin.dashboards.department.partials.layouts._secondary-cards', ['col' => 'col-xl-6 col-6'])
    </div>
</div>

<div class="row g-3">
    {{-- Stock-alert donut (In stock vs Low stock, centre total). --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['stock_status_breakdown'] ?? null, 'theme' => $theme])
    </div>
    {{-- Lowest stock balances. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
    </div>
    {{-- Pharmacy quick actions. --}}
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
    </div>
</div>

{{-- Department services + recent activity. --}}
<div class="row g-3">
    <div class="col-xl-6">
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
    </div>
    <div class="col-xl-6">
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
</div>
