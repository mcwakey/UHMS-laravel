<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use App\Services\LegacyMigration\Foundation\Environment\EnvironmentGuard;
use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\LaravelMetadataConnection;
use App\Services\LegacyMigration\Foundation\Environment\SchemaFingerprintService;
use Illuminate\Database\ConnectionInterface;
use RuntimeException;
use Throwable;

/**
 * Fail-closed boundary for installing the Phase 3 foundation schema.
 * Domain data writes are never authorized by this boundary.
 */
final class FoundationDatabaseWriteBoundary
{
    /** @param list<string> $requiredFoundationTables */
    public static function assertSchemaInstallAllowed(ConnectionInterface $connection, array $requiredFoundationTables = []): void
    {
        $driver = $connection->getDriverName();
        if (app()->runningUnitTests() && app()->environment('testing') && $driver === 'sqlite') {
            return;
        }

        $connectionName = $connection->getName();
        $databaseName = (string) $connection->getDatabaseName();
        $sourceConnection = (string) config('legacy-migration.source.connection');
        $targetConnection = (string) config('legacy-migration.target.connection');
        $targetDatabase = (string) config('legacy-migration.target.database');

        $allowed = (bool) config('legacy-migration.foundation.schema_writes_enabled', false)
            && (bool) config('legacy-migration.enabled', false)
            && ! app()->environment('production')
            && ! (bool) config('legacy-migration.execution.production_enabled', false)
            && in_array(app()->environment(), (array) config('legacy-migration.allowed_environments', []), true)
            && in_array($driver, ['mysql', 'mariadb'], true)
            && $connectionName !== ''
            && hash_equals($targetConnection, $connectionName)
            && ! hash_equals($sourceConnection, $connectionName)
            && $targetDatabase !== ''
            && hash_equals($targetDatabase, $databaseName)
            && ! in_array(strtolower($databaseName), ['uuhms', 'uhms', 'uuhmss'], true)
            && preg_match('/(^|[_-])(prod|production)([_-]|$)/i', $databaseName) !== 1;

        if (! $allowed) {
            throw new RuntimeException('Phase 3 foundation schema write blocked [LM-SEC-FOUNDATION-SCHEMA-WRITE-BLOCKED].');
        }

        try {
            $configuration = GuardConfiguration::fromArray((array) config('legacy-migration'), app()->environment());
            $guard = new EnvironmentGuard;
            $guard->assertRuntime(app()->environment(), $configuration);

            if ($requiredFoundationTables === []) {
                $metadata = new LaravelMetadataConnection($connectionName, $connection);
                $metadata->beginReadOnlySnapshot();
                try {
                    $observation = (new SchemaFingerprintService)->inspectTarget($metadata, $targetDatabase);
                } finally {
                    $metadata->rollbackReadOnlySnapshot();
                }
                $guard->assertTarget($configuration, $observation);
            } else {
                foreach ($requiredFoundationTables as $table) {
                    if (! str_starts_with($table, 'legacy_migration_') || ! $connection->getSchemaBuilder()->hasTable($table)) {
                        throw new RuntimeException('Phase 3 foundation prerequisite missing [LM-SEC-FOUNDATION-SCHEMA-PREREQUISITE].');
                    }
                }
                $version = $connection->selectOne('SELECT VERSION() AS database_version');
                if (! is_object($version)
                    || ! isset($version->database_version)
                    || ! hash_equals($configuration->targetVersion, (string) $version->database_version)) {
                    throw new RuntimeException('Phase 3 target version changed [LM-SEC-FOUNDATION-SCHEMA-VERSION].');
                }
            }
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException && str_contains($exception->getMessage(), '[LM-SEC-')) {
                throw $exception;
            }

            throw new RuntimeException('Phase 3 foundation pre-DDL verification failed [LM-SEC-FOUNDATION-PREFLIGHT].', 0, $exception);
        }
    }

    public static function assertSchemaRemovalAllowed(ConnectionInterface $connection): void
    {
        if (app()->runningUnitTests() && app()->environment('testing') && $connection->getDriverName() === 'sqlite') {
            return;
        }

        throw new RuntimeException('Phase 3 foundation schema removal is not authorized [LM-SEC-FOUNDATION-SCHEMA-REMOVAL-BLOCKED].');
    }
}
