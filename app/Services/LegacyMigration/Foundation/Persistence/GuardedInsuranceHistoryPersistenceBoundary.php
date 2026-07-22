<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

abstract class GuardedInsuranceHistoryPersistenceBoundary extends GuardedPersistenceBoundary implements InsuranceHistoryPersistenceBoundary
{
    final public function persistInsuranceHistory(DomainNeutralPersistenceCommand $command, PersistenceBoundaryContext $context): PersistenceBoundaryResult
    {
        return $this->invoke(PersistenceOperation::InsuranceHistory, $command, $context, $this->persistInsuranceHistoryCommit(...));
    }

    abstract protected function persistInsuranceHistoryCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult;
}
