@extends('layouts.app')
@section('title', __('reports.reports_dashboard'))

@section('content')
<x-page-header
    :title="__('reports.reports_dashboard')"
    icon="ti-chart-bar"
    description="Cross-module UHMS reporting overview for clinical, operational, financial, stock, and blood-bank work.">
    <x-slot:actions>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-report-analytics me-1"></i>{{ __('reports.hub_title') }}
        </a>
    </x-slot:actions>
</x-page-header>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">{{ __('reports.date_from') }}</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-4"><label class="form-label">{{ __('reports.date_to') }}</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-md-4"><button class="btn btn-primary w-100">{{ __('reports.refresh_dashboard') }}</button></div>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    @foreach($cards as $card)
        <div class="col-6 col-xl-3">
            <a href="{{ route($card['route'], $filters) }}" class="text-decoration-none">
                <div class="card h-100 border-start border-{{ $card['color'] }} border-3">
                    <div class="card-body py-3">
                        <div class="small text-muted">{{ $card['label'] }}</div>
                        <div class="h3 mb-0 text-dark">{{ $card['value'] }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.management.financial_summary') }}</h5></div>
            <div class="card-body">
                @foreach([
                    __('reports.management.billed')       => $financial['billed'],
                    __('reports.management.collected')    => $financial['collected'],
                    __('reports.management.outstanding')  => $financial['outstanding'],
                    __('reports.management.claims_total') => $financial['claims_total'],
                ] as $label => $value)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">{{ $label }}</span>
                        <strong>GHS {{ number_format((float) $value, 2) }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.management.rendering_risk') }}</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">{{ __('reports.management.billed_not_rendered') }}</span><strong>{{ $renderingRisk['billed_not_rendered'] }}</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">{{ __('reports.management.rendered_unpaid') }}</span><strong>{{ $renderingRisk['rendered_unpaid'] }}</strong></div>
                @if(\Illuminate\Support\Facades\Route::has('admin.service-renderings.reports'))
                    <a href="{{ route('admin.service-renderings.reports') }}" class="btn btn-outline-primary btn-sm mt-3">{{ __('reports.management.open_service_rendering_reports') }}</a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">{{ __('reports.management.report_catalogue') }}</h5></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($catalogue as $key => $report)
                        <div class="col-6"><a class="btn btn-light btn-sm w-100 text-start" href="{{ route('admin.reports.'.$key, $filters) }}">{{ $report['title'] }}</a></div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
