@extends('layouts.app')
@section('title', __('nursing.tasks.title'))
@section('content')
@php($taskShowRoute = ($workspaceContext['workspaceKey'] ?? null) === 'inpatient' ? 'inpatient.tasks.show' : 'nursing.tasks.show')
<x-page-header :title="__('nursing.tasks.title')" :description="$department->name" icon="ti-checklist" />
<div class="card border shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead><tr><th>{{ __('nursing.common.patient') }}</th><th>{{ __('nursing.common.description') }}</th><th>{{ __('nursing.common.status') }}</th><th>{{ __('nursing.common.due') }}</th><th>{{ __('nursing.common.assigned_to') }}</th><th></th></tr></thead>
    <tbody>@forelse($tasks as $task)<tr><td>{{ $task->patient?->patient_number }}</td><td><div class="fw-semibold">{{ $task->title }}</div><small class="text-muted">{{ $task->task_type }}</small></td><td><span class="badge bg-light text-dark">{{ $task->computed_status }}</span></td><td>{{ $task->due_at?->format('d M Y H:i') ?? '—' }}</td><td>{{ $task->assignedUser?->name ?? __('nursing.common.not_assigned') }}</td><td class="text-end"><a class="btn btn-sm btn-primary" href="{{ route($taskShowRoute, $task) }}">{{ __('nursing.common.view') }}</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">{{ __('nursing.tasks.empty') }}</td></tr>@endforelse</tbody>
</table></div></div><div class="mt-3">{{ $tasks->links() }}</div>
@endsection
