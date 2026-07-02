@extends('layouts.app')
@section('title', __('patients.privacy.historical_log_dry_run'))

@section('content')
<x-page-header-back
    :title="__('patients.privacy.historical_log_dry_run')"
    :href="route('admin.patient-privacy.audit')"
/>

<div class="card">
    <div class="card-body">
        <div class="alert alert-warning mb-3">
            <i class="ti ti-alert-triangle me-1"></i>{{ __('patients.privacy.dry_run_no_mutation') }}
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-lg rounded-circle bg-warning-subtle text-warning">
                <i class="ti ti-database-search fs-20"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1">{{ __('patients.privacy.historical_log_dry_run') }}</h5>
                <p class="mb-0 text-muted">{{ __('patients.privacy.dry_run_matches', ['count' => number_format($matches)]) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
