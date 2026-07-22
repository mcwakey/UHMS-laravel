<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

interface InsuranceHistoryPersistenceBoundary
{
    public function persistInsuranceHistory(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): PersistenceBoundaryResult;
}
