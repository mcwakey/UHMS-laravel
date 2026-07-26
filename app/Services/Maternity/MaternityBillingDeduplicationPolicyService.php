<?php

namespace App\Services\Maternity;

use App\Data\Maternity\Billing\MaternityBillingPolicyDecision as Decision;
use App\Enums\MaternityBillingDeduplicationPolicy as Policy;
use App\Models\AntenatalVisit;
use App\Models\ConsultationSpecialtyBillingApplication;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityBillingEvent;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.6 — READ-ONLY billing de-duplication policy.
 *
 * Decides which billing source WOULD own a clinical act, so the overlap between
 * an event-specific consultation specialty charge and a maternity event charge
 * is visible BEFORE Phase 14.2 posting is built.
 *
 * This service never creates an invoice item, an invoice, a maternity billing
 * event or a consultation billing application; it never recalculates an
 * invoice, posts a journal or reverses a charge.
 *
 * The distinction that matters:
 *
 *   A. BASE ENCOUNTER CHARGE — the general consultation attendance fee. It is
 *      legitimately separate from a clinical maternity event and is NEVER
 *      treated as a duplicate merely because an ANC visit exists.
 *   B. EVENT-SPECIFIC CONSULTATION CHARGE — a specialty mapping intended to
 *      charge the same act as the maternity event. This is the only thing that
 *      can conflict.
 *   C. MATERNITY EVENT CHARGE — bound to the actual source record.
 */
class MaternityBillingDeduplicationPolicyService
{
    /**
     * Specialty SECTION keys whose consultation charge would bill the same
     * clinical act as a given maternity mapping key.
     *
     * Derived from the approved source-of-truth matrix: these are the sections
     * that record the maternity act itself, so a specialty mapping billing on
     * them overlaps the maternity event charge. Sections that merely accompany
     * the encounter (complaints, plan, examination) are deliberately absent —
     * they belong to the base attendance charge.
     *
     * @var array<string, list<string>>
     */
    private const OVERLAPPING_SECTIONS = [
        'anc_registration_package' => ['antenatal_vitals', 'fetal_assessment', 'current_pregnancy'],
        'anc_follow_up' => ['antenatal_vitals', 'fetal_assessment'],
        'labor_observation' => ['labor_progress', 'fetal_assessment'],
        'maternity_admission' => ['labor_progress'],
        'newborn_care' => ['newborn_assessment'],
        'neonatal_observation' => ['newborn_assessment'],
        'newborn_resuscitation' => ['newborn_assessment'],
        'postnatal_mother_care' => ['postnatal_review'],
        'postnatal_newborn_care' => ['postnatal_review'],
    ];

    /**
     * Approved defaults for EVENT-SPECIFIC billing. Every clinical maternity
     * act belongs to its maternity record; the consultation keeps only its
     * attendance fee.
     *
     * @var array<string, Policy>
     */
    private const DEFAULTS = [
        AntenatalVisit::class => Policy::MATERNITY_EVENT_ONLY,
        LaborEpisode::class => Policy::MATERNITY_EVENT_ONLY,
        DeliveryRecord::class => Policy::MATERNITY_EVENT_ONLY,
        NewbornRecord::class => Policy::MATERNITY_EVENT_ONLY,
        PostnatalCase::class => Policy::MATERNITY_EVENT_ONLY,
    ];

    public function enabled(): bool
    {
        return (bool) config('billing.maternity_billing.deduplication_policy_enabled', false);
    }

    public function bothWhenConfiguredAllowed(): bool
    {
        return (bool) config('billing.maternity_billing.allow_both_when_configured', false);
    }

    public function manualSelectionAllowed(): bool
    {
        return (bool) config('billing.maternity_billing.allow_manual_selection', false);
    }

    /**
     * Evaluate the policy for one maternity source + mapping key.
     *
     * @param  int|null  $consultationRouteId  the consultation this act was recorded from, when known
     */
    public function decide(
        Model $sourceModel,
        string $mappingKey,
        ?int $consultationRouteId = null,
    ): Decision {
        if (! $this->enabled()) {
            // Flag off: a disabled decision with no queries and no behaviour
            // change anywhere else.
            return Decision::disabled($consultationRouteId);
        }

        $sourceType = $this->sourceType($sourceModel);
        $identity = $this->duplicateIdentity($sourceModel, $mappingKey);

        if ($identity === null) {
            return new Decision(
                policy: Policy::MANUAL_SELECTION,
                status: Decision::STATUS_MANUAL_REVIEW_REQUIRED,
                consultationRouteId: $consultationRouteId,
                maternitySourceType: $sourceType,
                maternitySourceId: $sourceModel->getKey(),
                maternityMappingKey: $mappingKey,
                reasonCode: 'missing_source_identity',
                warnings: ['missing_source_identity'],
                requiresManualReview: true,
                configuration: $this->configuration(),
            );
        }

        $policy = self::DEFAULTS[$this->baseClass($sourceModel)] ?? Policy::MANUAL_SELECTION;

        // An event-specific consultation charge for the SAME act is the only
        // thing that can conflict. The base attendance fee is not consulted.
        $conflicting = $consultationRouteId
            ? $this->eventSpecificConsultationApplication($consultationRouteId, $mappingKey)
            : null;

        $maternityEvent = $this->maternityBillingEvent($sourceModel, $sourceType, $mappingKey);

        $warnings = [];
        $requiresReview = false;

        if ($policy === Policy::BOTH_WHEN_CONFIGURED && ! $this->bothWhenConfiguredAllowed()) {
            $warnings[] = 'both_disabled';
            $requiresReview = true;
        }

        if ($policy === Policy::MANUAL_SELECTION && ! $this->manualSelectionAllowed()) {
            $warnings[] = 'manual_selection_disabled';
            $requiresReview = true;
        }

        [$status, $allowed, $suppressed, $reason] = $this->resolveOutcome(
            $policy, $conflicting !== null, $requiresReview
        );

        if ($conflicting !== null && $policy === Policy::MATERNITY_EVENT_ONLY) {
            $warnings[] = 'event_specific_duplicate_risk';
        }

        return new Decision(
            policy: $policy,
            status: $status,
            consultationRouteId: $consultationRouteId,
            maternitySourceType: $sourceType,
            maternitySourceId: $sourceModel->getKey(),
            maternityMappingKey: $mappingKey,
            consultationBillingSource: $conflicting
                ? Decision::SOURCE_CONSULTATION
                : null,
            allowedBillingSource: $allowed,
            suppressedBillingSource: $suppressed,
            reasonCode: $reason,
            consultationBillingApplicationId: $conflicting?->id,
            maternityBillingEventId: $maternityEvent?->id,
            duplicateIdentity: $identity,
            warnings: array_values(array_unique($warnings)),
            requiresManualReview: $requiresReview,
            configuration: $this->configuration(),
        );
    }

    /**
     * Policy for an Obstetrics consultation with NO maternity event: the
     * consultation charge simply stands.
     */
    public function decideForConsultationOnly(?int $consultationRouteId = null): Decision
    {
        if (! $this->enabled()) {
            return Decision::disabled($consultationRouteId);
        }

        return new Decision(
            policy: Policy::CONSULTATION_ONLY,
            status: Decision::STATUS_NO_CONFLICT,
            consultationRouteId: $consultationRouteId,
            allowedBillingSource: Decision::SOURCE_CONSULTATION,
            reasonCode: 'no_maternity_event',
            configuration: $this->configuration(),
        );
    }

    /**
     * The stable clinical identity of a billable act.
     *
     * Deliberately built from the SOURCE RECORD ID plus the mapping key —
     * never from patient + date, which would wrongly collapse two legitimate
     * ANC visits on the same day into one "duplicate".
     *
     * @return array<string, mixed>|null null when no reliable identity exists
     */
    public function duplicateIdentity(Model $sourceModel, string $mappingKey): ?array
    {
        $id = $sourceModel->getKey();

        if (! $id) {
            return null;
        }

        return [
            'source_type' => $this->sourceType($sourceModel),
            'source_id' => (int) $id,
            'mapping_key' => $mappingKey,
        ];
    }

    /* ── Internals ─────────────────────────────────────────────────────── */

    /**
     * @return array{0: string, 1: string, 2: ?string, 3: string}
     */
    private function resolveOutcome(Policy $policy, bool $hasConflict, bool $requiresReview): array
    {
        if ($requiresReview) {
            return [
                Decision::STATUS_MANUAL_REVIEW_REQUIRED,
                Decision::SOURCE_NONE,
                null,
                'requires_configuration',
            ];
        }

        return match ($policy) {
            Policy::MATERNITY_EVENT_ONLY => [
                $hasConflict
                    ? Decision::STATUS_DUPLICATE_SOURCE_SUPPRESSED
                    : Decision::STATUS_NO_CONFLICT,
                Decision::SOURCE_MATERNITY_EVENT,
                $hasConflict ? Decision::SOURCE_CONSULTATION : null,
                $hasConflict ? 'event_owned_by_maternity' : 'maternity_event_only',
            ],
            Policy::CONSULTATION_ONLY => [
                Decision::STATUS_NO_CONFLICT,
                Decision::SOURCE_CONSULTATION,
                null,
                'consultation_only',
            ],
            Policy::BOTH_WHEN_CONFIGURED => [
                Decision::STATUS_NO_CONFLICT,
                Decision::SOURCE_MATERNITY_EVENT,
                null,
                'both_when_configured',
            ],
            Policy::MANUAL_SELECTION => [
                Decision::STATUS_MANUAL_REVIEW_REQUIRED,
                Decision::SOURCE_NONE,
                null,
                'manual_selection',
            ],
        };
    }

    /**
     * An EVENT-SPECIFIC consultation specialty billing application whose
     * mapping targets the same clinical act as this maternity mapping key.
     *
     * The audit found that `consultation_specialty_service_mappings` has NO
     * maternity key column — the columns available are `section_key`,
     * `mapping_context`, `billing_trigger` and `department_type`. Rather than
     * invent a column, the overlap is declared here: a mapping is
     * event-specific for a maternity act when it bills one of the specialty
     * SECTIONS that record that same act.
     *
     * The base attendance charge is not a specialty billing application at all,
     * so it can never be returned here — that is what keeps A separate from B.
     */
    private function eventSpecificConsultationApplication(
        int $consultationRouteId,
        string $mappingKey,
    ): ?ConsultationSpecialtyBillingApplication {
        $sections = self::OVERLAPPING_SECTIONS[$mappingKey] ?? [];

        if ($sections === []) {
            return null;
        }

        return ConsultationSpecialtyBillingApplication::query()
            ->where('consultation_id', $consultationRouteId)
            ->whereHas('mapping', function ($query) use ($sections) {
                $query->whereIn('section_key', $sections);
            })
            ->latest('id')
            ->first();
    }

    private function maternityBillingEvent(
        Model $sourceModel,
        string $sourceType,
        string $mappingKey,
    ): ?MaternityBillingEvent {
        return MaternityBillingEvent::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceModel->getKey())
            ->where('mapping_key', $mappingKey)
            ->latest('id')
            ->first();
    }

    private function sourceType(Model $sourceModel): string
    {
        return match ($this->baseClass($sourceModel)) {
            AntenatalVisit::class => 'antenatal_visit',
            LaborEpisode::class => 'labor_episode',
            DeliveryRecord::class => 'delivery_record',
            NewbornRecord::class => 'newborn_record',
            PostnatalCase::class => 'postnatal_case',
            default => strtolower(class_basename($sourceModel)),
        };
    }

    /** @return class-string */
    private function baseClass(Model $sourceModel): string
    {
        foreach (array_keys(self::DEFAULTS) as $class) {
            if ($sourceModel instanceof $class) {
                return $class;
            }
        }

        return $sourceModel::class;
    }

    /** @return array<string, bool> */
    private function configuration(): array
    {
        return [
            'policy_enabled' => $this->enabled(),
            'allow_both_when_configured' => $this->bothWhenConfiguredAllowed(),
            'allow_manual_selection' => $this->manualSelectionAllowed(),
            'billing_enabled' => (bool) config('billing.maternity_billing.enabled', false),
            'newborn_billing_policy' => (string) config('billing.maternity_billing.newborn_billing_policy', 'mother') === 'mother',
        ];
    }
}
