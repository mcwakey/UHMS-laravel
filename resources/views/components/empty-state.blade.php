@props([
    'icon' => 'ti-inbox',
    'title' => null,
    'message' => 'No records found.',
])

{{--
    Standard empty state for tables/lists. Usage:

    <x-empty-state icon="ti-ambulance" title="No active emergencies"
                   message="No emergency cases are currently active.">
        <x-slot:action>
            <a href="..." class="btn btn-sm btn-primary">Create Emergency Case</a>
        </x-slot:action>
    </x-empty-state>
--}}
<div {{ $attributes->merge(['class' => 'text-center text-muted py-5']) }}>
    <div class="mb-2"><i class="ti {{ $icon }}" style="font-size:2.5rem;opacity:.4;" aria-hidden="true"></i></div>
    @if($title)<div class="fw-semibold text-dark mb-1">{{ $title }}</div>@endif
    <div>{{ $message }}</div>
    @isset($action)
        <div class="mt-3">{{ $action }}</div>
    @endisset
</div>
