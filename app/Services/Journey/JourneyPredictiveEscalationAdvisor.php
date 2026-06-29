<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyRiskPrediction;
use App\Enums\JourneyRiskLevel;

/**
 * Phase 9.10 — ADVISORY ONLY. Recommends a coordination action from a prediction but
 * NEVER assigns, escalates or notifies. Auto-action is gated by
 * `journey.predictive_escalation.enabled` (false by default) and is not implemented in
 * this phase — every recommendation requires a manual decision.
 */
class JourneyPredictiveEscalationAdvisor
{
    /**
     * @return array{recommendation:string,reason:string,auto_enabled:bool,manual_action_required:bool}
     */
    public function adviseFor(JourneyRiskPrediction $prediction): array
    {
        $recommendation = match ($prediction->riskLevel) {
            JourneyRiskLevel::CRITICAL => 'pre_assign',
            JourneyRiskLevel::HIGH => 'notify_supervisor',
            JourneyRiskLevel::MEDIUM => 'prioritize',
            default => 'watch',
        };

        return [
            'recommendation' => $recommendation,
            'reason' => $prediction->riskReason,
            // Advisory regardless of config — this service performs no actions.
            'auto_enabled' => (bool) config('journey.predictive_escalation.enabled', false),
            'manual_action_required' => true,
        ];
    }
}
