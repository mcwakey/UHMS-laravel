<?php

namespace App\Console\Commands\LegacyMigration;

use App\Services\LegacyMigration\Foundation\Environment\EnvironmentGuard;
use App\Services\LegacyMigration\Foundation\Environment\FoundationGuardException;
use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\LaravelMetadataConnection;
use App\Services\LegacyMigration\Foundation\Environment\SchemaFingerprintService;
use App\Services\LegacyMigration\Foundation\Environment\SourceAccountVerifier;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Throwable;

final class VerifySourceAccountCommand extends Command
{
    protected $signature = 'legacy-migration:verify-source-account {--json : Emit a privacy-safe machine-readable report}';

    protected $description = 'Verify that the Classic migration account is SELECT/metadata-only on exact uuhms';

    public function handle(DatabaseManager $databases): int
    {
        $connection = null;
        try {
            $environment = app()->environment();
            $rawConfiguration = config('legacy-migration');
            if (! is_array($rawConfiguration)) {
                throw new FoundationGuardException('FOUNDATION_CONFIG_MISSING', 'Migration foundation configuration is missing.');
            }
            $configuration = GuardConfiguration::fromArray($rawConfiguration, $environment);
            $guard = new EnvironmentGuard;
            $guard->assertRuntime($environment, $configuration);
            $connection = new LaravelMetadataConnection(
                $configuration->sourceConnection,
                $databases->connection($configuration->sourceConnection),
            );
            if (! hash_equals($configuration->sourceDatabase, $connection->configuredDatabase())) {
                throw new FoundationGuardException('FOUNDATION_SOURCE_CONFIGURATION_MISMATCH', 'The configured source database is not approved.');
            }
            $connection->beginReadOnlySnapshot();
            $verification = (new SourceAccountVerifier)->verify($connection);
            $observation = (new SchemaFingerprintService)->inspectSource($connection, $configuration->sourceDatabase);
            $guard->assertSource($configuration, $observation);
            $connection->rollbackReadOnlySnapshot();

            if ((bool) $this->option('json')) {
                $this->line(json_encode($verification->safeReport(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            } else {
                $this->info('Classic migration account verification passed.');
                $this->line('Scope: exact uuhms; permissions: SELECT/metadata-only; transaction: read-only');
                $this->line('Account identity and connection credentials: redacted');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $connection?->rollbackReadOnlySnapshot();
            $code = $exception instanceof FoundationGuardException ? $exception->faultCode : 'FOUNDATION_SOURCE_ACCOUNT_FAILED';
            $this->error("{$code}: Classic migration account verification failed closed.");
            $this->warn('DBA action: provision a dedicated account with SELECT/metadata-only access scoped to exact uuhms, then rerun this command.');
            $this->warn('The application will not create users, change grants, or continue with a broad account.');

            return self::FAILURE;
        }
    }
}
