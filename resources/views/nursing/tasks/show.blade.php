@extends('layouts.app')
@section('title', __('nursing.tasks.details'))
@section('content')
@php
    $inpatientWorkspace = ($workspaceContext['workspaceKey'] ?? null) === 'inpatient';
    $taskUpdateRoute = $inpatientWorkspace ? 'inpatient.tasks.update' : 'nursing.tasks.update';
@endphp
<x-page-header :title="__('nursing.tasks.details')" :description="$task->visit?->visit_number" icon="ti-checklist" />
<div class="card border shadow-sm"><div class="card-body"><dl class="row mb-0">
    <dt class="col-md-3">{{ __('nursing.common.patient') }}</dt><dd class="col-md-9">{{ $task->patient?->patient_number }}</dd>
    <dt class="col-md-3">{{ __('nursing.common.description') }}</dt><dd class="col-md-9"><strong>{{ $task->title }}</strong><br>{{ $task->description }}</dd>
    <dt class="col-md-3">{{ __('nursing.common.status') }}</dt><dd class="col-md-9">{{ $task->computed_status }}</dd>
    <dt class="col-md-3">{{ __('nursing.common.due') }}</dt><dd class="col-md-9">{{ $task->due_at?->format('d M Y H:i') ?? '—' }}</dd>
    <dt class="col-md-3">{{ __('nursing.common.assigned_to') }}</dt><dd class="col-md-9">{{ $task->assignedUser?->name ?? __('nursing.common.not_assigned') }}</dd>
  </dl>
  <div class="d-flex flex-wrap gap-2 mt-3">
    @if($task->visit)<a class="btn btn-outline-primary" href="{{ $inpatientWorkspace ? route('inpatient.visits.show', $task->visit) : route('nursing.opd.show', $task->visit) }}">{{ __('nursing.case.title') }}</a>@endif
    @can('clinical_tasks.complete')
      @if(!$task->assigned_to)<form method="POST" action="{{ route($taskUpdateRoute, $task) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="claim"><button class="btn btn-primary">{{ __('nursing.tasks.claim') }}</button></form>@endif
      @if(!in_array($task->status, [\App\Models\ClinicalTask::STATUS_IN_PROGRESS, \App\Models\ClinicalTask::STATUS_COMPLETED]))<form method="POST" action="{{ route($taskUpdateRoute, $task) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="start"><button class="btn btn-primary">{{ __('nursing.tasks.start') }}</button></form>@endif
      @if($task->status !== \App\Models\ClinicalTask::STATUS_COMPLETED)<form method="POST" action="{{ route($taskUpdateRoute, $task) }}">@csrf @method('PATCH')<input type="hidden" name="action" value="complete"><button class="btn btn-success">{{ __('nursing.tasks.complete') }}</button></form>@endif
    @endcan
  </div>
</div></div>
@endsection
