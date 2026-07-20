@include('admissions.partials.nursing-chat-styles')

@php
    $tasks = collect($tasks ?? [])->sortBy(fn ($task) => $task->due_at ?? $task->created_at)->values();
    $currentUserId = auth()->id();
    $initialsFor = function ($user) {
        $name = trim((string) ($user?->name ?? $user?->full_name ?? ''));
        if ($name === '') {
            return 'T';
        }

        $parts = preg_split('/\s+/', $name);
        return strtoupper(mb_substr($parts[0] ?? 'T', 0, 1).mb_substr($parts[count($parts) - 1] ?? '', 0, 1));
    };
@endphp

@forelse($tasks as $task)
    @php
        $author = $task->createdBy ?? $task->assignedTo;
        $isMine = $currentUserId && in_array((int) $currentUserId, array_filter([(int) ($task->created_by ?? 0), (int) ($task->assigned_to ?? 0)]), true);
        $isOpen = $task->status?->isOpen();
        $stamp = $task->created_at;
        $dueClass = $task->is_overdue ? 'text-danger fw-semibold' : 'text-muted';
    @endphp
    <div @class(['nursing-message', 'nursing-message--mine' => $isMine])>
        <div class="nursing-message__avatar">{{ $initialsFor($author) }}</div>
        <div class="nursing-message__bubble">
            <div class="nursing-message__meta">
                <span>
                    <span class="badge badge-soft-{{ $task->status?->color() ?? 'secondary' }} me-1">{{ $task->status?->label() ?? $task->status }}</span>
                    <strong>{{ $author->name ?? $author->full_name ?? __('admissions.general') }}</strong>
                </span>
                <span>{{ $stamp?->format('d M Y H:i') }}</span>
            </div>
            <div class="{{ $isOpen ? 'fw-semibold' : 'task-done fw-semibold' }}">{{ $task->title }}</div>
            @if($task->description)
                <div class="nursing-message__body mt-1">{{ $task->description }}</div>
            @endif
            <div class="d-flex flex-wrap gap-2 mt-2 small text-muted">
                <span><i class="ti ti-tag me-1"></i>{{ $task->task_type?->label() ?? __('admissions.general') }}</span>
                <span><i class="ti ti-flag me-1"></i>{{ ucfirst($task->priority ?? 'normal') }}</span>
                <span><i class="ti ti-user-check me-1"></i>{{ $task->assignedTo->name ?? __('admissions.unassigned') }}</span>
                <span class="{{ $dueClass }}"><i class="ti ti-calendar me-1"></i>{{ $task->due_at?->format('d M H:i') ?? '-' }}</span>
            </div>
            @if($isOpen)
                @can('admission.nursing.tasks.complete')
                <div class="nursing-message__actions">
                    <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.nursing-tasks.complete', [$admission, $task]) }}">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm btn-outline-success"><i class="ti ti-check me-1"></i>{{ __('admissions.mark_done_title') }}</button>
                    </form>
                </div>
                @endcan
            @endif
        </div>
    </div>
@empty
    <div class="text-center py-4 text-muted"><i class="ti ti-clipboard-off fs-1 d-block mb-2"></i>{{ __('admissions.no_nursing_tasks') }}</div>
@endforelse
