<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\LogRetentionOverride;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class LogsCleanupCommand extends Command
{
    protected $signature = 'logs:cleanup
        {--days= : Override the default retention window in days}
        {--dry-run : Report what would be deleted without deleting}';

    protected $description = 'Prune old activity log rows, honouring per-module retention overrides.';

    public function handle(ActivityLogService $logger): int
    {
        $defaultDays = (int) ($this->option('days') ?? config('activitylog.delete_records_older_than_days', 365));
        $dryRun = (bool) $this->option('dry-run');
        $overrides = LogRetentionOverride::pluck('retention_days', 'module')->all();

        $totalDeleted = 0;
        $perModule = [];

        $modules = collect(LogModule::cases())->map->value->all();
        $modules[] = '__default__'; // catch-all for legacy log_name values not in enum

        foreach ($modules as $module) {
            $days = $overrides[$module] ?? $defaultDays;
            $cutoff = now()->subDays($days);

            $query = Activity::query()->where('created_at', '<', $cutoff);
            if ($module === '__default__') {
                $query->whereNotIn('log_name', collect(LogModule::cases())->map->value->all());
            } else {
                $query->where('log_name', $module);
            }

            $count = (clone $query)->count();
            if ($count === 0) {
                continue;
            }

            $this->line(sprintf('%-30s %5d rows (retention %d days)', $module, $count, $days));
            $perModule[$module] = $count;
            $totalDeleted += $count;

            if (! $dryRun) {
                $query->delete();
            }
        }

        $this->info(($dryRun ? 'Would delete ' : 'Deleted ') . $totalDeleted . ' activity rows.');

        if (! $dryRun && $totalDeleted > 0) {
            $logger->log(LogModule::SYSTEM, 'RETENTION_PURGED', [
                'severity' => LogSeverity::NOTICE,
                'metadata' => [
                    'deleted_count' => $totalDeleted,
                    'per_module' => $perModule,
                    'default_days' => $defaultDays,
                ],
            ], null, 'Activity log retention purge');
        }

        return self::SUCCESS;
    }
}
