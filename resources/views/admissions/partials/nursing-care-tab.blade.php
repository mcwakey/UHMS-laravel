@php
    $careFlags = $careOverview['care_flags'] ?? collect();
    $openTasks = $careOverview['open_tasks'] ?? collect();
    $overdueTasks = $careOverview['overdue_tasks'] ?? collect();
    $recentNotes = $careOverview['recent_notes'] ?? collect();
    $handover = $careOverview['handover'] ?? [];
    $latestVitals = $careOverview['latest_vitals'] ?? null;
    $latestRound = $careOverview['latest_ward_round'] ?? null;
@endphp

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-nurse me-1"></i>{{ __('admissions.nursing_handover') }}</h5>
                @if($overdueTasks->isNotEmpty())
                    <span class="badge bg-danger">{{ $overdueTasks->count() }} {{ __('admissions.overdue_label') }}</span>
                @endif
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @forelse($careFlags as $flag)
                        <span class="badge bg-{{ $flag->color() }}">{{ $flag->label() }}</span>
                    @empty
                        <span class="badge badge-soft-secondary">{{ __('admissions.no_care_flags') }}</span>
                    @endforelse
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <tr><td class="text-muted">{{ __('admissions.location_card') }}</td><td class="fw-semibold">{{ $handover['location'] ?? '—' }}</td></tr>
                        <tr><td class="text-muted">{{ __('admissions.admitting_diagnosis_card') }}</td><td>{{ $handover['admitting_diagnosis'] ?: '—' }}</td></tr>
                        <tr><td class="text-muted">{{ __('admissions.last_vitals') }}</td><td class="{{ ($careOverview['vitals_overdue'] ?? false) ? 'text-danger fw-semibold' : '' }}">{{ $latestVitals?->recorded_at?->diffForHumans() ?? __('admissions.not_recorded') }}</td></tr>
                        <tr><td class="text-muted">{{ __('admissions.last_ward_round') }}</td><td class="{{ ($careOverview['ward_round_overdue'] ?? false) ? 'text-danger fw-semibold' : '' }}">{{ $latestRound?->round_date?->diffForHumans() ?? __('admissions.not_recorded') }}</td></tr>
                        <tr><td class="text-muted">{{ __('admissions.medications_due') }}</td><td>{{ $handover['medications_due'] ?? 0 }} {{ __('admissions.due_now_badge') }} · {{ $handover['medications_overdue'] ?? 0 }} {{ __('admissions.overdue_badge') }}</td></tr>
                        <tr><td class="text-muted">{{ __('admissions.open_nursing_tasks') }}</td><td>{{ $handover['open_tasks_count'] ?? 0 }}</td></tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-checklist me-1"></i>{{ __('admissions.inpatient_care_checklist') }}</h5></div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach(($careOverview['checklist'] ?? []) as $item)
                        @php
                            $status = $item['status'];
                            $icon = match($status) { 'complete' => 'ti-circle-check text-success', 'warning' => 'ti-alert-triangle text-danger', default => 'ti-clock text-warning' };
                        @endphp
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <i class="ti {{ $icon }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @can('admission.care_flags.manage')
        <div class="card mb-3">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-flag me-1"></i>{{ __('admissions.manage_care_flags') }}</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.care-flags.update', $admission) }}">
                    @csrf @method('PATCH')
                    <div class="row g-2">
                        @foreach(($careOverview['allowed_care_flags'] ?? collect()) as $flag)
                            <div class="col-sm-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="care_flags[]" value="{{ $flag->value }}" id="care-flag-{{ $flag->value }}" @checked(in_array($flag->value, $admission->care_flags ?? [], true))>
                                    <label class="form-check-label" for="care-flag-{{ $flag->value }}">{{ $flag->label() }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="text-end mt-3">
                        <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('admissions.save_care_flags') }}</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan
    </div>

    <div class="col-xl-7">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-clipboard-list me-1"></i>{{ __('admissions.nursing_tasks') }}</h5>
                <span class="badge bg-warning text-dark">{{ $openTasks->count() }} {{ __('admissions.pending_label') }}</span>
            </div>
            <div class="card-body">
                @can('admission.nursing.tasks.create')
                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.nursing-tasks.store', $admission) }}" class="border rounded p-2 mb-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small">{{ __('admissions.task_col') }}</label>
                            <input name="title" class="form-control form-control-sm" required maxlength="255" value="{{ old('title') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">{{ __('admissions.type_col') }}</label>
                            <select name="task_type" class="form-select form-select-sm">
                                <option value="">{{ __('admissions.select_type') }}</option>
                                @foreach(\App\Enums\NursingTaskType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('admissions.priority_col') }}</label>
                            <select name="priority" class="form-select form-select-sm">
                                @foreach(['normal', 'medium', 'high', 'urgent'] as $priority)
                                    <option value="{{ $priority }}">{{ ucfirst($priority) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">{{ __('admissions.due_col') }}</label>
                            <input type="datetime-local" name="due_at" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">{{ __('admissions.assigned_to_col') }}</label>
                            <select name="assigned_to" class="form-select form-select-sm">
                                <option value="">{{ __('admissions.unassigned') }}</option>
                                @foreach($nursingAssignableUsers as $assignableUser)
                                    <option value="{{ $assignableUser->id }}">{{ $assignableUser->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small">{{ __('admissions.description') }}</label>
                            <input name="description" class="form-control form-control-sm" maxlength="2000">
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>{{ __('admissions.add_nursing_task') }}</button>
                    </div>
                </form>
                @endcan

                @if($admission->nursingTasks->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="bg-light"><tr><th>{{ __('admissions.task_col') }}</th><th>{{ __('admissions.due_col') }}</th><th>{{ __('admissions.assigned_to_col') }}</th><th>{{ __('admissions.status_col') }}</th><th></th></tr></thead>
                            <tbody>
                                @foreach($admission->nursingTasks as $task)
                                    <tr class="{{ $task->is_overdue ? 'table-danger' : '' }}">
                                        <td>
                                            <div class="fw-semibold">{{ $task->title }}</div>
                                            <small class="text-muted">{{ $task->task_type?->label() ?? __('admissions.general') }} · {{ ucfirst($task->priority ?? 'normal') }}</small>
                                        </td>
                                        <td>{{ $task->due_at?->format('d M H:i') ?? '—' }}</td>
                                        <td>{{ $task->assignedTo->name ?? '—' }}</td>
                                        <td><span class="badge badge-soft-{{ $task->status?->color() ?? 'secondary' }}">{{ $task->status?->label() ?? $task->status }}</span></td>
                                        <td class="text-end">
                                            @if($task->status?->isOpen())
                                                @can('admission.nursing.tasks.complete')
                                                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.nursing-tasks.complete', [$admission, $task]) }}" class="d-inline">
                                                    @csrf @method('PATCH')
                                                    <button class="btn btn-sm btn-outline-success"><i class="ti ti-check"></i></button>
                                                </form>
                                                @endcan
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted"><i class="ti ti-clipboard-off fs-1 d-block mb-2"></i>{{ __('admissions.no_nursing_tasks') }}</div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0"><i class="ti ti-notes me-1"></i>{{ __('admissions.nursing_notes') }}</h5></div>
            <div class="card-body">
                @can('admission.nursing.notes.create')
                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.nursing-notes.store', $admission) }}" class="border rounded p-2 mb-3">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('admissions.type_col') }}</label>
                            <select name="note_type" class="form-select form-select-sm">
                                @foreach(\App\Enums\NursingNoteType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">{{ __('admissions.observed_at') }}</label>
                            <input type="datetime-local" name="observed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small">{{ __('admissions.notes') }}</label>
                            <textarea name="note" rows="3" class="form-control form-control-sm" required maxlength="5000">{{ old('note') }}</textarea>
                        </div>
                    </div>
                    <div class="text-end mt-2">
                        <button class="btn btn-sm btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('admissions.save_nursing_note') }}</button>
                    </div>
                </form>
                @endcan

                @forelse($recentNotes as $note)
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <div>
                                <span class="badge badge-soft-info">{{ $note->note_type?->label() ?? __('admissions.general') }}</span>
                                <span class="fw-semibold ms-1">{{ $note->nurse->name ?? $note->createdBy->name ?? '—' }}</span>
                            </div>
                            <small class="text-muted">{{ $note->observed_at?->format('d M Y H:i') ?? $note->created_at?->format('d M Y H:i') }}</small>
                        </div>
                        <p class="mb-0 mt-2">{{ $note->note }}</p>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted"><i class="ti ti-notes-off fs-1 d-block mb-2"></i>{{ __('admissions.no_nursing_notes') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
