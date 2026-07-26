<?php

namespace App\Services\Maternity\Context;

use App\Data\Maternity\MaternityContextTargetDescriptor;
use App\Enums\ConsultationMaternityContextType;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\LaborEpisode;
use App\Models\MaternityCase;
use App\Models\NewbornRecord;
use App\Models\PostnatalCase;
use App\Models\PregnancyProfile;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5 — shared maternity target derivation.
 *
 * Extracted verbatim from ConsultationMaternityLinkService (14R.2) so the
 * Emergency, Admission Request and Admission bridges reuse exactly the same
 * rules instead of re-implementing them:
 *
 *   - map a supported maternity model to its context type;
 *   - derive the longitudinal Pregnancy Profile root from the target chain;
 *   - resolve the OWNING patient (mother_patient_id for Newborn/Postnatal);
 *   - reject unsupported, unsaved or root-less targets (fail closed).
 *
 * Deliberately unchanged from 14R.2:
 *   - patient mismatch is always fatal;
 *   - visit mismatch is ALLOWED — maternity records are longitudinal and
 *     legitimately span visits;
 *   - a target whose pregnancy_profile_id is missing is never trusted.
 *
 * This service performs no writes and starts no maternity workflow.
 */
class MaternityContextTargetService
{
    /**
     * Build a typed descriptor for a maternity target.
     *
     * When $expectedPatientId is supplied, ownership is enforced fail-closed.
     * Pass null only for callers that legitimately have no patient yet.
     */
    public function describe(Model $target, ?int $expectedPatientId = null): MaternityContextTargetDescriptor
    {
        $contextType = ConsultationMaternityContextType::forModel($target);

        if (! $contextType) {
            throw MaternityContextTargetException::unsupportedTarget($target::class);
        }

        if (! $target->exists || $target->getKey() === null) {
            throw MaternityContextTargetException::invalidTarget();
        }

        $patientId = $this->owningPatientId($target);

        if ($patientId === 0) {
            throw MaternityContextTargetException::inconsistentContext();
        }

        if ($expectedPatientId !== null) {
            if ((int) $expectedPatientId === 0) {
                throw MaternityContextTargetException::invalidTarget();
            }

            if ($patientId !== (int) $expectedPatientId) {
                throw MaternityContextTargetException::patientMismatch();
            }
        }

        $foreignKeys = $this->foreignKeyPayload($target, $contextType);

        return new MaternityContextTargetDescriptor(
            contextType: $contextType,
            targetClass: $target::class,
            targetId: (int) $target->getKey(),
            pregnancyProfileId: (int) $foreignKeys['pregnancy_profile_id'],
            patientId: $patientId,
            maternityCaseId: $foreignKeys['maternity_case_id'] !== null
                ? (int) $foreignKeys['maternity_case_id']
                : null,
            visitId: $this->nullableInt($target, 'visit_id'),
            admissionId: $this->nullableInt($target, 'admission_id'),
            foreignKeys: $foreignKeys,
            warnings: [],
        );
    }

    /**
     * The context type for a target, without any ownership check.
     * Returns null for unsupported models rather than throwing.
     */
    public function contextTypeFor(Model $target): ?ConsultationMaternityContextType
    {
        return ConsultationMaternityContextType::forModel($target);
    }

    /**
     * The patient who OWNS this maternity record.
     *
     * Newborn and Postnatal records attach to the MOTHER in the O&G bridge — a
     * newborn ↔ paediatrics bridge is deliberately out of scope. Returns 0 when
     * the record carries no usable owner.
     */
    public function owningPatientId(Model $target): int
    {
        return $target instanceof NewbornRecord || $target instanceof PostnatalCase
            ? (int) ($target->mother_patient_id ?? 0)
            : (int) ($target->patient_id ?? 0);
    }

    /**
     * The full nullable-FK payload for a target, including the derived
     * longitudinal root.
     *
     * @return array<string, mixed>
     */
    public function foreignKeyPayload(Model $target, ?ConsultationMaternityContextType $contextType = null): array
    {
        $contextType ??= ConsultationMaternityContextType::forModel($target);

        if (! $contextType) {
            throw MaternityContextTargetException::unsupportedTarget($target::class);
        }

        $payload = [
            'pregnancy_profile_id' => null,
            'maternity_case_id' => null,
            'antenatal_visit_id' => null,
            'labor_episode_id' => null,
            'delivery_record_id' => null,
            'newborn_record_id' => null,
            'postnatal_case_id' => null,
        ];

        $payload[$contextType->foreignKey()] = $target->getKey();

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
            throw MaternityContextTargetException::inconsistentContext();
        }

        return $payload;
    }

    private function nullableInt(Model $target, string $attribute): ?int
    {
        $value = $target->getAttribute($attribute);

        return $value === null ? null : (int) $value;
    }
}
