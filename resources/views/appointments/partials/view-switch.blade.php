@props(['active' => 'list'])

@php
    $query = request()->except(['page']);
@endphp

<div class="btn-group" role="group" aria-label="{{ __('appointments.view_switch') }}">
    <a href="{{ $workspaceRoutes->route('admin.appointments.index', $query) }}"
       class="btn btn-sm {{ $active === 'list' ? 'btn-primary' : 'btn-outline-primary' }}"
       aria-pressed="{{ $active === 'list' ? 'true' : 'false' }}"
       title="{{ __('appointments.list_view') }}">
        <i class="ti ti-list me-1"></i>{{ __('appointments.list_view') }}
    </a>
    <a href="{{ $workspaceRoutes->route('admin.appointments.calendar', $query) }}"
       class="btn btn-sm {{ $active === 'calendar' ? 'btn-primary' : 'btn-outline-primary' }}"
       aria-pressed="{{ $active === 'calendar' ? 'true' : 'false' }}"
       title="{{ __('appointments.calendar_view') }}">
        <i class="ti ti-calendar-event me-1"></i>{{ __('appointments.calendar_view') }}
    </a>
</div>
