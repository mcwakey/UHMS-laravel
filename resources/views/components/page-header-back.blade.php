@props([
    'title',
    'href',
    'description' => null,
    'icon' => 'ti-chevron-left',
    'breadcrumbs' => null,
])

@php
    $breadcrumbs = $breadcrumbs ?? ($workspaceContext['breadcrumbs'] ?? null);
@endphp

{{--
    Compact back-navigation page header. Usage:

    <x-page-header-back
        :title="__('visits.create_new_visit')"
        :href="route('admin.visits.index')"
    />
--}}
<div {{ $attributes->merge(['class' => 'uhms-page-header-back d-flex align-items-sm-center justify-content-between flex-sm-row flex-column gap-2 mb-3']) }}>
    <div>
        @if($breadcrumbs)
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    @foreach($breadcrumbs as $crumb)
                        <li class="breadcrumb-item {{ empty($crumb['url']) ? 'active' : '' }}">
                            @if(!empty($crumb['url']))<a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>@else{{ $crumb['label'] }}@endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <h6 class="fw-bold mb-0 d-flex align-items-center">
            <a href="{{ $href }}" class="text-dark d-inline-flex align-items-center">
                @if($icon)
                    <i class="ti {{ $icon }} me-1"></i>
                @endif
                {{ $title }}
            </a>
        </h6>
        @if($description)
            <p class="text-muted small mb-0 mt-1">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
