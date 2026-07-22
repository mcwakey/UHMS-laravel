<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

abstract class GuardedAliasPersistenceBoundary extends GuardedPersistenceBoundary implements AliasPersistenceBoundary
{
    final public function persistAlias(DomainNeutralPersistenceCommand $command, PersistenceBoundaryContext $context): PersistenceBoundaryResult
    {
        return $this->invoke(PersistenceOperation::Alias, $command, $context, $this->persistAliasCommit(...));
    }

    abstract protected function persistAliasCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult;
}
