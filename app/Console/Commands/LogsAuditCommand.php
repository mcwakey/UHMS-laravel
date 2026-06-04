<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Funnel-aware coverage guardrail for the audit trail.
 *
 * Scans controllers for mutating actions (store/update/destroy/approve/…) and
 * decides whether each reaches the activity log. Because UHMS logs at SERVICE
 * FUNNELS (not in controllers), a naive "does the controller call the logger?"
 * heuristic over-reports massively. This command instead:
 *
 *   1. Builds the TRANSITIVE set of "logging services" — a service logs if it
 *      calls ActivityLogService directly OR delegates to a service that does
 *      (e.g. EmergencyMedicationService → MedicationAdministrationLogService).
 *   2. Classifies every flagged controller action:
 *        SERVICE_FUNNEL_COVERED · NEEDS_REVIEW · KNOWN_BACKLOG ·
 *        INTENTIONALLY_SKIPPED · MISSING_LOG
 *   3. Assigns a severity (CRITICAL…INFO) independent of classification.
 *   4. Compares against a baseline so new findings are visible.
 *
 * Config lives in config/logging_audit.php. It stays ADVISORY by default.
 *
 *   php artisan logs:audit
 *   php artisan logs:audit --json
 *   php artisan logs:audit --module=stock
 *   php artisan logs:audit --fail                  # any flagged (legacy)
 *   php artisan logs:audit --fail --only-real-gaps # only MISSING_LOG
 *   php artisan logs:audit --fail --min-severity=CRITICAL
 *   php artisan logs:audit --fail --strict         # anything not in baseline
 *   php artisan logs:audit --write-baseline        # snapshot accepted findings
 */
class LogsAuditCommand extends Command
{
    protected $signature = 'logs:audit
        {--json : Write storage/reports/logs-audit-report.json}
        {--module= : Limit to controllers whose path contains this string}
        {--fail : Exit non-zero when the (filtered) failing set is non-empty}
        {--only-real-gaps : With --fail, only MISSING_LOG findings count}
        {--min-severity= : With --fail, only findings >= this severity count (CRITICAL|HIGH|MEDIUM|LOW|INFO)}
        {--strict : With --fail, fail on any finding not present in the baseline}
        {--write-baseline : Write the accepted-findings baseline and exit}
        {--scan-path= : Scan this dir (relative to base path) for both controllers and services — for isolated tests}';

    protected $description = 'Funnel-aware audit of mutating actions that may be missing activity logging.';

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
        'pathway->record', 'PathwayService',
        'MedicalRecordEntryLog', 'entryLogs->',
        'timeline->record', 'TimelineService',
    ];

    private const CLASSIFICATIONS = [
        'MISSING_LOG', 'NEEDS_REVIEW', 'KNOWN_BACKLOG',
        'INTENTIONALLY_SKIPPED', 'SERVICE_FUNNEL_COVERED',
    ];

    private const SEVERITY_RANK = ['INFO' => 0, 'LOW' => 1, 'MEDIUM' => 2, 'HIGH' => 3, 'CRITICAL' => 4];

    public function handle(): int
    {
        $config = (array) config('logging_audit', []);
        $scan = $this->option('scan-path');
        $serviceRoots = $scan ? [base_path($scan)] : [app_path('Services'), app_path('Observers')];
        $controllerRoot = $scan ? base_path($scan) : app_path('Http/Controllers');

        $loggingServices = $this->buildLoggingServiceSet($config, $serviceRoots);

        $findings = $this->scanControllers($config, $loggingServices, $controllerRoot);

        if ($this->option('write-baseline')) {
            return $this->writeBaseline($findings, $config);
        }

        $baseline = $this->loadBaseline($config);
        foreach ($findings as &$f) {
            $f['in_baseline'] = isset($baseline[$f['controller']]);
        }
        unset($f);

        $this->render($findings, $loggingServices);

        if ($this->option('json')) {
            $this->writeJson($findings, $loggingServices);
        }

        if ($this->option('fail')) {
            return $this->evaluateFail($findings);
        }

        return self::SUCCESS;
    }

    /* ── Transitive "logging services" closure ──────────────────────── */

    /**
     * A service base-name is "logging" if it contains a logging marker or
     * delegates (transitively) to one that does. Seeded by config funnels.
     *
     * @return array<string,string> base-name => 'direct'|'transitive'|'config'
     */
    private function buildLoggingServiceSet(array $config, array $roots): array
    {
        $refs = [];     // base-name => [referenced service base-names]
        $direct = [];   // base-name => true (logs in its own source)

        foreach ($roots as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            foreach (File::allFiles($dir) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $name = $file->getFilenameWithoutExtension();
                $code = (string) file_get_contents($file->getPathname());

                if ($this->codeHasMarker($code)) {
                    $direct[$name] = true;
                }
                $refs[$name] = $this->referencedServices($code);
            }
        }

        $logs = [];
        foreach (array_keys($direct) as $n) {
            $logs[$n] = 'direct';
        }
        foreach ((array) ($config['covered_service_funnels'] ?? []) as $n) {
            $logs[$n] = $logs[$n] ?? 'config';
        }

        // Fixpoint: propagate "logs" through references.
        do {
            $changed = false;
            foreach ($refs as $name => $deps) {
                if (isset($logs[$name])) {
                    continue;
                }
                foreach ($deps as $dep) {
                    if (isset($logs[$dep])) {
                        $logs[$name] = 'transitive';
                        $changed = true;
                        break;
                    }
                }
            }
        } while ($changed);

        return $logs;
    }

    private function relativePath(string $path): string
    {
        $full = str_replace('\\', '/', $path);
        $base = str_replace('\\', '/', base_path()) . '/';
        return str_starts_with($full, $base) ? substr($full, strlen($base)) : $full;
    }

    private function codeHasMarker(string $code): bool
    {
        foreach (self::LOGGING_MARKERS as $marker) {
            if (str_contains($code, $marker)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,string> referenced App\Services\* base-names */
    private function referencedServices(string $code): array
    {
        if (! preg_match_all('/App\\\\Services\\\\([A-Za-z0-9_\\\\]+)/', $code, $m)) {
            return [];
        }
        $out = [];
        foreach ($m[1] as $fqcnTail) {
            $parts = explode('\\', $fqcnTail);
            $out[] = end($parts);
        }
        return array_values(array_unique($out));
    }

    /* ── Controller scan + classification ───────────────────────────── */

    /** @return array<int,array> findings */
    private function scanControllers(array $config, array $loggingServices, string $root): array
    {
        $module = $this->option('module');
        $findings = [];

        if (! is_dir($root)) {
            return [];
        }

        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $rel = $this->relativePath($file->getPathname());
            if ($module && stripos($rel, $module) === false) {
                continue;
            }

            $code = (string) file_get_contents($file->getPathname());

            $mutations = [];
            if (preg_match_all('/public function (\w+)\s*\(/', $code, $m)) {
                foreach ($m[1] as $method) {
                    if (in_array(strtolower($method), self::MUTATION_METHODS, true)) {
                        $mutations[] = $method;
                    }
                }
            }
            $mutations = array_values(array_unique($mutations));
            if ($mutations === []) {
                continue;
            }

            // Controllers that log inline are covered outright — never flagged.
            if ($this->codeHasMarker($code)) {
                continue;
            }

            $findings[] = $this->classify($rel, $code, $mutations, $config, $loggingServices);
        }

        usort($findings, function ($a, $b) {
            $bySev = self::SEVERITY_RANK[$b['severity']] <=> self::SEVERITY_RANK[$a['severity']];
            return $bySev !== 0 ? $bySev : strcmp($a['controller'], $b['controller']);
        });

        return $findings;
    }

    private function classify(string $rel, string $code, array $actions, array $config, array $loggingServices): array
    {
        $severity = $this->severityFor($actions, $config);
        $services = $this->referencedServices($code);

        $skipCtrl = $this->listed($config['intentionally_skipped_actions'] ?? [], $rel, null);
        $skipMethods = $this->matchedMethods($config['intentionally_skipped_actions'] ?? [], $rel, $actions);
        $backlogCtrl = $this->listed($config['backlog_controllers'] ?? [], $rel, null)
            || $this->listed($config['backlog_actions'] ?? [], $rel, null);
        $backlogMethods = array_merge(
            $this->matchedMethods($config['backlog_controllers'] ?? [], $rel, $actions),
            $this->matchedMethods($config['backlog_actions'] ?? [], $rel, $actions),
        );

        // 1. Intentionally skipped.
        if ($skipCtrl) {
            return $this->finding($rel, $actions, 'INTENTIONALLY_SKIPPED', $severity, 'Excluded by config (intentionally_skipped_actions).', $services);
        }
        $effective = array_values(array_diff($actions, $skipMethods));
        if ($effective === []) {
            return $this->finding($rel, $actions, 'INTENTIONALLY_SKIPPED', $severity, 'All actions excluded by config.', $services);
        }

        // 2. Covered (funnel / observer / verified false-positive).
        $coveredReason = $this->coverageReason($rel, $code, $services, $config, $loggingServices);
        if ($coveredReason !== null) {
            return $this->finding($rel, $actions, 'SERVICE_FUNNEL_COVERED', $severity, $coveredReason, $services);
        }

        // 3. Known backlog (deferred real gap).
        if ($backlogCtrl) {
            return $this->finding($rel, $actions, 'KNOWN_BACKLOG', $severity, 'Deferred lower-priority gap (config backlog).', $services);
        }
        $effective = array_values(array_diff($effective, $backlogMethods));
        if ($effective === []) {
            return $this->finding($rel, $actions, 'KNOWN_BACKLOG', $severity, 'Remaining actions are config backlog.', $services);
        }

        // 4. Delegates to an unverified service.
        if ($services !== []) {
            return $this->finding($rel, $effective, 'NEEDS_REVIEW', $severity,
                'Delegates to ' . implode(', ', $services) . ' — not verified to log. Confirm or wire a funnel.', $services);
        }

        // 5. No funnel anywhere — genuine gap.
        return $this->finding($rel, $effective, 'MISSING_LOG', $severity, 'No logging funnel found in controller or any service it uses.', $services);
    }

    private function coverageReason(string $rel, string $code, array $services, array $config, array $loggingServices): ?string
    {
        $fp = (array) ($config['known_false_positive_controllers'] ?? []);
        if (isset($fp[$rel])) {
            return $fp[$rel];
        }

        $logging = array_values(array_filter($services, fn ($s) => isset($loggingServices[$s])));
        if ($logging !== []) {
            $how = $loggingServices[$logging[0]];
            return sprintf('Delegates to %s (logs %s).', implode(', ', $logging), $how);
        }

        foreach ((array) ($config['covered_observers'] ?? []) as $model => $observer) {
            if (str_contains($code, 'App\\Models\\' . $model)) {
                return sprintf('%s changes are audited by %s.', $model, $observer);
            }
        }

        return null;
    }

    private function severityFor(array $actions, array $config): string
    {
        $tiers = (array) ($config['severity_tiers'] ?? []);
        $best = 'INFO';
        foreach ($actions as $action) {
            foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW'] as $tier) {
                if (in_array(strtolower($action), array_map('strtolower', (array) ($tiers[$tier] ?? [])), true)) {
                    if (self::SEVERITY_RANK[$tier] > self::SEVERITY_RANK[$best]) {
                        $best = $tier;
                    }
                    break;
                }
            }
        }
        return $best;
    }

    private function finding(string $rel, array $actions, string $classification, string $severity, string $reason, array $services): array
    {
        return [
            'controller' => $rel,
            'unlogged_actions' => array_values($actions), // legacy key
            'actions' => array_values($actions),
            'classification' => $classification,
            'severity' => $severity,
            'reason' => $reason,
            'services' => $services,
        ];
    }

    /** Whether $rel (optionally @$method) is listed (entries may be path or path@method). */
    private function listed(array $entries, string $rel, ?string $method): bool
    {
        foreach ($entries as $entry) {
            if ($entry === $rel) {
                return true;
            }
            if ($method !== null && $entry === $rel . '@' . $method) {
                return true;
            }
        }
        return false;
    }

    /** @return array<int,string> methods of $actions named in path@method entries */
    private function matchedMethods(array $entries, string $rel, array $actions): array
    {
        $out = [];
        foreach ($actions as $a) {
            if (in_array($rel . '@' . $a, $entries, true)) {
                $out[] = $a;
            }
        }
        return $out;
    }

    /* ── Baseline ───────────────────────────────────────────────────── */

    private function baselinePath(array $config): string
    {
        return $config['baseline_path'] ?? storage_path('app/logs-audit-baseline.json');
    }

    /** @return array<string,array> controller => entry */
    private function loadBaseline(array $config): array
    {
        $path = $this->baselinePath($config);
        if (! is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        $out = [];
        foreach ($data['accepted'] ?? [] as $entry) {
            if (! empty($entry['controller'])) {
                $out[$entry['controller']] = $entry;
            }
        }
        return $out;
    }

    private function writeBaseline(array $findings, array $config): int
    {
        // Accept everything EXCEPT high-risk real gaps — those must stay loud.
        // A "real gap" is an un-funnelled (MISSING_LOG) or unverified (NEEDS_REVIEW)
        // action; at HIGH/CRITICAL severity it is never baselined.
        $accepted = [];
        $refused = [];
        foreach ($findings as $f) {
            $isRealGap = in_array($f['classification'], ['MISSING_LOG', 'NEEDS_REVIEW'], true);
            $isHighRiskGap = $isRealGap
                && self::SEVERITY_RANK[$f['severity']] >= self::SEVERITY_RANK['HIGH'];
            if ($isHighRiskGap) {
                $refused[] = $f;
                continue;
            }
            $accepted[] = [
                'controller' => $f['controller'],
                'classification' => $f['classification'],
                'severity' => $f['severity'],
                'actions' => $f['actions'],
            ];
        }

        $path = $this->baselinePath($config);
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode([
            'generated_at' => now()->toIso8601String(),
            'note' => 'Accepted logs:audit findings. High-severity MISSING_LOG is intentionally excluded so real gaps stay visible.',
            'accepted_count' => count($accepted),
            'refused_high_risk_gaps' => array_map(fn ($f) => $f['controller'], $refused),
            'accepted' => $accepted,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Baseline written: ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path));
        $this->line(sprintf('  accepted: %d   refused (high-risk gaps kept visible): %d', count($accepted), count($refused)));
        foreach ($refused as $f) {
            $this->warn('  kept visible: [' . $f['severity'] . '] ' . $f['controller']);
        }
        return self::SUCCESS;
    }

    /* ── Output ─────────────────────────────────────────────────────── */

    private function render(array $findings, array $loggingServices): void
    {
        $by = $this->groupByClassification($findings);

        $this->newLine();
        $this->line('<options=bold>UHMS Logging Coverage Audit (funnel-aware)</>');
        $this->line('==========================================');
        $this->line(sprintf('Logging services detected (direct + transitive): %d', count($loggingServices)));
        $this->line(sprintf('Controllers flagged for classification:          %d', count($findings)));
        $this->newLine();

        $this->line('<options=bold>Summary by classification</>');
        foreach (self::CLASSIFICATIONS as $c) {
            $this->line(sprintf('  %-24s %d', $c, count($by[$c] ?? [])));
        }
        $this->newLine();

        $real = $by['MISSING_LOG'] ?? [];
        $this->line('<options=bold>Real missing logs by severity</>');
        foreach (['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO'] as $sev) {
            $n = count(array_filter($real, fn ($f) => $f['severity'] === $sev));
            if ($n > 0) {
                $this->line(sprintf('  %-9s %d', $sev, $n));
            }
        }
        if ($real === []) {
            $this->info('  none 🎉');
        }
        $this->newLine();

        $this->section('Real Missing Logs', $real, true);
        $this->section('Needs Review', $by['NEEDS_REVIEW'] ?? [], true);
        $this->section('Backlog', $by['KNOWN_BACKLOG'] ?? [], false);
        $this->section('Covered by Service Funnel', $by['SERVICE_FUNNEL_COVERED'] ?? [], false);
        $this->section('Skipped', $by['INTENTIONALLY_SKIPPED'] ?? [], false);

        $this->newLine();
        $this->line('Heuristic, now funnel-aware. "Covered" means a logging service/observer is reached;');
        $this->line('confirm method-level coverage for security/financial actions before trusting it.');
    }

    private function section(string $title, array $items, bool $verbose): void
    {
        if ($items === []) {
            return;
        }
        $this->newLine();
        $this->line('<options=bold>## ' . $title . ' (' . count($items) . ')</>');
        $limit = $verbose ? 200 : 60;
        foreach (array_slice($items, 0, $limit) as $f) {
            $tag = $f['in_baseline'] ?? false ? '' : ' <fg=yellow>[new]</>';
            $this->line(sprintf('  [%s] %s :: %s%s', $f['severity'], $f['controller'], implode(', ', $f['actions']), $tag));
            if ($verbose) {
                $this->line('        ' . $f['reason']);
            }
        }
        if (count($items) > $limit) {
            $this->line(sprintf('  … and %d more', count($items) - $limit));
        }
    }

    /** @return array<string,array<int,array>> */
    private function groupByClassification(array $findings): array
    {
        $by = [];
        foreach ($findings as $f) {
            $by[$f['classification']][] = $f;
        }
        return $by;
    }

    private function writeJson(array $findings, array $loggingServices): void
    {
        $by = $this->groupByClassification($findings);
        $summary = [];
        foreach (self::CLASSIFICATIONS as $c) {
            $summary[$c] = count($by[$c] ?? []);
        }

        $dir = storage_path('reports');
        @mkdir($dir, 0755, true);
        file_put_contents(
            $dir . '/logs-audit-report.json',
            json_encode([
                'generated_at' => now()->toIso8601String(),
                'controllers_flagged' => count($findings),
                'logging_services_detected' => count($loggingServices),
                'summary' => $summary,
                'real_missing_logs' => $summary['MISSING_LOG'] ?? 0,
                'findings' => $findings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $this->info('JSON report: storage/reports/logs-audit-report.json');
    }

    /* ── Fail evaluation ────────────────────────────────────────────── */

    private function evaluateFail(array $findings): int
    {
        $set = $findings;

        if ($this->option('only-real-gaps')) {
            $set = array_filter($set, fn ($f) => $f['classification'] === 'MISSING_LOG');
        }
        if ($this->option('strict')) {
            $set = array_filter($set, fn ($f) => empty($f['in_baseline']));
        }
        if ($min = $this->option('min-severity')) {
            $min = strtoupper((string) $min);
            $threshold = self::SEVERITY_RANK[$min] ?? 0;
            $set = array_filter($set, fn ($f) => self::SEVERITY_RANK[$f['severity']] >= $threshold);
        }

        if ($set !== []) {
            $this->error(sprintf('%d finding(s) match the failing criteria.', count($set)));
            return self::FAILURE;
        }

        $this->info('No findings match the failing criteria.');
        return self::SUCCESS;
    }
}
