<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use Closure;

interface NumberReservationStore
{
    public function findByPatientCoreKey(string $patientCoreKey): ?ExistingAllocation;

    /**
     * Execute under the exact sequence-coordinate row lock and transaction.
     * Missing-row implementations must use duplicate-key retry/upsert-and-lock.
     *
     * @template T
     *
     * @param  Closure(): T  $operation
     * @return T
     */
    public function withLockedCoordinate(PinnedNumberingConfiguration $configuration, Closure $operation): mixed;

    /** May only be called within withLockedCoordinate(). */
    public function nextOrdinal(PinnedNumberingConfiguration $configuration): int;

    /** Persist the reservation and sequence increment in the same transaction. */
    public function persist(AllocationRequest $request, string $number, int $ordinal): ExistingAllocation;
}
