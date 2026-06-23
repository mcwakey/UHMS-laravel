@props([
    'action' => null,
    'method' => 'GET',
    'resetUrl' => null,
    'title' => null,
    'icon' => 'ti-filter',
    'collapsible' => false,
    'applyLabel' => null,
    'resetLabel' => null,
    'showApply' => true,
    'showReset' => true,
    'rowClass' => 'row g-2 align-items-end',
    'actionsClass' => 'col-md-auto d-flex gap-2 align-items-end ms-md-auto',
    'bodyClass' => 'card-body py-2',
])

@php
    $title ??= __('common.filters');
    $applyLabel ??= __('common.apply_filters');
    $resetLabel ??= __('common.reset');
    $action = $action ?: url()->current();
    $resetUrl = $resetUrl ?: url()->current();
    $isGet = strtoupper($method) === 'GET';
    $bodyId = 'filterbar_'.\Illuminate\Support\Str::random(6);
@endphp

{{--
    Standard filter/search wrapper. Place filter fields (col-* divs) in the
    default slot. Apply + Reset buttons are added automatically, or provide an
    actions slot for page-specific controls.
--}}
<form method="{{ $isGet ? 'GET' : 'POST' }}" action="{{ $action }}" {{ $attributes->merge(['class' => 'card mb-3 uhms-filter-bar']) }}>
    @unless($isGet)
        @csrf
        @method($method)
    @endunless

    @if($collapsible)
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
            <span class="fw-semibold small"><i class="ti {{ $icon }} me-1"></i>{{ $title }}</span>
            <button class="btn btn-sm btn-link p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $bodyId }}">
                {{ __('common.toggle') }}
            </button>
        </div>
    @endif

    <div class="{{ $bodyClass }} {{ $collapsible ? 'collapse show' : '' }}" id="{{ $bodyId }}">
        <div class="{{ $rowClass }}">
            {{ $slot }}
            @isset($actions)
                <div class="{{ $actionsClass }}">{{ $actions }}</div>
            @else
                @if($showApply || $showReset)
                    <div class="{{ $actionsClass }}">
                        @if($showApply)
                            <button type="submit" class="btn btn-primary"><i class="ti {{ $icon }} me-1"></i>{{ $applyLabel }}</button>
                        @endif
                        @if($showReset)
                            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">{{ $resetLabel }}</a>
                        @endif
                    </div>
                @endif
            @endisset
        </div>
    </div>
</form>
