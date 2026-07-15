@extends('layouts.app')
@section('title', __('patients.merged_folder_title'))

@section('content')
<x-page-header-back
    :title="__('patients.title')"
    :href="$workspaceRoutes->route('admin.patients.index')"
/>

<div class="alert alert-warning d-flex align-items-start" role="alert">
    <i class="ti ti-lock fs-20 me-3 flex-shrink-0 mt-1"></i>
    <div>
        <h5 class="fw-bold mb-1">{{ __('patients.merged_locked_title') }}</h5>
        <p class="mb-2">
            {{ $patient->patient_number }} for {{ $patient->full_name }} {{ __('patients.merged') }}
            @if($patient->merged_at)
                {{ __('patients.merged_on') }} {{ $patient->merged_at->format('d M Y H:i') }}
            @endif
            {{ __('patients.merged_locked_body_suffix') }}
        </p>
        @if($patient->mergedToPatient)
            <a href="{{ $workspaceRoutes->route('admin.patients.show', $patient->mergedToPatient) }}" class="btn btn-primary btn-sm">
                {{ __('patients.open_main_folder', ['number' => $patient->mergedToPatient->patient_number, 'name' => $patient->mergedToPatient->full_name]) }}
            </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('patients.retained_aliases') }}</h5>
    </div>
    <div class="card-body">
        @forelse($patient->mergedToPatient?->aliases ?? collect() as $alias)
            <span class="badge bg-light text-dark border me-1 mb-1">{{ $alias->alias_type }}: {{ $alias->alias_value }}</span>
        @empty
            <span class="text-muted">{{ __('patients.no_aliases') }}</span>
        @endforelse
    </div>
</div>
@endsection
