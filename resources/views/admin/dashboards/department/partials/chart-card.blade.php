<div class="card shadow-sm mb-3">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-chart-line me-1"></i>{{ $trends['title'] ?? __('dashboards.department.seven_day_activity') }}</h6></div>
    <div class="card-body">
        @if(empty($trends['points']))
            @include('admin.dashboards.department.partials.empty-card', ['message' => __('dashboards.department.metric_unavailable'), 'icon' => 'ti-chart-line'])
        @else
            <div class="d-flex align-items-end gap-2" style="height: 120px;">
                @php $max = max(array_column($trends['points'], 'value') ?: [0]); @endphp
                @foreach($trends['points'] as $point)
                    @php $height = $max > 0 ? max(10, ((float) $point['value'] / $max) * 100) : 10; @endphp
                    <div class="flex-fill text-center">
                        <div class="bg-{{ $theme['accent_class'] ?? 'secondary' }} rounded-top mx-auto" style="height: {{ $height }}px; max-width: 28px;"></div>
                        <div class="small text-muted mt-1">{{ $point['label'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
