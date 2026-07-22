<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;

interface ReservationCoordinateContextFactory
{
    public function idempotencyContext(int $idempotencyRecordId, int $runId, int $sourceSnapshotId, int $targetSnapshotId): ProtectedStoreOperationContext;
}
