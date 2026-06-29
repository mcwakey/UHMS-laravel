<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Data\Journey\JourneyRiskPrediction;
use App\Enums\JourneyRiskLevel;
use App\Models\JourneyHandoffAssignment;

/**
 * Phase 9.9 — explainable, deterministic breach-risk scoring (no ML). Combines SLA
 * proximity, historical breach rate, assignment state, department pressure and
 * escalation into a 0–100 score with a human-readable reason + confidence.
 */
class JourneyRiskScoringService
{
    public function __construct(private JourneyEtaService $eta) {}

    public function score(JourneyHandoff $handoff, array $baseline, float $pressure = 0.0): JourneyRiskPrediction
    {
        $weights = (array) config('journey.prediction.risk_weights', []);
        $elapsed = $handoff->elapsedMinutes;
        $sla = max(1, $handoff->slaMinutes);
        $ratio = $elapsed / $sla;
        $breachRate = (float) ($baseline['breach_rate'] ?? 0);

        $assignmentFactor = match ($handoff->assignmentStatus) {
            'acknowledged' => 0.2,
            'assigned' => 0.55,
            default => 1.0, // unassigned (highest risk)
        };
        $escalationFactor = match ($handoff->escalationLevel) {
            'critical' => 1.0,
            'supervisor' => 0.6,
            'warning' => 0.3,
            default => 0.0,
        };

        $score = (int) round(min(100,
            min(1.0, $ratio) * ($weights['elapsed_ratio'] ?? 40)
            + $breachRate * ($weights['historical_breach_rate'] ?? 25)
            + $assignmentFactor * ($weights['current_assignment_state'] ?? 15)
            + min(1.0, $pressure) * ($weights['department_pressure'] ?? 10)
            + $escalationFactor * ($weights['escalation_state'] ?? 10)
        ));

        $level = JourneyRiskLevel::fromScore($score);
        // Already-breached SLA cannot be "low/medium" risk.
        if ($handoff->slaStatus === 'critical_breach') {
            $level = JourneyRiskLevel::CRITICAL;
        } elseif ($handoff->slaStatus === 'breached' && $level->priorityRank() < JourneyRiskLevel::HIGH->priorityRank()) {
            $level = JourneyRiskLevel::HIGH;
        }

        $eta = $this->eta->estimate($handoff, $baseline);

        return new JourneyRiskPrediction(
            visitId: $handoff->visitId,
            handoffIdentity: JourneyHandoffAssignment::buildIdentityKey(
                $handoff->visitId, $handoff->cause, $handoff->fromDepartmentId, $handoff->toDepartmentId, $handoff->toDepartmentType
            ),
            stage: $handoff->stage,
            cause: $handoff->cause,
            fromDepartmentId: $handoff->fromDepartmentId,
            fromDepartmentType: $handoff->fromDepartmentType,
            toDepartmentId: $handoff->toDepartmentId,
            toDepartmentType: $handoff->toDepartmentType,
            currentElapsedMinutes: $elapsed,
            slaMinutes: $handoff->slaMinutes,
            minutesToBreach: $handoff->minutesToBreach,
            estimatedRemainingMinutes: $eta['estimated_remaining_minutes'],
            estimatedResolutionMinutes: $eta['estimated_resolution_minutes'],
            riskScore: $score,
            riskLevel: $level,
            riskReason: $this->reason($ratio, $breachRate, $handoff, $eta['has_history']),
            confidence: $this->confidence((int) ($baseline['sample'] ?? 0)),
            recommendedPriority: $level->recommendedPriority(),
        );
    }

    private function confidence(int $sample): string
    {
        $low = (int) config('journey.prediction.confidence.low_sample_threshold', 5);
        $medium = (int) config('journey.prediction.confidence.medium_sample_threshold', 20);

        return match (true) {
            $sample < $low => 'low',
            $sample < $medium => 'medium',
            default => 'high',
        };
    }

    private function reason(float $ratio, float $breachRate, JourneyHandoff $handoff, bool $hasHistory): string
    {
        $pct = (int) round($ratio * 100);
        $rate = (int) round($breachRate * 100);
        $unassigned = $handoff->isUnassigned();

        return match (true) {
            $handoff->slaStatus === 'critical_breach' => __('journey.risk.reason.critical_breach'),
            $handoff->slaStatus === 'breached' && $unassigned => __('journey.risk.reason.unassigned_breached'),
            $ratio >= 0.8 && $breachRate >= 0.5 && $hasHistory => __('journey.risk.reason.near_sla_high_breach', ['pct' => $pct, 'rate' => $rate]),
            $unassigned && $ratio >= 0.6 => __('journey.risk.reason.unassigned_nearing', ['pct' => $pct]),
            in_array($handoff->escalationLevel, ['supervisor', 'critical'], true) => __('journey.risk.reason.escalated'),
            ! $hasHistory => __('journey.risk.reason.sla_only', ['pct' => $pct]),
            $breachRate >= 0.5 => __('journey.risk.reason.slow_path', ['rate' => $rate]),
            default => __('journey.risk.reason.low', ['pct' => $pct]),
        };
    }
}
