@extends('layouts.app')
@section('title', 'Visit Preview — ' . $visit->visit_number)

@section('content')
{{-- ── Page Header ──────────────────────────────────────────────── --}}
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">
            <i class="ti ti-eye-search me-2 text-primary"></i>Visit Preview
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
        <a href="{{ route('admin.visits.mar-chart', $visit) }}" class="btn btn-primary btn-sm">
            <i class="ti ti-layout-grid me-1"></i>MAR Chart
        </a>
        @endcan
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-printer me-1"></i>Print Summary
        </button>
        @can('visits.view')
        <a href="{{ route('admin.visits.show', $visit) }}" class="btn btn-outline-info btn-sm">
            <i class="ti ti-layout-list me-1"></i>Visit Detail
        </a>
        @endcan
        @can('visits.view')
        <a href="{{ route('admin.visits.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="ti ti-arrow-left me-1"></i>Back to Visits
        </a>
        @endcan
    </div>
</div>

{{-- ── Visit + Patient header bar ────────────────────────────────── --}}
<div class="alert alert-light border mb-4 d-flex align-items-center gap-3 flex-wrap">
    <div>
        <span class="text-muted small">Patient</span><br>
        <strong>{{ $visit->patient?->full_name ?? '—' }}</strong>
        @if($visit->patient?->patient_number)
            <small class="text-muted ms-1">({{ $visit->patient->patient_number }})</small>
        @endif
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">Visit</span><br>
        <strong>{{ $visit->visit_number }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">Type</span><br>
        <strong>{{ $visit->visit_type?->label() ?? '—' }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">Status</span><br>
        <strong>{{ $visit->status?->label() ?? '—' }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">Doctor</span><br>
        <strong>{{ $visit->currentConsultationDoctor()?->full_name ?? '—' }}</strong>
    </div>
    <div class="vr d-none d-sm-block"></div>
    <div>
        <span class="text-muted small">Department</span><br>
        <strong>{{ $visit->currentDepartment?->name ?? '—' }}</strong>
    </div>
</div>

{{-- ── Summary + Timeline via shared partial ──────────────────── --}}
@include('visits.partials.visit-preview-summary', [
    'visit'   => $visit,
    'preview' => $preview,
])

@component('components.footer')
@endcomponent
@endsection
