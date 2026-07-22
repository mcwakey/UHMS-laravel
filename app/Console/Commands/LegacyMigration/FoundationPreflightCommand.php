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

final class FoundationPreflightCommand extends Command
{
    protected $signature = 'legacy-migration:foundation-preflight {--json : Emit a privacy-safe machine-readable report}';

    protected $description = 'Run zero-write fail-closed Classic/target migration foundation guards';

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
            $result = (new FoundationPreflightService)->run(
                $environment,
                $configuration,
                new LaravelMetadataConnection($configuration->sourceConnection, $databases->connection($configuration->sourceConnection)),
                new LaravelMetadataConnection($configuration->targetConnection, $databases->connection($configuration->targetConnection)),
            );
            $report = $result->safeReport();
            if ((bool) $this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            } else {
                $this->info('Migration foundation preflight passed.');
                $this->line('Mode: read-only; business rows written: 0');
                $this->line('Source account: approved SELECT/metadata-only on exact uuhms');
                $this->line('Source and target schema coordinates: matched');
                $this->line('Configuration fingerprint: '.$report['configuration_fingerprint']);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $code = $exception instanceof FoundationGuardException ? $exception->faultCode : 'FOUNDATION_PREFLIGHT_FAILED';
            $this->error("{$code}: Migration foundation preflight failed closed.");

            return self::FAILURE;
        }
    }
}
