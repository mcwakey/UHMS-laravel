<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;

final class RunManifestService
{
    public function __construct(
        private readonly SnapshotManifestIntegrityService $integrity,
        private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher,
    ) {}

    /** @param array<string, string> $versions */
    public function create(
        SnapshotManifest $source,
        SnapshotManifest $target,
        VerifiedPolicyBundle $policyBundle,
        string $configurationFingerprint,
        VerifiedRunPrerequisites $prerequisites,
        array $versions,
    ): RunManifest {
        if ($source->kind !== 'coordinated_source' || $target->kind !== 'target_collision') {
            throw new SnapshotException('FOUNDATION_RUN_SNAPSHOTS_INVALID', 'A run requires one source and one target-collision snapshot.');
        }
        if ($source->authority !== 'authoritative_direct_capture'
            || $target->authority !== 'authoritative_direct_capture'
            || preg_match('/\A[a-f0-9]{64}\z/', $source->authorityReference) !== 1
            || preg_match('/\A[a-f0-9]{64}\z/', $target->authorityReference) !== 1
            || $source->protectedSetTokens === []
            || $target->protectedSetTokens === []
            || ! $this->integrity->verify($source)
            || ! $this->integrity->verify($target)) {
            throw new SnapshotException('FOUNDATION_RUN_CALLER_ASSERTED_EVIDENCE_REJECTED', 'A run requires authoritative direct capture evidence.');
        }
        if (! hash_equals($source->runToken, $target->runToken)) {
            throw new SnapshotException('FOUNDATION_RUN_TOKEN_MISMATCH', 'The source and target snapshots do not belong to the same run.');
        }
        $references = $prerequisites->references();
        $cohortManifestHash = $references['cohortAuthorityReference'];
        $contractBundleHash = $policyBundle->bundleHash;
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
        $versions['authoritative_policy_bundle'] = $policyBundle->version;
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
            authorityReferences: $references,
        );
    }
}
