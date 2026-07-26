<?php

namespace App\Data\Maternity;

use App\Enums\ConsultationMaternityContextType;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 14R.5 — the single typed description of "what maternity record is this,
 * and what does linking to it imply".
 *
 * Produced only by MaternityContextTargetService. Every bridge (Consultation,
 * Emergency, Admission Request, Admission) consumes this instead of re-deriving
 * context types, foreign keys or patient ownership for itself.
 */
final class MaternityContextTargetDescriptor
{
    /**
     * @param  class-string<Model>  $targetClass
     * @param  array<string, mixed>  $foreignKeys  full nullable-FK payload
     * @param  list<string>  $warnings  non-fatal advisories
     */
    public function __construct(
        public readonly ConsultationMaternityContextType $contextType,
        public readonly string $targetClass,
        public readonly int $targetId,
        public readonly int $pregnancyProfileId,
        public readonly int $patientId,
        public readonly ?int $maternityCaseId,
        public readonly ?int $visitId,
        public readonly ?int $admissionId,
        public readonly array $foreignKeys,
        public readonly array $warnings = [],
    ) {}

    /**
     * The FK payload plus context_type, ready to merge into a link row.
     *
     * @return array<string, mixed>
     */
    public function linkPayload(): array
    {
        return ['context_type' => $this->contextType->value] + $this->foreignKeys;
    }

    /**
     * True when this descriptor's patient is the one the caller expects. Newborn
     * and Postnatal descriptors already carry the MOTHER's patient id, which is
     * the O&G bridge convention.
     */
    public function belongsToPatient(?int $patientId): bool
    {
        return $patientId !== null && (int) $patientId === $this->patientId;
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}
