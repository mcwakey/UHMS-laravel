<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface ReservationAttributeFactory
{
    /**
     * Supply protected run/idempotency/version/audit metadata for the foundation row.
     * The store overwrites all allocation-derived fields.
     *
     * @return array<string, mixed>
     */
    public function make(AllocationRequest $request, string $number, int $ordinal): array;
}
