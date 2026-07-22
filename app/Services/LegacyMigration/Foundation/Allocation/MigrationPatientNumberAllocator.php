<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface MigrationPatientNumberAllocator
{
    public function allocate(AllocationRequest $request): AllocationResult;
}
