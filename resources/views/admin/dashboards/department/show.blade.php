@extends('layouts.app')
@section('title', $dashboard['title'] ?? __('dashboards.department.department_dashboard'))

@section('content')
@include('admin.dashboards.department.partials.hero', compact('context', 'theme', 'dashboard', 'available_dashboards', 'key'))

@if(!empty($is_preview))
<div class="alert alert-info d-flex align-items-center gap-2 py-2">
    <i class="ti ti-eye"></i>
    @php $ownLabel = app(\App\Services\Dashboard\DepartmentDashboardResolver::class)->labelFor($resolved_key); @endphp
    <span>{!! __('dashboards.previewing_dashboard', ['title' => '<strong>'.e($title).'</strong>', 'own' => '<strong>'.e($ownLabel).'</strong>']) !!}</span>
</div>
@endif

@php
    // Department TYPE chooses the layout family (structure / emphasis); a missing
    // family safely falls back to the generic department layout.
    $layoutFamily = $layout_family ?? 'generic_department';
    $layoutView = 'admin.dashboards.department.partials.layouts.'.$layoutFamily;
@endphp
@includeFirst([$layoutView, 'admin.dashboards.department.partials.layouts.generic_department'])
@endsection

@push('styles')
<style>
    .department-kpi-link { transition: transform .15s ease, box-shadow .15s ease; }
    .department-kpi-link:hover { transform: translateY(-1px); box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .12) !important; }
    .department-apex-chart { width: 100%; }
    @media (max-width: 575.98px) {
        .department-apex-chart { min-height: 190px !important; }
        .department-kpi-link .card-body,
        .card .card-body { overflow-wrap: anywhere; }
    }
</style>
@endpush
