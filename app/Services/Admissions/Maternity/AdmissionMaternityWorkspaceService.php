<?php

namespace App\Services\Admissions\Maternity;

use App\Data\Maternity\OperationalMaternityContext;
use App\Data\Maternity\OperationalMaternityViewModel;
use App\Models\Admission;
use App\Models\User;
use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Enums\AdmissionStatus;
use App\Services\Maternity\Context\MaternityContextCardBuilder;
use App\Services\Maternity\Context\MaternityHandoffActionFactory;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5 — prepares the Admission workspace maternity card.
 *
 * Built once per request in the Admission composer. Flag off → returns
 * immediately with ZERO queries. A direct non-maternity admission takes the
 * resolver's fast no-context path.
 *
 * Admission continues to own bed, ward, nursing notes and tasks, MAR, transfers
 * and discharge. This card shows maternity records read-only and links out to
 * the Maternity workspace for every clinical write — it renders no ANC, labor,
 * delivery, newborn or postnatal FORM.
 */
class AdmissionMaternityWorkspaceService
{
    public function __construct(
        private readonly AdmissionMaternityContextResolver $resolver,
        private readonly MaternityContextCardBuilder $cards,
        private readonly MaternityHandoffActionFactory $actions,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function build(Admission $admission, ?User $user = null): OperationalMaternityViewModel
    {
        if (! $this->flags->admissionContextEnabled()) {
            return OperationalMaternityViewModel::disabled();
        }

        $context = $this->resolver->resolve($admission);
        $returnContext = $this->returnContext($admission);

        return new OperationalMaternityViewModel(
            contextEnabled: true,
            // Admission context management is link/unlink only — Admission
            // never starts ANC, labor, delivery or postnatal work.
            handoffsEnabled: false,
            status: $context->status,
            resolutionSource: $context->resolutionSource,
            pregnancyProfileId: $context->pregnancyProfile?->id,
            pregnancyProfileUrl: $this->route('admin.maternity.pregnancies.show', $context->pregnancyProfile),
            laborEpisodeId: $context->laborEpisode?->id,
            laborEpisodeUrl: $this->route('admin.maternity.labor.show', $context->laborEpisode),
            postnatalCaseId: $context->postnatalCase?->id,
            cards: $this->cards->forContext($context),
            actions: $this->actions($user, $context),
            warnings: $context->warnings,
            returnContext: $returnContext?->toArray(),
            requestState: $this->originState($admission, $context),
            candidateProfiles: $context->isAmbiguous()
                ? $context->candidateProfiles?->map(fn ($profile) => [
                    'id' => $profile->id,
                    'label' => __('maternity_handoffs.cards.record_ref', [
                        'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
                    ]),
                ])->values()->all()
                : null,
            handoffActions: $this->buildActions($admission, $user, $context, $returnContext),
        );
    }

    /* ── Typed actions (Phase 14R.5.1) ─────────────────────────────────── */

    /**
     * Admission may link, relink and unlink context — and nothing else. No ANC,
     * Labor, Delivery, Newborn or Postnatal record is ever created here.
     *
     * @return array<string, Action>
     */
    private function buildActions(
        Admission $admission,
        ?User $user,
        OperationalMaternityContext $context,
        ?\App\Support\Maternity\MaternityReturnContext $returnContext,
    ): array {
        if (! $user) {
            return [];
        }

        $isExplicit = $context->isResolved() && $context->isExplicit();
        $params = ['admission' => $admission->id];
        $candidateUrl = $this->route('admin.admissions.maternity-context.candidates', $admission);
        $summary = $this->contextSummary($context);

        // A discharged admission is a closed episode: correcting its clinical
        // context afterwards is not an operation this phase opens up.
        $admissionOpen = $admission->status !== AdmissionStatus::DISCHARGED;
        $lifecycleReason = $admissionOpen ? null : __('maternity_handoffs.states.admission_discharged');

        $canLink = $user->can('admission.maternity_context.link')
            && $user->can('maternity.pregnancy.view');

        return [
            'link_profile' => $this->actions->make([
                'action' => 'link_profile',
                'label' => __('maternity_handoffs.admission.link_context'),
                'description' => __('maternity_handoffs.modal.patient_scoped_search'),
                'feature_enabled' => ! $isExplicit,
                'has_permission' => $canLink,
                'lifecycle_ok' => $admissionOpen,
                'lifecycle_reason' => $lifecycleReason,
                'route' => 'admin.admissions.maternity-context.link',
                'parameters' => $params,
                'modal' => 'admissionLinkPregnancyModal',
                'required_fields' => ['pregnancy_profile_id'],
                'source_module' => 'admission',
                'source_record_id' => $admission->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => [
                    'candidate_url' => $candidateUrl,
                    'summary' => $summary,
                    'notices' => [__('maternity_handoffs.admission.clinical_writes_in_maternity')],
                ],
            ], $returnContext),

            'relink_profile' => $this->actions->make([
                'action' => 'relink_profile',
                'label' => __('maternity_handoffs.admission.correct_context'),
                'description' => __('maternity_handoffs.admission.correct_context_description'),
                'feature_enabled' => $isExplicit,
                'has_permission' => $canLink,
                'lifecycle_ok' => $admissionOpen,
                'lifecycle_reason' => $lifecycleReason,
                'route' => 'admin.admissions.maternity-context.relink',
                'parameters' => $params,
                'modal' => 'admissionRelinkPregnancyModal',
                'confirmation' => Action::CONFIRM_REASON,
                'required_fields' => ['pregnancy_profile_id', 'reason'],
                'source_module' => 'admission',
                'source_record_id' => $admission->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => [
                    'candidate_url' => $candidateUrl,
                    'summary' => $summary,
                    'notices' => [__('maternity_handoffs.modal.history_preserved')],
                ],
            ], $returnContext),

            'unlink_profile' => $this->actions->make([
                'action' => 'unlink_profile',
                'label' => __('consultation_maternity.actions.unlink_profile'),
                'feature_enabled' => $isExplicit,
                'has_permission' => $user->can('admission.maternity_context.unlink')
                    && $user->can('maternity.pregnancy.view'),
                'lifecycle_ok' => $admissionOpen,
                'lifecycle_reason' => $lifecycleReason,
                'route' => 'admin.admissions.maternity-context.unlink',
                'parameters' => $params,
                'modal' => 'admissionUnlinkPregnancyModal',
                'confirmation' => Action::CONFIRM_REASON,
                'required_fields' => ['reason'],
                'source_module' => 'admission',
                'source_record_id' => $admission->id,
                'target_context_type' => 'pregnancy_profile',
                'context' => [
                    'summary' => $summary,
                    'notices' => [__('maternity_handoffs.modal.history_preserved')],
                ],
            ], $returnContext),
        ];
    }

    /**
     * Read-only display values for a modal body. Identifiers and dates only,
     * plus how the context was resolved — carried from the request, linked
     * directly, or inferred from a matching admission_id.
     *
     * @return array<string, ?string>
     */
    private function contextSummary(OperationalMaternityContext $context): array
    {
        $profile = $context->pregnancyProfile;

        $origin = match ($context->resolutionSource) {
            OperationalMaternityContext::SOURCE_EXPLICIT => __('maternity_handoffs.admission.context_linked_directly'),
            OperationalMaternityContext::SOURCE_REQUEST => __('maternity_handoffs.admission.context_from_request'),
            OperationalMaternityContext::SOURCE_ADMISSION_RECORDS => __('maternity_handoffs.admission.context_from_admission_id'),
            default => null,
        };

        return array_filter([
            __('maternity_handoffs.cards.pregnancy') => $profile
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
                ])
                : null,
            __('maternity_handoffs.fields.edd') => $profile?->estimated_due_date?->format('d M Y'),
            __('maternity_handoffs.ownership.handoff_context') => $origin,
        ], fn ($value) => $value !== null);
    }

    public function returnContext(Admission $admission): ?MaternityReturnContext
    {
        return MaternityReturnContext::make(
            MaternityReturnContext::MODULE_ADMISSION,
            'admin.admissions.show',
            ['admission' => $admission->id],
            'maternity-context',
        );
    }

    /**
     * Where this admission — and its maternity context — came from.
     *
     * A legacy `maternity` request whose `source_id` is ambiguous (it may be an
     * ANC visit id OR a labor episode id, depending on which service raised it)
     * is reported as a WARNING rather than guessed at.
     *
     * @return array<string, mixed>
     */
    private function originState(Admission $admission, OperationalMaternityContext $context): array
    {
        $request = $admission->admissionRequest;

        $ambiguousLegacySource = $request
            && $request->source_type?->value === 'maternity'
            && $request->source_id !== null
            && $context->resolutionSource !== OperationalMaternityContext::SOURCE_REQUEST;

        return [
            'id' => $request?->id,
            'status' => $request?->status?->label(),
            'source' => $request?->source_type?->label(),
            'url' => $request ? $this->route('admin.admissions.requests.show', $request) : null,
            'context_from_request' => $context->resolutionSource === OperationalMaternityContext::SOURCE_REQUEST,
            'ambiguous_legacy_source' => $ambiguousLegacySource,
            'ambiguous_legacy_notice' => $ambiguousLegacySource
                ? __('maternity_handoffs.admission.ambiguous_legacy_source')
                : null,
        ];
    }

    /**
     * Admission may view, link, relink and unlink context — and nothing else.
     *
     * It may never auto-create a pregnancy profile, start ANC or labor, create
     * delivery/newborn/postnatal records, or choose between multiple profiles.
     *
     * @return array<string, bool>
     */
    private function actions(?User $user, OperationalMaternityContext $context): array
    {
        if (! $user) {
            return [];
        }

        $isExplicit = $context->isResolved() && $context->isExplicit();

        return [
            'view' => $user->can('admission.maternity_context.view'),
            'link' => $user->can('admission.maternity_context.link')
                && $user->can('maternity.pregnancy.view'),
            'relink' => $isExplicit && $user->can('admission.maternity_context.link'),
            'unlink' => $isExplicit && $user->can('admission.maternity_context.unlink'),
        ];
    }

    private function route(string $name, mixed $parameter): ?string
    {
        if ($parameter === null) {
            return null;
        }

        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}
