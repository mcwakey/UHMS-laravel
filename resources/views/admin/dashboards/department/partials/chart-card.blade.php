@php
    $chart = $chart ?? null;
    $fallbackPoints = $trends['points'] ?? [];
    $labels = $chart['labels'] ?? array_column($fallbackPoints, 'label');
    $values = $chart['datasets'][0]['data'] ?? array_column($fallbackPoints, 'value');
    $title = $chart['title'] ?? ($trends['title'] ?? __('dashboards.department.seven_day_activity'));
    $max = max($values ?: [0]);
    $chartId = 'dept_chart_'.\Illuminate\Support\Str::random(8);
@endphp
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0"><i class="ti ti-chart-line me-1"></i>{{ $title }}</h6>
        @if(!empty($chart['format']))
            <span class="badge bg-light text-dark">{{ $chart['format'] }}</span>
        @endif
    </div>
    <div class="card-body">
        @if(!empty($chart['restricted']))
            @include('admin.dashboards.department.partials.restricted-card', ['card' => ['title' => $title, 'message' => __('dashboards.department.restricted_metric')]])
        @elseif(empty($labels) || !empty($chart['empty_state']))
            @include('admin.dashboards.department.partials.empty-card', ['message' => __('dashboards.department.metric_unavailable'), 'icon' => 'ti-chart-line'])
        @else
            {{-- ApexChart is the primary visual; the CSS bars only appear when JS is off. --}}
            <div
                id="{{ $chartId }}"
                class="department-apex-chart"
                style="min-height: 260px;"
                data-chart='@json($chart)'
                data-accent="{{ $theme['chart_accent'] ?? '#6c757d' }}">
            </div>
            <noscript>
                <div class="d-flex align-items-end gap-2" style="height: 140px;">
                    @foreach($labels as $i => $label)
                        @php
                            $v = (float) ($values[$i] ?? 0);
                            $height = $max > 0 ? max(10, ($v / $max) * 120) : 10;
                        @endphp
                        <div class="flex-fill text-center">
                            <div class="bg-{{ $theme['accent_class'] ?? 'secondary' }} rounded-top mx-auto" style="height: {{ $height }}px; max-width: 28px;"></div>
                            <div class="small text-muted mt-1">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            </noscript>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script src="{{ asset('build/plugins/apexchart/apexcharts.min.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (!window.ApexCharts) return;

                document.querySelectorAll('.department-apex-chart').forEach(function (el) {
                    if (el.dataset.rendered) return;
                    const payload = JSON.parse(el.dataset.chart || '{}');
                    const first = (payload.datasets || [])[0] || {};
                    const raw = payload.type === 'doughnut' ? 'donut' : (payload.type || 'bar');
                    // Render trend lines as smooth gradient area charts (modern dashboard look).
                    const type = raw === 'line' ? 'area' : raw;
                    const labels = payload.labels || [];
                    const values = first.data || [];
                    if (!labels.length || !values.length) return;

                    const accent = el.dataset.accent || '#6c757d';
                    const palette = first.backgroundColor && Array.isArray(first.backgroundColor) ? first.backgroundColor : [accent];

                    const options = {
                        chart: { type: type, height: 260, toolbar: { show: false }, fontFamily: 'inherit', parentHeightOffset: 0 },
                        labels: labels,
                        series: (type === 'donut' || type === 'pie') ? values : [{ name: first.label || payload.title || '', data: values }],
                        colors: palette,
                        stroke: { curve: 'smooth', width: type === 'bar' ? 0 : 3 },
                        fill: type === 'area'
                            ? { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] } }
                            : { opacity: 1 },
                        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
                        dataLabels: { enabled: false },
                        xaxis: { categories: labels, labels: { rotate: -35, style: { fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                        yaxis: { labels: { formatter: function (value) { return Math.round(value).toString(); } } },
                        legend: { position: 'bottom' },
                        grid: { strokeDashArray: 4, borderColor: 'rgba(0,0,0,.06)' },
                        tooltip: { y: { formatter: function (value) { return Math.round(value).toString(); } } },
                    };

                    new ApexCharts(el, options).render();
                    el.dataset.rendered = '1';
                });
            });
        </script>
    @endpush
@endonce
