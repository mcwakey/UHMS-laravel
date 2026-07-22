<?php

namespace App\Console\Commands;

use App\Services\LegacyMigration\Evidence\ClassicEvidenceCaptureService;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use RuntimeException;
use Throwable;

final class LegacyMigrationCaptureClassicEvidenceCommand extends Command
{
    protected $signature = 'legacy-migration:capture-classic-evidence
        {--connection=legacy_uhms : Dedicated Laravel connection for Classic inspection}
        {--expected-database=uuhms : Required exact approved Classic database name}
        {--output=docs/legacy-migration/evidence : Output directory relative to the project root}';

    protected $description = 'Regenerate sanitized evidence in a repeatable-read, session read-only transaction for approved Classic uuhms';

    public function handle(ClassicEvidenceCaptureService $service, DatabaseManager $databases): int
    {
        try {
            $connectionName = (string) $this->option('connection');
            $expectedDatabase = (string) $this->option('expected-database');
            if ($connectionName !== 'legacy_uhms' || $expectedDatabase !== 'uuhms') {
                throw new RuntimeException('Classic evidence capture refuses every connection/schema except [legacy_uhms]/[uuhms].');
            }

            $connection = $databases->connection($connectionName);
            if (! hash_equals('uuhms', (string) $connection->getDatabaseName())) {
                throw new RuntimeException('Classic evidence capture refused the configured connection because it does not select [uuhms].');
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

            $this->info('Classic evidence captured with SELECT-only queries in a repeatable-read, session read-only transaction.');
            $this->line('Database: '.$result['database']);
            $this->line('Tables: '.$result['table_count'].'; columns: '.$result['column_count']);
            $this->line('Exact rows: '.$result['total_rows']);
            $this->line('Fingerprint: '.$result['fingerprint']);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
