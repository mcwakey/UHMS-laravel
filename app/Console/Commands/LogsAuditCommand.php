<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Coverage guardrail for the audit trail. Scans controllers for mutating
 * actions (store/update/destroy/approve/dispense/administer/…) and flags those
 * whose class never calls ActivityLogService — i.e. likely-unlogged actions.
 *
 * It is a heuristic, not a proof: a controller may log via an observer or a
 * service it delegates to. Treat findings as "review these", not "broken".
 *
 *   php artisan logs:audit
 *   php artisan logs:audit --json
 *   php artisan logs:audit --module=patients
 *   php artisan logs:audit --fail
 */
class LogsAuditCommand extends Command
{
    protected $signature = 'logs:audit
        {--json : Write storage/reports/logs-audit-report.json}
        {--module= : Limit to controllers whose path contains this string}
        {--fail : Exit non-zero if any controller has unlogged mutating actions}';

    protected $description = 'Audit the codebase for mutating actions that are likely missing activity logging.';

    /** Method names that represent a state-changing action worth logging. */
    private const MUTATION_METHODS = [
        'store', 'update', 'destroy', 'delete', 'approve', 'reject', 'submit',
        'verify', 'pay', 'dispense', 'administer', 'transfer', 'merge', 'cancel',
        'reverse', 'refund', 'issue', 'record', 'complete', 'assign', 'discharge',
        'admit', 'override', 'waive', 'release', 'restore', 'toggle',
    ];

    /** Signs that a class participates in logging (directly or via a service). */
    private const LOGGING_MARKERS = [
        'ActivityLogService', 'activityLog', '->log(', 'logCreated', 'logUpdated',
        'logDeleted', 'logCorrection', 'logOverride', 'logSecurity', 'activity(',
        'logPatientAction', 'logVisitAction', 'logFinancialAction', 'logStockAction',
        'pathway->record', 'PathwayService', // workflow services that log+pathway
    ];

    public function handle(): int
    {
        $module = $this->option('module');
        $root = app_path('Http/Controllers');
        $findings = [];

        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            $rel = 'app/Http/Controllers' . explode('Http/Controllers', $path)[1];
            if ($module && stripos($rel, $module) === false) {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());

            // Mutating public methods declared in this controller.
            $mutations = [];
            if (preg_match_all('/public function (\w+)\s*\(/', $code, $m)) {
                foreach ($m[1] as $method) {
                    if (in_array(strtolower($method), self::MUTATION_METHODS, true)) {
                        $mutations[] = $method;
                    }
                }
            }
            if ($mutations === []) {
                continue;
            }

            $logs = false;
            foreach (self::LOGGING_MARKERS as $marker) {
                if (str_contains($code, $marker)) {
                    $logs = true;
                    break;
                }
            }

            if (! $logs) {
                $findings[] = [
                    'controller' => $rel,
                    'unlogged_actions' => array_values(array_unique($mutations)),
                ];
            }
        }

        usort($findings, fn ($a, $b) => strcmp($a['controller'], $b['controller']));

        // ── Output ───────────────────────────────────────────────────
        $this->newLine();
        $this->line('<options=bold>UHMS Logging Coverage Audit</>');
        $this->line('===========================');
        $this->line(sprintf('Controllers with likely-unlogged mutating actions: %d', count($findings)));
        $this->newLine();
        foreach (array_slice($findings, 0, 80) as $f) {
            $this->warn('  ' . $f['controller']);
            $this->line('      ' . implode(', ', $f['unlogged_actions']));
        }
        if (count($findings) > 80) {
            $this->line(sprintf('  … and %d more', count($findings) - 80));
        }
        $this->newLine();
        $this->line('Heuristic only — a controller may log via an observer or delegated service.');

        if ($this->option('json')) {
            $dir = storage_path('reports');
            @mkdir($dir, 0755, true);
            file_put_contents(
                $dir . '/logs-audit-report.json',
                json_encode([
                    'generated_at' => now()->toIso8601String(),
                    'controllers_flagged' => count($findings),
                    'findings' => $findings,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->info('JSON report: storage/reports/logs-audit-report.json');
        }

        if ($this->option('fail') && $findings !== []) {
            $this->error(sprintf('%d controller(s) have unlogged mutating actions.', count($findings)));
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
