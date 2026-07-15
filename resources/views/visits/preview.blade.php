@extends('layouts.app')
@section('title', __('visits.preview_title') . ' — ' . $visit->visit_number)

@section('content')
<x-page-header-back
        :title="__('visits.preview_title')"
        :href="$workspaceRoutes->route('admin.visits.show', $visit)"
    >
    <x-slot:actions>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-printer me-1"></i>{{ __('visits.print_summary') }}
        </button>
    </x-slot:actions>
</x-page-header-back>

<!-- <div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <i class="ti ti-eye-search me-2 text-primary"></i>{{ __('visits.preview_title') }}
        </h4>
        <small class="text-muted">
            {{ $visit->visit_number }}
            @if($visit->patient)
                &nbsp;·&nbsp;{{ $visit->patient->full_name }}
            @endif
            @if($visit->visit_date)
                &nbsp;·&nbsp;{{ $visit->visit_date->format('d M Y') }}
            @endif
        </small>
    </div>

    <div class="d-flex gap-2 flex-wrap">
        @can('mar_chart.view')
        <a href="{{ $workspaceRoutes->route('admin.visits.mar-chart', $visit) }}" class="btn btn-primary btn-sm">
            <i class="ti ti-layout-grid me-1"></i>{{ __('visits.mar_chart') }}
        </a>
        @endcan
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-printer me-1"></i>{{ __('visits.print_summary') }}
        </button>
        @can('visits.view')
        <a href="{{ $workspaceRoutes->route('admin.visits.show', $visit) }}" class="btn btn-outline-info btn-sm">
            <i class="ti ti-layout-list me-1"></i>{{ __('visits.visit_detail_btn') }}
        </a>
        @endcan
        @can('visits.view')
        <a href="{{ $workspaceRoutes->route('admin.visits.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>{{ __('visits.back_to_visits_btn') }}
        </a>
        @endcan
    </div>
</div> -->

{{-- ── Visit + Patient header bar ────────────────────────────────── --}}
<div class="alert alert-light border mb-4 d-flex align-items-center gap-3 flex-wrap">
    <div>
        <span class="text-muted small">{{ __('visits.patient_header') }}</span><br>
        <strong>{{ $visit->patient?->full_name ?? '—' }}</strong>
            · <small class="text-muted ms-1">{{ $visit->patient->gender }} · {{ $visit->patient->age }}y</small>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('patients.patient_id') }}</span><br>
        <strong>{{ $visit->patient->patient_number }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('visits.visit_header') }}</span><br>
        <strong>{{ $visit->visit_number }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('visits.type_header') }}</span><br>
        <strong>{{ $visit->visit_type?->translatedLabel() ?? '—' }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('visits.status_header') }}</span><br>
        <strong>{{ $visit->status?->translatedLabel() ?? '—' }}</strong>
    </div>
    <!-- <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('visits.doctor_header') }}</span><br>
        <strong>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</strong>
    </div> -->
    <!-- <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('visits.dept_header') }}</span><br>
        <strong>{{ $visit->currentDepartment?->name ?? '—' }}</strong>
    </div> -->
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">{{ __('visits.date_header') }}</span><br>
        <strong>{{ $visit->visit_date->format('d M Y') }}</strong>
    </div>
</div>

{{-- ── Summary + Timeline via shared partial ──────────────────── --}}
@include('visits.partials.visit-preview-summary', [
    'visit'   => $visit,
    'preview' => $preview,
])

@endsection
