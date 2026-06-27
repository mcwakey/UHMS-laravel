@php
    $value = $card['value'] ?? 0;
    $restricted = is_array($value) && ($value['restricted'] ?? false);
    $display = $restricted
        ? __('dashboards.department.restricted')
        : (($card['format'] ?? null) === 'currency' ? '₵'.number_format((float) $value, 2) : number_format((float) $value));
    $tag = !empty($card['route']) && ! $restricted ? 'a' : 'div';
    $variant = $card['variant'] ?? 'secondary';
@endphp
<{{ $tag }} @if($tag === 'a') href="{{ $card['route'] }}" @endif class="card h-100 border-0 shadow-sm text-decoration-none {{ $tag === 'a' ? 'department-kpi-link' : '' }}">
    <div class="card-body">
        <span class="avatar rounded-2 bg-{{ $variant }} text-white mb-2">
            <i class="ti {{ $card['icon'] ?? 'ti-circle-dot' }} fs-20"></i>
        </span>
        <p class="text-muted small mb-1">{{ $card['title'] ?? '' }}</p>
        <h4 class="fw-bold mb-0 text-dark">{{ $display }}</h4>
        @if(!empty($card['subtitle']))
            <small class="text-muted">{{ $card['subtitle'] }}</small>
        @endif
    </div>
</{{ $tag }}>
