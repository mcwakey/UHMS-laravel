@extends('layouts.app')
@section('title', 'Scheduled Procedures')

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-calendar-event me-2"></i>Scheduled Procedures</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.procedures.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>Procedure Catalog
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.procedures.schedule') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search patient or procedure..." value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="From date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="To date">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>Filter</button>
                <a href="{{ route('admin.procedures.schedule') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Patient</th>
                        <th>Procedure</th>
                        <th>Department</th>
                        <th>Scheduled Date</th>
                        <th>Performed By</th>
                        <th class="text-center">Consent</th>
                        <th class="text-center">Status</th>
                        <th style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patientProcedures as $pp)
                    <tr>
                        <td>
                            <strong>{{ $pp->patient->full_name ?? 'N/A' }}</strong>
                            <div class="small text-muted">{{ $pp->patient->patient_number ?? '' }}</div>
                        </td>
                        <td>
                            <strong>{{ $pp->procedure->name ?? 'N/A' }}</strong>
                            @if($pp->procedure && $pp->procedure->code)
                            <div class="small text-muted"><code>{{ $pp->procedure->code }}</code></div>
                            @endif
                        </td>
                        <td>{{ $pp->procedure->department->name ?? '—' }}</td>
                        <td>
                            {{ $pp->scheduled_date->format('M d, Y H:i') }}
                            @if($pp->performed_date)
                            <div class="small text-success">Done: {{ $pp->performed_date->format('M d, Y H:i') }}</div>
                            @endif
                        </td>
                        <td>{{ $pp->performedByUser->name ?? '—' }}</td>
                        <td class="text-center">
                            @if($pp->consent_signed)
                            <i class="ti ti-check text-success"></i>
                            @else
                            <i class="ti ti-x text-danger"></i>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $pp->status_color }}">{{ $pp->status_label }}</span>
                        </td>
                        <td>
                            @if($pp->status === 'scheduled')
                            <form method="POST" action="{{ route('admin.procedures.start', $pp) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm btn-outline-primary" title="Start"><i class="ti ti-player-play me-1"></i>Start</button>
                            </form>
                            <x-confirm-form :action="route('admin.procedures.cancel', $pp)" method="PATCH"
                                button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-x"
                                confirm-title="Cancel this procedure?" confirm-text="The scheduled procedure will be cancelled." confirm-button="Yes, cancel" require-reason reason-placeholder="Reason for cancellation" />
                            @elseif($pp->status === 'in_progress')
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#completeModal{{ $pp->id }}">
                                <i class="ti ti-check me-1"></i>Complete
                            </button>
                            @endif

                            @if($pp->notes || $pp->outcome)
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $pp->id }}" title="Details">
                                <i class="ti ti-eye"></i>
                            </button>
                            @endif
                        </td>
                    </tr>

                    @if($pp->status === 'in_progress')
                    <!-- Complete Modal -->
                    <div class="modal fade" id="completeModal{{ $pp->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form method="POST" action="{{ route('admin.procedures.complete', $pp) }}">
                                    @csrf @method('PATCH')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Complete Procedure</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted">{{ $pp->procedure->name }} for {{ $pp->patient->full_name ?? 'patient' }}</p>
                                        <div class="mb-3">
                                            <label class="form-label">Outcome</label>
                                            <textarea name="outcome" class="form-control" rows="3" placeholder="Procedure outcome / findings..."></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes...">{{ $pp->notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success">Mark Complete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($pp->notes || $pp->outcome)
                    <!-- Details Modal -->
                    <div class="modal fade" id="detailModal{{ $pp->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Procedure Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @if($pp->notes)
                                    <h6>Notes</h6>
                                    <p>{{ $pp->notes }}</p>
                                    @endif
                                    @if($pp->outcome)
                                    <h6>Outcome</h6>
                                    <p>{{ $pp->outcome }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                            No scheduled procedures found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($patientProcedures->hasPages())
    <div class="card-footer">
        {{ $patientProcedures->links() }}
    </div>
    @endif
</div>
@endsection
