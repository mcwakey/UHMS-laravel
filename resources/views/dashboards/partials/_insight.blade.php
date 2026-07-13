{{-- Flow-insight banner (same visual grammar as the department journey insight).
     Param: $insight = [variant, icon, title, badge, cause => [icon,label]|null,
                        action|null, link => [url,label]|null] --}}
@if(!empty($insight))
<div class="alert alert-{{ $insight['variant'] ?? 'warning' }} border-0 shadow-sm mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="fw-bold d-flex align-items-center gap-1"><i class="ti {{ $insight['icon'] ?? 'ti-route' }}"></i>{{ $insight['title'] }}</span>
        @if(!empty($insight['badge']))
            <span class="badge bg-{{ $insight['variant'] ?? 'warning' }}-subtle text-{{ $insight['variant'] ?? 'warning' }}">{{ $insight['badge'] }}</span>
        @endif
        @if(!empty($insight['cause']))
            <span class="d-inline-flex align-items-center gap-1"><i class="ti {{ $insight['cause']['icon'] }}"></i>{{ __('journey.bottleneck.top_cause') }}: <strong>{{ $insight['cause']['label'] }}</strong></span>
        @endif
        @if(!empty($insight['action']))
            <span class="text-muted small d-inline-flex align-items-center gap-1"><i class="ti ti-arrow-right"></i>{{ __('journey.insight.next_action') }}: {{ $insight['action'] }}</span>
        @endif
        @if(!empty($insight['link']))
            <a href="{{ $insight['link']['url'] }}" class="btn btn-sm btn-{{ $insight['variant'] ?? 'warning' }} ms-auto">
                <i class="ti ti-list-check me-1"></i>{{ $insight['link']['label'] }}
            </a>
        @endif
    </div>
</div>
@endif
