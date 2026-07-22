<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

abstract class GuardedEmergencyContactPersistenceBoundary extends GuardedPersistenceBoundary implements EmergencyContactPersistenceBoundary
{
    final public function persistEmergencyContact(DomainNeutralPersistenceCommand $command, PersistenceBoundaryContext $context): PersistenceBoundaryResult
    {
        return $this->invoke(PersistenceOperation::EmergencyContact, $command, $context, $this->persistEmergencyContactCommit(...));
    }

    abstract protected function persistEmergencyContactCommit(AuthorizedPersistenceCommand $command): PersistenceBoundaryResult;
}
