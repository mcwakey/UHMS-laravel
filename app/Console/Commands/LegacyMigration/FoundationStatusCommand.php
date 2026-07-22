<?php

namespace App\Console\Commands\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Environment\EnvironmentGuard;
use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use App\Services\LegacyMigration\Foundation\Environment\FoundationPreflightService;
use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\LaravelMetadataConnection;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Throwable;

final class FoundationStatusCommand extends Command
{
    protected $signature = 'legacy-migration:foundation-status {--json : Emit a privacy-safe machine-readable report}';

    protected $description = 'Report current zero-write migration foundation guard status';

    public function handle(DatabaseManager $databases): int
    {
        try {
            $environment = app()->environment();
            $rawConfiguration = config('legacy-migration');
            if (! is_array($rawConfiguration)) {
                throw new FoundationGuardException('FOUNDATION_CONFIG_MISSING', 'Migration foundation configuration is missing.');
            }
            $configuration = GuardConfiguration::fromArray($rawConfiguration, $environment);
            (new EnvironmentGuard)->assertRuntime($environment, $configuration);
            $report = (new FoundationPreflightService)->run(
                $environment,
                $configuration,
                new LaravelMetadataConnection($configuration->sourceConnection, $databases->connection($configuration->sourceConnection)),
                new LaravelMetadataConnection($configuration->targetConnection, $databases->connection($configuration->targetConnection)),
            )->safeReport();
            $report['commit_authorized'] = false;
            $report['status_label'] = 'DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED';

            if ((bool) $this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            } else {
                $this->info('Foundation status: guards passed; read-only inspection only.');
                $this->line('Verdict: DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED');
                $this->line('Business rows written: 0');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $code = $exception instanceof FoundationGuardException ? $exception->faultCode : 'FOUNDATION_STATUS_FAILED';
            if ((bool) $this->option('json')) {
                $this->line(json_encode([
                    'status' => 'FAIL_CLOSED',
                    'fault_code' => $code,
                    'business_rows_written' => 0,
                    'details_redacted' => true,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            } else {
                $this->error("Foundation status: FAIL_CLOSED ({$code}).");
                $this->line('Business rows written: 0; diagnostic details redacted.');
            }

            return self::FAILURE;
        }
    }
}
