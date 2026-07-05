@extends('layouts.app')
@section('title', __('maternity.maternity_reports'))
@section('content')
<x-page-header :title="__('maternity.maternity_reports')" icon="ti-report-analytics">
    <x-slot:actions>
        <a href="{{ route('admin.maternity.billing-readiness.show') }}" class="btn btn-outline-primary btn-md fs-13">{{ __('maternity.billing_mapping_readiness') }}</a>
        <a href="{{ route('admin.maternity.dashboard') }}" class="btn btn-outline-secondary btn-md fs-13">{{ __('common.back') }}</a>
    </x-slot:actions>
</x-page-header>

<div class="row g-3 mb-3">
    @foreach($overview['cards'] as $key => $value)
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-3"><div class="fs-4 fw-bold">{{ $value }}</div><small class="text-muted">{{ __('maternity.report_cards.'.$key) }}</small></div></div>
    </div>
    @endforeach
</div>

<div class="row g-3">
    @foreach($reports as $report)
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">{{ __('maternity.report_names.'.$report) }}</h5>
                <p class="text-muted mb-3">{{ __('maternity.report_descriptions.'.$report) }}</p>
                <a class="btn btn-outline-primary" href="{{ route('admin.maternity.reports.'.$report) }}">{{ __('common.view') }}</a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
