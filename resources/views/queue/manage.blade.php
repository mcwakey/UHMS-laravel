@extends('layouts.app')
@section('title', 'Queue Management')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0">Queue Management</h4>
    </div>
    <div class="d-flex gap-2">
        <a data-no-inertia href="{{ route('admin.queue.board') }}" class="btn btn-outline-info btn-md" target="_blank">
            <i class="ti ti-external-link me-1"></i>Queue Board
        </a>
        @can('visits.create')
        <a href="{{ route('admin.visits.create') }}" class="btn btn-primary btn-md">
            <i class="ti ti-plus me-1"></i>New Visit
        </a>
        @endcan
    </div>
</div>

<!-- Department Select -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.queue.manage') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Select Department</label>
                <select name="department_id" class="form-select" onchange="this.form.submit()">
                    <option value="">— Choose Department —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $selectedDepartment == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            @if($selectedDepartment)
            <div class="col-md-2">
                <form method="POST" action="{{ route('admin.queue.call-next') }}">
                    @csrf
                    <input type="hidden" name="department_id" value="{{ $selectedDepartment }}">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="ti ti-player-play me-1"></i>Call Next
                    </button>
                </form>
            </div>
            @endif
        </form>
    </div>
</div>

@if($selectedDepartment)
<div class="row">
    <!-- Currently Serving -->
    <div class="col-lg-4">
        <div class="card border-primary mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="fw-bold mb-0"><i class="ti ti-user-check me-1"></i>Now Serving ({{ $serving->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @forelse($serving as $entry)
                <div class="p-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-primary fs-16 px-3 py-2">#{{ $entry->queue_number }}</span>
                        <span class="badge bg-{{ $entry->visit->priority->color() }}">{{ $entry->visit->priority->label() }}</span>
                    </div>
                    <h6 class="fw-bold mb-1">{{ $entry->visit->patient->full_name }}</h6>
                    <small class="text-muted d-block">{{ $entry->visit->patient->patient_number }}</small>
                    <small class="text-muted">Called {{ $entry->called_at?->diffForHumans() }}</small>
                    <div class="mt-2 d-flex gap-1">
                        <form method="POST" action="{{ route('admin.queue.complete', $entry) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm"><i class="ti ti-check me-1"></i>Done</button>
                        </form>
                        <form method="POST" action="{{ route('admin.queue.skip', $entry) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-warning btn-sm"><i class="ti ti-player-skip-forward me-1"></i>Skip</button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="p-3 text-center text-muted">
                    <i class="ti ti-user-off fs-1 d-block mb-2"></i>
                    No one being served
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Waiting Queue -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0"><i class="ti ti-clock-hour-4 me-1"></i>Waiting Queue ({{ $queue->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Queue #</th>
                                <th>Patient</th>
                                <th>Visit</th>
                                <th>Priority</th>
                                <th>Waiting Since</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($queue as $entry)
                            <tr class="{{ $entry->priority->value === 'emergency' ? 'table-danger' : ($entry->priority->value === 'urgent' ? 'table-warning' : '') }}">
                                <td>
                                    <span class="fw-bold fs-16">#{{ $entry->queue_number }}</span>
                                </td>
                                <td>
                                    <div class="fw-medium">{{ $entry->visit->patient->full_name }}</div>
                                    <small class="text-muted">{{ $entry->visit->patient->patient_number }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.visits.show', $entry->visit) }}" class="text-primary">{{ $entry->visit->visit_number }}</a>
                                    <br><small class="text-muted">{{ $entry->visit->visit_type->label() }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $entry->priority->color() }}">{{ $entry->priority->label() }}</span>
                                </td>
                                <td>
                                    <span class="text-muted">{{ $entry->created_at->diffForHumans() }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <form method="POST" action="{{ route('admin.queue.call-next') }}">
                                            @csrf
                                            <input type="hidden" name="department_id" value="{{ $selectedDepartment }}">
                                            <button type="submit" class="btn btn-primary btn-sm" title="Serve"><i class="ti ti-player-play"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.queue.skip', $entry) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-outline-warning btn-sm" title="Skip"><i class="ti ti-player-skip-forward"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="ti ti-mood-happy fs-1 d-block mb-2"></i>
                                    Queue is empty — no patients waiting
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="text-center py-5">
    <i class="ti ti-building-hospital fs-1 text-muted d-block mb-3"></i>
    <h5 class="text-muted">Select a department to manage its queue</h5>
</div>
@endif
@endsection
