<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

/** Phase 3 implements the allocator but authorizes no real reservation. */
final class Phase3AllocationWriteGuard implements FoundationAllocationWriteGuard
{
    public function assertReservationWriteAllowed(string $connection): void
    {
        throw AllocationException::failClosed('PATIENT-NUM-PHASE3-RESERVATION-BLOCKED');
    }
}
