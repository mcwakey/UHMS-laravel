@php
    $value = $card['value'] ?? 0;
    $restricted = is_array($value) && ($value['restricted'] ?? false);
    $tag = !empty($card['route']) && ! $restricted ? 'a' : 'div';
@endphp
<{{ $tag }} @if($tag === 'a') href="{{ $card['route'] }}" @endif class="card h-100 border-start border-{{ $card['variant'] ?? 'secondary' }} border-3 shadow-sm text-decoration-none {{ $tag === 'a' ? 'department-kpi-link' : '' }}">
    <div class="card-body py-2">
        <div class="text-muted small">{{ $card['title'] ?? '' }}</div>
        <div class="fw-bold text-dark">{{ $restricted ? __('dashboards.department.restricted') : number_format((float) $value) }}</div>
    </div>
</{{ $tag }}>
