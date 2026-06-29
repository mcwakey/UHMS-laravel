<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;

/**
 * Phase 9.10 — ETA distribution boundary. The average comes from baselines; median /
 * p75 are DEFERRED because the aggregate snapshots store totals, not per-handoff
 * distributions, and percentiles aren't cheap to fake. Returning null is honest. A
 * future per-handoff (privacy-safe, bucketed) store could fill these in.
 */
class JourneyEtaDistributionService
{
    public function __construct(private JourneyPredictionBaselineService $baselines) {}

    /** @return array{average_eta:int,median_eta:?int,p75_eta:?int,sample_size:int} */
    public function distributionFor(JourneyHandoff $handoff): array
    {
        $baseline = $this->baselines->baselineFor($handoff);

        return [
            'average_eta' => (int) (! empty($baseline['avg_resolve']) ? $baseline['avg_resolve'] : $baseline['avg_wait']),
            'median_eta' => null, // deferred — snapshots hold totals, not distributions
            'p75_eta' => null,    // deferred
            'sample_size' => (int) ($baseline['sample'] ?? 0),
        ];
    }
}
