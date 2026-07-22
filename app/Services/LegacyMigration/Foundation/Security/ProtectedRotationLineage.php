<?php

namespace App\Services\LegacyMigration\Foundation\Security;

final class ProtectedRotationLineage
{
    public function __construct(
        public readonly string $oldTokenReference,
        public readonly string $newTokenReference,
        public readonly string $oldKeyId,
        public readonly string $oldKeyVersion,
        public readonly string $newKeyId,
        public readonly string $newKeyVersion,
        public readonly string $reason,
        public readonly string $authorityReference,
        public readonly ?int $runId,
        public readonly ?int $sourceSnapshotId,
        public readonly ?int $targetSnapshotId,
        public readonly bool $oldVerified,
        public readonly bool $newVerified,
        public readonly string $state,
    ) {
        if ($this->oldTokenReference === '' || $this->newTokenReference === '' || hash_equals($this->oldTokenReference, $this->newTokenReference)
            || ! $this->oldVerified || ! $this->newVerified || $this->state !== 'verified') {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-LINEAGE-001');
        }
    }
}
