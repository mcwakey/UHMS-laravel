<?php

namespace App\Services\LegacyMigration\Foundation\Validation;

use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;

final readonly class ExistingTargetEvidence
{
    public function __construct(
        public string $evidenceVersion,
        public string $contractVersion,
        public string $runToken,
        public string $targetSnapshotId,
        public string $domain,
        public ProtectedToken $targetReference,
        public ProtectedToken $lockEvidence,
        public ProtectedToken $lineageEvidence,
        public bool $targetExists,
        public bool $softDeleted,
        public bool $mergedOrRedirected,
        public ProtectedToken $beforeState,
        public ProtectedToken $afterState,
        public int $intendedTargetMutations,
        public int $observedTargetMutations,
    ) {}
}
