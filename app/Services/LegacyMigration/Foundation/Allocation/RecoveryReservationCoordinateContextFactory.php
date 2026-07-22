<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Recovery\RecoveryCoordinateHint;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAttributeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;

final readonly class RecoveryReservationCoordinateContextFactory implements ReservationCoordinateContextFactory
{
    public function __construct(private RecoveryJournalAttributeFactory $attributes) {}

    public function idempotencyContext(int $idempotencyRecordId, int $runId, int $sourceSnapshotId, int $targetSnapshotId): ProtectedStoreOperationContext
    {
        return $this->attributes->readContext('idempotency', new RecoveryCoordinateHint(
            $idempotencyRecordId, $runId, $sourceSnapshotId, $targetSnapshotId,
        ));
    }
}
