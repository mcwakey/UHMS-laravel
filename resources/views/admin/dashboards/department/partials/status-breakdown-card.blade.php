@php
    $labels = $chart['labels'] ?? [];
    $values = $chart['datasets'][0]['data'] ?? [];
    $total = array_sum($values ?: [0]);
@endphp
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h6 class="fw-bold mb-0"><i class="ti ti-chart-pie me-1"></i>{{ $chart['title'] ?? __('dashboards.department.charts.queue_status_breakdown') }}</h6>
    </div>
    <div class="card-body">
        @if(!empty($chart['restricted']))
            @include('admin.dashboards.department.partials.restricted-card', ['card' => ['title' => $chart['title'] ?? '', 'message' => __('dashboards.department.restricted_metric')]])
        @elseif(empty($labels) || $total <= 0)
            @include('admin.dashboards.department.partials.empty-card', ['message' => __('dashboards.department.no_chart_data'), 'icon' => 'ti-chart-pie'])
        @else
            <div class="vstack gap-2">
                @foreach($labels as $index => $label)
                    @php
                        $value = (int) ($values[$index] ?? 0);
                        $percent = $total > 0 ? round(($value / $total) * 100) : 0;
                    @endphp
                    <div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>{{ $label }}</span>
                            <span class="fw-semibold">{{ $value }}</span>
                        </div>
                        <div class="progress" style="height: 7px;">
                            <div class="progress-bar bg-{{ $theme['accent_class'] ?? 'primary' }}" style="width: {{ $percent }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
