@props([
    'action' => null,
    'method' => 'GET',
    'resetUrl' => null,
    'title' => 'Filters',
    'icon' => 'ti-filter',
    'collapsible' => false,
    'applyLabel' => 'Apply Filters',
    'resetLabel' => 'Reset',
])

@php
    $action = $action ?: url()->current();
    $resetUrl = $resetUrl ?: url()->current();
    $isGet = strtoupper($method) === 'GET';
    $bodyId = 'filterbar_'.\Illuminate\Support\Str::random(6);
@endphp

{{--
    Standard GET filter/search wrapper. Place filter fields (col-* divs) in the
    default slot; Apply + Reset buttons are added automatically. Preserves the
    query string via standard GET submission.
--}}
<form method="{{ $isGet ? 'GET' : 'POST' }}" action="{{ $action }}" {{ $attributes->merge(['class' => 'card mb-3']) }}>
    @unless($isGet)
        @csrf
        @method($method)
    @endunless

    @if($collapsible)
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small"><i class="ti {{ $icon }} me-1"></i>{{ $title }}</span>
            <button class="btn btn-sm btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $bodyId }}">
                Toggle
            </button>
        </div>
    @endif

    <div class="card-body {{ $collapsible ? 'collapse show' : '' }}" id="{{ $bodyId }}">
        <div class="row g-2 align-items-end">
            {{ $slot }}
            <div class="col-md-auto d-flex gap-2 align-items-end ms-md-auto">
                <button type="submit" class="btn btn-primary"><i class="ti {{ $icon }} me-1"></i>{{ $applyLabel }}</button>
                <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">{{ $resetLabel }}</a>
            </div>
        </div>
    </div>
</form>
