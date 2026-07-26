{{--
    Phase 14R.5 — Emergency workspace maternity context card.

    Data comes from EmergencyMaternityWorkspaceService, built once in the
    controller. NOTHING is resolved or queried here.

    Emergency retains ownership of triage, acuity, vitals, notes, bay,
    treatment, tasks and disposition — none of which appear on this card.

    Expects: $maternity (OperationalMaternityViewModel|null), $case
--}}
@php($maternity = $maternity ?? null)

@if ($maternity && $maternity->shouldRender() && $maternity->can('view'))
    @php($returnFields = $maternity->returnContext['parameters'] ?? [])

    <div class="card mb-3" id="maternity-context">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0">{{ __('maternity_handoffs.emergency.title') }}</h6>

                @if ($maternity->isLinked())
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        {{ __('maternity_handoffs.admission.context_linked_directly') }}
                    </span>
                @elseif ($maternity->isSuggested())
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        {{ __('maternity_handoffs.emergency.suggested_context') }}
                    </span>
                @endif
            </div>

            <span class="text-muted" style="font-size: .75rem;">
                {{ __('maternity_handoffs.emergency.emergency_owns_acute_care') }}
                &middot;
                {{ __('maternity_handoffs.emergency.maternity_owns_pregnancy') }}
            </span>
        </div>

        <div class="card-body">
            @foreach ($maternity->warnings as $warning)
                <div class="alert alert-warning py-2 px-3 small mb-2">{{ $warning }}</div>
            @endforeach

            @if ($maternity->isAmbiguous())
                <div class="alert alert-warning py-2 px-3 small mb-2">
                    {{ __('maternity_handoffs.emergency.ambiguous_profile') }}
                </div>
            @endif

            @if (! $maternity->hasContext())
                <p class="text-muted small mb-2">{{ __('maternity_handoffs.emergency.no_context_notice') }}</p>
            @else
                @include('maternity.partials.context-cards', ['cards' => $maternity->cards])
            @endif

            @if ($maternity->isSuggested())
                {{-- A suggestion is NEVER persisted without this explicit confirmation. --}}
                <p class="text-muted small mt-2 mb-0">{{ __('maternity_handoffs.emergency.confirm_link') }}</p>
            @endif

            @if ($maternity->handoffsEnabled)
                <div class="d-flex flex-wrap gap-2 mt-3">
                    @if ($maternity->can('link'))
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal" data-bs-target="#emergencyLinkPregnancyModal">
                            {{ __('maternity_handoffs.emergency.link_pregnancy_profile') }}
                        </button>
                    @endif

                    @if ($maternity->can('create_profile') && ! $maternity->isLinked())
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#emergencyCreatePregnancyModal">
                            {{ __('maternity_handoffs.emergency.create_pregnancy_profile') }}
                        </button>
                    @endif

                    @if ($maternity->can('start_labor'))
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#emergencyStartLaborModal">
                            {{ __('maternity_handoffs.emergency.start_or_open_labor') }}
                        </button>
                    @endif

                    @if ($maternity->can('create_admission_request'))
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="modal" data-bs-target="#emergencyMaternityAdmissionRequestModal">
                            {{ __('maternity_handoffs.emergency.create_or_open_admission_request') }}
                        </button>
                    @endif

                    @if ($maternity->can('unlink') && $maternity->isLinked())
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#emergencyUnlinkPregnancyModal">
                            {{ __('consultation_maternity.actions.unlink_profile') }}
                        </button>
                    @endif
                </div>

                @if (! empty($maternity->requestState['id']))
                    <div class="mt-2 small">
                        <span class="text-muted">{{ __('maternity_handoffs.consultation.existing_admission_request') }}:</span>
                        @if (! empty($maternity->requestState['url']))
                            <a href="{{ $maternity->requestState['url'] }}">#{{ $maternity->requestState['id'] }}</a>
                        @else
                            #{{ $maternity->requestState['id'] }}
                        @endif
                        <span class="text-muted">{{ $maternity->requestState['status'] }}</span>
                    </div>
                @endif
            @endif

            @if ($maternity->returnContext && ! empty($returnFields))
                {{-- Hidden return context for the modals below; internal named
                     route only, so an external URL can never be submitted. --}}
                <template id="emergencyMaternityReturnContext">
                    @foreach (($maternity->returnContext['parameters'] ?? []) as $key => $value)
                        <input type="hidden" name="return_parameters[{{ $key }}]" value="{{ $value }}">
                    @endforeach
                </template>
            @endif
        </div>
    </div>
@endif
