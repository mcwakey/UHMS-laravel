@extends('layouts.app')
@section('title', 'Merged Patient Folder')

@section('content')
<div class="d-flex mb-3">
    <h6 class="fw-bold mb-0 d-flex align-items-center">
        <a href="{{ route('admin.patients.index') }}" class="text-dark"><i class="ti ti-chevron-left me-1"></i>Patients</a>
    </h6>
</div>

<div class="alert alert-warning d-flex align-items-start" role="alert">
    <i class="ti ti-lock fs-20 me-3 flex-shrink-0 mt-1"></i>
    <div>
        <h5 class="fw-bold mb-1">This patient folder has been merged and locked</h5>
        <p class="mb-2">
            {{ $patient->patient_number }} for {{ $patient->full_name }} was merged
            @if($patient->merged_at)
                on {{ $patient->merged_at->format('d M Y H:i') }}
            @endif
            into the main folder below.
        </p>
        @if($patient->mergedToPatient)
            <a href="{{ route('admin.patients.show', $patient->mergedToPatient) }}" class="btn btn-primary btn-sm">
                Open {{ $patient->mergedToPatient->patient_number }} - {{ $patient->mergedToPatient->full_name }}
            </a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Retained Aliases</h5>
    </div>
    <div class="card-body">
        @forelse($patient->mergedToPatient?->aliases ?? collect() as $alias)
            <span class="badge bg-light text-dark border me-1 mb-1">{{ $alias->alias_type }}: {{ $alias->alias_value }}</span>
        @empty
            <span class="text-muted">No aliases recorded.</span>
        @endforelse
    </div>
</div>
@endsection
