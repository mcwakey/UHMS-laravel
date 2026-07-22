<?php

namespace App\Services\LegacyMigration\Foundation\Security;

use DateTimeImmutable;

final class RetentionPolicy
{
    public function __construct(
        public readonly string $policyReference,
        public readonly string $accessClassification,
        public readonly string $retentionClassification,
        public readonly int $minimumRetentionDays,
        public readonly bool $legalHold,
        public readonly bool $operationalHold,
        public readonly DateTimeImmutable $reviewAt,
        public readonly bool $purgeEnabled,
        public readonly ?string $purgeAuthority,
        public readonly bool $ownerApproved,
        public readonly bool $retainTombstone,
    ) {
        if ($this->policyReference === '' || $this->accessClassification === '' || $this->retentionClassification === '' || $this->minimumRetentionDays < 0) {
            throw SecurityConfigurationException::forCode('LM-SEC-RETENTION-POLICY-001');
        }
        if ($this->purgeEnabled && (! $this->ownerApproved || $this->purgeAuthority === null || $this->purgeAuthority === '')) {
            throw SecurityConfigurationException::forCode('LM-SEC-RETENTION-AUTHORITY-001');
        }
    }
}
