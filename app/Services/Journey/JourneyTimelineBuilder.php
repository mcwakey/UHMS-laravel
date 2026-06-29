<?php

namespace App\Services\Journey;

use App\Enums\PatientJourneyStage;
use App\Models\Visit;

/**
 * Builds the ordered, renderable journey timeline for a visit. Each relevant stage
 * is marked completed / active / waiting / skipped, with the minutes spent in it.
 * Reuses PatientJourneyService (snapshot) + JourneyDelayService (durations).
 */
class JourneyTimelineBuilder
{
    public function __construct(
        private PatientJourneyService $journey,
        private JourneyDelayService $delays,
    ) {}

    /**
     * @param  array<string,mixed>|null  $snapshot  reuse a prebuilt snapshot
     * @return list<array{stage:PatientJourneyStage,label:string,icon:string,status:string,minutes:?int}>
     */
    public function build(Visit $visit, ?array $snapshot = null): array
    {
        $snapshot ??= $this->journey->snapshot($visit);
        $current = $snapshot['current_stage'];
        $touched = array_map(fn (PatientJourneyStage $s) => $s->value, $snapshot['touched_stages']);
        $durations = $this->delays->durations($visit)['per_stage'];

        $rows = [];
        foreach ($snapshot['relevant_stages'] as $stage) {
            $rows[] = [
                'stage' => $stage,
                'label' => $stage->translatedLabel(),
                'icon' => $stage->icon(),
                'status' => $this->stageStatus($stage, $current, $touched, (bool) $snapshot['is_completed']),
                'minutes' => $durations[$stage->value] ?? null,
            ];
        }

        return $rows;
    }

    private function stageStatus(PatientJourneyStage $stage, ?PatientJourneyStage $current, array $touched, bool $isCompleted): string
    {
        if ($current === null) {
            return 'waiting';
        }
        if ($stage === $current) {
            return $isCompleted ? 'completed' : 'active';
        }
        if ($stage->order() < $current->order()) {
            return in_array($stage->value, $touched, true) ? 'completed' : 'skipped';
        }

        return 'waiting';
    }
}
