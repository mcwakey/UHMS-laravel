@php
    $chart = $chart ?? null;
    $fallbackPoints = $trends['points'] ?? [];
    $labels = $chart['labels'] ?? array_column($fallbackPoints, 'label');
    $values = $chart['datasets'][0]['data'] ?? array_column($fallbackPoints, 'value');
    $title = $chart['title'] ?? ($trends['title'] ?? __('dashboards.department.seven_day_activity'));
    $max = max($values ?: [0]);
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
            <div class="d-flex align-items-end gap-2" style="height: 140px;">
                @foreach($labels as $index => $label)
                    @php
                        $value = (float) ($values[$index] ?? 0);
                        $height = $max > 0 ? max(10, ($value / $max) * 120) : 10;
                    @endphp
                    <div class="flex-fill text-center">
                        <div class="bg-{{ $theme['accent_class'] ?? 'secondary' }} rounded-top mx-auto" style="height: {{ $height }}px; max-width: 28px;"></div>
                        <div class="small text-muted mt-1">{{ $label }}</div>
                        <div class="small fw-semibold">{{ (($chart['format'] ?? 'number') === 'currency') ? number_format($value, 2) : number_format($value) }}</div>
                    </div>
                @endforeach
            </div>
            <noscript>
                <div class="table-responsive mt-3">
                    <table class="table table-sm mb-0">
                        @foreach($labels as $index => $label)
                            <tr><td>{{ $label }}</td><td class="text-end">{{ $values[$index] ?? 0 }}</td></tr>
                        @endforeach
                    </table>
                </div>
            </noscript>
        @endif
    </div>
</div>
