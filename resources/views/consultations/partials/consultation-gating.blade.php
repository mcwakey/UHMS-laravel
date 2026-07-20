{{-- ============================================================ --}}
{{-- CONSULTATION GATING - Start / reopen consultation banner --}}
{{-- ============================================================ --}}
@if($needsStart)
<div class="card border-warning mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-1 text-warning"><i class="ti ti-player-play me-1"></i>{{ __('consultations.workspace.consultation_not_started') }}</h6>
            <small class="text-muted">{{ __('consultations.workspace.start_instruction') }}</small>
        </div>
        @if($canStartSession)
        <form method="POST" action="{{ $workspaceRoutes->route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
            @csrf
            <button type="submit" class="btn btn-warning"><i class="ti ti-player-play me-1"></i>{{ __('consultations.workspace.start_consultation') }}</button>
        </form>
        @endif
    </div>
</div>
@elseif($canEdit)
<div class="alert alert-success py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-pencil me-2"></i><strong>{{ __('consultations.workspace.consultation_in_progress') }}</strong>&nbsp;- {{ __('consultations.workspace.consultation_in_progress_help') }}
</div>
@elseif(($reopenEligibility ?? null)?->allowed && $selectedRoute)
<div class="card border-warning mb-3">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
            <div>
                <h6 class="fw-bold mb-1 text-warning"><i class="ti ti-lock-open me-1"></i>{{ __('consultations.reopen.title') }}</h6>
                <small class="text-muted">{{ $reopenEligibility->message }}</small>
            </div>
            <span class="badge bg-secondary">{{ __('visits.actions.view_readonly') }}</span>
        </div>
        <form method="POST" action="{{ $workspaceRoutes->route('admin.consultations.routes.reopen', [$visit, $selectedRoute]) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md">
                <label class="form-label small">{{ __('consultations.reopen.reason') }}</label>
                <input type="text" name="reason" class="form-control form-control-sm" required minlength="5" maxlength="1000" value="{{ old('reason') }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="ti ti-lock-open me-1"></i>{{ __('consultations.reopen.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@elseif($selectedRoute && in_array($selectedRoute->status, [\App\Models\VisitConsultationRoute::STATUS_COMPLETED, \App\Models\VisitConsultationRoute::STATUS_CANCELLED], true))
<div class="alert alert-secondary py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-eye me-2"></i><strong>{{ __('visits.actions.view_readonly') }}</strong>&nbsp;- {{ ($reopenEligibility ?? null)?->message ?? __('consultations.reopen.blocked') }}
</div>
@elseif($selectedRoute && ! $canEdit && ! $needsStart && ($eligibilityLockMessage ?? null))
<div class="alert alert-secondary py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-eye me-2"></i><strong>{{ __('visits.actions.view_readonly') }}</strong>&nbsp;- {{ $eligibilityLockMessage }}
</div>
@endif

@if($isSelectedRouteLocked && ! $canCorrectLocked)
<div class="alert alert-secondary py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-lock me-2"></i><strong>{{ __('consultations.workspace.session_locked') }}</strong>&nbsp;- {{ __('consultations.workspace.session_locked_help') }}
</div>
@endif

@if(($sessionEligibilityActions['offer_readmit_or_extend'] ?? false) && $visit->admission)
@can('admissions.extend')
<div class="card border-info mb-3">
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
            <div>
                <h6 class="fw-bold mb-1 text-info"><i class="ti ti-bed me-1"></i>{{ __('admissions.readmit_extend_patient') }}</h6>
                <small class="text-muted">{{ __('consultations.lock_reasons.readmit_or_extend_to_continue') }}</small>
            </div>
        </div>
        <form method="POST" action="{{ $workspaceRoutes->route('admin.admissions.extend', $visit->admission) }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-md">
                <label class="form-label small">{{ __('consultations.reopen.reason') }}</label>
                <input type="text" name="reason" class="form-control form-control-sm" required minlength="5" maxlength="1000" value="{{ old('reason') }}">
            </div>
            <div class="col-md-auto">
                <button type="submit" class="btn btn-sm btn-info">
                    <i class="ti ti-bed me-1"></i>{{ __('admissions.extend_admission') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endcan
@endif
