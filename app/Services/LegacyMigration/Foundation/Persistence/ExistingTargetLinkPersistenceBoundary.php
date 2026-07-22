<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

interface ExistingTargetLinkPersistenceBoundary
{
    /** Existing-target implementations may write migration metadata only. */
    public function linkExistingTarget(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): PersistenceBoundaryResult;
}
