<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Data\Journey\JourneyRiskPrediction;
use App\Enums\JourneyRiskLevel;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Phase 9.9 — orchestrates breach-risk predictions for active handoffs. Capability-
 * aware (reuses the worklist), bounded, and batches baselines (one cached snapshot
 * read for the whole set). Predictions are explainable estimates, never facts.
 */
class JourneyPredictionService
{
    public function __construct(
        private JourneyHandoffWorklistService $worklist,
        private JourneyPredictionBaselineService $baselines,
        private JourneyRiskScoringService $scoring,
    ) {}

    public function predictForHandoff(JourneyHandoff $handoff, ?User $user = null): JourneyRiskPrediction
    {
        return $this->scoring->score($handoff, $this->baselines->baselineFor($handoff));
    }

    /**
     * Score an already-resolved set of handoffs (no re-resolution). One cached
     * baseline read for the whole batch.
     *
     * @param  list<JourneyHandoff>  $handoffs
     * @return Collection<int, JourneyRiskPrediction> keyed by visit id
     */
    public function predictForHandoffs(array $handoffs): Collection
    {
        if ($handoffs === [] || ! config('journey.prediction.enabled', true)) {
            return collect();
        }

        $this->baselines->baselineMap(); // warm the cache once
        $pressure = $this->pressureByType($handoffs);

        return collect($handoffs)
            ->map(fn (JourneyHandoff $h) => $this->scoring->score($h, $this->baselines->baselineFor($h), $pressure[$h->toDepartmentType] ?? 0.0))
            ->keyBy(fn (JourneyRiskPrediction $p) => $p->visitId);
    }

    /** @return Collection<int, JourneyRiskPrediction> worst-risk first. */
    public function predictForWorklist(User $user, array $filters = []): Collection
    {
        $predictions = $this->predictForHandoffs($this->worklist->forUser($user, $filters));
        $predictions = $this->applyFilters($predictions, $filters);

        return $predictions->sortByDesc(fn (JourneyRiskPrediction $p) => $p->rank())->values();
    }

    /** @return array<string, mixed> cached predictive summary for the user. */
    public function summaryForUser(User $user): array
    {
        if (! config('journey.prediction.enabled', true)) {
            return $this->emptySummary();
        }

        $ttl = (int) config('journey.prediction.cache.summary_ttl', 60);

        return Cache::remember('journey:prediction:summary:u'.$user->id, $ttl,
            fn () => $this->summarise($this->predictForHandoffs($this->worklist->forUser($user))));
    }

    /** @return array<string, mixed> */
    public function summaryForDepartment(Department $department, User $user): array
    {
        if (! config('journey.prediction.enabled', true)) {
            return $this->emptySummary();
        }

        return $this->summarise($this->predictForHandoffs($this->worklist->owedByDepartment($department, $user)));
    }

    // ------------------------------------------------------------------

    /** @param Collection<int, JourneyRiskPrediction> $predictions */
    private function summarise(Collection $predictions): array
    {
        if ($predictions->isEmpty()) {
            return $this->emptySummary();
        }

        $likely = $predictions->filter(fn ($p) => $p->isLikelyToBreach());
        $causes = [];
        $departments = [];
        foreach ($likely as $p) {
            $causes[$p->cause->value] = ($causes[$p->cause->value] ?? 0) + 1;
            if ($p->toDepartmentType) {
                $departments[$p->toDepartmentType] = ($departments[$p->toDepartmentType] ?? 0) + 1;
            }
        }
        arsort($causes);
        arsort($departments);

        return [
            'likely' => $likely->count(),
            'high' => $predictions->filter(fn ($p) => $p->riskLevel === JourneyRiskLevel::HIGH)->count(),
            'critical' => $predictions->filter(fn ($p) => $p->riskLevel === JourneyRiskLevel::CRITICAL)->count(),
            'avg_remaining' => (int) round($predictions->avg(fn ($p) => $p->estimatedRemainingMinutes ?? 0)),
            'top_cause' => array_key_first($causes),
            'top_department' => array_key_first($departments),
        ];
    }

    /** @param Collection<int, JourneyRiskPrediction> $predictions */
    private function applyFilters(Collection $predictions, array $filters): Collection
    {
        if (! empty($filters['risk_level'])) {
            $predictions = $predictions->filter(fn ($p) => $p->riskLevel->value === $filters['risk_level']);
        }
        if (! empty($filters['likely_to_breach'])) {
            $predictions = $predictions->filter(fn ($p) => $p->isLikelyToBreach());
        }
        if (! empty($filters['confidence'])) {
            $predictions = $predictions->filter(fn ($p) => $p->confidence === $filters['confidence']);
        }

        return $predictions;
    }

    /** @param list<JourneyHandoff> $handoffs */
    private function pressureByType(array $handoffs): array
    {
        $total = [];
        $breached = [];
        foreach ($handoffs as $h) {
            $type = $h->toDepartmentType;
            $total[$type] = ($total[$type] ?? 0) + 1;
            if ($h->isBreached()) {
                $breached[$type] = ($breached[$type] ?? 0) + 1;
            }
        }
        $pressure = [];
        foreach ($total as $type => $count) {
            $pressure[$type] = $count > 0 ? ($breached[$type] ?? 0) / $count : 0.0;
        }

        return $pressure;
    }

    private function emptySummary(): array
    {
        return ['likely' => 0, 'high' => 0, 'critical' => 0, 'avg_remaining' => 0, 'top_cause' => null, 'top_department' => null];
    }
}
