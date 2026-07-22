<?php

namespace App\Console\Commands;

use App\Services\LegacyMigration\Evidence\TargetSchemaInspectionService;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use RuntimeException;
use Throwable;

final class LegacyMigrationInspectTargetCommand extends Command
{
    protected $signature = 'legacy-migration:inspect-target
        {--connection= : Laravel target connection; defaults to the configured default}
        {--expected-database= : Required exact non-production target database name}
        {--output=docs/legacy-migration : Output directory relative to the project root}';

    protected $description = 'Capture a read-only, sanitized fingerprint and drift report for the installed non-production target schema';

    public function handle(TargetSchemaInspectionService $service, DatabaseManager $databases): int
    {
        try {
            $connectionName = (string) ($this->option('connection') ?: config('database.default'));
            $expectedDatabase = (string) $this->option('expected-database');
            if ($expectedDatabase === '' || in_array(strtolower($expectedDatabase), ['uuhms', 'uhms', 'uuhmss'], true)) {
                throw new RuntimeException('Target inspection requires an exact non-Classic, non-production database name.');
            }
            if (app()->environment('production') || preg_match('/(^|[_-])(prod|production)([_-]|$)/i', $expectedDatabase)) {
                throw new RuntimeException('Target inspection is forbidden for production environments or production-like database names.');
            }

            $connection = $databases->connection($connectionName);
            if (! hash_equals($expectedDatabase, (string) $connection->getDatabaseName())) {
                throw new RuntimeException('Target inspection refused because the configured database does not match --expected-database.');
            }

            $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $connection->statement('SET SESSION TRANSACTION READ ONLY');
            $connection->beginTransaction();
            try {
                $result = $service->capture(
                    connectionName: $connectionName,
                    expectedDatabase: $expectedDatabase,
                    outputDirectory: (string) $this->option('output'),
                );
                $connection->commit();
            } catch (Throwable $exception) {
                if ($connection->transactionLevel() > 0) {
                    $connection->rollBack();
                }

                throw $exception;
            }

            $this->info('Installed target schema evidence captured in a consistent read-only transaction.');
            $this->line('Database: '.$result['database']);
            $this->line('Tables: '.$result['table_count'].'; columns: '.$result['column_count']);
            $this->line('Fingerprint: '.$result['fingerprint']);

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('Target inspection failed closed; sensitive connection diagnostics were suppressed.');

            return self::FAILURE;
        }
    }
}
