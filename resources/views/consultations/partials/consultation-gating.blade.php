{{-- ============================================================ --}}
{{-- CONSULTATION GATING — Start Consultation banner --}}
{{-- ============================================================ --}}
@if($needsStart)
<div class="card border-warning mb-3">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h6 class="fw-bold mb-1 text-warning"><i class="ti ti-player-play me-1"></i>{{ __('consultations.workspace.consultation_not_started') }}</h6>
            <small class="text-muted">{{ __('consultations.workspace.start_instruction') }}</small>
        </div>
        @can('consultations.create')
        <form method="POST" action="{{ route('admin.consultations.routes.activate', [$visit, $selectedRoute]) }}">
            @csrf
            <button type="submit" class="btn btn-warning"><i class="ti ti-player-play me-1"></i>{{ __('consultations.workspace.start_consultation') }}</button>
        </form>
        @endcan
    </div>
</div>
@elseif($canEdit)
<div class="alert alert-success py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-pencil me-2"></i><strong>{{ __('consultations.workspace.consultation_in_progress') }}</strong>&nbsp;— {{ __('consultations.workspace.consultation_in_progress_help') }}
</div>
@endif
@if($isSelectedRouteLocked && ! $canCorrectLocked)
<div class="alert alert-secondary py-2 mb-3 small d-flex align-items-center">
    <i class="ti ti-lock me-2"></i><strong>{{ __('consultations.workspace.session_locked') }}</strong>&nbsp;- {{ __('consultations.workspace.session_locked_help') }}
</div>
@endif
