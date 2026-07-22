<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

use Illuminate\Database\ConnectionInterface;
use stdClass;

final class LaravelMetadataConnection implements MetadataConnection
{
    private bool $readOnlySnapshotActive = false;

    public function __construct(
        private readonly string $connectionName,
        private readonly ConnectionInterface $connection,
    ) {}

    public function name(): string
    {
        return $this->connectionName;
    }

    public function configuredDatabase(): string
    {
        return (string) $this->connection->getDatabaseName();
    }

    public function select(string $sql, array $bindings = []): array
    {
        $normalized = strtoupper(ltrim($sql));
        if (! str_starts_with($normalized, 'SELECT ') && ! str_starts_with($normalized, 'SHOW GRANTS')) {
            throw new FoundationGuardException('FOUNDATION_QUERY_REJECTED', 'A non-read-only foundation query was rejected.');
        }
        if (! $this->readOnlySnapshotActive) {
            throw new FoundationGuardException('FOUNDATION_SNAPSHOT_REQUIRED', 'A read-only metadata snapshot is required.');
        }

        return array_map(
            static fn (array|stdClass $row): array => (array) $row,
            $this->connection->select($sql, $bindings),
        );
    }

    public function beginReadOnlySnapshot(): void
    {
        if ($this->readOnlySnapshotActive || $this->connection->transactionLevel() !== 0) {
            throw new FoundationGuardException('FOUNDATION_TRANSACTION_STATE', 'The metadata connection transaction state is unsafe.');
        }

        $this->connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $this->connection->statement('SET TRANSACTION READ ONLY');
        $this->connection->beginTransaction();
        $this->readOnlySnapshotActive = true;
    }

    public function rollbackReadOnlySnapshot(): void
    {
        if ($this->connection->transactionLevel() > 0) {
            $this->connection->rollBack();
        }
        $this->readOnlySnapshotActive = false;
    }

    public function readOnlySnapshotActive(): bool
    {
        return $this->readOnlySnapshotActive;
    }
}
