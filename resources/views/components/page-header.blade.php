@props([
    'title',
    'description' => null,
    'icon' => null,        // optional ti-* icon shown before the title
    'breadcrumbs' => null,  // optional array of ['label' => , 'url' => (nullable)]
])

@php
    $breadcrumbs = $breadcrumbs ?? ($workspaceContext['breadcrumbs'] ?? null);
@endphp

{{--
    Standard UHMS page header. Usage:

    <x-page-header title="Patients" description="Manage patient folders." icon="ti-users">
        <x-slot:actions>
            <a href="..." class="btn btn-primary"><i class="ti ti-plus me-1"></i>New Patient</a>
        </x-slot:actions>
    </x-page-header>
--}}
<div {{ $attributes->merge(['class' => 'uhms-page-header d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2 border-bottom']) }}>
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
        <h4 class="fw-bold mb-1">@if($icon)<i class="ti {{ $icon }} me-2 text-primary"></i>@endif{{ $title }}@if(isset($slot) && ! $slot->isEmpty()) {{ $slot }}@endif</h4>
        @if($description)<p class="text-muted mb-0">{{ $description }}</p>@endif
    </div>
    @isset($actions)
        <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</div>
