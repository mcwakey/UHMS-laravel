<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

abstract class GuardedExistingTargetLinkPersistenceBoundary extends GuardedPersistenceBoundary implements ExistingTargetLinkPersistenceBoundary
{
    final public function linkExistingTarget(DomainNeutralPersistenceCommand $command, PersistenceBoundaryContext $context): PersistenceBoundaryResult
    {
        return $this->invoke(PersistenceOperation::ExistingTargetLink, $command, $context, $this->linkExistingTargetMetadataCommit(...));
    }

    /** Implementations may persist foundation metadata but must never mutate the target. */
    abstract protected function linkExistingTargetMetadataCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult;
}
