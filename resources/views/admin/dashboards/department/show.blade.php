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

@if(!empty($primary_cards))
<div class="row g-3 mb-3">
    @foreach($primary_cards as $card)
        <div class="col-xl-3 col-md-6">
            @include('admin.dashboards.department.partials.kpi-card', ['card' => $card, 'theme' => $theme])
        </div>
    @endforeach
</div>
@endif

@if(!empty($secondary_cards))
<div class="row g-2 mb-3">
    @foreach($secondary_cards as $card)
        <div class="col-xl-2 col-md-4 col-6">
            @include('admin.dashboards.department.partials.mini-kpi-card', ['card' => $card])
        </div>
    @endforeach
</div>
@endif

<div class="row g-3">
    <div class="col-xl-8">
        @include('admin.dashboards.department.partials.work-queue-card', ['queue' => $work_queue, 'theme' => $theme])
        @include('admin.dashboards.department.partials.chart-card', ['chart' => $charts['activity_trend'] ?? null, 'trends' => $trends, 'theme' => $theme])
        <div class="row g-3">
            <div class="col-lg-6">
                @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['queue_status_breakdown'] ?? [], 'theme' => $theme])
            </div>
            <div class="col-lg-6">
                @include('admin.dashboards.department.partials.status-breakdown-card', ['chart' => $charts['request_status_breakdown'] ?? [], 'theme' => $theme])
            </div>
        </div>
        @include('admin.dashboards.department.partials.activity-list', ['activities' => $activities])
    </div>
    <div class="col-xl-4">
        @include('admin.dashboards.department.partials.quick-actions', ['quick_actions' => $quick_actions, 'theme' => $theme])
        @include('admin.dashboards.department.partials.services-card', ['services' => $services])
        @include('admin.dashboards.department.partials.stock-usage-card', ['stock_usage' => $stock_usage])
        @foreach($restricted ?? [] as $restrictedCard)
            @include('admin.dashboards.department.partials.restricted-card', ['card' => $restrictedCard])
        @endforeach
    </div>
</div>
@endsection
