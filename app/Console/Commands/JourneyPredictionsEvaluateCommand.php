<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Services\ActivityLogService;
use App\Services\Journey\JourneyPredictionAccuracyService;
use App\Services\Journey\JourneyPredictionEvaluationService;
use App\Services\Journey\JourneyPredictionOutcomeService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Phase 9.10 — capture predictions and/or evaluate them against actual outcomes.
 * Idempotent, bounded, dry-run safe. No notifications. Audited.
 */
class JourneyPredictionsEvaluateCommand extends Command
{
    protected $signature = 'journey:predictions:evaluate
        {--capture : Capture current predictions}
        {--evaluate : Evaluate pending captures}
        {--from= : Evaluation range start}
        {--to= : Evaluation range end}
        {--department= : Limit to a department id}
        {--cause= : Limit to a delay cause}
        {--risk= : Limit captures to a risk level}
        {--limit=500 : Max rows processed}
        {--dry-run : Report only}';

    protected $description = 'Capture journey breach predictions and evaluate them against actual outcomes.';

    public function handle(
        JourneyPredictionOutcomeService $capture,
        JourneyPredictionEvaluationService $evaluation,
        JourneyPredictionAccuracyService $accuracy,
        ActivityLogService $activity,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $doCapture = (bool) $this->option('capture');
        $doEvaluate = (bool) $this->option('evaluate');
        if (! $doCapture && ! $doEvaluate) {
            $doCapture = $doEvaluate = true; // default: both
        }

        $filters = [
            'limit' => max(1, (int) ($this->option('limit') ?: 500)),
            'department' => $this->option('department') ? (int) $this->option('department') : null,
            'cause' => $this->option('cause') ?: null,
            'dry_run' => $dryRun,
        ];
        $runId = (string) Str::uuid();
        $captured = $scanned = $checked = $evaluated = 0;

        if ($doCapture) {
            $result = $capture->captureForActiveHandoffs($filters);
            $captured = $result['captured'];
            $scanned = $result['scanned'];
        }
        if ($doEvaluate) {
            $result = $evaluation->evaluatePending($filters);
            $checked = $result['checked'];
            $evaluated = $result['evaluated'];
        }

        $summary = $accuracy->accuracySummary(['_ttl' => 0]);

        if (! $dryRun) {
            $activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_PREDICTION_EVALUATE_RUN', [
                'severity' => LogSeverity::INFO,
                'command_run_id' => $runId,
                'date_from' => $this->option('from'),
                'date_to' => $this->option('to'),
                'filters' => array_filter(['department' => $filters['department'], 'cause' => $filters['cause']]),
                'captured_count' => $captured,
                'evaluated_count' => $evaluated,
                'dry_run' => false,
                'precision' => $summary['precision'],
                'recall' => $summary['recall'],
                'false_alarm_rate' => $summary['false_alarm_rate'],
            ], null, 'Journey prediction capture/evaluation');
        }

        $this->line(($dryRun ? '[dry-run] ' : '').sprintf(
            'Captured: %d (scanned %d)  Evaluated: %d (checked %d)  Precision: %s%%  Recall: %s%%',
            $captured, $scanned, $evaluated, $checked, $summary['precision'], $summary['recall']
        ));

        return self::SUCCESS;
    }
}
