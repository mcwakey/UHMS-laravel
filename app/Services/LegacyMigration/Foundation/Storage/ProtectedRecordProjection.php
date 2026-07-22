<?php

namespace App\Services\LegacyMigration\Foundation\Storage;

final class ProtectedRecordProjection
{
    /** @param array<string, mixed> $fields */
    public function __construct(
        public readonly string $recordType,
        public readonly int $recordId,
        public readonly array $fields,
    ) {}
}
