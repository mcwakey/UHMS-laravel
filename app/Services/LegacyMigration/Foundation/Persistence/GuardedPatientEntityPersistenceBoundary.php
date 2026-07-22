<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

abstract class GuardedPatientEntityPersistenceBoundary extends GuardedPersistenceBoundary implements PatientEntityPersistenceBoundary
{
    final public function persistPatientEntity(DomainNeutralPersistenceCommand $command, PersistenceBoundaryContext $context): PersistenceBoundaryResult
    {
        return $this->invoke(PersistenceOperation::PatientEntity, $command, $context, $this->persistPatientEntityCommit(...));
    }

    abstract protected function persistPatientEntityCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult;
}
