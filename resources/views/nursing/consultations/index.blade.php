@extends('layouts.app')
@section('title', __('nursing.consultations.title'))
@section('content')
<x-page-header :title="__('nursing.consultations.title')" :description="__('nursing.consultations.subtitle', ['department' => $department->name])" icon="ti-stethoscope" />

<form method="GET" action="{{ route('nursing.consultations.index') }}" class="card card-body border mb-3">
    <div class="row g-2">
        <div class="col-md-6"><input name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('nursing.queue.search_placeholder') }}"></div>
        <div class="col-md-3"><select name="status" class="form-select"><option value="">{{ __('nursing.common.status') }}</option>@foreach(['PENDING', 'ACTIVE', 'PAUSED', 'COMPLETED', 'CANCELLED'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill">{{ __('nursing.common.filter') }}</button><a href="{{ route('nursing.consultations.index') }}" class="btn btn-outline-secondary">{{ __('nursing.common.reset') }}</a></div>
    </div>
</form>

<div class="card border shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>{{ __('nursing.common.patient') }}</th><th>{{ __('nursing.common.visit') }}</th><th>{{ __('nursing.consultations.sessions') }}</th><th>{{ __('nursing.common.status') }}</th><th>{{ __('nursing.consultations.clinician') }}</th><th></th></tr></thead>
    <tbody>@forelse($visits as $visit)
        @php($current = $visit->consultationRoutes->sortByDesc('id')->first())
        <tr><td class="fw-semibold">{{ $visit->patient?->patient_number }}</td><td>{{ $visit->visit_number }}</td><td>{{ $visit->consultation_routes_count }}</td><td><span class="badge bg-light text-dark">{{ $current?->status ?? '—' }}</span></td><td>{{ $current?->doctor?->name ?? __('nursing.common.not_assigned') }}</td><td class="text-end"><a href="{{ route('nursing.consultations.show', $visit) }}" class="btn btn-sm btn-primary">{{ __('nursing.common.view') }}</a></td></tr>
    @empty<tr><td colspan="6" class="text-center text-muted py-5">{{ __('nursing.consultations.empty') }}</td></tr>@endforelse</tbody>
</table></div></div><div class="mt-3">{{ $visits->links() }}</div>
@endsection
