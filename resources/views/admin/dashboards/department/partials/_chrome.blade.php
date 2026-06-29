{{-- Shared dashboard chrome: hero + personality strip + preview banner + styles.
     Included by dashboard-body (family layouts) and by bespoke per-type showcases. --}}
@include('admin.dashboards.department.partials.hero', compact('context', 'theme', 'dashboard', 'available_dashboards', 'key'))

@include('admin.dashboards.department.partials.priority-banner')

@include('admin.dashboards.department.partials.journey-insight')

@include('admin.dashboards.department.partials.identity-widget')

@includeWhen(!empty($dashboardPersonalization), 'admin.dashboards.department.partials.personalized-command-strip')

@if(!empty($is_preview))
<div class="alert alert-info d-flex align-items-center gap-2 py-2">
    <i class="ti ti-eye"></i>
    @php $ownLabel = app(\App\Services\Dashboard\DepartmentDashboardResolver::class)->labelFor($resolved_key); @endphp
    <span>{!! __('dashboards.previewing_dashboard', ['title' => '<strong>'.e($title).'</strong>', 'own' => '<strong>'.e($ownLabel).'</strong>']) !!}</span>
</div>
@endif

@once
@push('styles')
<style>
    .department-kpi-link { transition: transform .15s ease, box-shadow .15s ease; }
    .department-kpi-link:hover { transform: translateY(-1px); box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .12) !important; }
    .department-apex-chart { width: 100%; }
    .department-list-row { transition: background-color .12s ease; }
    .department-list-row:last-child { border-bottom: 0 !important; }
    .department-list-row:hover { background-color: rgba(15, 23, 42, .03); }
    .department-list-row--priority { border-left: 3px solid var(--bs-danger); background-color: rgba(220, 53, 69, .04); }
    .department-identity-widget { background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
    @media (max-width: 575.98px) {
        .department-apex-chart { min-height: 190px !important; }
        .department-kpi-link .card-body,
        .card .card-body { overflow-wrap: anywhere; }
    }
</style>
@endpush
@endonce
