<?php

namespace App\Services\Emergency\Maternity;

use App\Data\Maternity\OperationalMaternityContext;
use App\Data\Maternity\OperationalMaternityViewModel;
use App\Models\EmergencyCase;
use App\Models\User;
use App\Models\Ward;
use App\Services\Maternity\Context\MaternityContextCardBuilder;
use App\Services\Maternity\Context\MaternityHandoffActionFactory;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5 — prepares the Emergency workspace maternity card.
 *
 * Called once from the real Emergency case composer. When the context flag is
 * off it returns immediately with ZERO queries: no resolver call, no link
 * lookup, no pregnancy-profile probe.
 *
 * Emergency retains ownership of triage, acuity, vitals, notes, bay, treatment,
 * tasks and disposition. None of those are duplicated into Maternity, and this
 * card never renders an Emergency clinical field.
 */
class EmergencyMaternityWorkspaceService
{
    public function __construct(
        private readonly EmergencyMaternityContextResolver $resolver,
        private readonly EmergencyMaternityHandoffService $handoffs,
        private readonly MaternityContextCardBuilder $cards,
        private readonly MaternityHandoffActionFactory $actions,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function build(EmergencyCase $case, ?User $user = null): OperationalMaternityViewModel
    {
        if (! $this->flags->emergencyContextEnabled()) {
            return OperationalMaternityViewModel::disabled();
        }

        $context = $this->resolver->resolve($case);
        $handoffsEnabled = $this->flags->emergencyHandoffsEnabled();

        // The open admission request is only meaningful when handoffs are on;
        // the lookup is skipped entirely otherwise.
        $openRequest = $handoffsEnabled ? $this->handoffs->existingOpenRequest($case) : null;
        $requestState = $handoffsEnabled ? $this->requestState($openRequest) : null;
        $returnContext = $this->returnContext($case);

        return new OperationalMaternityViewModel(
            contextEnabled: true,
            handoffsEnabled: $handoffsEnabled,
            status: $context->status,
            resolutionSource: $context->resolutionSource,
            pregnancyProfileId: $context->pregnancyProfile?->id,
            pregnancyProfileUrl: $this->route('admin.maternity.pregnancies.show', $context->pregnancyProfile),
            laborEpisodeId: $context->laborEpisode?->id,
            laborEpisodeUrl: $this->route('admin.maternity.labor.show', $context->laborEpisode),
            postnatalCaseId: $context->postnatalCase?->id,
            cards: $this->cards->forContext($context),
            actions: $this->actions($user, $context->isExplicit() && $context->isResolved(), $handoffsEnabled),
            warnings: $context->warnings,
            returnContext: $returnContext?->toArray(),
            requestState: $requestState,
            candidateProfiles: $context->isAmbiguous()
                ? $context->candidateProfiles?->map(fn ($profile) => [
                    'id' => $profile->id,
                    'label' => __('maternity_handoffs.cards.record_ref', [
                        'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
                    ]),
                ])->values()->all()
                : null,
            handoffActions: $this->buildActions(
                $case, $user, $context, $handoffsEnabled, $openRequest, $returnContext
            ),
        );
    }

    /* ── Typed actions (Phase 14R.5.1) ─────────────────────────────────── */

    /**
     * @return array<string, \App\Data\Maternity\MaternityHandoffActionViewModel>
     */
    private function buildActions(
        EmergencyCase $case,
        ?User $user,
        OperationalMaternityContext $context,
        bool $handoffsEnabled,
        $openRequest,
        ?MaternityReturnContext $returnContext,
    ): array {
        if (! $user) {
            return [];
        }

        $contextEnabled = $this->flags->emergencyContextEnabled();
        $hasExplicitLink = $context->isResolved() && $context->isExplicit()
            && $context->pregnancyProfile !== null;
        $profile = $context->pregnancyProfile;

        // Emergency's own lifecycle: a disposed or cancelled case is finished,
        // so no maternity mutation may be launched from it.
        $caseOpen = ! in_array($case->emergency_status, [
            EmergencyCase::STATUS_DISPOSED,
            EmergencyCase::STATUS_CANCELLED,
        ], true);
        $lifecycleReason = $caseOpen ? null : __('maternity_handoffs.states.emergency_case_closed');

        $params = ['emergencyCase' => $case->id];
        $candidateUrl = $this->route('admin.emergency.cases.maternity-context.candidates', $case);
        $summary = $this->contextSummary($context);

        $canLink = $user->can('emergency.maternity_context.link')
            && $user->can('maternity.pregnancy.view');

        $actions = [];

        /* Link / confirm a suggested profile */
        $actions['link_profile'] = $this->actions->make([
            'action' => 'link_profile',
            'label' => $context->isSuggested()
                ? __('maternity_handoffs.emergency.confirm_suggested_profile')
                : __('maternity_handoffs.emergency.link_pregnancy_profile'),
            'description' => $context->isSuggested()
                ? __('maternity_handoffs.emergency.suggested_context_notice')
                : __('maternity_handoffs.modal.patient_scoped_search'),
            // Context-only mode may READ but never mutate.
            'feature_enabled' => $contextEnabled && $handoffsEnabled && ! $hasExplicitLink,
            'has_permission' => $canLink,
            'lifecycle_ok' => $caseOpen,
            'lifecycle_reason' => $lifecycleReason,
            'route' => 'admin.emergency.cases.maternity-context.link',
            'parameters' => $params,
            'modal' => 'emergencyLinkPregnancyModal',
            'required_fields' => ['pregnancy_profile_id'],
            'source_module' => 'emergency',
            'source_record_id' => $case->id,
            'target_context_type' => 'pregnancy_profile',
            'context' => [
                'candidate_url' => $candidateUrl,
                'summary' => $summary,
                'notices' => [__('maternity_handoffs.emergency.no_context_notice')],
            ],
        ], $returnContext);

        /* Relink with a mandatory reason */
        $actions['relink_profile'] = $this->actions->make([
            'action' => 'relink_profile',
            'label' => __('maternity_handoffs.emergency.relink_pregnancy_profile'),
            'feature_enabled' => $contextEnabled && $handoffsEnabled && $hasExplicitLink,
            'has_permission' => $canLink,
            'lifecycle_ok' => $caseOpen,
            'lifecycle_reason' => $lifecycleReason,
            'route' => 'admin.emergency.cases.maternity-context.relink',
            'parameters' => $params,
            'modal' => 'emergencyRelinkPregnancyModal',
            'confirmation' => \App\Data\Maternity\MaternityHandoffActionViewModel::CONFIRM_REASON,
            'required_fields' => ['pregnancy_profile_id', 'reason'],
            'source_module' => 'emergency',
            'source_record_id' => $case->id,
            'target_context_type' => 'pregnancy_profile',
            'context' => ['candidate_url' => $candidateUrl, 'summary' => $summary],
        ], $returnContext);

        /* Unlink with a mandatory reason; history is preserved */
        $actions['unlink_profile'] = $this->actions->make([
            'action' => 'unlink_profile',
            'label' => __('maternity_handoffs.emergency.unlink_pregnancy_profile'),
            'feature_enabled' => $contextEnabled && $handoffsEnabled && $hasExplicitLink,
            'has_permission' => $user->can('emergency.maternity_context.unlink')
                && $user->can('maternity.pregnancy.view'),
            'lifecycle_ok' => $caseOpen,
            'lifecycle_reason' => $lifecycleReason,
            'route' => 'admin.emergency.cases.maternity-context.unlink',
            'parameters' => $params,
            'modal' => 'emergencyUnlinkPregnancyModal',
            'confirmation' => \App\Data\Maternity\MaternityHandoffActionViewModel::CONFIRM_REASON,
            'required_fields' => ['reason'],
            'source_module' => 'emergency',
            'source_record_id' => $case->id,
            'target_context_type' => 'pregnancy_profile',
            'context' => ['summary' => $summary, 'notices' => [
                __('maternity_handoffs.modal.history_preserved'),
            ]],
        ], $returnContext);

        /* Explicit profile creation */
        $actions['create_profile'] = $this->actions->make([
            'action' => 'create_profile',
            'label' => __('maternity_handoffs.emergency.create_pregnancy_profile'),
            'description' => __('maternity_handoffs.emergency.create_profile_explicit_only'),
            'feature_enabled' => $contextEnabled && $handoffsEnabled && ! $hasExplicitLink,
            'has_permission' => $user->can('emergency.maternity_context.create_profile')
                && $user->can('maternity.pregnancy.create'),
            'lifecycle_ok' => $caseOpen,
            'lifecycle_reason' => $lifecycleReason,
            'route' => 'admin.emergency.cases.maternity-context.create-profile',
            'parameters' => $params,
            'modal' => 'emergencyCreatePregnancyModal',
            'source_module' => 'emergency',
            'source_record_id' => $case->id,
            'target_context_type' => 'pregnancy_profile',
            'context' => ['summary' => $summary],
        ], $returnContext);

        /* Start or open Labor — explicit link required, suggestion insufficient */
        $activeLabor = $hasExplicitLink && $profile && $handoffsEnabled
            ? $this->handoffs->activeLaborEpisode($profile)
            : null;

        $actions['start_labor'] = $this->actions->make([
            'action' => 'start_labor',
            'label' => __('maternity_handoffs.emergency.start_labor'),
            'existing_label' => __('maternity_handoffs.emergency.open_existing_labor'),
            'feature_enabled' => $contextEnabled && $handoffsEnabled,
            'has_permission' => $user->can('emergency.maternity_context.start_labor')
                && $user->can('maternity.labor.start'),
            'lifecycle_ok' => $caseOpen,
            'lifecycle_reason' => $lifecycleReason,
            'context_ok' => $hasExplicitLink,
            'context_reason' => __('maternity_handoffs.states.explicit_link_required'),
            'existing' => $activeLabor ? [
                'id' => $activeLabor->id,
                'label' => __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.labor'), 'id' => $activeLabor->id,
                ]),
                'status' => $activeLabor->status?->label(),
                'url' => $this->route('admin.maternity.labor.show', $activeLabor),
            ] : null,
            'route' => 'admin.emergency.cases.maternity-context.start-labor',
            'parameters' => $params,
            'modal' => 'emergencyStartLaborModal',
            'required_fields' => ['pregnancy_profile_id'],
            'source_module' => 'emergency',
            'source_record_id' => $case->id,
            'target_context_type' => 'labor',
            'context' => [
                'summary' => $summary,
                'pregnancy_profile_id' => $profile?->id,
            ],
        ], $returnContext);

        /* Create or open the Emergency admission request */
        $actions['create_admission_request'] = $this->actions->make([
            'action' => 'create_admission_request',
            'label' => __('maternity_handoffs.emergency.create_admission_request'),
            'existing_label' => __('maternity_handoffs.emergency.open_existing_admission_request'),
            'feature_enabled' => $contextEnabled && $handoffsEnabled,
            'has_permission' => $user->can('emergency.maternity_context.create_admission_request')
                && $user->can('admission.requests.create'),
            'lifecycle_ok' => $caseOpen,
            'lifecycle_reason' => $lifecycleReason,
            // A SUGGESTED context must be confirmed before a maternity-aware
            // request is raised — the suggestion itself is never propagated.
            'context_ok' => $hasExplicitLink || $context->isNone(),
            'context_reason' => __('maternity_handoffs.emergency.confirm_context_first'),
            'existing' => $openRequest ? [
                'id' => $openRequest->id,
                'label' => __('maternity_handoffs.consultation.existing_admission_request')
                    .' #'.$openRequest->id,
                'status' => $openRequest->status?->label(),
                'url' => $this->route('admin.admissions.requests.show', $openRequest),
            ] : null,
            'route' => 'admin.emergency.cases.maternity-context.admission-request',
            'parameters' => $params,
            'modal' => 'emergencyMaternityAdmissionRequestModal',
            'source_module' => 'emergency',
            'source_record_id' => $case->id,
            'target_module' => 'admission',
            'target_context_type' => 'pregnancy_profile',
            'context' => [
                'summary' => $summary,
                'operational_source' => __('maternity_handoffs.modal.source_emergency'),
                'clinical_context' => __('maternity_handoffs.modal.context_maternity'),
                'default_priority' => 'urgent',
                'wards' => $handoffsEnabled && $caseOpen ? $this->wards() : [],
                'notices' => [
                    __('maternity_handoffs.emergency.emergency_owns_acute_care'),
                    __('maternity_handoffs.modal.no_admission_created'),
                ],
            ],
        ], $returnContext);

        return $actions;
    }

    /**
     * Bounded, active-only ward list. Beds are never loaded — no action here
     * reserves one.
     *
     * @return list<array{id: int, name: string}>
     */
    private function wards(): array
    {
        return Ward::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name'])
            ->map(fn (Ward $ward) => ['id' => (int) $ward->id, 'name' => (string) $ward->name])
            ->all();
    }

    /**
     * Read-only display values for a modal body. Identifiers and dates only.
     *
     * @return array<string, ?string>
     */
    private function contextSummary(OperationalMaternityContext $context): array
    {
        $profile = $context->pregnancyProfile;

        return array_filter([
            __('maternity_handoffs.cards.pregnancy') => $profile
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
                ])
                : null,
            __('maternity_handoffs.fields.gestational_age') => $profile?->gestational_age_weeks !== null
                ? sprintf('%dw %dd', (int) $profile->gestational_age_weeks, (int) ($profile->gestational_age_days ?? 0))
                : null,
            __('maternity_handoffs.fields.edd') => $profile?->estimated_due_date?->format('d M Y'),
            __('maternity_handoffs.cards.labor') => $context->laborEpisode
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.labor'), 'id' => $context->laborEpisode->id,
                ])
                : null,
        ], fn ($value) => $value !== null);
    }

    /** A safe return context pointing back at this emergency case. */
    public function returnContext(EmergencyCase $case): ?MaternityReturnContext
    {
        return MaternityReturnContext::make(
            MaternityReturnContext::MODULE_EMERGENCY,
            'admin.emergency.cases.show',
            ['emergencyCase' => $case->id],
            'maternity-context',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestState($request): array
    {
        return [
            'id' => $request?->id,
            'status' => $request?->status?->label(),
            'url' => $request ? $this->route('admin.admissions.requests.show', $request) : null,
        ];
    }

    /**
     * Permission-resolved actions.
     *
     * Every bridge permission is paired with the underlying domain permission —
     * the bridge never escalates access to Maternity or Admission.
     *
     * @return array<string, bool>
     */
    private function actions(?User $user, bool $hasExplicitLink, bool $handoffsEnabled): array
    {
        if (! $user) {
            return [];
        }

        $canView = $user->can('emergency.maternity_context.view');

        if (! $handoffsEnabled) {
            // Context-only mode: navigation is allowed, mutation is not.
            return ['view' => $canView];
        }

        return [
            'view' => $canView,
            'link' => $user->can('emergency.maternity_context.link')
                && $user->can('maternity.pregnancy.view'),
            'unlink' => $hasExplicitLink && $user->can('emergency.maternity_context.unlink'),
            'create_profile' => $user->can('emergency.maternity_context.create_profile')
                && $user->can('maternity.pregnancy.create'),
            'start_labor' => $hasExplicitLink
                && $user->can('emergency.maternity_context.start_labor')
                && $user->can('maternity.labor.start'),
            'create_admission_request' => $user->can('emergency.maternity_context.create_admission_request')
                && $user->can('admission.requests.create'),
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
