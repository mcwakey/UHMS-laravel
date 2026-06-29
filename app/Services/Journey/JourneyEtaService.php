<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;

/**
 * Phase 9.9 — explainable time estimates for an active handoff. Uses the historical
 * baseline when there's enough sample, otherwise falls back to the SLA threshold.
 * Never overstates precision — outputs are estimates with a translatable note.
 */
class JourneyEtaService
{
    /**
     * @return array{estimated_remaining_minutes:?int,estimated_resolution_minutes:?int,note_key:string,has_history:bool}
     */
    public function estimate(JourneyHandoff $handoff, array $baseline): array
    {
        $elapsed = $handoff->elapsedMinutes;
        $sla = max(1, $handoff->slaMinutes);
        $minSample = (int) config('journey.prediction.minimum_snapshot_count', 5);
        $hasHistory = (int) ($baseline['sample'] ?? 0) >= $minSample;

        // Expected total time in this handoff: historical average wait, else the SLA.
        $expectedTotal = $hasHistory && ! empty($baseline['avg_wait']) ? (int) $baseline['avg_wait'] : $sla;
        $remaining = $expectedTotal - $elapsed; // negative = over expected

        $estimatedRemaining = $hasHistory ? max(0, $remaining) : max(0, $sla - $elapsed);
        $resolution = $hasHistory && ! empty($baseline['avg_resolve']) ? (int) $baseline['avg_resolve'] : null;

        $noteKey = match (true) {
            ! $hasHistory => 'sla_estimate',
            $elapsed >= $sla => 'already_breached',
            $remaining <= 0 => 'over_expected',
            default => 'remaining',
        };

        return [
            'estimated_remaining_minutes' => $estimatedRemaining,
            'estimated_resolution_minutes' => $resolution,
            'note_key' => $noteKey,
            'has_history' => $hasHistory,
        ];
    }
}
