@props([
    'title',
    'value' => null,
    'subtitle' => null,
    'icon' => null,
    'variant' => 'primary',
    'route' => null,
    'trend' => null,          // free text, e.g. "+12% vs last month"
    'format' => null,         // number | currency | percent | null
])

@php
    $display = $value;
    if (is_numeric($value)) {
        $display = match ($format) {
            'currency' => 'GHS '.number_format((float) $value, 2),
            'percent' => rtrim(rtrim(number_format((float) $value, 1), '0'), '.').'%',
            'number' => number_format((float) $value),
            default => $value,
        };
    }
@endphp

<div {{ $attributes->merge(['class' => "card h-100 border-start border-{$variant} border-3 position-relative"]) }}>
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="min-w-0">
                <div class="small text-muted text-truncate">{{ $title }}</div>
                <div class="h3 mb-0 text-dark">{{ $display ?? '—' }}</div>
                @if($subtitle)<div class="small text-muted mt-1">{{ $subtitle }}</div>@endif
                @if($trend)<div class="small text-{{ $variant }} mt-1">{{ $trend }}</div>@endif
            </div>
            @if($icon)
                <span class="d-inline-flex align-items-center justify-content-center rounded bg-{{ $variant }}-subtle flex-shrink-0" style="width:40px;height:40px;">
                    <i class="ti {{ $icon }} text-{{ $variant }}" style="font-size:20px;" aria-hidden="true"></i>
                </span>
            @endif
        </div>
        @if($route)
            <a href="{{ $route }}" class="stretched-link" aria-label="{{ $title }} — view details"></a>
        @endif
    </div>
</div>
