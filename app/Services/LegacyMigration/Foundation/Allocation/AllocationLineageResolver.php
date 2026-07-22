<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface AllocationLineageResolver
{
    /** Resolve only an exact successful crosswalk/idempotency lineage, or fail on conflict. */
    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation;
}
