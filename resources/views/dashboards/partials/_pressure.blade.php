{{-- Pressure widget (same visual grammar as the department identity widget).
     Param: $widget = [title, icon, variant, value, level, metrics[]['text']] --}}
@if(!empty($widget))
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="avatar avatar-lg rounded-2 bg-{{ $widget['variant'] }}-subtle text-{{ $widget['variant'] }} flex-shrink-0">
            <i class="ti {{ $widget['icon'] ?? 'ti-activity' }} fs-26"></i>
        </span>
        <div class="flex-fill min-w-0">
            <div class="d-flex align-items-baseline justify-content-between gap-2">
                <span class="text-muted small text-uppercase fw-semibold">{{ $widget['title'] }}</span>
                <span class="h5 fw-bold mb-0 text-{{ $widget['variant'] }}">{{ $widget['value'] }}</span>
            </div>
            @if(!is_null($widget['level'] ?? null))
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-{{ $widget['variant'] }}" role="progressbar" style="width: {{ (int) $widget['level'] }}%"></div>
                </div>
            @endif
            @if(!empty($widget['metrics']))
                <div class="d-flex flex-wrap gap-3 mt-2 small text-muted">
                    @foreach($widget['metrics'] as $metric)
                        <span class="d-inline-flex align-items-center gap-1"><i class="ti ti-point-filled text-{{ $widget['variant'] }} fs-10"></i>{{ $metric['text'] }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endif
