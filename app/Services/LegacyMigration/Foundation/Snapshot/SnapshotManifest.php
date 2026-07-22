<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

final readonly class SnapshotManifest
{
    /**
     * @param array<string, string> $queryHashes
     * @param array<string, string> $setHashes
     */
    public function __construct(
        public string $snapshotId,
        public string $kind,
        public string $runToken,
        public string $capturedAtUtc,
        public string $schemaFingerprint,
        public string $databaseVersion,
        public string $configurationFingerprint,
        public string $contractBundleHash,
        public array $queryHashes,
        public array $setHashes,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'manifest_version' => '3.0.0',
            'snapshot_id' => $this->snapshotId,
            'kind' => $this->kind,
            'run_token' => $this->runToken,
            'captured_at_utc' => $this->capturedAtUtc,
            'schema_fingerprint' => $this->schemaFingerprint,
            'database_version' => $this->databaseVersion,
            'configuration_fingerprint' => $this->configurationFingerprint,
            'contract_bundle_hash' => $this->contractBundleHash,
            'query_hashes' => $this->queryHashes,
            'set_hashes' => $this->setHashes,
            'contains_raw_identifiers' => false,
            'database_writes' => 0,
        ];
    }
}
