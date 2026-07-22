<?php

namespace App\Services\LegacyMigration\Foundation\Security;

/** Explicit fail-closed binding when no approved rotation configuration exists. */
final class UnavailableProtectedRecordRotationAuthority implements ProtectedRecordRotationAuthority
{
    public function approve(
        ProtectedStoreOperationContext $context,
        ProtectedRecordEnvelope $oldEnvelope,
        array $canonicalMessages,
        string $reason,
    ): ApprovedProtectedRotation {
        throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-ROTATION-AUTHORITY-MISSING-001');
    }
}
