<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final readonly class RunManifest
{
    /** @param array<string, string> $versions */
    public function __construct(
        public string $runToken,
        public string $sourceSnapshotId,
        public string $targetSnapshotId,
        public string $cohortManifestHash,
        public string $contractBundleHash,
        public string $configurationFingerprint,
        public array $versions,
        public array $authorityReferences = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'manifest_version' => '3.0.0',
            'run_token' => $this->runToken,
            'source_snapshot_id' => $this->sourceSnapshotId,
            'target_snapshot_id' => $this->targetSnapshotId,
            'cohort_manifest_hash' => $this->cohortManifestHash,
            'contract_bundle_hash' => $this->contractBundleHash,
            'configuration_fingerprint' => $this->configurationFingerprint,
            'versions' => $this->versions,
            'authority_references' => $this->authorityReferences,
            'contains_raw_identifiers' => false,
        ];
    }
}
