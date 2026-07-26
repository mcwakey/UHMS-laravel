<?php

namespace App\Services\Maternity\Handoffs;

use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\DeliveryRecord;
use App\Models\EmergencyCase;
use App\Models\EmergencyMaternityLink;
use App\Models\LaborEpisode;
use App\Models\PostnatalCase;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\Emergency\Maternity\EmergencyMaternityLinkService;
use App\Services\EmergencyCaseService;
use App\Services\Maternity\Context\MaternityContextTargetService;
use App\Services\Maternity\Context\MaternityIntegrationFlags;
use App\Services\Maternity\Context\MaternityLinkException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.5 — explicit Maternity → Emergency escalation handoff.
 *
 * Labor and Postnatal already carry escalation/referral INDICATORS
 * (`labor_episodes.emergency_escalation_required`,
 * `postnatal_cases.referral_required`). Those flags remain purely clinical
 * signals: setting one creates NO emergency case. Only the explicit
 * "Create/Open Emergency Case" action does, and only through the existing
 * EmergencyCaseService — no EmergencyCase row is ever inserted by hand.
 *
 * After handoff Emergency is the operational owner of the acute episode; the
 * maternity source record is preserved untouched and remains Maternity-owned.
 *
 * Out of scope by design: Theatre case creation, and any automatic admission
 * request. The clinician chooses an Emergency disposition later if needed.
 */
class MaternityEmergencyHandoffService
{
    /** The maternity records that may escalate to Emergency. */
    private const SUPPORTED_SOURCES = [
        LaborEpisode::class,
        DeliveryRecord::class,
        PostnatalCase::class,
    ];

    /**
     * Default arrival mode for an internal maternity escalation. Matches the
     * existing EmergencyCaseController validation set — no new value is
     * introduced by this phase.
     */
    private const DEFAULT_ARRIVAL_MODE = 'TRANSFER_FROM_WARD';

    public function __construct(
        private readonly EmergencyCaseService $emergencyCases,
        private readonly EmergencyMaternityLinkService $links,
        private readonly MaternityContextTargetService $targets,
        private readonly ActivityLogService $activityLog,
        private readonly MaternityIntegrationFlags $flags,
    ) {}

    public function enabled(): bool
    {
        return $this->flags->maternityEmergencyHandoffsEnabled();
    }

    public function supports(Model $source): bool
    {
        foreach (self::SUPPORTED_SOURCES as $class) {
            if ($source instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * An emergency case already linked to this maternity record and still open.
     *
     * Duplicate identity: maternity source record + ACTIVE linked emergency
     * case + same patient. Disposed and cancelled cases are finished and are
     * never reused.
     */
    public function existingLinkedCase(Model $source): ?EmergencyCase
    {
        $contextType = $this->targets->contextTypeFor($source);

        if (! $contextType) {
            return null;
        }

        $caseIds = EmergencyMaternityLink::query()
            ->active()
            ->forContextType($contextType)
            ->where($contextType->foreignKey(), $source->getKey())
            ->pluck('emergency_case_id');

        if ($caseIds->isEmpty()) {
            return null;
        }

        return EmergencyCase::query()
            ->whereIn('id', $caseIds)
            ->where('patient_id', $this->targets->owningPatientId($source))
            ->whereNotIn('emergency_status', [
                EmergencyCase::STATUS_DISPOSED,
                EmergencyCase::STATUS_CANCELLED,
            ])
            ->latest('id')
            ->first();
    }

    /**
     * An active emergency case already open on this record's visit, even if it
     * is not yet linked.
     *
     * Emergency's OWN duplicate rule forbids a second active case per visit, so
     * escalating into an in-progress emergency episode must attach to it rather
     * than fail. "Open Emergency Case" is exactly that path — the case is
     * reused and linked, never duplicated.
     */
    public function existingCaseOnVisit(Model $source): ?EmergencyCase
    {
        $visitId = $source->visit_id ?? null;

        if (! $visitId) {
            return null;
        }

        return EmergencyCase::query()
            ->where('visit_id', $visitId)
            ->where('patient_id', $this->targets->owningPatientId($source))
            ->whereNotIn('emergency_status', [
                EmergencyCase::STATUS_DISPOSED,
                EmergencyCase::STATUS_CANCELLED,
            ])
            ->latest('id')
            ->first();
    }

    /**
     * Create — or re-open — the Emergency case for a maternity escalation.
     *
     * Repeated clicks return the SAME case. Exactly one emergency case is ever
     * created per active escalation.
     *
     * @param  array<string, mixed>  $data  arrival_mode / chief_complaint / ...
     * @return array{case: EmergencyCase, link: Model, reused: bool}
     */
    public function createOrReuseEmergencyCase(Model $source, array $data, User $actor): array
    {
        if (! $this->supports($source)) {
            throw MaternityLinkException::unsupportedTarget($source::class);
        }

        $patientId = $this->targets->owningPatientId($source);

        if ($patientId === 0) {
            throw MaternityLinkException::inconsistentContext();
        }

        return DB::transaction(function () use ($source, $data, $actor, $patientId) {
            // Reuse in two situations: an already-linked active case, or an
            // active case on the same visit that has not been linked yet.
            // Both are "open the existing emergency episode", never a second one.
            $existing = $this->existingLinkedCase($source) ?? $this->existingCaseOnVisit($source);

            if ($existing) {
                // Idempotent: same target, so the link service returns the
                // existing row rather than writing a second one.
                $link = $this->links->linkForHandoff($existing, $source, $actor, [
                    'escalated_from' => class_basename($source),
                ]);

                return ['case' => $existing, 'link' => $link, 'reused' => true];
            }

            // Always through the existing service — never a manual insert.
            $case = $this->emergencyCases->create(array_merge($data, [
                'patient_id' => $patientId,
                'visit_id' => $data['visit_id'] ?? $source->visit_id ?? null,
                'arrival_mode' => $data['arrival_mode'] ?? self::DEFAULT_ARRIVAL_MODE,
                'source' => $data['source'] ?? 'maternity',
            ]), $actor);

            $link = $this->links->linkForHandoff($case, $source, $actor, [
                'escalated_from' => class_basename($source),
            ]);

            $this->activityLog->log(
                LogModule::MATERNITY,
                'MATERNITY_EMERGENCY_HANDOFF_CREATED',
                [
                    'patient_id' => $patientId,
                    'visit_id' => $case->visit_id,
                    'causer' => $actor,
                    'metadata' => array_filter([
                        'source_module' => 'maternity',
                        'source_record_type' => class_basename($source),
                        'source_record_id' => $source->getKey(),
                        'emergency_case_id' => $case->id,
                        'pregnancy_profile_id' => $source->pregnancy_profile_id ?? null,
                        'handoff_role' => ConsultationMaternityLinkRole::HANDOFF->value,
                    ], fn ($value) => $value !== null),
                ],
                $case,
                'MATERNITY_EMERGENCY_HANDOFF_CREATED',
            );

            return ['case' => $case, 'link' => $link, 'reused' => false];
        });
    }
}
