@extends('layouts.app')
@section('title', __('procedures.scheduled_procedures'))

@section('content')
<!-- Page Header -->
<div class="d-flex align-items-sm-center flex-sm-row flex-column gap-2 mb-3 pb-3 border-bottom">
    <div class="flex-grow-1">
        <h4 class="fw-bold mb-0"><i class="ti ti-calendar-event me-2"></i>{{ __('procedures.scheduled_procedures') }}</h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.procedures.index') }}" class="btn btn-outline-secondary btn-md">
            <i class="ti ti-arrow-left me-1"></i>{{ __('procedures.procedure_catalog') }}
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
                <input type="text" name="search" class="form-control form-control-sm" placeholder="{{ __('procedures.search_patient_procedure') }}" value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">{{ __('common.all_statuses') }}</option>
                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>{{ __('procedures.status_scheduled') }}</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ __('procedures.status_in_progress') }}</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('procedures.status_completed') }}</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('procedures.status_cancelled') }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}" placeholder="{{ __('procedures.from_date') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}" placeholder="{{ __('procedures.to_date') }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ti ti-filter me-1"></i>{{ __('common.filter') }}</button>
                <a aria-label="{{ __('common.close') }}" title="{{ __('common.close') }}" href="{{ route('admin.procedures.schedule') }}" class="btn btn-sm btn-outline-secondary"><i class="ti ti-x"></i></a>
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
                        <th>{{ __('common.patient') }}</th>
                        <th>{{ __('procedures.procedure_col') }}</th>
                        <th>{{ __('common.department') }}</th>
                        <th>{{ __('procedures.scheduled_date') }}</th>
                        <th>{{ __('procedures.performed_by') }}</th>
                        <th class="text-center">{{ __('procedures.consent') }}</th>
                        <th class="text-center">{{ __('common.status') }}</th>
                        <th style="width:180px">{{ __('common.actions') }}</th>
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
                            <div class="small text-success">{{ __('procedures.done_prefix') }}: {{ $pp->performed_date->format('M d, Y H:i') }}</div>
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
                                <button class="btn btn-sm btn-outline-primary" title="{{ __('procedures.start') }}"><i class="ti ti-player-play me-1"></i>{{ __('procedures.start') }}</button>
                            </form>
                            <x-confirm-form :action="route('admin.procedures.cancel', $pp)" method="PATCH"
                                button-label="" button-class="btn btn-sm btn-outline-danger" icon="ti-x"
                                :confirm-title="__('procedures.cancel_procedure_title')" :confirm-text="__('procedures.cancel_procedure_text')" :confirm-button="__('procedures.yes_cancel')" require-reason :reason-placeholder="__('procedures.reason_for_cancellation')" />
                            @elseif($pp->status === 'in_progress')
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#completeModal{{ $pp->id }}">
                                <i class="ti ti-check me-1"></i>{{ __('procedures.complete') }}
                            </button>
                            @endif

                            @if($pp->notes || $pp->outcome)
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $pp->id }}" title="{{ __('common.details') }}">
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
                                        <h5 class="modal-title">{{ __('procedures.complete_procedure') }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p class="text-muted">{{ __('procedures.procedure_for', ['procedure' => $pp->procedure->name, 'patient' => $pp->patient->full_name ?? __('common.patient')]) }}</p>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('procedures.outcome') }}</label>
                                            <textarea name="outcome" class="form-control" rows="3" placeholder="{{ __('procedures.outcome_placeholder') }}"></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('common.notes') }}</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('procedures.additional_notes_placeholder') }}">{{ $pp->notes }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                                        <button type="submit" class="btn btn-success">{{ __('procedures.mark_complete') }}</button>
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
                                    <h5 class="modal-title">{{ __('procedures.procedure_details') }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    @if($pp->notes)
                                    <h6>{{ __('common.notes') }}</h6>
                                    <p>{{ $pp->notes }}</p>
                                    @endif
                                    @if($pp->outcome)
                                    <h6>{{ __('procedures.outcome') }}</h6>
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
                            {{ __('procedures.no_scheduled_procedures') }}
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
