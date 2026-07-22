<?php

namespace App\Services\LegacyMigration\Foundation\Security;

interface ProtectedRecordRotationAuthority
{
    /** @param array<string,CanonicalMessage> $canonicalMessages */
    public function approve(
        ProtectedStoreOperationContext $context,
        ProtectedRecordEnvelope $oldEnvelope,
        array $canonicalMessages,
        string $reason,
    ): ApprovedProtectedRotation;
}
