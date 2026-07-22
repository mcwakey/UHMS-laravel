<?php

namespace App\Services\LegacyMigration\Foundation\Runtime;

final readonly class ApplicationMigrationRuntime
{
    public function __construct(
        public MigrationRuntimeContext $context,
        public SideEffectGuard $sideEffects,
        public SideEffectIsolationRegistry $registry,
    ) {}
}
