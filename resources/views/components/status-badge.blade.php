@props([
    'status' => null,
    // domain key in config/ui.php: visit, invoice, mar, stock, emergency, triage,
    // theatre, lab, blood_unit, blood_request, crossmatch, claim, priority, ...
    'domain' => 'default',
    'label' => null,   // override the displayed text
    'size' => null,    // 'sm' for a smaller badge
    'icon' => null,    // optional ti-* icon
])

@php
    $raw = $status instanceof \BackedEnum ? $status->value : (string) ($status ?? '');
    $key = strtoupper(trim($raw));

    // Resolve a Bootstrap contextual variant: domain map → priority/top-level map
    // → generic default map → configured fallback. Guarantees a consistent
    // colour for the same status everywhere in UHMS.
    $variant = config("ui.status.{$domain}.{$key}")
        ?? config("ui.{$domain}.{$key}")
        ?? config("ui.status.default.{$key}")
        ?? config('ui.fallback_variant', 'secondary');

    $textDark = in_array($variant, config('ui.dark_text_variants', []), true) ? ' text-dark' : '';
    $sizeClass = $size === 'sm' ? ' fs-12' : '';

    $text = $label ?? \Illuminate\Support\Str::of($raw)->replace('_', ' ')->title()->value();
    if ($text === '') {
        $text = '—';
    }
@endphp

<span {{ $attributes->merge(['class' => "badge bg-{$variant}{$textDark}{$sizeClass}"]) }}>
    @if($icon)<i class="ti {{ $icon }} me-1"></i>@endif{{ $text }}
</span>
