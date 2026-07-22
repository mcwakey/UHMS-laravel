<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface FoundationAllocationWriteGuard
{
    public function assertReservationWriteAllowed(string $connection): void;
}
