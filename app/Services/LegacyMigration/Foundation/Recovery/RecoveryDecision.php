<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryDecision
{
    public function __construct(
        public CrashBoundary $boundary,
        public RecoveryDisposition $disposition,
        public MigrationState $state,
        public AtomicUnit $unit,
        public bool $operatorReviewRequired,
        public bool $targetWritesPermitted,
    ) {}
}
