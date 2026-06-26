<div class="card shadow-sm mb-3">
    <div class="card-header"><h6 class="fw-bold mb-0"><i class="ti ti-bolt me-1"></i>{{ __('dashboards.department.department_quick_actions') }}</h6></div>
    <div class="card-body d-grid gap-2">
        @forelse($quick_actions as $action)
            <a href="{{ $action['route'] }}" class="btn btn-outline-{{ $theme['accent_class'] ?? 'secondary' }} text-start">
                <i class="ti {{ $action['icon'] }} me-1"></i>{{ $action['label'] }}
            </a>
        @empty
            <div class="text-muted small">{{ __('dashboards.department.metric_unavailable') }}</div>
        @endforelse
    </div>
</div>
