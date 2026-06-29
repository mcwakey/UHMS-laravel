<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Services\ActivityLogService;
use App\Services\Journey\JourneyAnalyticsSnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Phase 9.8 — build aggregate journey flow snapshots. Idempotent (upsert), bounded,
 * dry-run safe. Defaults to today; supports a date or a date range.
 */
class JourneyAnalyticsSnapshotCommand extends Command
{
    protected $signature = 'journey:analytics:snapshot
        {--date= : Single date (default today)}
        {--from= : Range start (with --to)}
        {--to= : Range end}
        {--granularity=day : day|hour}
        {--department= : Reserved (snapshots are type-level)}
        {--cause= : Limit to a delay cause}
        {--dry-run : Report only; persist nothing}
        {--limit= : Cap rows processed}';

    protected $description = 'Build aggregate journey flow analytics snapshots from live handoff + assignment data.';

    public function handle(JourneyAnalyticsSnapshotService $service, ActivityLogService $activity): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $granularity = $this->option('granularity') ?: 'day';
        $runId = (string) Str::uuid();

        try {
            [$from, $to] = $this->resolveRange();
        } catch (\Throwable $e) {
            $this->error('Invalid date input.');

            return self::FAILURE;
        }

        $rows = $service->snapshotForRange($from, $to, $granularity);

        if ($cause = $this->option('cause')) {
            $rows = array_values(array_filter($rows, fn ($row) => $row['cause'] === $cause));
        }
        if ($limit = (int) $this->option('limit')) {
            $rows = array_slice($rows, 0, $limit);
        }

        $generated = count($rows);
        $upserted = $dryRun ? 0 : $service->upsertSnapshot($rows);
        $departments = count(array_unique(array_merge(
            array_column($rows, 'from_department_type'),
            array_column($rows, 'to_department_type'),
        )));
        $causes = count(array_unique(array_column($rows, 'cause')));

        if (! $dryRun) {
            $activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_ANALYTICS_SNAPSHOT_RUN', [
                'severity' => LogSeverity::INFO,
                'command_run_id' => $runId,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
                'granularity' => $granularity,
                'rows_generated' => $generated,
                'rows_upserted' => $upserted,
                'dry_run' => false,
            ], null, 'Journey analytics snapshot');
        }

        $this->line(($dryRun ? '[dry-run] ' : '').sprintf(
            'Range: %s → %s  Granularity: %s  Rows generated: %d  Rows upserted: %d  Departments: %d  Causes: %d',
            $from->toDateString(), $to->toDateString(), $granularity, $generated, $upserted, $departments, $causes
        ));

        return self::SUCCESS;
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function resolveRange(): array
    {
        if ($this->option('from') && $this->option('to')) {
            return [Carbon::parse($this->option('from'))->startOfDay(), Carbon::parse($this->option('to'))->startOfDay()];
        }
        if ($this->option('date')) {
            $date = Carbon::parse($this->option('date'))->startOfDay();

            return [$date, $date->copy()];
        }
        $today = Carbon::today();

        return [$today, $today->copy()];
    }
}
