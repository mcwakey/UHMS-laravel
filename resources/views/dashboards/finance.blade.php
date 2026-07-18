@extends('layouts.app')
@section('title', __('finance.dashboard.title'))

@section('content')
@php($t = fn ($k, $r = []) => __('finance.dashboard.'.$k, $r))
@php($tc = fn ($k, $r = []) => __('role_dashboards.common.'.$k, $r))
@php($invColor = fn ($s) => match ((string) $s) {
    'paid' => 'success',
    'partially_paid' => 'info',
    'cancelled', 'refunded' => 'danger',
    'draft' => 'secondary',
    default => 'warning',
})
@php($claimColor = fn ($s) => match ((string) $s) {
    'paid', 'approved' => 'success',
    'ready', 'submitted' => 'info',
    'rejected' => 'danger',
    default => 'warning',
})
@php($money = fn ($v) => number_format((float) $v, 2))

{{-- Header --}}
<div class="d-flex align-items-sm-center justify-content-between flex-wrap gap-2 mb-4">
    <h4 class="fw-bold mb-0">{{ $t('title') }}</h4>
    <div class="d-flex gap-2">
        @can('payments.create')
            <a href="{{ route('finance.billing.payments.receive') }}" class="btn btn-primary"><i class="ti ti-cash me-1"></i>{{ $t('collect_payment') }}</a>
        @endcan
        @can('invoices.view')
            <a href="{{ route('finance.billing.invoices.index') }}" class="btn btn-outline-dark"><i class="ti ti-file-invoice me-1"></i>{{ $t('open_invoices') }}</a>
        @endcan
    </div>
</div>

@include('dashboards.partials._insight', ['insight' => $insight])
@include('dashboards.partials._pressure', ['widget' => $pressure])

{{-- KPI row --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('billed_today'), 'value' => $money($kpis['billed_today']['value']), 'icon' => 'ti-receipt', 'color' => 'primary', 'caption' => $t('gross_invoiced')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('collected_today'), 'value' => $money($kpis['collected_today']['value']), 'icon' => 'ti-cash', 'color' => 'success', 'caption' => $t('payments_received')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('outstanding'), 'value' => $money($kpis['outstanding']['value']), 'icon' => 'ti-report-money', 'color' => 'warning', 'caption' => $t('open_receivables')])
    </div>
    <div class="col-xl-3 col-md-6">
        @include('dashboards.partials._kpi', ['label' => $t('unpaid_invoices'), 'value' => $kpis['unpaid_invoices']['value'], 'icon' => 'ti-file-alert', 'color' => 'danger', 'caption' => $t('awaiting_settlement')])
    </div>
</div>

{{-- Billing trend + payment methods --}}
<div class="row g-3 mb-4">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('billing_trend') }}</h5>
                <div class="d-flex align-items-center gap-3 small text-muted">
                    <span><i class="ti ti-point-filled text-primary"></i>{{ $t('billed') }}</span>
                    <span><i class="ti ti-point-filled text-success"></i>{{ $t('collected') }}</span>
                </div>
            </div>
            <div class="card-body"><div id="billingChart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header"><h5 class="fw-bold mb-0">{{ $t('payment_methods_today') }}</h5></div>
            <div class="card-body d-flex align-items-center justify-content-center"><div id="methodChart"></div></div>
        </div>
    </div>
</div>

{{-- Unpaid invoices + claims attention --}}
<div class="row g-3">
    <div class="col-xl-8">
        <div class="card border shadow-sm h-100 mb-0">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="fw-bold mb-0">{{ $t('largest_open_invoices') }}</h5>
                @can('invoices.view')
                    <a href="{{ route('finance.billing.invoices.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr>
                            <th>{{ $t('invoice') }}</th><th>{{ $tc('patient') }}</th><th>{{ $t('total') }}</th><th>{{ $t('balance') }}</th><th>{{ $tc('status') }}</th><th></th>
                        </tr></thead>
                        <tbody>
                        @forelse($unpaidInvoices as $invoice)
                            <tr>
                                <td class="fw-semibold">{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->patient?->full_name ?? $invoice->external_party_name ?? '—' }}</td>
                                <td>{{ $money($invoice->total_amount) }}</td>
                                <td><span class="text-danger fw-semibold">{{ $money($invoice->balance) }}</span></td>
                                <td><span class="badge badge-soft-{{ $invColor($invoice->status->value ?? $invoice->status) }}">{{ ucfirst(str_replace('_', ' ', (string) ($invoice->status->value ?? $invoice->status))) }}</span></td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('finance.billing.invoices.show', $invoice) }}" class="btn btn-sm btn-primary">{{ $tc('view') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ $t('no_open_invoices') }}</td></tr>
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
                <h5 class="fw-bold mb-0">{{ $t('claims_attention') }}</h5>
                @can('claims.view')
                    <a href="{{ route('finance.claims.index') }}" class="btn btn-sm btn-outline-secondary">{{ $tc('view_all') }}</a>
                @endcan
            </div>
            <div class="card-body">
                @forelse($claimsAttention as $claim)
                    <div class="d-flex align-items-center justify-content-between {{ $loop->last ? '' : 'border-bottom pb-3 mb-3' }}">
                        <div>
                            <span class="fw-semibold d-block">{{ $claim->claim_number }}</span>
                            <span class="text-muted small">{{ $claim->patient?->full_name ?? '—' }}</span>
                        </div>
                        <span class="badge badge-soft-{{ $claimColor($claim->status->value ?? $claim->status) }}">{{ ucfirst(str_replace('_', ' ', (string) ($claim->status->value ?? $claim->status))) }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted my-4">{{ $t('no_claims_attention') }}</p>
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
    new ApexCharts(document.querySelector('#billingChart'), {
        chart: { height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: @json(__('finance.dashboard.billed')), type: 'column', data: @json($billingTrend['billed']) },
            { name: @json(__('finance.dashboard.collected')), type: 'line', data: @json($billingTrend['collected']) },
        ],
        xaxis: { categories: @json($billingTrend['labels']) },
        colors: ['#3538CD', '#0E9384'],
        stroke: { curve: 'smooth', width: [0, 2] },
        plotOptions: { bar: { columnWidth: '45%', borderRadius: 4 } },
        dataLabels: { enabled: false },
        legend: { show: false },
        grid: { borderColor: '#E9EAF3', strokeDashArray: 4 },
    }).render();

    const methods = @json($paymentMethods);
    new ApexCharts(document.querySelector('#methodChart'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: Object.values(methods),
        labels: Object.keys(methods),
        colors: ['#0E9384', '#3538CD', '#F7C325', '#E91E63', '#98A2B3'],
        legend: { position: 'bottom' },
        dataLabels: { formatter: (v) => Math.round(v) + '%' },
        plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: {
            show: true, label: @json(__('finance.dashboard.total_collected')),
            formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString(),
        } } } } },
        noData: { text: @json(__('role_dashboards.common.no_data')) },
    }).render();
});
</script>
@endpush
