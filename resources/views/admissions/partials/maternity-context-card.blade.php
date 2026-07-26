{{--
    Phase 14R.5 / 14R.5.1 — Admission workspace maternity context card.

    Data comes from AdmissionMaternityWorkspaceService, built once in the
    controller. NOTHING is resolved or queried here.

    Read-only by design: this card contains NO ANC, labor observation, delivery,
    newborn or postnatal form. The only mutations are link / relink / unlink of
    the context itself. Bed, ward, nursing, MAR and discharge remain
    Admission-owned and are untouched.

    Expects: $maternity (OperationalMaternityViewModel|null), $admission
--}}
@php($maternity = $maternity ?? null)

@if ($maternity && $maternity->shouldRender() && $maternity->can('view'))
    <div class="card mb-3" id="maternity-context">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0">{{ __('maternity_handoffs.admission.title') }}</h6>

                @if (! empty($maternity->requestState['context_from_request']))
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                        {{ __('maternity_handoffs.admission.context_from_request') }}
                    </span>
                @elseif ($maternity->isLinked())
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        {{ __('maternity_handoffs.admission.context_linked_directly') }}
                    </span>
                @endif
            </div>

            <span class="text-muted" style="font-size: .75rem;">
                {{ __('maternity_handoffs.admission.admission_owns') }}
            </span>
        </div>

        <div class="card-body">
            @foreach ($maternity->warnings as $warning)
                <div class="alert alert-warning py-2 px-3 small mb-2">{{ $warning }}</div>
            @endforeach

            @if ($maternity->isAmbiguous())
                <div class="alert alert-warning py-2 px-3 small mb-2">
                    {{ __('maternity_handoffs.admission.context_conflict') }}
                </div>
            @endif

            @if (! empty($maternity->requestState['ambiguous_legacy_source']))
                {{-- Legacy `source_type = maternity` requests carry an ambiguous
                     source_id (ANC visit OR labor episode). Warn; never guess. --}}
                <div class="alert alert-warning py-2 px-3 small mb-2">
                    {{ $maternity->requestState['ambiguous_legacy_notice'] }}
                </div>
            @endif

            @if (! $maternity->hasContext())
                <p class="text-muted small mb-0">{{ __('maternity_handoffs.cards.no_context') }}</p>
            @else
                @include('maternity.partials.context-cards', ['cards' => $maternity->cards])

                <p class="text-muted small mt-2 mb-0">
                    {{ __('maternity_handoffs.admission.clinical_writes_in_maternity') }}
                </p>
            @endif

            @if (! empty($maternity->requestState['id']))
                {{-- Operational origin, shown separately from clinical context. --}}
                <div class="mt-2 small">
                    <span class="text-muted">{{ __('maternity_handoffs.consultation.existing_admission_request') }}:</span>
                    @if (! empty($maternity->requestState['url']))
                        <a href="{{ $maternity->requestState['url'] }}">#{{ $maternity->requestState['id'] }}</a>
                    @else
                        #{{ $maternity->requestState['id'] }}
                    @endif
                    <span class="text-muted">{{ $maternity->requestState['source'] }}</span>
                </div>
            @endif

            @include('maternity.partials.handoff-triggers', [
                'actions' => $maternity->visibleActions(),
            ])
        </div>
    </div>

    @foreach ($maternity->modalActions() as $action)
        @include('maternity.partials.handoff-modal', ['action' => $action])
    @endforeach

    @include('maternity.partials.handoff-scripts', [
        'reopenModalId' => (isset($errors) && $errors->any()) ? old('_handoff_modal') : null,
    ])
@endif
