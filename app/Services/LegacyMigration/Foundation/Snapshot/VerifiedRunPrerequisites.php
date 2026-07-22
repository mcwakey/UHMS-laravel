<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final readonly class VerifiedRunPrerequisites
{
    private function __construct(
        public string $cohortAuthorityReference,
        public string $hmacAuthorityReference,
        public string $environmentAuthorityReference,
        public string $remediationAuthorityReference,
        public string $targetStatePolicyAuthorityReference,
    ) {}

    public static function fromAuthorities(
        VerifiedCohortAuthority $cohort,
        VerifiedHmacAuthority $hmac,
        VerifiedEnvironmentAuthority $environment,
        VerifiedRemediationAuthority $remediation,
        VerifiedTargetStatePolicyAuthority $targetStatePolicy,
    ): self {
        return new self(
            $cohort->reference(),
            $hmac->reference(),
            $environment->reference(),
            $remediation->reference(),
            $targetStatePolicy->reference(),
        );
    }

    /** @return array<string,string> */
    public function references(): array
    {
        return get_object_vars($this);
    }
}
