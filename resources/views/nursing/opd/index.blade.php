@extends('layouts.app')
@section('title', __('nursing.queue.title'))
@section('content')
<x-page-header :title="__('nursing.queue.title')" :description="__('nursing.queue.subtitle', ['department' => $department->name])" icon="ti-list-numbers" />

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach(['all', 'active', 'waiting_for_triage', 'triage_in_progress', 'vitals_incomplete', 'waiting_for_consultation', 'nursing_action_required', 'completed_today'] as $tab)
        <a href="{{ route('nursing.opd.queue', ['category' => $tab]) }}" class="btn btn-sm {{ $category === $tab ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ __('nursing.queue.'.$tab) }} @if($tab !== 'all' && isset($metrics[$tab])) <span class="badge bg-light text-dark ms-1">{{ $metrics[$tab] }}</span>@endif
        </a>
    @endforeach
</div>

<form class="card card-body border mb-3" method="GET" action="{{ route('nursing.opd.queue') }}">
    <input type="hidden" name="category" value="{{ $category }}">
    <div class="row g-2">
        <div class="col-md-6"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="{{ __('nursing.queue.search_placeholder') }}"></div>
        <div class="col-md-3"><select class="form-select" name="priority"><option value="">{{ __('nursing.common.priority') }}</option>@foreach(\App\Enums\Priority::cases() as $priority)<option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>@endforeach</select></div>
        <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary flex-fill">{{ __('nursing.common.filter') }}</button><a class="btn btn-outline-secondary" href="{{ route('nursing.opd.queue') }}">{{ __('nursing.common.reset') }}</a></div>
    </div>
</form>

<div class="card border shadow-sm"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0"><thead><tr><th>{{ __('nursing.common.patient') }}</th><th>{{ __('nursing.common.visit') }}</th><th>{{ __('nursing.common.status') }}</th><th>{{ __('nursing.common.priority') }}</th><th>{{ __('nursing.queue.waiting_time') }}</th><th>{{ __('nursing.metrics.vitals_incomplete') }}</th><th></th></tr></thead>
    <tbody>
    @forelse($visits as $visit)
        <tr>
            <td class="fw-semibold">{{ $visit->patient?->patient_number }}</td><td>{{ $visit->visit_number }}</td>
            <td><span class="badge bg-{{ $visit->status?->color() ?? 'secondary' }}">{{ $visit->status?->translatedLabel() ?? $visit->status }}</span></td>
            <td>{{ $visit->priority?->label() ?? '—' }}</td>
            <td>{{ ($visit->arrived_at ?? $visit->checked_in_at)?->diffForHumans(null, true) ?? '—' }}</td>
            <td><span class="badge bg-{{ $visit->latestVitals ? 'success' : 'warning' }}">{{ $visit->latestVitals ? __('common.yes') : __('common.no') }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route('nursing.opd.show', $visit) }}">{{ __('nursing.common.view') }}</a></td>
        </tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-5">{{ __('nursing.queue.empty') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
<div class="mt-3">{{ $visits->links() }}</div>
@endsection
