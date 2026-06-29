<?php

namespace App\Console\Commands;

use App\Enums\JourneyRiskLevel;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Services\ActivityLogService;
use App\Services\Journey\JourneyHandoffNotificationService;
use App\Services\Journey\JourneyHandoffWorklistService;
use App\Services\Journey\JourneyPredictionService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Phase 9.9 — scan active (near-breach+) handoffs, score breach risk and report the
 * high/critical set. Notifications are OFF unless both `prediction_alerts.enabled`
 * and `--notify` are set; when on they reuse Phase 9.7 routing + dedupe (no spam,
 * no patient identifiers). Dry-run mutates nothing.
 */
class JourneyPredictionsCheckCommand extends Command
{
    protected $signature = 'journey:predictions:check
        {--dry-run : Report only}
        {--limit=500 : Max handoffs scanned}
        {--department= : Limit to a destination department id}
        {--risk=high : Minimum risk level counted (low|medium|high|critical)}
        {--notify : Send alerts (also requires prediction_alerts.enabled)}';

    protected $description = 'Score active handoffs for breach risk and (optionally) alert on high/critical risk.';

    public function handle(
        JourneyPredictionService $predictions,
        JourneyHandoffWorklistService $worklist,
        JourneyHandoffNotificationService $notifications,
        ActivityLogService $activity,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) ($this->option('limit') ?: 500));
        $departmentId = $this->option('department') ? (int) $this->option('department') : null;
        $minLevel = JourneyRiskLevel::tryFrom((string) $this->option('risk')) ?? JourneyRiskLevel::HIGH;
        $alertsOn = ! $dryRun && (bool) $this->option('notify') && (bool) config('journey.prediction_alerts.enabled', false);
        $runId = (string) Str::uuid();

        // Hospital-wide near-breach+ handoffs (bounded); index by visit for lookup.
        $handoffs = $worklist->unassignedBreachedHandoffs($departmentId, null, $limit);
        $byVisit = collect($handoffs)->keyBy(fn ($h) => $h->visitId);
        $scored = $predictions->predictForHandoffs($handoffs);

        $checked = $scored->count();
        $high = $scored->filter(fn ($p) => $p->riskLevel === JourneyRiskLevel::HIGH)->count();
        $critical = $scored->filter(fn ($p) => $p->riskLevel === JourneyRiskLevel::CRITICAL)->count();
        $notified = 0;

        if ($alertsOn) {
            foreach ($scored as $prediction) {
                $send = ($prediction->riskLevel === JourneyRiskLevel::CRITICAL && config('journey.prediction_alerts.notify_critical_risk', true))
                    || ($prediction->riskLevel === JourneyRiskLevel::HIGH && config('journey.prediction_alerts.notify_high_risk', false));
                if (! $send || $prediction->riskLevel->priorityRank() < $minLevel->priorityRank()) {
                    continue;
                }
                $handoff = $byVisit->get($prediction->visitId);
                if ($handoff && $notifications->notifyCriticalUnassigned($handoff) > 0) {
                    $notified++;
                }
            }
        }

        if (! $dryRun) {
            $activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_PREDICTION_CHECK_RUN', [
                'severity' => LogSeverity::INFO,
                'command_run_id' => $runId,
                'risk_filter' => $minLevel->value,
                'department_id' => $departmentId,
                'limit' => $limit,
                'dry_run' => false,
                'checked_count' => $checked,
                'high_risk_count' => $high,
                'critical_risk_count' => $critical,
                'notifications_sent' => $notified,
            ], null, 'Journey prediction check');
        }

        $this->line(($dryRun ? '[dry-run] ' : '').sprintf(
            'Checked: %d  High risk: %d  Critical risk: %d  Notifications: %d',
            $checked, $high, $critical, $notified
        ));

        return self::SUCCESS;
    }
}
