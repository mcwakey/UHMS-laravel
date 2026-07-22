<?php

namespace Tests\Unit\LegacyMigration\Foundation\Environment;

use App\Services\LegacyMigration\Foundation\Environment\MetadataConnection;

final class FakeMetadataConnection implements MetadataConnection
{
    /** @var array<int, string> */
    public array $queries = [];

    public bool $active = false;

    /** @param callable(string, array<int, scalar|null>): array<int, array<string, mixed>> $resolver */
    public function __construct(
        private readonly string $connectionName,
        private readonly string $database,
        private readonly mixed $resolver,
    ) {}

    public function name(): string
    {
        return $this->connectionName;
    }

    public function configuredDatabase(): string
    {
        return $this->database;
    }

    public function select(string $sql, array $bindings = []): array
    {
        $this->queries[] = $sql;

        return ($this->resolver)($sql, $bindings);
    }

    public function beginReadOnlySnapshot(): void
    {
        $this->active = true;
    }

    public function rollbackReadOnlySnapshot(): void
    {
        $this->active = false;
    }

    public function readOnlySnapshotActive(): bool
    {
        return $this->active;
    }
}
