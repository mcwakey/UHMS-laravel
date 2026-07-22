<?php

namespace App\Services\LegacyMigration\Foundation\Privacy;

use App\Services\LegacyMigration\Foundation\Runtime\OperationalGuardScopeManifest;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class PrivacyScanner
{
    private const MAX_FILE_BYTES = 20_000_000;

    /** @var list<string> */
    private const REQUIRED_PHASE_3_SCOPE = [
        'docs/legacy-migration',
        'config/legacy-migration.php',
        '.env.example',
        'app/Services/LegacyMigration/Foundation',
        'app/Services/LegacyMigration/Evidence',
        'app/Models/LegacyMigration',
        'app/Providers/AppServiceProvider.php',
        'app/Console/Commands/LegacyMigration',
        'app/Console/Commands/LegacyMigrationCaptureClassicEvidenceCommand.php',
        'app/Console/Commands/LegacyMigrationInspectTargetCommand.php',
        'database/migrations/2026_07_22_000110_create_legacy_migration_run_foundation_tables.php',
        'database/migrations/2026_07_22_000111_create_legacy_migration_protected_store_tables.php',
        'database/migrations/2026_07_22_000112_create_legacy_migration_recovery_tables.php',
        'database/migrations/2026_07_22_000113_create_legacy_migration_installation_journal.php',
        'database/migrations/2026_07_22_000114_create_legacy_migration_protected_lifecycle_tables.php',
        'database/migrations/2026_07_22_000115_create_legacy_migration_recovery_journal.php',
        'tests/Unit/LegacyMigration/Foundation',
        'tests/Feature/LegacyMigration/Foundation',
    ];

    /** @var list<string> */
    private const OPTIONAL_GENERATED_SCOPE = [
        'storage/app/legacy-migration/reports',
        'storage/app/legacy-migration/exports',
        'storage/app/legacy-migration/dry-runs',
        'storage/logs/legacy-migration.log',
    ];

    public function __construct(
        private readonly StructuredDetectorRegistry $registry = new StructuredDetectorRegistry,
        private readonly SyntheticFixtureAllowList $allowList = new SyntheticFixtureAllowList,
    ) {}

    /**
     * @param  list<string>  $configuredRoots
     * @param  list<string>  $additionalPaths
     */
    public function scan(string $projectRoot, array $configuredRoots = ['docs/legacy-migration'], array $additionalPaths = []): PrivacyScanResult
    {
        $root = realpath($projectRoot);
        if ($root === false || ! is_dir($root)) {
            throw new RuntimeException('Privacy scan root is invalid [LM-PRIV-SCOPE-001].');
        }

        $mandatory = array_values(array_unique([
            ...$configuredRoots,
            ...self::REQUIRED_PHASE_3_SCOPE,
            ...OperationalGuardScopeManifest::sourcePaths(),
            ...$additionalPaths,
        ]));
        $requested = array_values(array_unique([...$mandatory, ...self::OPTIONAL_GENERATED_SCOPE]));
        $files = [];
        $scopeFailures = [];
        foreach ($requested as $path) {
            if (! is_string($path) || $path === '') {
                throw new RuntimeException('Privacy scan scope is invalid [LM-PRIV-SCOPE-002].');
            }
            $absolute = $this->resolveInsideRoot($root, $path);
            if (! file_exists($absolute)) {
                if (in_array($path, $mandatory, true)) {
                    $scopeFailures[] = [
                        'detector' => 'artifact_missing',
                        'path' => $this->relative($root, $absolute),
                    ];
                }

                continue;
            }
            if (! is_readable($absolute) || is_link($absolute)) {
                if (in_array($path, $mandatory, true)) {
                    $scopeFailures[] = [
                        'detector' => 'artifact_scope_unreadable',
                        'path' => $this->relative($root, $absolute),
                    ];
                }

                continue;
            }
            if (is_dir($absolute)) {
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS));
                foreach ($iterator as $file) {
                    if ($file instanceof SplFileInfo && $file->isFile() && ! $file->isLink()) {
                        $files[$this->relative($root, $file->getPathname())] = $file->getPathname();
                    }
                }
            } elseif (is_file($absolute) && ! is_link($absolute)) {
                $files[$this->relative($root, $absolute)] = $absolute;
            }
        }
        ksort($files, SORT_STRING);

        $manifestPaths = array_keys($files);
        sort($manifestPaths, SORT_STRING);
        $manifestHash = hash('sha256', implode("\n", $manifestPaths));

        $findings = [];
        $scanned = [];
        $validSyntheticFixtureSeen = false;
        foreach ($this->registry->detectors() as $detector) {
            $detector->reset();
        }

        foreach ($files as $relative => $absolute) {
            if (! is_readable($absolute) || filesize($absolute) > self::MAX_FILE_BYTES) {
                $findings[] = new PrivacyFinding('artifact_unscannable', $relative, 1, 1);

                continue;
            }
            $content = file_get_contents($absolute);
            if (! is_string($content) || str_contains($content, "\0") || preg_match('//u', $content) !== 1) {
                $findings[] = new PrivacyFinding('artifact_unscannable', $relative, 1, 1);

                continue;
            }
            $scanned[] = $relative;

            if ($relative === SyntheticFixtureAllowList::PATH) {
                $validSyntheticFixtureSeen = $this->allowList->isValid($relative, $content);
                if (! $validSyntheticFixtureSeen) {
                    $findings[] = new PrivacyFinding('synthetic_fixture_contract', $relative, 1, 1);
                }
            }

            foreach ($this->registry->detectors() as $detector) {
                foreach ($detector->scan($relative, $content) as $finding) {
                    $findings[] = $this->allowList->allows($finding, $content) ? $finding->allowlisted() : $finding;
                }
            }
        }
        foreach ($this->registry->detectors() as $detector) {
            array_push($findings, ...$detector->finish());
        }
        foreach ($scopeFailures as $failure) {
            $findings[] = new PrivacyFinding($failure['detector'], $failure['path'], 1, 1);
        }

        $coverageDifference = count(array_diff($manifestPaths, $scanned))
            + count(array_diff($scanned, $manifestPaths))
            + count($scopeFailures);
        $structuredChecks = [
            'artifact_manifest_ordinal_path_hash',
            'coverage_difference_zero',
            'synthetic_fixture_namespace_and_markers',
            'redacted_diagnostics_only',
            'aggregate_only_report_enforcement',
            'containment_rotation_and_complete_rescan_on_finding',
        ];
        if (isset($files[SyntheticFixtureAllowList::PATH]) && ! $validSyntheticFixtureSeen) {
            $coverageDifference++;
        }

        return new PrivacyScanResult(
            $manifestHash,
            count($files),
            $coverageDifference,
            [...$this->registry->patternClasses(), 'artifact_unscannable', 'artifact_missing', 'artifact_scope_unreadable', 'synthetic_fixture_contract'],
            $structuredChecks,
            $findings,
        );
    }

    private function resolveInsideRoot(string $root, string $path): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (str_contains($normalized, "\0") || preg_match('#(^|[\\/])\.\.([\\/]|$)#', $normalized) === 1) {
            throw new RuntimeException('Privacy scan scope is invalid [LM-PRIV-SCOPE-003].');
        }

        $absolute = preg_match('#^(?:[A-Za-z]:[\\/]|[\\/]{2})#', $normalized) === 1
            ? $normalized
            : $root.DIRECTORY_SEPARATOR.ltrim($normalized, DIRECTORY_SEPARATOR);
        $parent = realpath(is_dir($absolute) ? $absolute : dirname($absolute));
        if ($parent !== false && ! str_starts_with(strtolower($parent.DIRECTORY_SEPARATOR), strtolower(rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Privacy scan scope is outside the project [LM-PRIV-SCOPE-004].');
        }

        return $absolute;
    }

    private function relative(string $root, string $absolute): string
    {
        return str_replace('\\', '/', ltrim(substr($absolute, strlen($root)), '\\/'));
    }
}
