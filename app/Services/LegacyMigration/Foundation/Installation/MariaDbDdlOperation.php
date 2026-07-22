<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

final readonly class MariaDbDdlOperation
{
    public function __construct(
        public string $migration,
        public DdlObjectType $type,
        public string $name,
        public ?string $sql,
        public string $definitionHash,
        public string $operationId,
    ) {}
}
