@php
    $value = $card['value'] ?? 0;
    $restricted = is_array($value) && ($value['restricted'] ?? false);
    $display = $restricted
        ? __('dashboards.department.restricted')
        : (($card['format'] ?? null) === 'currency' ? '₵'.number_format((float) $value, 2) : number_format((float) $value));
    $tag = !empty($card['route']) && ! $restricted ? 'a' : 'div';
    // Rotate the background art per card (admin-dashboard style).
    $bg = 'build/img/bg/bg-0'.(((int) ($index ?? 0) % 4) + 1).'.svg';
@endphp
<{{ $tag }} @if($tag === 'a') href="{{ $card['route'] }}" @endif class="position-relative border card rounded-2 shadow-sm h-100 overflow-hidden text-decoration-none {{ $tag === 'a' ? 'department-kpi-link' : '' }}">
    <img src="{{ asset($bg) }}" alt="" class="position-absolute start-0 top-0 opacity-75">
    <div class="card-body position-relative">
        <div class="d-flex align-items-center mb-2 justify-content-between">
            <span class="avatar rounded-circle bg-{{ $card['variant'] ?? ($theme['accent_class'] ?? 'secondary') }} text-white">
                <i class="ti {{ $card['icon'] ?? 'ti-circle-dot' }} fs-24"></i>
            </span>
            @if($tag === 'a')
                <span class="text-muted small">{{ __('common.view_all') }} <i class="ti ti-arrow-right"></i></span>
            @endif
        </div>
        <p class="mb-1 text-muted small">{{ $card['title'] ?? '' }}</p>
        <h3 class="fw-bold mb-0 text-dark">{{ $display }}</h3>
        @if(!empty($card['subtitle']))
            <small class="text-muted">{{ $card['subtitle'] }}</small>
        @endif
    </div>
</{{ $tag }}>
