<?php

namespace App\Services\Journey;

use App\Models\JourneyHandoffAssignment;
use App\Models\JourneyPredictionOutcome;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Phase 9.10 — evaluates captured predictions against actual outcomes (≥ delay hours
 * later). Reliable signals only: the persisted assignment lifecycle + the re-derived
 * current handoff. When the outcome genuinely cannot be determined, the row is left
 * unevaluated. Idempotent, bounded, no patient-level leakage.
 */
class JourneyPredictionEvaluationService
{
    public function __construct(
        private JourneyHandoffAssignmentService $assignments,
        private JourneySlaService $sla,
    ) {}

    /** @return array{checked:int,evaluated:int} */
    public function evaluatePending(array $filters = []): array
    {
        $delay = (int) config('journey.prediction_accuracy.evaluation_delay_hours', 24);
        $limit = (int) ($filters['limit'] ?? 500);
        $dryRun = (bool) ($filters['dry_run'] ?? false);

        $rows = JourneyPredictionOutcome::query()
            ->unevaluated()
            ->where('created_at', '<=', Carbon::now()->subHours($delay))
            ->when($filters['department'] ?? null, fn ($q, $d) => $q->where(fn ($x) => $x->where('to_department_id', (int) $d)->orWhere('from_department_id', (int) $d)))
            ->when($filters['cause'] ?? null, fn ($q, $c) => $q->where('cause', $c))
            ->limit($limit)
            ->get();

        $checked = 0;
        $evaluated = 0;
        foreach ($rows as $outcome) {
            $checked++;
            if ($dryRun) {
                continue;
            }
            if ($this->evaluateOutcome($outcome)->evaluated_at !== null) {
                $evaluated++;
            }
        }

        return ['checked' => $checked, 'evaluated' => $evaluated];
    }

    public function evaluateOutcome(JourneyPredictionOutcome $outcome): JourneyPredictionOutcome
    {
        if ($outcome->visit_id === null) {
            return $outcome; // nothing to evaluate against
        }

        $slaMinutes = max(1, $this->sla->slaFor($outcome->cause));
        $capturedAt = $outcome->created_at;
        $predictedBreachAt = $outcome->predicted_minutes_to_breach !== null
            ? $capturedAt->copy()->addMinutes($outcome->predicted_minutes_to_breach)
            : null;

        // Match the persisted assignment(s) for this exact handoff identity.
        $matching = JourneyHandoffAssignment::where('visit_id', $outcome->visit_id)->get()
            ->filter(fn ($a) => hash('sha256', $a->identityKey()) === $outcome->handoff_identity_hash);
        $resolved = $matching->first(fn ($a) => in_array($a->status, [JourneyHandoffAssignment::STATUS_RESOLVED, JourneyHandoffAssignment::STATUS_DISMISSED], true));

        // Re-derive the current handoff to see if it is still active.
        $current = $this->assignments->activeHandoffFor($outcome->visit_id);
        $stillActive = $current !== null
            && hash('sha256', JourneyHandoffAssignment::buildIdentityKey(
                $current->visitId, $current->cause, $current->fromDepartmentId, $current->toDepartmentId, $current->toDepartmentType
            )) === $outcome->handoff_identity_hash;

        $visitExists = Visit::whereKey($outcome->visit_id)->exists();
        $actualResolved = $resolved !== null || (! $stillActive && $visitExists);

        // Did it actually breach?
        $actualBreached = match (true) {
            $outcome->predicted_minutes_to_breach !== null && $outcome->predicted_minutes_to_breach <= 0 => true, // already breached at capture
            $resolved !== null && $resolved->resolved_at !== null && $predictedBreachAt !== null => $resolved->resolved_at->gt($predictedBreachAt),
            $stillActive && $current !== null => $current->elapsedMinutes > $slaMinutes,
            default => null, // cannot determine
        };

        $actualCritical = $actualBreached === true
            && $stillActive && $current !== null
            && $current->elapsedMinutes >= 2 * $slaMinutes;

        $ttAck = $resolved && $resolved->assigned_at && $resolved->acknowledged_at
            ? (int) round($resolved->assigned_at->diffInMinutes($resolved->acknowledged_at)) : null;
        $ttResolve = $resolved && $resolved->assigned_at && $resolved->resolved_at
            ? (int) round($resolved->assigned_at->diffInMinutes($resolved->resolved_at)) : null;
        $actualMinutesToResolve = $resolved && $resolved->resolved_at && $capturedAt
            ? (int) round($capturedAt->diffInMinutes($resolved->resolved_at)) : null;

        // Only mark evaluated when we determined something meaningful.
        if ($actualBreached !== null || $actualResolved) {
            $outcome->update([
                'actual_breached' => $actualBreached,
                'actual_critical_breached' => $actualBreached === true ? $actualCritical : ($actualBreached === false ? false : null),
                'actual_resolved' => $actualResolved,
                'actual_minutes_to_resolve' => $actualMinutesToResolve,
                'actual_time_to_acknowledge' => $ttAck,
                'actual_time_to_resolve' => $ttResolve,
                'evaluated_at' => Carbon::now(),
            ]);
        }

        return $outcome;
    }
}
