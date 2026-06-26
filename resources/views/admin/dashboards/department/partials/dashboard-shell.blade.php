@php
    $dashboardPersonalization = [
        'key' => $dashboardPersonality ?? ($key ?? 'generic'),
    ];
@endphp

@include('admin.dashboards.department.partials.dashboard-body')
