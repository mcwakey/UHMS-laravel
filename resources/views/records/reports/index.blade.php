@extends('layouts.app')

@section('title', __('records.reports.title'))

@section('content')
<x-page-header :title="__('records.reports.title')" :description="__('records.reports.description')" icon="ti-report-analytics" />

<div class="row g-3">
    @foreach($reports as $report)
        <div class="col-md-4">
            <a href="{{ route($report['route']) }}" class="card h-100 text-decoration-none text-reset">
                <div class="card-body">
                    <i class="ti {{ $report['icon'] }} fs-28 text-primary"></i>
                    <h5 class="mt-3">{{ __('records.reports.'.$report['key']) }}</h5>
                    <p class="text-muted mb-0">{{ __('records.reports.'.$report['key'].'_description') }}</p>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
