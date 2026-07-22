<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

abstract class GuardedCurrentMembershipPersistenceBoundary extends GuardedPersistenceBoundary implements CurrentMembershipPersistenceBoundary
{
    final public function persistCurrentMembership(DomainNeutralPersistenceCommand $command, PersistenceBoundaryContext $context): PersistenceBoundaryResult
    {
        return $this->invoke(PersistenceOperation::CurrentMembership, $command, $context, $this->persistCurrentMembershipCommit(...));
    }

    abstract protected function persistCurrentMembershipCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult;
}
