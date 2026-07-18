@extends('layouts.app')
@section('title', __('nursing.treatments.details'))
@section('content')
<x-page-header :title="__('nursing.treatments.details')" :description="$visit->visit_number.' · '.$visit->patient?->patient_number" icon="ti-first-aid-kit" />
<div class="card border shadow-sm"><div class="card-body">
    @forelse($treatments as $treatment)<div class="border-bottom py-3"><h6>{{ $treatment->type }}</h6><p class="mb-1">{{ $treatment->description }}</p><small class="text-muted">{{ $treatment->doctor?->name }} · {{ $treatment->created_at?->format('d M Y H:i') }}</small></div>@empty<p class="text-muted mb-0">{{ __('nursing.treatments.empty') }}</p>@endforelse
    <a class="btn btn-primary mt-3" href="{{ ($workspaceContext['workspaceKey'] ?? null) === 'inpatient' ? route('inpatient.visits.show', $visit) : route('nursing.opd.show', $visit) }}">{{ __('nursing.case.title') }}</a>
</div></div>
@endsection
