<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

interface AllocationFaultInjector
{
    public function inject(AllocationFaultPoint $point): void;
}
