@extends('layouts.app')
@section('title', __('role_dashboards.pharmacist.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('role_dashboards.pharmacist.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($rxColor = fn ($s) => match ((string) $s) {
    'dispensed', 'completed' => 'success',
    'partially_dispensed', 'partial' => 'info',
    'cancelled' => 'danger',
    default => 'warning',
})

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        @can('store.purchase.create')
            <a href="{{ route('admin.store.purchase-orders.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ $t('new_purchase_order') }}</a>
        @endcan
        <a href="{{ route('admin.pharmacy.dispensing.index') }}" class="btn btn-outline-dark"><i class="ti ti-packages me-1"></i>{{ $t('manage_stock') }}</a>
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('total_medicines'), 'value' => $kpis['medicines']['value'], 'trend' => $kpis['medicines']['trend'], 'icon' => 'ti-pill', 'color' => 'primary', 'caption' => $t('new_in_7_days')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('pending_orders'), 'value' => $kpis['pending_orders']['value'], 'icon' => 'ti-shopping-cart', 'color' => 'warning', 'caption' => $t('awaiting_fulfillment')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('low_stock_items'), 'value' => $kpis['low_stock']['value'], 'icon' => 'ti-alert-triangle', 'color' => 'danger', 'caption' => $t('need_reordering')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('expiring_soon'), 'value' => $kpis['expiring']['value'], 'badge' => $t('days', ['days' => 30]), 'badgeColor' => 'info', 'icon' => 'ti-calendar-due', 'color' => 'info', 'caption' => $t('requires_attention')])
    </div>
</div>

{{-- Sales trend + stock by category --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('sales_trend') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-success"></i>{{ $t('units_sold') }}</span>
                    <span><i class="ti ti-point-filled text-primary"></i>{{ $t('revenue') }}</span>
                </div>
            </div>
            <div class="card-body"><div id="salesChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('stock_by_category') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="categoryChart"></div></div>
        </div>
    </div>
</div>

{{-- Recent prescriptions + expiry alerts --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('recent_prescriptions') }}</h5>
                <span class="badge bg-light text-dark border">{{ $tc('today') }}</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $tc('patient') }}</th><th>{{ $t('medicine') }}</th><th>{{ $t('prescribed_by') }}</th><th>{{ $tc('status') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($recentPrescriptions as $prescription)
                            @php($statusValue = strtolower((string) ($prescription->status?->value ?? $prescription->status ?? 'pending')))
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @include('dashboards.partials._avatar', ['name' => $prescription->patient?->full_name, 'color' => 'primary'])
                                        <div>
                                            <span class="fw-semibold d-block">{{ $prescription->patient?->full_name }}</span>
                                            <span class="text-muted small">{{ $prescription->prescription_number }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $prescription->items->first()?->drug_name ?? '—' }}@if($prescription->items->count() > 1) <span class="text-muted small">+{{ $prescription->items->count() - 1 }}</span>@endif</td>
                                <td>{{ $prescription->doctor?->name ?? '—' }}</td>
                                <td><span class="badge badge-soft-{{ $rxColor($statusValue) }}">{{ ucfirst(str_replace('_', ' ', $statusValue)) }}</span></td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.pharmacy.dispensing.show', $prescription) }}" class="btn btn-sm {{ in_array($statusValue, ['dispensed', 'completed', 'cancelled'], true) ? 'btn-outline-secondary' : 'btn-primary' }}">
                                        {{ in_array($statusValue, ['dispensed', 'completed', 'cancelled'], true) ? $tc('view') : $t('dispense') }}
                                    </a>
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
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('expiry_alerts') }}</h5></div>
            <div class="card-body">
                @forelse($expiryAlerts as $alert)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $alert->name }}</span>
                            <span class="text-muted small">{{ $t('batch') }} #{{ $alert->batch_no ?? '—' }}</span>
                        </div>
                        <span class="badge {{ $alert->days_left <= 7 ? 'bg-danger' : ($alert->days_left <= 14 ? 'bg-warning' : 'bg-primary') }}">{{ $t('days', ['days' => $alert->days_left]) }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_expiry_alerts') }}</p>
                @endforelse
                <a href="{{ route('admin.pharmacy.dispensing.index') }}" class="btn btn-light w-100 mt-2">{{ $t('view_all_alerts') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- Low stock table --}}
<div class="card border shadow-sm mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="fw-bold mb-0">{{ $t('low_stock_medicines') }}</h5>
        <a href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary">{{ $t('manage_stock') }}</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light"><tr>
                    <th>{{ $t('medicine') }}</th><th>{{ $t('category') }}</th><th>{{ $t('available_qty') }}</th><th>{{ $t('reorder_level') }}</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($lowStock as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item->name }}</td>
                        <td>{{ ucfirst((string) ($item->dosage_form ?? '—')) }}</td>
                        <td><span class="text-danger fw-semibold">{{ rtrim(rtrim(number_format((float) $item->qty, 2), '0'), '.') }}</span></td>
                        <td>{{ rtrim(rtrim(number_format((float) $item->reorder_level, 2), '0'), '.') }}</td>
                        <td class="text-end pe-3"><a href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-sm btn-primary">{{ $t('reorder') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">{{ $t('no_low_stock') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Top selling + suppliers --}}
<div class="row g-3">
    <div class="col-xl-6">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('top_selling') }}</h5></div>
            <div class="card-body">
                @forelse($topSelling as $i => $row)
                    @php($colors = ['primary', 'success', 'warning', 'info', 'danger'])
                    <div class="{{ $loop->last ? '' : 'mb-3' }}">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold">{{ $row['name'] }}</span>
                            <span class="text-muted small">{{ $t('units', ['units' => number_format($row['units'])]) }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $colors[$i % 5] }}" role="progressbar" style="width: {{ $row['pct'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $tc('no_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('suppliers_overview') }}</h5>
                <a href="{{ route('admin.store.purchase-orders.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
            </div>
            <div class="card-body">
                @forelse($suppliers as $supplier)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-md bg-soft-success text-success rounded-2"><i class="ti ti-truck-delivery"></i></span>
                            <div>
                                <span class="fw-semibold d-block">{{ $supplier->name }}</span>
                                <span class="text-muted small">{{ $t('orders_this_month', ['count' => $supplier->month_orders]) }}</span>
                            </div>
                        </div>
                        <span class="badge badge-soft-{{ $supplier->month_orders > 0 ? 'success' : 'secondary' }}">{{ $supplier->month_orders }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $tc('no_data') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ URL::asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    new ApexCharts(document.querySelector('#salesChart'), {
        chart: { height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('role_dashboards.pharmacist.units_sold')), type: 'column', data: @json($salesTrend['units']) },
            { name: @json(__('role_dashboards.pharmacist.revenue')), type: 'line', data: @json($salesTrend['revenue']) },
        ],
        xaxis: { categories: @json($salesTrend['labels']) },
        yaxis: [{ title: { text: '' } }, { opposite: true, labels: { show: false } }],
        colors: ['#0E9384', '#3538CD'],
        stroke: { curve: 'smooth', width: [0, 2] },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const categories = @json($stockByCategory);
    new ApexCharts(document.querySelector('#categoryChart'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: Object.values(categories),
        labels: Object.keys(categories),
        colors: ['#3538CD', '#0E9384', '#F7C325', '#E91E63', '#98A2B3'],
        legend: { position: 'bottom' },
        dataLabels: { formatter: (v) => Math.round(v) + '%' },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: {
            show: true, label: @json(__('role_dashboards.pharmacist.total_stock')),
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
        } } } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
