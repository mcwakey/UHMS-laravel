<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;

interface ReservationProtectionFactory
{
    public function connectionName(): string;

    public function context(AllocationRequest $request): ProtectedStoreOperationContext;

    /** Fixed-width lookup digest expected in the protected reservation row. */
    public function expectedProtectedSourceToken(AllocationRequest $request): string;

    /** @return array<string,array{encoded_token:string,domain:string}> */
    public function tokenSet(AllocationRequest $request, string $number, int $ordinal): array;
}
