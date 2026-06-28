{{-- Priority banner — only renders when the department has something needing
     attention. Alerts are derived from already-computed (capability-gated) values. --}}
@if(!empty($alerts))
@php $hasCritical = collect($alerts)->contains(fn ($a) => ($a['variant'] ?? '') === 'danger'); @endphp
<div class="alert alert-{{ $hasCritical ? 'danger' : 'warning' }} border-0 shadow-sm d-flex flex-wrap align-items-center gap-3 mb-3 department-priority-banner">
    <span class="fw-bold d-flex align-items-center gap-1 flex-shrink-0">
        <i class="ti ti-alert-triangle"></i>{{ __('dashboards.department.attention_required') }}
    </span>
    <div class="d-flex flex-wrap gap-2">
        @foreach(collect($alerts)->take(4) as $alert)
            <span class="badge bg-{{ $alert['variant'] }}-subtle text-{{ $alert['variant'] }} d-inline-flex align-items-center gap-1">
                <i class="ti {{ $alert['icon'] ?? 'ti-point-filled' }}"></i>{{ $alert['message'] }}
            </span>
        @endforeach
    </div>
</div>
@endif
