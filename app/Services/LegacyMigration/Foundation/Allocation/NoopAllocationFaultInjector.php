<?php

namespace App\Services\LegacyMigration\Foundation\Allocation;

final class NoopAllocationFaultInjector implements AllocationFaultInjector
{
    public function inject(AllocationFaultPoint $point): void {}
}
