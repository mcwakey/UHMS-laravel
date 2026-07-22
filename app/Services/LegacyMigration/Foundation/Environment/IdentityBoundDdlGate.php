<?php

namespace App\Services\LegacyMigration\Foundation\Environment;

final class IdentityBoundDdlGate
{
    public function __construct(private readonly IdentityReferenceHasher $hasher) {}

    /** @template T @param callable(): T $operation @return T */
    public function execute(PhysicalServerIdentityVerification $verification, callable $operation): mixed
    {
        $this->assertVerified($verification);

        return $operation();
    }

    public function assertVerified(PhysicalServerIdentityVerification $verification): void
    {
        if ($verification->approvedIdentityReference === ''
            || $verification->structuralIdentityReference === ''
            || ! $verification->isAuthentic($this->hasher)) {
            throw new FoundationGuardException('FOUNDATION_DDL_IDENTITY_REQUIRED', 'Foundation DDL requires verified physical target identity.');
        }
    }
}
