@php
    $value = $card['value'] ?? 0;
    $restricted = is_array($value) && ($value['restricted'] ?? false);
@endphp
<div class="card h-100 border-start border-{{ $card['variant'] ?? 'secondary' }} border-3 shadow-sm">
    <div class="card-body py-2">
        <div class="text-muted small">{{ $card['title'] ?? '' }}</div>
        <div class="fw-bold">{{ $restricted ? __('dashboards.department.restricted') : number_format((float) $value) }}</div>
    </div>
</div>
