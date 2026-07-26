{{--
    Phase 14R.5.1 — explicit Maternity → Emergency escalation panel.

    Prepared by MaternityEmergencyHandoffPresenter in the controller; this
    partial performs NO queries and makes no decisions. Renders nothing while
    MATERNITY_EMERGENCY_HANDOFFS_ENABLED is off, or when the clinician lacks
    either the bridge permission or emergency.case.create.

    The escalation flag on the source record creates nothing on its own — only
    confirming the dialog below creates or opens an Emergency Case, through the
    existing EmergencyCaseService.

    Expects: $actions (array of MaternityHandoffActionViewModel)
--}}
@php
    $emergencyActions = $actions ?? [];
@endphp

@if (! empty($emergencyActions))
    <div class="card mb-3" id="maternity-emergency-handoff">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">{{ __('maternity_handoffs.escalation.title') }}</h6>
            <span class="text-muted" style="font-size: .75rem;">
                {{ __('maternity_handoffs.emergency.emergency_owns_acute_care') }}
            </span>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-0">
                {{ __('maternity_handoffs.escalation.flag_created_nothing') }}
            </p>

            @include('maternity.partials.handoff-triggers', [
                'actions' => collect($emergencyActions)
                    ->filter(fn ($action) => $action->visible
                        && ($action->isExecutable() || $action->showsReason()))
                    ->all(),
            ])
        </div>
    </div>

    @foreach ($emergencyActions as $emergencyAction)
        @include('maternity.partials.handoff-modal', ['action' => $emergencyAction])
    @endforeach

    @include('maternity.partials.handoff-scripts', [
        'reopenModalId' => (isset($errors) && $errors->any()) ? old('_handoff_modal') : null,
    ])
@endif
