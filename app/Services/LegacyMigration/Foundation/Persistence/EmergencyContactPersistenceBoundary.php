<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

interface EmergencyContactPersistenceBoundary
{
    public function persistEmergencyContact(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): PersistenceBoundaryResult;
}
