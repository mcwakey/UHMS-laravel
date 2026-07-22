<?php

namespace App\Services\LegacyMigration\Foundation\Recovery;

final readonly class RecoveryObservation
{
    public function __construct(
        public CrashBoundary $boundary,
        public RecoveryEvidence $evidence,
    ) {}
}
