<?php

namespace App\Services\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\IdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use RuntimeException;

final readonly class InstallationSessionCapability
{
    private function __construct(
        public InstallationIdentityContract $installationIdentity,
        public string $auditReference,
        public array $manifestVersions,
        private string $seal,
    ) {}

    /** @param list<string> $manifestVersions */
    public static function issue(InstallationIdentityContract $installationIdentity, string $auditReference, array $manifestVersions, IdentityReferenceHasher $hasher): self
    {
        $installationIdentity->assertAuthentic($hasher);
        if ($auditReference === '' || $manifestVersions === []) {
            throw new RuntimeException('Installation session authority is incomplete.');
        }
        $payload = implode('|', [$installationIdentity->contractReference, $auditReference, ...$manifestVersions]);

        return new self($installationIdentity, $auditReference, $manifestVersions, $hasher->reference('ddl_installation_session', $payload));
    }

    public function assertAuthentic(string $version, PhysicalServerIdentityVerification $identity, IdentityReferenceHasher $hasher): void
    {
        $this->installationIdentity->assertAuthentic($hasher);
        $payload = implode('|', [$this->installationIdentity->contractReference, $this->auditReference, ...$this->manifestVersions]);
        if (! hash_equals($identity->approvedIdentityReference, $this->installationIdentity->basePhysicalIdentityReference)
            || ! hash_equals($identity->connectionInstanceReference, $this->installationIdentity->connectionInstanceReference)
            || ! in_array($version, $this->manifestVersions, true)
            || ! isset($this->installationIdentity->manifestPayloadHashes[$version])
            || ! hash_equals($this->seal, $hasher->reference('ddl_installation_session', $payload))) {
            throw new RuntimeException('Installation session capability is invalid.');
        }
    }
}
