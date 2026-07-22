<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use DateTimeImmutable;

final class RetentionPurgeGate
{
    public function assertEligible(
        RetentionPolicy $policy,
        string $accessClassification,
        string $retentionClassification,
        DateTimeImmutable $recordedAt,
        DateTimeImmutable $requestedAt,
        bool $integrityVerified,
        bool $businessLineageWouldBeDestroyed,
    ): void {
        if (! $policy->ownerApproved || ! $policy->purgeEnabled || $policy->legalHold || $policy->operationalHold) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-POLICY-BLOCKED-001');
        }
        if (! hash_equals($policy->accessClassification, $accessClassification)
            || ! hash_equals($policy->retentionClassification, $retentionClassification)) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-CLASSIFICATION-001');
        }
        if ($requestedAt < $policy->reviewAt || $requestedAt < $recordedAt->modify('+'.$policy->minimumRetentionDays.' days')) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-TOO-EARLY-001');
        }
        if (! $integrityVerified || $businessLineageWouldBeDestroyed) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-STORE-PURGE-INTEGRITY-001');
        }
    }
}
