@extends('layouts.app')
@section('title', __('stores.dashboard.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('stores.dashboard.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($reqColor = fn ($s) => match ((string) $s) {
    'completed' => 'success',
    'approved', 'partially_approved' => 'info',
    'awaiting_acknowledgement', 'partially_acknowledged' => 'primary',
    'cancelled' => 'danger',
    default => 'warning',
})
@php($poColor = fn ($s) => match ((string) $s) {
    'received' => 'success',
    'partially_received' => 'info',
    'cancelled' => 'danger',
    default => 'warning',
})

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        @can('store.requisition.view')
            <a href="{{ route('stores.stock-requisitions.index') }}" class="btn btn-primary"><i class="ti ti-clipboard-list me-1"></i>{{ $t('open_requisitions') }}</a>
        @endcan
        @can('store.purchase.view')
            <a href="{{ route('stores.stock.balances') }}" class="btn btn-outline-dark"><i class="ti ti-packages me-1"></i>{{ $t('open_stock') }}</a>
        @endcan
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('pending_requisitions'), 'value' => $kpis['pending_requisitions']['value'], 'icon' => 'ti-clipboard-list', 'color' => 'warning', 'caption' => $t('awaiting_approval')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('awaiting_issue_kpi'), 'value' => $kpis['awaiting_issue']['value'], 'icon' => 'ti-transfer', 'color' => 'info', 'caption' => $t('approved_for_issue')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('awaiting_receipt_kpi'), 'value' => $kpis['awaiting_receipt']['value'], 'icon' => 'ti-truck-delivery', 'color' => 'primary', 'caption' => $t('purchase_orders_open')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('low_stock'), 'value' => $kpis['low_stock']['value'], 'icon' => 'ti-alert-triangle', 'color' => 'danger', 'caption' => $t('below_reorder_level')])
    </div>
</div>

{{-- Movement trend + stock by type --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('movement_trend') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-success"></i>{{ $t('stock_in') }}</span>
                    <span><i class="ti ti-point-filled text-primary"></i>{{ $t('stock_out') }}</span>
                </div>
            </div>
            <div class="card-body"><div id="movementChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('stock_by_type') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="stockTypeChart"></div></div>
        </div>
    </div>
</div>

{{-- Requisition worklist + deliveries --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('active_requisitions') }}</h5>
                @can('store.requisition.view')
                    <a href="{{ route('stores.stock-requisitions.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $t('requisition') }}</th><th>{{ $t('department') }}</th><th>{{ $t('items') }}</th><th>{{ $tc('status') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($recentRequisitions as $requisition)
                            <tr>
                                <td>
                                    <span class="fw-semibold d-block">{{ $requisition->requisition_number }}</span>
                                    <span class="text-muted small">{{ $requisition->requested_at?->diffForHumans() }}</span>
                                </td>
                                <td>{{ $requisition->department?->name ?? '—' }}</td>
                                <td>{{ $requisition->items_count }}</td>
                                <td><span class="badge badge-soft-{{ $reqColor($requisition->status->value ?? $requisition->status) }}">{{ ucfirst(str_replace('_', ' ', (string) ($requisition->status->value ?? $requisition->status))) }}</span></td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('stores.stock-requisitions.show', $requisition) }}" class="btn btn-sm btn-primary">{{ $tc('view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ $tc('no_data') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('awaiting_deliveries') }}</h5>
                @can('store.purchase.view')
                    <a href="{{ route('stores.purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body">
                @forelse($awaitingDeliveries as $order)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $order->po_number }}</span>
                            <span class="text-muted small">{{ $order->supplier?->name ?? '—' }}</span>
                        </div>
                        <span class="badge badge-soft-{{ $poColor($order->status->value ?? $order->status) }}">{{ ucfirst(str_replace('_', ' ', (string) ($order->status->value ?? $order->status))) }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_pending_deliveries') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Low stock table --}}
<div class="card border shadow-sm mb-0">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0">{{ $t('low_stock_products') }}</h5>
        @can('store.purchase.view')
            <a href="{{ route('stores.stock.balances') }}" class="btn btn-sm btn-outline-secondary">{{ $t('open_stock') }}</a>
        @endcan
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>{{ $t('product') }}</th><th>{{ $t('code') }}</th><th>{{ $t('on_hand') }}</th><th>{{ $t('reorder_level') }}</th>
                </tr></thead>
                <tbody>
                @forelse($lowStock as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->name }}</td>
                        <td>{{ $item->code ?? '—' }}</td>
                        <td><span class="text-danger fw-semibold">{{ rtrim(rtrim(number_format((float) $item->qty, 2), '0'), '.') }}</span></td>
                        <td>{{ rtrim(rtrim(number_format((float) $item->reorder_level, 2), '0'), '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">{{ $t('no_low_stock') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#movementChart'), {
        chart: { height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('stores.dashboard.stock_in')), type: 'column', data: @json($movementTrend['in']) },
            { name: @json(__('stores.dashboard.stock_out')), type: 'line', data: @json($movementTrend['out']) },
        ],
        xaxis: { categories: @json($movementTrend['labels']) },
        colors: ['#0E9384', '#3538CD'],
        stroke: { curve: 'smooth', width: [0, 2] },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const stockTypes = @json($stockByType);
    new ApexCharts(document.querySelector('#stockTypeChart'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: Object.values(stockTypes),
        labels: Object.keys(stockTypes),
        colors: ['#3538CD', '#0E9384', '#F7C325', '#E91E63', '#98A2B3'],
        legend: { position: 'bottom' },
        dataLabels: { formatter: (v) => Math.round(v) + '%' },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: {
            show: true, label: @json(__('stores.dashboard.total_stock')),
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
        } } } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
