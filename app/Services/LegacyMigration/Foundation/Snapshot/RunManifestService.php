<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final class RunManifestService
{
    public function __construct(private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher) {}

    /** @param array<string, string> $versions */
    public function create(
        SnapshotManifest $source,
        SnapshotManifest $target,
        string $cohortManifestHash,
        string $contractBundleHash,
        string $configurationFingerprint,
        array $versions,
    ): RunManifest {
        if ($source->kind !== 'coordinated_source' || $target->kind !== 'target_collision') {
            throw new SnapshotException('FOUNDATION_RUN_SNAPSHOTS_INVALID', 'A run requires one source and one target-collision snapshot.');
        }
        if (! hash_equals($source->runToken, $target->runToken)) {
            throw new SnapshotException('FOUNDATION_RUN_TOKEN_MISMATCH', 'The source and target snapshots do not belong to the same run.');
        }
        $cohortManifestHash = $this->hasher->assertDigest($cohortManifestHash, 'cohort manifest');
        $contractBundleHash = $this->hasher->assertDigest($contractBundleHash, 'contract bundle');
        $configurationFingerprint = $this->hasher->assertDigest($configurationFingerprint, 'configuration');
        if (! hash_equals($contractBundleHash, $source->contractBundleHash)
            || ! hash_equals($contractBundleHash, $target->contractBundleHash)
            || ! hash_equals($configurationFingerprint, $source->configurationFingerprint)
            || ! hash_equals($configurationFingerprint, $target->configurationFingerprint)) {
            throw new SnapshotException('FOUNDATION_RUN_COORDINATE_MISMATCH', 'Run manifest coordinates are inconsistent.');
        }
        if ($versions === []) {
            throw new SnapshotException('FOUNDATION_RUN_VERSIONS_INCOMPLETE', 'The run manifest version bundle is incomplete.');
        }
        ksort($versions, SORT_STRING);
        foreach ($versions as $name => $version) {
            if (! is_string($name) || trim($name) === '' || ! is_string($version) || trim($version) === '') {
                throw new SnapshotException('FOUNDATION_RUN_VERSIONS_INCOMPLETE', 'The run manifest version bundle is incomplete.');
            }
        }

        return new RunManifest(
            runToken: $source->runToken,
            sourceSnapshotId: $source->snapshotId,
            targetSnapshotId: $target->snapshotId,
            cohortManifestHash: $cohortManifestHash,
            contractBundleHash: $contractBundleHash,
            configurationFingerprint: $configurationFingerprint,
            versions: $versions,
        );
    }
}
