<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use DateTimeImmutable;

final class RemediationCandidate
{
    public function __construct(
        public readonly string $remediationReference,
        public readonly string $sourceTokenReference,
        public readonly string $fieldRule,
        public readonly string $valueFingerprint,
        public readonly int $precedenceOrdinal,
        public readonly bool $approved,
        public readonly bool $revoked,
        public readonly bool $conflicted,
        public readonly ?string $supersededByReference,
        public readonly DateTimeImmutable $approvedAt,
        public readonly ?DateTimeImmutable $expiresAt,
        public readonly int $runId,
        public readonly int $sourceSnapshotId,
        public readonly ?int $targetSnapshotId,
        public readonly string $authorityEvidenceReference,
        public readonly EnvelopeVerifiedIntegrityAuthority $integrityAuthority,
    ) {
        if ($this->remediationReference === '' || $this->sourceTokenReference === '' || $this->fieldRule === '' || $this->authorityEvidenceReference === '' || $this->precedenceOrdinal < 1) {
            throw ProtectedStoreAccessDeniedException::forCode('LM-SEC-REMEDIATION-SHAPE-001');
        }
    }
}
