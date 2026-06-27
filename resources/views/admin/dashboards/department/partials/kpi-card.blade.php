@php
    $value = $card['value'] ?? 0;
    $restricted = is_array($value) && ($value['restricted'] ?? false);
    $display = $restricted
        ? __('dashboards.department.restricted')
        : (($card['format'] ?? null) === 'currency' ? '₵'.number_format((float) $value, 2) : number_format((float) $value));
    $tag = !empty($card['route']) && ! $restricted ? 'a' : 'div';
    // Rotate the background art per card (admin-dashboard style).
    $bg = 'build/img/bg/bg-0'.(((int) ($index ?? 0) % 4) + 1).'.svg';

    $spark = (! $restricted && !empty($card['spark']) && array_sum($card['spark']) > 0) ? $card['spark'] : null;
    $delta = $card['delta'] ?? null;
    $deltaUp = ($card['delta_dir'] ?? 'up') === 'up';
    $sparkType = ($card['format'] ?? null) === 'currency' ? 'area' : 'bar';
    $sparkColor = $theme['chart_accent'] ?? '#6c757d';
    $sparkId = 'dept_spark_'.\Illuminate\Support\Str::random(6);
@endphp
<{{ $tag }} @if($tag === 'a') href="{{ $card['route'] }}" @endif class="position-relative border card rounded-2 shadow-sm h-100 overflow-hidden text-decoration-none {{ $tag === 'a' ? 'department-kpi-link' : '' }}">
    <img src="{{ asset($bg) }}" alt="" class="position-absolute start-0 top-0 opacity-75">
    <div class="card-body position-relative">
        <div class="d-flex align-items-center mb-2 justify-content-between">
            <span class="avatar rounded-circle bg-{{ $card['variant'] ?? ($theme['accent_class'] ?? 'secondary') }} text-white">
                <i class="ti {{ $card['icon'] ?? 'ti-circle-dot' }} fs-24"></i>
            </span>
            @if($delta !== null)
                <span class="badge bg-{{ $deltaUp ? 'success' : 'danger' }}-subtle text-{{ $deltaUp ? 'success' : 'danger' }} fw-semibold">
                    {{ $deltaUp ? '+' : '' }}{{ $delta }}%
                </span>
            @elseif($tag === 'a')
                <span class="text-muted small">{{ __('common.view_all') }} <i class="ti ti-arrow-right"></i></span>
            @endif
        </div>
        <p class="mb-1 text-muted small">{{ $card['title'] ?? '' }}</p>
        <div class="d-flex align-items-end justify-content-between gap-2">
            <div class="min-w-0">
                <h3 class="fw-bold mb-0 text-dark">{{ $display }}</h3>
                @if($spark)
                    <small class="text-muted">{{ __('dashboards.department.in_last_7_days') }}</small>
                @elseif(!empty($card['subtitle']))
                    <small class="text-muted">{{ $card['subtitle'] }}</small>
                @endif
            </div>
            @if($spark)
                <div id="{{ $sparkId }}" class="department-spark flex-shrink-0" style="width: 92px; height: 46px;"
                    data-spark='@json($spark)' data-spark-type="{{ $sparkType }}" data-spark-color="{{ $sparkColor }}"></div>
            @endif
        </div>
    </div>
</{{ $tag }}>

@once
@push('scripts')
<script>
    // KPI sparklines. ApexCharts is loaded by the chart card already on the page.
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.ApexCharts) return;
        document.querySelectorAll('.department-spark').forEach(function (el) {
            if (el.dataset.rendered) return;
            const data = JSON.parse(el.dataset.spark || '[]');
            if (!data.length) return;
            const type = el.dataset.sparkType === 'area' ? 'area' : 'bar';
            const color = el.dataset.sparkColor || '#6c757d';
            new ApexCharts(el, {
                chart: { type: type, height: 46, sparkline: { enabled: true } },
                series: [{ data: data }],
                colors: [color],
                stroke: { curve: 'smooth', width: type === 'area' ? 2 : 0 },
                fill: type === 'area'
                    ? { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } }
                    : { opacity: 1 },
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 2 } },
                tooltip: { enabled: false },
            }).render();
            el.dataset.rendered = '1';
        });
    });
</script>
@endpush
@endonce
