@extends('layouts.app')
@section('title', __('reports.hub_title'))

@section('content')
<x-page-header
    :title="__('reports.hub_title')"
    :description="__('reports.hub_description')"
    icon="ti-report-analytics" />

@foreach($sections as $sectionKey => $reports)
    @php $meta = $sectionMeta[$sectionKey] ?? ['title' => ucwords(str_replace('_', ' ', $sectionKey)), 'icon' => 'ti-report']; @endphp
    <div class="mb-4">
        <h5 class="fw-semibold text-muted mb-2">
            <i class="ti {{ $meta['icon'] }} me-1"></i>{{ $meta['title'] }}
        </h5>
        <div class="row g-3">
            @foreach($reports as $report)
                @php $routeExists = \Illuminate\Support\Facades\Route::has($report['route']); @endphp
                <div class="col-12 col-sm-6 col-xl-4">
                    <a href="{{ $routeExists ? route($report['route']) : '#' }}"
                       class="card text-decoration-none h-100 {{ $routeExists ? 'hover-shadow' : 'opacity-50 pe-none' }}">
                        <div class="card-body d-flex align-items-start gap-3">
                            <span class="badge bg-primary-subtle text-primary p-2 mt-1">
                                <i class="ti {{ $report['icon'] ?? 'ti-report' }} fs-5"></i>
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-dark lh-sm">{{ $report['title'] }}</div>
                                <div class="small text-muted mt-1">{{ $report['description'] }}</div>
                                <div class="mt-2 d-flex gap-1 flex-wrap">
                                    @if($report['exportable'])
                                        <span class="badge bg-success-subtle text-success"><i class="ti ti-file-type-csv me-1"></i>{{ __('reports.export_csv') }}</span>
                                    @endif
                                    @if($report['printable'])
                                        <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-printer me-1"></i>{{ __('reports.print_label') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@if(empty($sections))
    <x-empty-state
        icon="ti-report-off"
        :title="__('reports.no_records')"
        :message="__('reports.hub_description')" />
@endif
@endsection

@push('styles')
<style>
.hover-shadow { transition: box-shadow .15s ease, transform .15s ease; }
.hover-shadow:hover { box-shadow: 0 .25rem .75rem rgba(0,0,0,.10); transform: translateY(-1px); }
</style>
@endpush
