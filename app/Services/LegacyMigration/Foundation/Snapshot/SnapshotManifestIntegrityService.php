<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;

final class SnapshotManifestIntegrityService
{
    public function __construct(
        private readonly HmacTokenService $hmac,
        private readonly CanonicalTypedMessageEncoder $encoder,
        private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher,
    ) {}

    public function seal(SnapshotManifest $manifest): SnapshotManifest
    {
        $seal = $this->hmac->tokenize(
            new TokenDomain('artifact_integrity'),
            $this->encoder->encode([TypedValue::string($this->identity($manifest))]),
        )->encode();

        return new SnapshotManifest(
            $manifest->snapshotId, $manifest->kind, $manifest->runToken, $manifest->capturedAtUtc,
            $manifest->schemaFingerprint, $manifest->databaseVersion, $manifest->configurationFingerprint,
            $manifest->contractBundleHash, $manifest->queryHashes, $manifest->setHashes, $manifest->authority,
            $manifest->authorityReference, $manifest->protectedSetTokens, $seal,
            $manifest->setCounts,
            $manifest->schemaTableCount, $manifest->schemaColumnCount,
            $manifest->captureNonceReference,
        );
    }

    public function verify(SnapshotManifest $manifest): bool
    {
        try {
            return $manifest->authoritySeal !== '' && $this->hmac->verify(
                new TokenDomain('artifact_integrity'),
                $this->encoder->encode([TypedValue::string($this->identity($manifest))]),
                ProtectedToken::parse($manifest->authoritySeal),
            );
        } catch (\Throwable) {
            return false;
        }
    }

    private function identity(SnapshotManifest $manifest): string
    {
        return $this->hasher->hash([
            'snapshot_id' => $manifest->snapshotId,
            'kind' => $manifest->kind,
            'run_token' => $manifest->runToken,
            'captured_at_utc' => $manifest->capturedAtUtc,
            'schema_fingerprint' => $manifest->schemaFingerprint,
            'database_version' => $manifest->databaseVersion,
            'configuration_fingerprint' => $manifest->configurationFingerprint,
            'contract_bundle_hash' => $manifest->contractBundleHash,
            'query_hashes' => $manifest->queryHashes,
            'authority' => $manifest->authority,
            'authority_reference' => $manifest->authorityReference,
            'protected_set_tokens' => $manifest->protectedSetTokens,
            'set_counts' => $manifest->setCounts,
            'schema_table_count' => $manifest->schemaTableCount,
            'schema_column_count' => $manifest->schemaColumnCount,
            'capture_nonce_reference' => $manifest->captureNonceReference,
        ]);
    }
}
