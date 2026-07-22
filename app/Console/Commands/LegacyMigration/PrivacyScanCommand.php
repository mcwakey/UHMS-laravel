<?php

namespace App\Console\Commands\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Privacy\PrivacyScanner;
use App\Services\LegacyMigration\Foundation\Privacy\SafeDiagnosticEncoder;
use Illuminate\Console\Command;
use Throwable;

final class PrivacyScanCommand extends Command
{
    protected $signature = 'legacy-migration:privacy-scan
        {--path=* : Additional repository-relative artifact file or directory}
        {--json : Emit a machine-readable redacted result}';

    protected $description = 'Fail-closed privacy scan of legacy-migration artifacts with redacted diagnostics';

    public function handle(PrivacyScanner $scanner, SafeDiagnosticEncoder $diagnostics): int
    {
        try {
            $configuredRoots = config('legacy-migration.privacy.scan_roots', ['docs/legacy-migration']);
            if (! is_array($configuredRoots)) {
                throw new \RuntimeException('Privacy scan configuration is invalid.');
            }
            $additional = $this->option('path');
            $result = $scanner->scan(
                base_path(),
                array_values($configuredRoots),
                is_array($additional) ? array_values($additional) : [],
            );

            if ((bool) $this->option('json')) {
                $this->line($diagnostics->json($result->jsonSerialize()));
            } else {
                $this->line('Scanner: '.($result->jsonSerialize()['scanner_version']));
                $this->line('Files: '.$result->fileCount);
                $this->line('Coverage difference: '.$result->coverageDifference);
                $this->line('Unallowlisted findings: '.$result->unallowlistedFindingCount());
                foreach ($result->findings as $finding) {
                    if (! $finding->allowlisted) {
                        $safe = $diagnostics->finding($finding);
                        $this->error($safe['path'].' | '.$safe['detector_id'].' | '.$safe['location']);
                    }
                }
                if ($result->releaseBlocked()) {
                    $this->error($result::CONTAINMENT);
                } else {
                    $this->info('Privacy scan passed: coverage difference and unallowlisted findings are zero.');
                }
            }

            return $result->releaseBlocked() ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $safe = $diagnostics->exception($exception);
            $this->error($safe['error_code'].' '.$safe['detail']);

            return self::FAILURE;
        }
    }
}
