<?php

namespace App\Services\Consultation\Maternity;

use App\Data\Maternity\MaternityHandoffActionViewModel as Action;
use App\Models\ConsultationSpecialtyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Models\Ward;
use App\Services\Consultation\ConsultationSessionEligibilityService;
use App\Services\Maternity\Context\MaternityHandoffActionFactory;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Support\Maternity\MaternityReturnContext;

/**
 * Phase 14R.5 / 14R.5.1 — prepares the Consultation maternity handoff panel.
 *
 * Built once in HandlesConsultationWorkspace. When
 * MATERNITY_CONSULTATION_HANDOFFS_ENABLED is off it returns
 * `['enabled' => false]` immediately with ZERO queries — no resolver call, no
 * request lookup, no postnatal lookup, no ward lookup.
 *
 * Availability rules:
 *   - Create Admission Request → Obstetrics only, never Gynaecology;
 *   - Refer to Obstetrics/Maternity → Gynaecology only;
 *   - Postnatal review → either, and read-only.
 *
 * 14R.5.1 adds typed `MaternityHandoffActionViewModel`s so Blade renders from a
 * resolved contract instead of rediscovering permissions, lifecycle, reuse or
 * return URLs for itself.
 */
class ConsultationMaternityHandoffPresenter
{
    public const OBSTETRICS = 'obstetrics';
    public const GYNAECOLOGY = 'gynecology';

    public function __construct(
        private readonly ConsultationMaternityContextResolver $resolver,
        private readonly ConsultationMaternityAdmissionRequestService $admissionRequests,
        private readonly ConsultationPostnatalReviewService $postnatal,
        private readonly GynaecologyObstetricsReferralService $referrals,
        private readonly ConsultationSessionEligibilityService $eligibility,
        private readonly MaternityHandoffActionFactory $actions,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(
        ?VisitConsultationRoute $consultation,
        ?ConsultationSpecialtyProfile $profile,
        ?User $user,
    ): array {
        $code = $profile?->code;
        $isObstetrics = $code === self::OBSTETRICS;
        $isGynaecology = $code === self::GYNAECOLOGY;

        if (! $this->flags->consultationHandoffsEnabled()
            || ! $consultation
            || ! $user
            || (! $isObstetrics && ! $isGynaecology)) {
            return ['enabled' => false, 'actions' => []];
        }

        // Explicit context only: a handoff must never carry a guessed record.
        $context = $this->resolver->resolveExplicitOnly($consultation);
        $hasExplicitLink = $context->isResolved() && $context->pregnancyProfile !== null;

        $canCreateRequest = $isObstetrics
            && $user->can('consultation.maternity_context.create_admission_request')
            && $user->can('admission.requests.create');

        $canRefer = $isGynaecology
            && $user->can('consultation.maternity_context.refer_obstetrics')
            && $user->can('consultations.create');

        $canOpenPostnatal = $user->can('consultation.maternity_context.open_postnatal')
            && $user->can('maternity.postnatal.view');

        // Only look up the open request when the button could actually show.
        $openRequest = $hasExplicitLink && $canCreateRequest
            ? $this->admissionRequests->existingOpenRequest($consultation, $context)
            : null;

        $postnatalCase = $canOpenPostnatal ? $context->postnatalCase : null;

        // Lifecycle comes from the SAME service the server guard uses, so the
        // UI can never disagree with the mutation boundary.
        $lifecycle = $this->lifecycle($consultation, $user);

        $returnContext = $this->returnContext($consultation);

        return [
            'enabled' => true,
            'can_view' => $user->can('consultation.maternity_context.view'),
            'is_obstetrics' => $isObstetrics,
            'is_gynaecology' => $isGynaecology,
            'has_explicit_link' => $hasExplicitLink,
            'pregnancy_profile_id' => $context->pregnancyProfile?->id,
            'can_create_admission_request' => $canCreateRequest,
            'can_refer_obstetrics' => $canRefer,
            'can_open_postnatal' => $canOpenPostnatal,
            'open_admission_request' => $openRequest ? [
                'id' => $openRequest->id,
                'status' => $openRequest->status?->label(),
                'url' => $this->route('admin.admissions.requests.show', $openRequest),
            ] : null,
            'postnatal' => $postnatalCase ? $this->postnatal->projection($postnatalCase) : null,
            'lifecycle' => $lifecycle,
            'return_context' => $returnContext?->toArray(),
            'actions' => $this->buildActions(
                $consultation, $user, $context, $hasExplicitLink,
                $canCreateRequest, $canRefer, $canOpenPostnatal,
                $openRequest, $postnatalCase, $lifecycle, $returnContext,
                $isObstetrics, $isGynaecology,
            ),
        ];
    }

    /* ── Typed actions ─────────────────────────────────────────────────── */

    /**
     * @return array<string, Action>
     */
    private function buildActions(
        VisitConsultationRoute $consultation,
        User $user,
        $context,
        bool $hasExplicitLink,
        bool $canCreateRequest,
        bool $canRefer,
        bool $canOpenPostnatal,
        $openRequest,
        $postnatalCase,
        array $lifecycle,
        ?MaternityReturnContext $returnContext,
        bool $isObstetrics,
        bool $isGynaecology,
    ): array {
        $visitId = $consultation->visit_id;
        $actions = [];

        /* Consultation → Admission Request (Obstetrics only) */
        $actions['create_admission_request'] = $this->actions->make([
            'action' => 'create_admission_request',
            'label' => __('maternity_handoffs.consultation.create_admission_request'),
            'existing_label' => __('maternity_handoffs.consultation.open_existing_admission_request'),
            'description' => __('maternity_handoffs.consultation.admission_request_from_consultation'),
            // Never offered in Gynaecology, regardless of permissions.
            'feature_enabled' => $isObstetrics,
            'has_permission' => $canCreateRequest,
            'lifecycle_ok' => $lifecycle['allowed'],
            'lifecycle_reason' => $lifecycle['message'],
            'context_ok' => $hasExplicitLink,
            'context_reason' => __('maternity_handoffs.consultation.explicit_link_required'),
            'existing' => $openRequest ? [
                'id' => $openRequest->id,
                'label' => __('maternity_handoffs.consultation.existing_admission_request')
                    .' #'.$openRequest->id,
                'status' => $openRequest->status?->label(),
                'url' => $this->route('admin.admissions.requests.show', $openRequest),
            ] : null,
            'route' => 'admin.consultations.maternity-context.admission-request',
            'parameters' => ['visit' => $visitId],
            'modal' => 'consultationMaternityAdmissionRequestModal',
            'required_fields' => [],
            'source_module' => 'consultation',
            'source_record_id' => $consultation->id,
            'target_module' => 'admission',
            'target_context_type' => 'pregnancy_profile',
            'context' => [
                'summary' => $this->contextSummary($context),
                'operational_source' => __('maternity_handoffs.modal.source_consultation'),
                'clinical_context' => __('maternity_handoffs.modal.context_maternity'),
                'default_priority' => 'routine',
                'wards' => $canCreateRequest && $hasExplicitLink && $lifecycle['allowed']
                    ? $this->wards()
                    : [],
                'notices' => [
                    __('maternity_handoffs.consultation.no_billing_posted'),
                ],
            ],
        ], $returnContext);

        /* Gynaecology → Obstetrics referral, incl. the K1 fallback */
        $referralUnavailable = null;
        $fallbackUrl = null;
        if ($canRefer && $hasExplicitLink && $lifecycle['allowed']) {
            // Resolved once; the modal and the fallback share the result.
            if (! $this->referrals->targetDepartment()) {
                $referralUnavailable = __('maternity_handoffs.fallback.obstetrics_mapping_unavailable');
                $fallbackUrl = $this->standardConsultationUrl($consultation);
            }
        }

        $actions['refer_obstetrics'] = $this->actions->make([
            'action' => 'refer_obstetrics',
            'label' => __('maternity_handoffs.consultation.refer_obstetrics'),
            'description' => __('maternity_handoffs.consultation.remains_gynaecology'),
            'feature_enabled' => $isGynaecology,
            'has_permission' => $canRefer,
            'lifecycle_ok' => $lifecycle['allowed'],
            'lifecycle_reason' => $lifecycle['message'],
            'context_ok' => $hasExplicitLink,
            'context_reason' => __('maternity_handoffs.consultation.explicit_link_required'),
            'unavailable_reason' => $referralUnavailable,
            'fallback_url' => $fallbackUrl,
            'fallback_label' => __('maternity_handoffs.fallback.continue_standard_consultation'),
            'route' => 'admin.consultations.maternity-context.refer-obstetrics',
            'parameters' => ['visit' => $visitId],
            'modal' => 'consultationReferObstetricsModal',
            'source_module' => 'consultation',
            'source_record_id' => $consultation->id,
            'target_module' => 'consultation',
            'target_context_type' => 'pregnancy_profile',
            'context' => [
                'summary' => $this->contextSummary($context),
                'notices' => [
                    __('maternity_handoffs.fallback.gynaecology_unchanged'),
                ],
            ],
        ], $returnContext);

        /* Postnatal review — a context action, available after completion */
        $candidates = $canOpenPostnatal && ! $postnatalCase
            ? $this->postnatal->candidates($consultation)
                ->map(fn ($case) => [
                    'id' => $case->id,
                    'label' => __('maternity_handoffs.cards.record_ref', [
                        'type' => __('maternity_handoffs.cards.postnatal'), 'id' => $case->id,
                    ]),
                ])->all()
            : [];

        $actions['link_postnatal'] = $this->actions->make([
            'action' => 'link_postnatal',
            'label' => __('maternity_handoffs.postnatal.link_case'),
            'existing_label' => __('maternity_handoffs.consultation.open_postnatal_review'),
            'description' => __('maternity_handoffs.postnatal.observations_remain_in_maternity'),
            'feature_enabled' => true,
            'has_permission' => $canOpenPostnatal,
            // Linking an existing record for review is a CONTEXT action, so it
            // stays available on a completed encounter (14R.2 policy).
            'lifecycle_ok' => true,
            'context_ok' => $postnatalCase !== null || $candidates !== [],
            'context_reason' => __('maternity_handoffs.postnatal.no_case_available'),
            'existing' => $postnatalCase ? [
                'id' => $postnatalCase->id,
                'label' => __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.postnatal'), 'id' => $postnatalCase->id,
                ]),
                'url' => $this->route('admin.maternity.postnatal.show', $postnatalCase),
            ] : null,
            'route' => 'admin.consultations.maternity-context.postnatal-review',
            'parameters' => ['visit' => $visitId],
            'modal' => 'consultationPostnatalReviewModal',
            'required_fields' => ['postnatal_case_id'],
            'source_module' => 'consultation',
            'source_record_id' => $consultation->id,
            'target_module' => 'maternity',
            'target_context_type' => 'postnatal',
            'context' => ['candidates' => $candidates],
        ], $returnContext);

        return $actions;
    }

    /* ── Helpers ───────────────────────────────────────────────────────── */

    /**
     * Lifecycle decision from the SAME eligibility service the server-side
     * mutation guard uses — the UI can never be more permissive than the guard.
     *
     * @return array{allowed: bool, code: ?string, message: ?string}
     */
    private function lifecycle(VisitConsultationRoute $consultation, User $user): array
    {
        $visit = $consultation->visit;

        if (! $visit) {
            return [
                'allowed' => false,
                'code' => 'missing_visit',
                'message' => __('maternity_handoffs.states.blocked_lifecycle'),
            ];
        }

        $decision = $this->eligibility->addItemDecision($visit, $consultation, $user);

        return [
            'allowed' => (bool) ($decision['allowed'] ?? false),
            'code' => $decision['code'] ?? null,
            'message' => $decision['allowed'] ?? false
                ? null
                : ($decision['message'] ?? __('maternity_handoffs.states.blocked_lifecycle')),
        ];
    }

    private function returnContext(VisitConsultationRoute $consultation): ?MaternityReturnContext
    {
        return MaternityReturnContext::make(
            MaternityReturnContext::MODULE_CONSULTATION,
            'admin.consultations.show',
            ['visit' => $consultation->visit_id],
            'maternity-handoffs',
        );
    }

    /**
     * The existing standard create-consultation flow, used as the K1 fallback.
     * Patient and visit are preserved; no department is preselected, because
     * preselecting an invalid one is exactly the failure being avoided.
     */
    private function standardConsultationUrl(VisitConsultationRoute $consultation): ?string
    {
        foreach ([
            ['admin.visits.show', ['visit' => $consultation->visit_id]],
            ['admin.consultations.show', ['visit' => $consultation->visit_id]],
        ] as [$name, $parameters]) {
            try {
                return route($name, $parameters);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Bounded, active-only ward list for the request form. Beds are never
     * loaded — this action reserves none.
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
     * Read-only summary shown inside a modal. Values only — no clinical
     * narrative, and nothing here is editable.
     *
     * @return array<string, ?string>
     */
    private function contextSummary($context): array
    {
        $profile = $context->pregnancyProfile ?? null;

        return array_filter([
            __('maternity_handoffs.cards.pregnancy') => $profile
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.pregnancy'), 'id' => $profile->id,
                ])
                : null,
            __('maternity_handoffs.fields.edd') => $profile?->estimated_due_date?->format('d M Y'),
            __('maternity_handoffs.cards.anc') => $context->antenatalVisit
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.anc'), 'id' => $context->antenatalVisit->id,
                ])
                : null,
            __('maternity_handoffs.cards.labor') => $context->laborEpisode
                ? __('maternity_handoffs.cards.record_ref', [
                    'type' => __('maternity_handoffs.cards.labor'), 'id' => $context->laborEpisode->id,
                ])
                : null,
        ], fn ($value) => $value !== null);
    }

    private function route(string $name, mixed $parameter): ?string
    {
        try {
            return route($name, $parameter);
        } catch (\Throwable) {
            return null;
        }
    }
}
