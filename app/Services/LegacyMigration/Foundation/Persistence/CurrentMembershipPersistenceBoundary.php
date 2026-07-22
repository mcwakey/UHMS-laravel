<?php

namespace App\Services\LegacyMigration\Foundation\Persistence;

interface CurrentMembershipPersistenceBoundary
{
    public function persistCurrentMembership(
        DomainNeutralPersistenceCommand $command,
        PersistenceBoundaryContext $context,
    ): PersistenceBoundaryResult;
}
