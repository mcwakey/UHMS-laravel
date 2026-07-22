<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

interface MetadataConnection
{
    public function name(): string;

    public function configuredDatabase(): string;

    /** @param array<int, scalar|null> $bindings @return array<int, array<string, mixed>> */
    public function select(string $sql, array $bindings = []): array;

    public function beginReadOnlySnapshot(): void;

    public function rollbackReadOnlySnapshot(): void;

    public function readOnlySnapshotActive(): bool;
}
