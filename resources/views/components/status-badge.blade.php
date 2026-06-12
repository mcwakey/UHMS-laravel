@props([
    'status' => null,
    // domain key in config/ui.php: visit, invoice, mar, stock, emergency, triage,
    // theatre, lab, blood_unit, blood_request, crossmatch, claim, priority, ...
    'domain' => 'default',
    'label' => null,   // override the displayed text
    'size' => null,    // 'sm' for a smaller badge
    'icon' => null,    // optional ti-* icon
    'soft' => false,   // use the subtle `badge-soft-*` style instead of solid `bg-*`
])

@php
    $enum = $status instanceof \BackedEnum ? $status : null;
    $raw = $enum ? $enum->value : (string) ($status ?? '');
    $key = strtoupper(trim($raw));

    // Resolve a Bootstrap contextual variant. A backed enum that exposes its own
    // color() is the source of truth for its colour (e.g. InvoiceStatus,
    // RequisitionStatus) — this guarantees zero visual change when adopting the
    // component. Otherwise fall back: domain map → priority/top-level map →
    // generic default map → configured fallback.
    $enumVariant = ($enum && method_exists($enum, 'color')) ? $enum->color() : null;
    $variant = $enumVariant
        ?? config("ui.status.{$domain}.{$key}")
        ?? config("ui.{$domain}.{$key}")
        ?? config("ui.status.default.{$key}")
        ?? config('ui.fallback_variant', 'secondary');

    $base = $soft ? "badge badge-soft-{$variant}" : "badge bg-{$variant}";
    // Solid tints need dark text for contrast; soft badges already use dark text.
    $textDark = (! $soft && in_array($variant, config('ui.dark_text_variants', []), true)) ? ' text-dark' : '';
    $sizeClass = $size === 'sm' ? ' fs-12' : '';

    // Translated label: statuses.{domain}.{status} → statuses.default.{status}
    // → enum translatedLabel()/label() → title-cased raw. Explicit `label` prop always wins.
    $langKey = strtolower(trim($raw));
    if ($label !== null) {
        $text = $label;
    } elseif ($langKey !== '' && \Illuminate\Support\Facades\Lang::has("statuses.{$domain}.{$langKey}")) {
        $text = __("statuses.{$domain}.{$langKey}");
    } elseif ($langKey !== '' && \Illuminate\Support\Facades\Lang::has("statuses.default.{$langKey}")) {
        $text = __("statuses.default.{$langKey}");
    } elseif ($enum && method_exists($enum, 'translatedLabel')) {
        $text = $enum->translatedLabel();
    } elseif ($enum && method_exists($enum, 'label')) {
        $text = $enum->label();
    } else {
        $text = \Illuminate\Support\Str::of($raw)->replace('_', ' ')->title()->value();
    }
    if ($text === '') {
        $text = '—';
    }
@endphp

<span {{ $attributes->merge(['class' => trim("{$base}{$textDark}{$sizeClass}")]) }}>
    @if($icon)<i class="ti {{ $icon }} me-1"></i>@endif{{ $text }}
</span>
