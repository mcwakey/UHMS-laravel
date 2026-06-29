<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyRiskPrediction;
use App\Models\JourneyPredictionOutcome;
use Illuminate\Support\Carbon;

/**
 * Phase 9.10 — captures current breach-risk predictions for active handoffs so they
 * can be evaluated against actual outcomes later. PRIVACY: stores a HASHED identity
 * (no patient name, no visit number, no clinical detail); `visit_id` is an internal
 * reference for evaluation only. Idempotent per handoff/date/hour.
 */
class JourneyPredictionOutcomeService
{
    public function __construct(
        private JourneyHandoffWorklistService $worklist,
        private JourneyPredictionService $predictions,
    ) {}

    /** @return array{captured:int,scanned:int} */
    public function captureForActiveHandoffs(array $filters = []): array
    {
        if (! config('journey.prediction.enabled', true)) {
            return ['captured' => 0, 'scanned' => 0];
        }

        $limit = (int) ($filters['limit'] ?? 500);
        $dryRun = (bool) ($filters['dry_run'] ?? false);

        $handoffs = $this->worklist->unassignedBreachedHandoffs(
            $filters['department'] ?? null,
            $filters['cause'] ?? null,
            $limit,
        );
        $predictions = $this->predictions->predictForHandoffs($handoffs);

        $captured = 0;
        foreach ($predictions as $prediction) {
            if (! $dryRun && $this->capturePrediction($prediction)) {
                $captured++;
            }
        }

        return ['captured' => $dryRun ? 0 : $captured, 'scanned' => $predictions->count()];
    }

    public function capturePrediction(JourneyRiskPrediction $prediction): ?JourneyPredictionOutcome
    {
        return JourneyPredictionOutcome::updateOrCreate(
            [
                'handoff_identity_hash' => $this->identityHash($prediction),
                'prediction_date' => Carbon::today()->toDateString(),
                'prediction_hour' => (int) Carbon::now()->format('H'),
            ],
            [
                'visit_id' => $prediction->visitId,
                'from_department_id' => $prediction->fromDepartmentId,
                'from_department_type' => $prediction->fromDepartmentType,
                'to_department_id' => $prediction->toDepartmentId,
                'to_department_type' => $prediction->toDepartmentType,
                'cause' => $prediction->cause->value,
                'predicted_risk_level' => $prediction->riskLevel->value,
                'predicted_risk_score' => $prediction->riskScore,
                'predicted_minutes_to_breach' => $prediction->minutesToBreach,
                'predicted_remaining_minutes' => $prediction->estimatedRemainingMinutes,
                'confidence' => $prediction->confidence,
            ],
        );
    }

    /** Stable one-way hash of the handoff identity — never reveals patient data. */
    public function identityHash(JourneyRiskPrediction $prediction): string
    {
        return hash('sha256', $prediction->handoffIdentity);
    }
}
