<?php

namespace App\Data\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\JourneyRiskLevel;
use App\Enums\PatientJourneyStage;

/**
 * Immutable, explainable breach-risk prediction for one active handoff (Phase 9.9).
 * Predictions are ESTIMATES, not facts — every one carries a reason + confidence.
 * Primitives + enums only; no Eloquent leaks into Blade.
 */
final class JourneyRiskPrediction
{
    public function __construct(
        public readonly int $visitId,
        public readonly string $handoffIdentity,
        public readonly ?PatientJourneyStage $stage,
        public readonly JourneyDelayCause $cause,
        public readonly ?int $fromDepartmentId,
        public readonly ?string $fromDepartmentType,
        public readonly ?int $toDepartmentId,
        public readonly ?string $toDepartmentType,
        public readonly int $currentElapsedMinutes,
        public readonly int $slaMinutes,
        public readonly ?int $minutesToBreach,
        public readonly ?int $estimatedRemainingMinutes,
        public readonly ?int $estimatedResolutionMinutes,
        public readonly int $riskScore,
        public readonly JourneyRiskLevel $riskLevel,
        public readonly string $riskReason,
        public readonly string $confidence,           // low | medium | high
        public readonly string $recommendedPriority,   // routine | watch | prioritize | urgent
    ) {}

    public function isLikelyToBreach(): bool
    {
        return in_array($this->riskLevel, [JourneyRiskLevel::HIGH, JourneyRiskLevel::CRITICAL], true);
    }

    /** Sort weight: highest risk first, then soonest breach. */
    public function rank(): int
    {
        $minutes = $this->minutesToBreach ?? 0;

        return $this->riskScore * 100000 - min(max($minutes, -99999), 99999);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'visit_id' => $this->visitId,
            'handoff_identity' => $this->handoffIdentity,
            'stage' => $this->stage?->value,
            'cause' => $this->cause->value,
            'from_department_id' => $this->fromDepartmentId,
            'from_department_type' => $this->fromDepartmentType,
            'to_department_id' => $this->toDepartmentId,
            'to_department_type' => $this->toDepartmentType,
            'current_elapsed_minutes' => $this->currentElapsedMinutes,
            'sla_minutes' => $this->slaMinutes,
            'minutes_to_breach' => $this->minutesToBreach,
            'estimated_remaining_minutes' => $this->estimatedRemainingMinutes,
            'estimated_resolution_minutes' => $this->estimatedResolutionMinutes,
            'risk_score' => $this->riskScore,
            'risk_level' => $this->riskLevel->value,
            'risk_reason' => $this->riskReason,
            'confidence' => $this->confidence,
            'recommended_priority' => $this->recommendedPriority,
        ];
    }
}
