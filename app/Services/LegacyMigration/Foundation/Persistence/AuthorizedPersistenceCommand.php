<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

use App\Services\LegacyMigration\Foundation\Runtime\ExecutionMode;

/**
 * A boundary token emitted only after the shared runtime and persistence guard
 * has validated the command. It contains no business-domain payload.
 */
final readonly class AuthorizedPersistenceCommand
{
    public function __construct(
        public DomainNeutralPersistenceCommand $command,
        public ExecutionMode $mode,
        public bool $businessWritesAllowed,
        public bool $metadataOnly,
    ) {}
}
