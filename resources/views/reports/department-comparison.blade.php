@extends('layouts.app')
@section('title', __('reports.department_comparison.title'))

@section('content')
<x-page-header
    :title="__('reports.department_comparison.title')"
    :description="__('reports.department_comparison.description')"
    icon="ti-chart-bar" />

<x-filter-bar :action="route('admin.reports.department-comparison.index')" method="GET" data-auto-filter-form="department-comparison">
    <div class="col-md-2">
        <label class="form-label small">{{ __('reports.date_from') }}</label>
        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
    </div>
    <div class="col-md-2">
        <label class="form-label small">{{ __('reports.date_to') }}</label>
        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('reports.department_comparison.department_type') }}</label>
        <select name="department_type" class="form-select">
            <option value="">{{ __('reports.department_comparison.all_types') }}</option>
            @foreach($departmentTypes as $type)
                <option value="{{ $type->value }}" @selected(($filters['department_type'] ?? '') === $type->value)>{{ $type->translatedLabel() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">{{ __('reports.department_comparison.departments') }}</label>
        <select name="department_ids[]" class="form-select" multiple>
            @foreach($availableDepartments as $department)
                <option value="{{ $department->id }}" @selected(in_array($department->id, $filters['department_ids'] ?? []))>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-auto">
        <a href="{{ route('admin.reports.department-comparison.index') }}" class="btn btn-outline-secondary"><i class="ti ti-x"></i></a>
    </div>
</x-filter-bar>

<div class="row g-3 mb-3">
    @foreach([
        ['label' => __('reports.department_comparison.total_departments'), 'value' => $totals['departments'], 'icon' => 'ti-building-hospital', 'color' => 'primary'],
        ['label' => __('reports.department_comparison.total_activity'), 'value' => $totals['activity'], 'icon' => 'ti-activity', 'color' => 'info'],
        ['label' => __('reports.department_comparison.total_services'), 'value' => $totals['services'], 'icon' => 'ti-list-details', 'color' => 'success'],
        ['label' => __('reports.department_comparison.total_pending'), 'value' => $totals['pending_work'], 'icon' => 'ti-clock', 'color' => 'warning'],
    ] as $card)
        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="avatar bg-{{ $card['color'] }} text-white"><i class="ti {{ $card['icon'] }}"></i></span>
                    <div>
                        <div class="text-muted small">{{ $card['label'] }}</div>
                        <div class="h4 mb-0">{{ number_format($card['value']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-end mb-2">
    @can('reports.department_comparison.export')
        <a href="{{ route('admin.reports.department-comparison.export', request()->query()) }}" class="btn btn-outline-primary btn-sm">
            <i class="ti ti-file-type-csv me-1"></i>{{ __('reports.department_comparison.export') }}
        </a>
    @endcan
</div>

@include('admin.dashboards.department.partials.comparison-table', [
    'rows' => $rows,
    'canViewRevenue' => $can_view_revenue,
    'canViewStock' => $can_view_stock,
])

@if(!empty($type_rollups))
<div class="card mt-3">
    <div class="card-header"><h6 class="fw-bold mb-0">{{ __('reports.department_comparison.type_rollups') }}</h6></div>
    <div class="card-body">
        <div class="row g-2">
            @foreach($type_rollups as $rollup)
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100">
                        <div class="fw-semibold">{{ $rollup['type_label'] }}</div>
                        <div class="small text-muted">{{ __('reports.department_comparison.departments_count', ['count' => $rollup['departments']]) }}</div>
                        <div class="d-flex gap-3 small mt-2">
                            <span>{{ __('reports.department_comparison.activity') }}: <strong>{{ $rollup['activity'] }}</strong></span>
                            <span>{{ __('reports.department_comparison.services') }}: <strong>{{ $rollup['services'] }}</strong></span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
