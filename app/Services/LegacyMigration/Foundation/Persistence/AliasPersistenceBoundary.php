<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

interface AliasPersistenceBoundary
{
    public function persistAlias(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): PersistenceBoundaryResult;
}
