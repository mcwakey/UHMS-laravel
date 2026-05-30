<?php

namespace App\Console\Commands;

use App\Support\PermissionMeta;
use Illuminate\Console\Command;
use Illuminate\Routing\Route as RouteInstance;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;

/**
 * Permissions / Roles / Modules audit.
 *
 * Cross-checks:
 *   - permissions referenced by `can:<perm>` middleware vs DB permissions
 *   - admin mutation routes that have NO `can:` middleware
 *   - DB permissions never used in any route
 *   - permission name collisions (likely typos / duplicates)
 *
 * Output: console table by default; JSON file with `--json`.
 */
class PermissionsAuditCommand extends Command
{
    protected $signature = 'permissions:audit {--json : Also write storage/reports/permissions_audit.json} {--strict : Exit with code 1 if drift is detected}';

    protected $description = 'Audit permissions, role assignments, and route authorisation.';

    public function handle(): int
    {
        $dbPermissions = Permission::pluck('name')->all();
        $dbSet = array_flip($dbPermissions);

        $usedInRoutes  = [];
        $unprotected   = [];
        $totalMutation = 0;

        foreach (Route::getRoutes() as $route) {
            /** @var RouteInstance $route */
            $uri      = $route->uri();
            $methods  = array_diff($route->methods(), ['HEAD']);
            $mwList   = $route->gatherMiddleware();
            $isMutation = (bool) array_intersect($methods, ['POST', 'PUT', 'PATCH', 'DELETE']);

            // Track permissions referenced
            foreach ($mwList as $mw) {
                if (is_string($mw) && str_starts_with($mw, 'can:')) {
                    $perm = substr($mw, 4);
                    // can:perm,model — we only want the perm name
                    $perm = explode(',', $perm)[0];
                    $usedInRoutes[$perm] = true;
                }
            }

            // Mutation routes under /admin/* should have a can: middleware
            if ($isMutation && str_starts_with($uri, 'admin/')) {
                $totalMutation++;
                $hasCan = false;
                foreach ($mwList as $mw) {
                    if (is_string($mw) && (str_starts_with($mw, 'can:') || str_starts_with($mw, 'role:'))) {
                        $hasCan = true;
                        break;
                    }
                }
                if (! $hasCan) {
                    $unprotected[] = sprintf('[%s] %s', implode('|', $methods), $uri);
                }
            }
        }

        $referencedNotInDb = array_values(array_filter(array_keys($usedInRoutes), fn ($p) => ! isset($dbSet[$p])));
        $unusedInRoutes    = array_values(array_filter($dbPermissions, fn ($p) => ! isset($usedInRoutes[$p])));

        // Duplicate / near-duplicate detection
        $duplicates = [];
        $names = $dbPermissions;
        sort($names);
        foreach ($names as $i => $a) {
            for ($j = $i + 1; $j < count($names); $j++) {
                $b = $names[$j];
                if (levenshtein($a, $b) <= 2 && $a !== $b) {
                    $duplicates[] = [$a, $b];
                }
            }
        }

        // Risk distribution
        $byRisk = ['LOW' => 0, 'NORMAL' => 0, 'HIGH' => 0, 'CRITICAL' => 0];
        foreach ($dbPermissions as $p) {
            $byRisk[PermissionMeta::risk($p)] = ($byRisk[PermissionMeta::risk($p)] ?? 0) + 1;
        }

        // ── Output ────────────────────────────────────────────────
        $this->info('Permissions audit');
        $this->line(str_repeat('-', 60));
        $this->line(sprintf('Total permissions in DB:           %d', count($dbPermissions)));
        $this->line(sprintf('Permissions referenced in routes:  %d', count($usedInRoutes)));
        $this->line(sprintf('Admin mutation routes scanned:     %d', $totalMutation));
        $this->line('');
        $this->line('Risk distribution:');
        foreach ($byRisk as $level => $count) {
            $this->line(sprintf('  %-9s %d', $level, $count));
        }

        $this->line('');
        $this->warn(sprintf('Permissions referenced by routes but missing in DB (%d):', count($referencedNotInDb)));
        foreach ($referencedNotInDb as $p) {
            $this->line('  - ' . $p);
        }

        $this->line('');
        $this->warn(sprintf('Admin mutation routes with no can:/role: middleware (%d):', count($unprotected)));
        foreach (array_slice($unprotected, 0, 50) as $u) {
            $this->line('  - ' . $u);
        }
        if (count($unprotected) > 50) {
            $this->line(sprintf('  ... and %d more', count($unprotected) - 50));
        }

        $this->line('');
        $this->warn(sprintf('Possible duplicate permission names (%d pairs):', count($duplicates)));
        foreach ($duplicates as [$a, $b]) {
            $this->line(sprintf('  - %s  ⇄  %s', $a, $b));
        }

        if ($this->option('json')) {
            $payload = [
                'generated_at'        => now()->toIso8601String(),
                'totals' => [
                    'permissions_in_db'   => count($dbPermissions),
                    'used_in_routes'      => count($usedInRoutes),
                    'admin_mutation_routes' => $totalMutation,
                ],
                'risk_distribution'   => $byRisk,
                'referenced_not_in_db'=> $referencedNotInDb,
                'unused_in_routes'    => $unusedInRoutes,
                'unprotected_routes'  => $unprotected,
                'possible_duplicates' => $duplicates,
            ];
            $dir  = storage_path('reports');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir . DIRECTORY_SEPARATOR . 'permissions_audit.json';
            file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('JSON report: ' . $file);
        }

        if ($this->option('strict')) {
            $drift = count($referencedNotInDb) + count($unprotected);
            if ($drift > 0) {
                $this->error(sprintf('Strict mode: %d drift item(s) detected.', $drift));
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
