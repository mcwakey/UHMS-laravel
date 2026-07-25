<?php

namespace App\Services\Consultation\Maternity;

use App\Enums\ConsultationMaternityContextType;
use App\Enums\ConsultationMaternityLinkRole;
use App\Enums\LogModule;
use App\Models\AntenatalVisit;
use App\Models\ConsultationMaternityLink;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityCase;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use App\Models\User;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 14R.2 — the only supported way to create, replace or retire a
 * consultation ↔ maternity link.
 *
 * Guarantees:
 *  - context type and every foreign key are DERIVED from the target model;
 *    callers never supply raw id combinations.
 *  - the consultation patient must own the maternity record (fail closed).
 *  - exactly one active link per (consultation, context type); history is
 *    never deleted, only retired via `active_slot = null`.
 *
 * This service does not create maternity records and does not start any
 * maternity workflow.
 */
class ConsultationMaternityLinkService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    public function getActiveLink(
        VisitConsultationRoute $consultation,
        ConsultationMaternityContextType $contextType,
    ): ?ConsultationMaternityLink {
        return ConsultationMaternityLink::query()
            ->forConsultation($consultation)
            ->forContextType($contextType)
            ->active()
            ->first();
    }

    /** @return Collection<int, ConsultationMaternityLink> */
    public function getActiveLinks(VisitConsultationRoute $consultation): Collection
    {
        return ConsultationMaternityLink::query()
            ->forConsultation($consultation)
            ->active()
            ->with([
                'pregnancyProfile', 'maternityCase', 'antenatalVisit',
                'laborEpisode', 'deliveryRecord', 'newbornRecord', 'postnatalCase',
            ])
            ->orderBy('id')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Validation + derivation
    |--------------------------------------------------------------------------
    */

    /**
     * Validate that the target is supported and belongs to the consultation's
     * patient. Throws a domain exception on any inconsistency (fail closed).
     */
    public function validateTarget(VisitConsultationRoute $consultation, Model $target): ConsultationMaternityContextType
    {
        $contextType = ConsultationMaternityContextType::forModel($target);

        if (! $contextType) {
            throw ConsultationMaternityLinkException::unsupportedTarget($target::class);
        }

        if (! $target->exists || $target->getKey() === null) {
            throw ConsultationMaternityLinkException::invalidTarget();
        }

        $consultationPatientId = (int) $consultation->patient_id;
        if ($consultationPatientId === 0) {
            throw ConsultationMaternityLinkException::invalidTarget();
        }

        // Newborn links in the O&G bridge attach to the MOTHER. A newborn ↔
        // paediatrics bridge is deliberately out of scope for this phase.
        $targetPatientId = $target instanceof NewbornRecord || $target instanceof PostnatalCase
            ? (int) $target->mother_patient_id
            : (int) ($target->patient_id ?? 0);

        if ($targetPatientId === 0) {
            throw ConsultationMaternityLinkException::inconsistentContext();
        }

        // Visit mismatch is allowed on purpose: maternity records are
        // longitudinal and legitimately span visits. Patient mismatch never is.
        if ($targetPatientId !== $consultationPatientId) {
            throw ConsultationMaternityLinkException::patientMismatch();
        }

        return $contextType;
    }

    /**
     * Build the full foreign-key payload for a target, deriving the
     * longitudinal pregnancy-profile root from the target chain.
     *
     * @return array<string, mixed>
     */
    public function deriveContextPayload(Model $target): array
    {
        $contextType = ConsultationMaternityContextType::forModel($target);

        if (! $contextType) {
            throw ConsultationMaternityLinkException::unsupportedTarget($target::class);
        }

        $payload = [
            'context_type' => $contextType->value,
            'pregnancy_profile_id' => null,
            'maternity_case_id' => null,
            'antenatal_visit_id' => null,
            'labor_episode_id' => null,
            'delivery_record_id' => null,
            'newborn_record_id' => null,
            'postnatal_case_id' => null,
        ];

        $payload[$contextType->foreignKey()] = $target->getKey();

        // Derive the longitudinal root (and case, where the target carries it).
        $payload['pregnancy_profile_id'] = match (true) {
            $target instanceof PregnancyProfile => $target->getKey(),
            $target instanceof MaternityCase,
            $target instanceof AntenatalVisit,
            $target instanceof LaborEpisode,
            $target instanceof DeliveryRecord,
            $target instanceof NewbornRecord,
            $target instanceof PostnatalCase => $target->pregnancy_profile_id,
            default => null,
        };

        if (! $target instanceof MaternityCase && isset($target->maternity_case_id)) {
            $payload['maternity_case_id'] = $target->maternity_case_id;
        }

        // A target whose longitudinal root is missing cannot be trusted.
        if ($payload['pregnancy_profile_id'] === null) {
            throw ConsultationMaternityLinkException::inconsistentContext();
        }

        return $payload;
    }

    /*
    |--------------------------------------------------------------------------
    | Mutations
    |--------------------------------------------------------------------------
    */

    /**
     * Link a maternity record to a consultation.
     *
     * Idempotent for the same target. If a DIFFERENT target is already active
     * for this context type, the caller must call relink() explicitly — this
     * method will not silently replace a clinician's link.
     */
    public function link(
        VisitConsultationRoute $consultation,
        Model $target,
        User $actor,
        ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::PRIMARY,
        ?string $reason = null,
        array $metadata = [],
    ): ConsultationMaternityLink {
        $contextType = $this->validateTarget($consultation, $target);
        $payload = $this->deriveContextPayload($target);

        return DB::transaction(function () use ($consultation, $contextType, $payload, $actor, $role, $reason, $metadata, $target) {
            $existing = ConsultationMaternityLink::query()
                ->forConsultation($consultation)
                ->forContextType($contextType)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing) {
                // Same target → idempotent no-op.
                if ((int) $existing->targetId() === (int) $target->getKey()) {
                    return $existing;
                }

                throw ConsultationMaternityLinkException::relinkRequired();
            }

            $link = $this->createActiveRow($consultation, $payload, $actor, $role, $reason, $metadata);

            $this->logLinkAction('CONSULTATION_MATERNITY_CONTEXT_LINKED', $link, $actor, $consultation);

            return $link;
        });
    }

    /**
     * Replace the active link for a context type, preserving the old row as
     * history. A reason is mandatory.
     */
    public function relink(
        VisitConsultationRoute $consultation,
        Model $target,
        User $actor,
        string $reason,
        ConsultationMaternityLinkRole $role = ConsultationMaternityLinkRole::PRIMARY,
        array $metadata = [],
    ): ConsultationMaternityLink {
        if (trim($reason) === '') {
            throw ConsultationMaternityLinkException::reasonRequired();
        }

        $contextType = $this->validateTarget($consultation, $target);
        $payload = $this->deriveContextPayload($target);

        return DB::transaction(function () use ($consultation, $contextType, $payload, $actor, $role, $reason, $metadata, $target) {
            $existing = ConsultationMaternityLink::query()
                ->forConsultation($consultation)
                ->forContextType($contextType)
                ->active()
                ->lockForUpdate()
                ->first();

            if ($existing && (int) $existing->targetId() === (int) $target->getKey()) {
                return $existing;
            }

            $previousId = $existing?->id;
            $previousTargetId = $existing?->targetId();

            if ($existing) {
                $this->retireRow($existing, $actor, $reason);
            }

            $link = $this->createActiveRow($consultation, $payload, $actor, $role, $reason, $metadata);

            $this->logLinkAction(
                'CONSULTATION_MATERNITY_CONTEXT_RELINKED',
                $link,
                $actor,
                $consultation,
                $reason,
                ['previous_link_id' => $previousId, 'previous_target_id' => $previousTargetId],
            );

            return $link;
        });
    }

    /**
     * Retire an active link. The row is never deleted — it becomes history.
     */
    public function unlink(
        VisitConsultationRoute $consultation,
        ConsultationMaternityContextType $contextType,
        User $actor,
        string $reason,
    ): ConsultationMaternityLink {
        if (trim($reason) === '') {
            throw ConsultationMaternityLinkException::reasonRequired();
        }

        return DB::transaction(function () use ($consultation, $contextType, $actor, $reason) {
            $link = ConsultationMaternityLink::query()
                ->forConsultation($consultation)
                ->forContextType($contextType)
                ->active()
                ->lockForUpdate()
                ->first();

            if (! $link) {
                throw ConsultationMaternityLinkException::linkNotFound();
            }

            $this->retireRow($link, $actor, $reason);

            $this->logLinkAction(
                'CONSULTATION_MATERNITY_CONTEXT_UNLINKED',
                $link->refresh(),
                $actor,
                $consultation,
                $reason,
            );

            return $link;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function createActiveRow(
        VisitConsultationRoute $consultation,
        array $payload,
        User $actor,
        ConsultationMaternityLinkRole $role,
        ?string $reason,
        array $metadata,
    ): ConsultationMaternityLink {
        try {
            return ConsultationMaternityLink::create($payload + [
                'consultation_route_id' => $consultation->id,
                'link_role' => $role->value,
                'linked_by' => $actor->id,
                'linked_at' => now(),
                'reason' => $reason,
                'metadata' => $metadata === [] ? null : $metadata,
                'active_slot' => ConsultationMaternityLink::ACTIVE_SLOT,
            ]);
        } catch (QueryException $e) {
            // The unique (consultation, context_type, active_slot) index is the
            // final race guard. Convert a duplicate-key race into either an
            // idempotent result or a clear domain error.
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            $winner = ConsultationMaternityLink::query()
                ->forConsultation($consultation)
                ->forContextType($payload['context_type'])
                ->active()
                ->first();

            $targetId = $payload[
                ConsultationMaternityContextType::from($payload['context_type'])->foreignKey()
            ] ?? null;

            if ($winner && (int) $winner->targetId() === (int) $targetId) {
                return $winner;
            }

            throw ConsultationMaternityLinkException::relinkRequired();
        }
    }

    private function retireRow(ConsultationMaternityLink $link, User $actor, string $reason): void
    {
        $link->forceFill([
            'unlinked_by' => $actor->id,
            'unlinked_at' => now(),
            'active_slot' => null,
            'reason' => $reason,
        ])->save();
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        // 23000/23505 across MySQL/MariaDB/Postgres; SQLite reports 23000 too.
        return in_array((string) $e->getCode(), ['23000', '23505'], true);
    }

    /**
     * Audit trail. Deliberately identifiers-only — never clinical notes or
     * maternity content.
     */
    private function logLinkAction(
        string $action,
        ConsultationMaternityLink $link,
        User $actor,
        VisitConsultationRoute $consultation,
        ?string $reason = null,
        array $extra = [],
    ): void {
        $this->activityLog->log(
            LogModule::CONSULTATION,
            $action,
            [
                'patient_id' => $consultation->patient_id,
                'visit_id' => $consultation->visit_id,
                'causer' => $actor,
                'metadata' => array_filter([
                    'consultation_route_id' => $consultation->id,
                    'context_type' => $link->context_type?->value,
                    'link_role' => $link->link_role?->value,
                    'target_record_id' => $link->targetId(),
                    'pregnancy_profile_id' => $link->pregnancy_profile_id,
                    'reason' => $reason,
                ] + $extra, fn ($value) => $value !== null),
            ],
            $link,
            $action,
        );
    }
}
