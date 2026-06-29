<?php

namespace App\Services\Journey;

use App\Enums\PatientJourneyStage;
use App\Enums\VisitStatus;
use App\Models\Visit;
use Illuminate\Support\Carbon;

/**
 * Stage durations and delay classification — computed from the visit's existing
 * created_at + timestamped status logs against configurable thresholds
 * (`config/journey.php`). Statuses: normal / delayed / critical.
 */
class JourneyDelayService
{
    public function __construct(private PatientJourneyService $journey) {}

    /**
     * Elapsed time in the current stage and its delay classification.
     *
     * @param  array<string,mixed>|null  $snapshot  reuse a prebuilt snapshot to avoid recompute
     * @return array{minutes:int,status:string,blocked:bool,stage:?PatientJourneyStage,threshold:array}
     */
    public function currentDelay(Visit $visit, ?array $snapshot = null): array
    {
        $snapshot ??= $this->journey->snapshot($visit);
        $stage = $snapshot['current_stage'];
        $since = $snapshot['entered_current_at'];

        if ($stage === null || $since === null || $snapshot['is_terminal'] || $snapshot['is_completed']) {
            return ['minutes' => 0, 'status' => 'normal', 'blocked' => false, 'stage' => $stage, 'threshold' => $this->thresholdFor($stage)];
        }

        $minutes = (int) round($since->diffInMinutes(now()));
        $threshold = $this->thresholdFor($stage);
        $status = $minutes >= $threshold['critical'] ? 'critical' : ($minutes >= $threshold['delayed'] ? 'delayed' : 'normal');

        return [
            'minutes' => $minutes,
            'status' => $status,
            'blocked' => $status !== 'normal',
            'stage' => $stage,
            'threshold' => $threshold,
        ];
    }

    /** The stage the patient is blocked at (current stage if delayed/critical), else null. */
    public function blockedStage(Visit $visit, ?array $snapshot = null): ?PatientJourneyStage
    {
        $delay = $this->currentDelay($visit, $snapshot);

        return $delay['blocked'] ? $delay['stage'] : null;
    }

    /**
     * Minutes spent in each stage (from the status-log timeline) + total visit duration.
     *
     * @return array{per_stage:array<string,int>,total_minutes:int}
     */
    public function durations(Visit $visit): array
    {
        $logs = ($visit->relationLoaded('statusLogs') ? $visit->statusLogs : $visit->statusLogs()->get())
            ->filter(fn ($log) => $log->timestamp !== null)
            ->values();

        // Reconstruct the timeline: (created_at, initial status) then each transition.
        $createdAt = $visit->created_at ? Carbon::parse($visit->created_at) : null;
        $points = [];
        if ($createdAt) {
            $initial = $logs->first()->from_status ?? ($this->visitStatusValue($visit));
            $points[] = ['at' => $createdAt, 'status' => (string) $initial];
        }
        foreach ($logs as $log) {
            $points[] = ['at' => Carbon::parse($log->timestamp), 'status' => (string) $log->to_status];
        }

        $end = $visit->checked_out_at ? Carbon::parse($visit->checked_out_at) : now();
        $perStage = [];
        $count = count($points);
        for ($i = 0; $i < $count; $i++) {
            $stage = $this->stageFor($points[$i]['status']);
            if ($stage === null) {
                continue;
            }
            $segmentEnd = $points[$i + 1]['at'] ?? $end;
            $perStage[$stage->value] = ($perStage[$stage->value] ?? 0) + (int) round($points[$i]['at']->diffInMinutes($segmentEnd));
        }

        return [
            'per_stage' => $perStage,
            'total_minutes' => $createdAt ? (int) round($createdAt->diffInMinutes($end)) : 0,
        ];
    }

    /** @return array{delayed:int,critical:int} */
    public function thresholdFor(?PatientJourneyStage $stage): array
    {
        if ($stage === null) {
            return config('journey.default_threshold');
        }

        return config('journey.thresholds.'.$stage->value, config('journey.default_threshold'));
    }

    private function stageFor(string $statusValue): ?PatientJourneyStage
    {
        $status = VisitStatus::tryFrom($statusValue);

        return $status ? PatientJourneyStage::fromVisitStatus($status) : null;
    }

    private function visitStatusValue(Visit $visit): string
    {
        $status = $visit->status;

        return $status instanceof VisitStatus ? $status->value : (string) $status;
    }
}
