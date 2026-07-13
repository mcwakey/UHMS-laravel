{{-- Initials avatar. Params: name, color (default primary), size (default md) --}}
@php
    $parts = preg_split('/\s+/', trim((string) ($name ?? '')));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1).substr($parts[1] ?? ($parts[0] ?? ''), 0, 1));
@endphp
<span class="avatar avatar-{{ $size ?? 'md' }} bg-soft-{{ $color ?? 'primary' }} text-{{ $color ?? 'primary' }} rounded-circle fw-semibold flex-shrink-0">{{ $initials ?: '?' }}</span>
