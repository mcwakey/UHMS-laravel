@php
    $careFlags = $careOverview['care_flags'] ?? collect();
    $openTasks = $careOverview['open_tasks'] ?? collect();
    $recentNotes = $careOverview['recent_notes'] ?? collect();
    $allNursingNotes = $admission->nursingNotes ?? $recentNotes;
@endphp

<div class="row g-3">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <h5 class="card-title mb-0"><i class="ti ti-notes me-1"></i>{{ __('admissions.nursing_notes') }}</h5>
                @can('admission.nursing.notes.create')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNursingNoteModal">
                    <i class="ti ti-plus me-1"></i>{{ __('admissions.add_nursing_note') }}
                </button>
                @endcan
            </div>
            <div class="card-body">
                <div class="nursing-thread">
                    @include('admissions.partials.nursing-notes-thread', ['notes' => $allNursingNotes])
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-checklist me-1"></i>{{ __('admissions.inpatient_care_checklist') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @foreach(($careOverview['checklist'] ?? []) as $item)
                        @php
                            $status = $item['status'];
                            $icon = match($status) {
                                'complete' => 'ti-circle-check text-success',
                                'warning' => 'ti-alert-triangle text-danger',
                                default => 'ti-clock text-warning',
                            };
                        @endphp
                        <div class="list-group-item d-flex align-items-center gap-2">
                            <i class="ti {{ $icon }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="ti ti-clipboard-list me-1"></i>{{ __('admissions.nursing_tasks') }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning text-dark">{{ $openTasks->count() }} {{ __('admissions.pending_label') }}</span>
                    @can('admission.nursing.tasks.create')
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNursingTaskModal">
                        <i class="ti ti-plus me-1"></i>{{ __('admissions.add_nursing_task') }}
                    </button>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <div class="nursing-thread">
                    @include('admissions.partials.nursing-tasks-thread', ['tasks' => $admission->nursingTasks])
                </div>
            </div>
        </div>

        @can('admission.care_flags.manage')
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-flag me-1"></i>{{ __('admissions.manage_care_flags') }}</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.care-flags.update', $admission) }}">
                    @csrf
                    @method('PATCH')
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
</div>

@can('admission.nursing.notes.create')
<div class="modal fade" id="addNursingNoteModal" tabindex="-1" aria-labelledby="addNursingNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.nursing-notes.store', $admission) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addNursingNoteModalLabel"><i class="ti ti-notes me-1"></i>{{ __('admissions.add_nursing_note') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admissions.type_col') }}</label>
                        <select name="note_type" class="form-select">
                            @foreach(\App\Enums\NursingNoteType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('note_type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admissions.observed_at') }}</label>
                        <input type="datetime-local" name="observed_at" class="form-control" value="{{ old('observed_at', now()->format('Y-m-d\TH:i')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admissions.notes') }}</label>
                        <textarea name="note" rows="5" class="form-control" required maxlength="5000">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>{{ __('admissions.save_nursing_note') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('admission.nursing.tasks.create')
<div class="modal fade" id="addNursingTaskModal" tabindex="-1" aria-labelledby="addNursingTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.nursing-tasks.store', $admission) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="addNursingTaskModalLabel"><i class="ti ti-clipboard-list me-1"></i>{{ __('admissions.add_nursing_task') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admissions.task_col') }}</label>
                        <input name="title" class="form-control" required maxlength="255" value="{{ old('title') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admissions.type_col') }}</label>
                        <select name="task_type" class="form-select">
                            <option value="">{{ __('admissions.select_type') }}</option>
                            @foreach(\App\Enums\NursingTaskType::cases() as $type)
                                <option value="{{ $type->value }}" @selected(old('task_type') === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admissions.priority_col') }}</label>
                        <select name="priority" class="form-select">
                            @foreach(['normal', 'medium', 'high', 'urgent'] as $priority)
                                <option value="{{ $priority }}" @selected(old('priority', 'normal') === $priority)>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admissions.due_col') }}</label>
                        <input type="datetime-local" name="due_at" class="form-control" value="{{ old('due_at') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('admissions.assigned_to_col') }}</label>
                        <select name="assigned_to" class="form-select">
                            <option value="">{{ __('admissions.unassigned') }}</option>
                            @foreach($nursingAssignableUsers as $assignableUser)
                                <option value="{{ $assignableUser->id }}" @selected((string) old('assigned_to') === (string) $assignableUser->id)>{{ $assignableUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admissions.description') }}</label>
                        <input name="description" class="form-control" maxlength="2000" value="{{ old('description') }}">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary"><i class="ti ti-plus me-1"></i>{{ __('admissions.add_nursing_task') }}</button>
            </div>
        </form>
    </div>
</div>
@endcan
