<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

interface PatientEntityPersistenceBoundary
{
    public function persistPatientEntity(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): PersistenceBoundaryResult;
}
