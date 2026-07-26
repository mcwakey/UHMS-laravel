{{--
    Phase 14R.5 / 14R.5.1 — Consultation maternity handoff actions.

    Dark by default. Renders nothing unless
    MATERNITY_CONSULTATION_HANDOFFS_ENABLED is on AND the clinician holds both
    the bridge permission and the target module's own permission.

    Obstetrics gets "Create Admission Request"; Gynaecology gets
    "Refer to Obstetrics/Maternity" and stays Gynaecology. Postnatal review is
    available to both, read-only.

    All state is prepared by ConsultationMaternityHandoffPresenter — nothing is
    resolved or queried here, and every trigger comes from a typed action model.

    Expects: $handoffs (array|null)
--}}
@php($handoffs = $handoffs ?? null)

@if ($handoffs && ! empty($handoffs['enabled']) && ! empty($handoffs['can_view']))
    @php($actions = $handoffs['actions'] ?? [])

    <div class="card mb-3 section-specialty" id="maternity-handoffs">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">{{ __('maternity_handoffs.consultation.title') }}</h6>
            <span class="text-muted" style="font-size: .75rem;">
                {{ __('maternity_handoffs.ownership.maternity_longitudinal_record') }}
            </span>
        </div>

        <div class="card-body">
            @if (empty($handoffs['has_explicit_link']))
                <p class="text-muted small mb-2">
                    {{ __('maternity_handoffs.consultation.explicit_link_required') }}
                </p>
            @endif

            @if (! empty($handoffs['lifecycle']) && empty($handoffs['lifecycle']['allowed']))
                {{-- Same decision the server-side mutation guard makes, so the
                     UI can never be more permissive than the boundary. --}}
                <div class="alert alert-warning py-2 px-3 small mb-2">
                    {{ $handoffs['lifecycle']['message'] }}
                </div>
            @endif

            @include('maternity.partials.handoff-triggers', [
                'actions' => collect($actions)
                    ->filter(fn ($action) => $action->visible
                        && ($action->isExecutable() || $action->showsReason()))
                    ->all(),
            ])

            @if (! empty($handoffs['is_gynaecology']))
                <p class="text-muted small mt-2 mb-0">
                    {{ __('maternity_handoffs.consultation.remains_gynaecology') }}
                </p>
            @endif

            @if (! empty($handoffs['postnatal']))
                {{-- READ-ONLY projection. Observations are recorded in
                     Maternity; nothing here is editable or duplicated. --}}
                <div class="border rounded p-2 mt-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div class="fw-semibold small">{{ __('maternity_handoffs.postnatal.review') }}</div>
                        <span class="badge bg-light text-secondary border fw-normal">
                            {{ $handoffs['postnatal']['owner_label'] }}
                        </span>
                    </div>
                    <div class="text-muted" style="font-size: .75rem;">
                        {{ $handoffs['postnatal']['record_label'] }}
                    </div>
                    <dl class="row mb-0 mt-2 g-0" style="font-size: .8rem;">
                        <dt class="col-6 fw-normal text-muted">{{ __('maternity_handoffs.fields.mother_ready') }}</dt>
                        <dd class="col-6 mb-1 text-end">
                            {{ $handoffs['postnatal']['readiness']['mother_ready_at'] ?? __('maternity_handoffs.fields.not_ready') }}
                        </dd>
                        <dt class="col-6 fw-normal text-muted">{{ __('maternity_handoffs.fields.newborn_ready') }}</dt>
                        <dd class="col-6 mb-1 text-end">
                            {{ $handoffs['postnatal']['readiness']['newborn_ready_at'] ?? __('maternity_handoffs.fields.not_ready') }}
                        </dd>
                    </dl>
                    <div class="text-muted fst-italic mt-1" style="font-size: .72rem;">
                        {{ $handoffs['postnatal']['readiness']['advisory_note'] }}
                        {{ __('maternity_handoffs.postnatal.record_observations_in_maternity') }}
                    </div>
                </div>
            @endif

            <p class="text-muted small mt-2 mb-0">
                {{ __('maternity_handoffs.consultation.no_billing_posted') }}
            </p>
        </div>
    </div>

    @foreach ($actions as $action)
        @include('maternity.partials.handoff-modal', ['action' => $action])
    @endforeach

    @include('maternity.partials.handoff-scripts', [
        'reopenModalId' => (isset($errors) && $errors->any()) ? old('_handoff_modal') : null,
    ])
@endif
