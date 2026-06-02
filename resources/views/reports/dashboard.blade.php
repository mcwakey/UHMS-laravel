@extends('layouts.app')
@section('title', 'Reports Dashboard')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
    <div>
        <h4 class="fw-bold mb-1"><i class="ti ti-chart-bar me-2 text-primary"></i>Reports Dashboard</h4>
        <p class="text-muted mb-0">Cross-module UHMS reporting overview for clinical, operational, financial, stock, and blood-bank work.</p>
    </div>
</div>

<form method="GET" class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">From</label><input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-md-4"><label class="form-label">To</label><input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-md-4"><button class="btn btn-primary w-100">Refresh Dashboard</button></div>
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
            <div class="card-header bg-white"><h5 class="card-title mb-0">Financial Summary</h5></div>
            <div class="card-body">
                @foreach([
                    'Billed' => $financial['billed'],
                    'Collected' => $financial['collected'],
                    'Outstanding' => $financial['outstanding'],
                    'Claims Total' => $financial['claims_total'],
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
            <div class="card-header bg-white"><h5 class="card-title mb-0">Rendering Risk</h5></div>
            <div class="card-body">
                <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Billed Not Rendered</span><strong>{{ $renderingRisk['billed_not_rendered'] }}</strong></div>
                <div class="d-flex justify-content-between py-2"><span class="text-muted">Rendered Unpaid</span><strong>{{ $renderingRisk['rendered_unpaid'] }}</strong></div>
                <a href="{{ route('admin.service-renderings.reports') }}" class="btn btn-outline-primary btn-sm mt-3">Open Service Rendering Reports</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white"><h5 class="card-title mb-0">Report Catalogue</h5></div>
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
