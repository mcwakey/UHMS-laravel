{{--
    Shared consultation (doctor-ordered) tasks table.
    Params:
        $tasks (required)   — collection of ConsultationTask
        $detailed (bool)    — show description + completed-at column (default false)
--}}
@php $detailed = $detailed ?? false; @endphp
@if($tasks->count() > 0)
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="bg-light">
            <tr>
                <th style="width:3rem"></th>
                <th>{{ __('admissions.task_col') }}</th>
                <th style="width:8rem">{{ __('admissions.priority_col') }}</th>
                <th style="width:12rem">{{ __('admissions.assigned_to_col') }}</th>
                <th style="width:8rem">{{ __('admissions.due_col') }}</th>
                <th style="width:8rem">{{ __('admissions.status_col') }}</th>
                @if($detailed)<th style="width:9rem">{{ __('admissions.completed_col') }}</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($tasks->sortBy('status') as $task)
            @php $isDone = $task->status === 'completed'; @endphp
            <tr class="{{ $isDone ? 'table-success' : '' }}">
                <td class="text-center">
                    <form method="POST" action="{{ route('admin.consultations.tasks.toggle', $task) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-sm {{ $isDone ? 'btn-success' : 'btn-outline-success' }} p-1" title="{{ $isDone ? __('admissions.mark_pending_title') : __('admissions.mark_done_title') }}">
                            <i class="ti ti-check fs-13"></i>
                        </button>
                    </form>
                </td>
                <td>
                    <div class="{{ $isDone ? 'task-done' : 'fw-medium' }}">{{ $task->title }}</div>
                    @if($detailed && $task->description)<small class="text-muted">{{ Str::limit($task->description, 80) }}</small>@endif
                </td>
                <td><span class="badge bg-{{ match($task->priority) { 'high'=>'danger','medium'=>'warning',default=>'secondary' } }}">{{ ucfirst($task->priority ?? 'normal') }}</span></td>
                <td>{{ $task->assignedUser->name ?? '—' }}</td>
                <td>@if($task->due_date)<span class="{{ !$isDone && $task->due_date->isPast() ? 'text-danger fw-medium' : '' }}">{{ $task->due_date->format('d M') }}</span>@else—@endif</td>
                <td><span class="badge badge-soft-{{ match($task->status){ 'completed'=>'success','in_progress'=>'info','cancelled'=>'secondary',default=>'warning'} }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                @if($detailed)<td>@if($task->completed_at)<small>{{ $task->completed_at->format('d M H:i') }}</small>@else—@endif</td>@endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-4 text-muted"><i class="ti ti-clipboard-off fs-1 d-block mb-2"></i>{{ __('admissions.no_tasks') }}</div>
@endif
