@extends('layouts.app')
@section('title', __('nursing.case.title'))
@section('content')
<x-page-header :title="__('nursing.case.title').' · '.$visit->visit_number" :description="$visit->patient?->patient_number" icon="ti-stethoscope" />

<div class="card border-start border-primary border-4 mb-3"><div class="card-body d-flex flex-wrap justify-content-between gap-3">
    <div><small class="text-muted">{{ __('nursing.case.patient_identifier') }}</small><div class="fw-bold fs-5">{{ $visit->patient?->patient_number }}</div></div>
    <div><small class="text-muted">{{ __('nursing.common.status') }}</small><div><span class="badge bg-{{ $visit->status?->color() ?? 'secondary' }}">{{ $visit->status?->translatedLabel() ?? $visit->status }}</span></div></div>
    <div><small class="text-muted">{{ __('nursing.common.department') }}</small><div>{{ $visit->currentDepartment?->name ?? '—' }}</div></div>
    <div class="d-flex gap-2 align-items-center">
        @can('vitals.create')<a href="{{ route('nursing.triage.create', $visit) }}" class="btn btn-outline-primary">{{ __('nursing.case.record_triage') }}</a><a href="{{ route('nursing.vitals.create', ['visit_id' => $visit->id]) }}" class="btn btn-primary">{{ __('nursing.case.record_vitals') }}</a>@endcan
    </div>
</div></div>

<div class="row g-3">
    <div class="col-xl-6"><div class="card border h-100"><div class="card-header"><h5 class="mb-0">{{ __('nursing.case.triage') }}</h5></div><div class="card-body">
        @if($visit->triage)<dl class="row mb-0"><dt class="col-5">{{ __('nursing.common.status') }}</dt><dd class="col-7">{{ $visit->triage->triage_score?->label() ?? '—' }}</dd><dt class="col-5">{{ __('nursing.common.recorded_at') }}</dt><dd class="col-7">{{ $visit->triage->triaged_at?->format('d M Y H:i') }}</dd><dt class="col-5">{{ __('nursing.common.recorded_by') }}</dt><dd class="col-7">{{ $visit->triage->triagedBy?->name ?? '—' }}</dd></dl>@else<p class="text-muted mb-0">{{ __('nursing.case.no_data') }}</p>@endif
    </div></div></div>
    <div class="col-xl-6"><div class="card border h-100"><div class="card-header"><h5 class="mb-0">{{ __('nursing.case.vitals') }}</h5></div><div class="card-body">
        @forelse($visit->vitals as $vital)<div class="border-bottom py-2"><span class="fw-semibold">BP {{ $vital->blood_pressure ?? '—' }}</span> · HR {{ $vital->heart_rate ?? '—' }} · SpO₂ {{ $vital->spo2 ?? '—' }}% · {{ $vital->temperature ?? '—' }}°C <small class="text-muted d-block">{{ $vital->recorded_at?->format('d M Y H:i') }} · {{ $vital->recordedBy?->name }}</small></div>@empty<p class="text-muted mb-0">{{ __('nursing.case.no_data') }}</p>@endforelse
    </div></div></div>
    <div class="col-xl-6"><div class="card border h-100"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">{{ __('nursing.case.tasks') }}</h5><a href="{{ route('nursing.tasks.index') }}">{{ __('nursing.common.view') }}</a></div><div class="card-body">
        @forelse($visit->clinicalTasks as $task)<div class="border-bottom py-2"><span class="fw-semibold">{{ $task->title }}</span><span class="badge bg-light text-dark float-end">{{ $task->computed_status }}</span><small class="text-muted d-block">{{ $task->due_at?->format('d M H:i') }}</small></div>@empty<p class="text-muted mb-0">{{ __('nursing.case.no_data') }}</p>@endforelse
    </div></div></div>
    <div class="col-xl-6"><div class="card border h-100"><div class="card-header"><h5 class="mb-0">{{ __('nursing.case.treatments') }}</h5></div><div class="card-body">
        @forelse($treatments as $treatment)<div class="border-bottom py-2"><span class="fw-semibold">{{ $treatment->type }}</span><div>{{ $treatment->description }}</div><small class="text-muted">{{ $treatment->doctor?->name }}</small></div>@empty<p class="text-muted mb-0">{{ __('nursing.case.no_data') }}</p>@endforelse
    </div></div></div>
</div>
@endsection
