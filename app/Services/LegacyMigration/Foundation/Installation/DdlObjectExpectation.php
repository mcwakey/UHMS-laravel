<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use InvalidArgumentException;

final readonly class DdlObjectExpectation
{
    public function __construct(
        public string $migrationVersion,
        public DdlObjectType $type,
        public string $name,
        public string $definitionHash,
        public string $installOperationId,
        public bool $metadataOnly = false,
    ) {
        if ($migrationVersion === '' || $name === '' || $installOperationId === ''
            || preg_match('/\A[a-f0-9]{64}\z/', $definitionHash) !== 1) {
            throw new InvalidArgumentException('Invalid expected foundation DDL object.');
        }
        if (! str_starts_with($name, 'legacy_migration_') && ! str_starts_with($name, 'lm_')) {
            throw new InvalidArgumentException('Foundation DDL expectation is outside the reserved namespace.');
        }
    }

    public function coordinate(): string
    {
        return $this->type->value.':'.$this->name;
    }
}
