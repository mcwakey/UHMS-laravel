@php
    $value = $card['value'] ?? 0;
    $restricted = is_array($value) && ($value['restricted'] ?? false);
    $display = $restricted
        ? __('dashboards.department.restricted')
        : (($card['format'] ?? null) === 'currency' ? '₵'.number_format((float) $value, 2) : number_format((float) $value));
@endphp
<div class="card h-100 border-0 shadow-sm overflow-hidden">
    <div class="card-body position-relative">
        <img src="{{ asset('build/img/bg/bg-02.svg') }}" alt="" class="position-absolute end-0 top-0 opacity-25">
        <div class="d-flex align-items-start justify-content-between position-relative">
            <div>
                <p class="text-muted small mb-1">{{ $card['title'] ?? '' }}</p>
                <h3 class="fw-bold mb-0">{{ $display }}</h3>
            </div>
            <span class="avatar rounded-circle bg-{{ $card['variant'] ?? ($theme['accent_class'] ?? 'secondary') }} text-white">
                <i class="ti {{ $card['icon'] ?? 'ti-circle-dot' }} fs-22"></i>
            </span>
        </div>
    </div>
</div>
