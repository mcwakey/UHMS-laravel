<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final readonly class VerifiedEnvironmentAuthority implements VerifiedRunAuthority
{
    private function __construct(private string $reference) {}

    public static function fromSnapshots(
        SnapshotManifest $source,
        SnapshotManifest $target,
        SnapshotManifestIntegrityService $integrity,
    ): self {
        if (! $integrity->verify($source) || ! $integrity->verify($target)
            || $source->authority !== 'authoritative_direct_capture'
            || $target->authority !== 'authoritative_direct_capture'
            || ! hash_equals($source->configurationFingerprint, $target->configurationFingerprint)
            || preg_match('/\A[a-f0-9]{64}\z/', $source->authorityReference) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/', $target->authorityReference) !== 1) {
            throw new SnapshotException('FOUNDATION_ENVIRONMENT_AUTHORITY_INVALID', 'Snapshot environment authority is not verified.');
        }

        return new self((new CanonicalManifestHasher)->hash([
            'authority' => 'verified_capture_environment',
            'configuration' => $source->configurationFingerprint,
            'source_account' => $source->authorityReference,
            'physical_target' => $target->authorityReference,
        ]));
    }

    public function reference(): string
    {
        return $this->reference;
    }
}
