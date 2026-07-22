<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\MetadataConnection;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Storage\RunManifestRepository;
use App\Services\LegacyMigration\Foundation\Storage\SnapshotRepository;
use App\Services\LegacyMigration\Foundation\Validation\VerifiedPolicyBundle;
use Illuminate\Support\Facades\DB;

/** The sole supported authority path from direct capture to protected run storage. */
final class AuthoritativeRunCaptureCoordinator
{
    public function __construct(
        private readonly AuthoritativeSnapshotCapture $capture,
        private readonly SnapshotManifestIntegrityService $snapshotIntegrity,
        private readonly RunManifestService $manifests,
        private readonly RunManifestRepository $runs,
        private readonly SnapshotRepository $snapshots,
        private readonly HmacTokenService $hmac,
        private readonly CanonicalTypedMessageEncoder $encoder,
        private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher,
    ) {}

    public function captureAndPersist(
        MetadataConnection $sourceConnection,
        MetadataConnection $targetConnection,
        PhysicalIdentityTargetSnapshotAuthority $targetAuthority,
        VerifiedPolicyBundle $policyBundle,
        VerifiedCaptureConfiguration $configuration,
    ): AuthoritativeRunCaptureResult {
        $this->assertConnections($sourceConnection, $targetConnection, $configuration);
        $runToken = $this->token('migration_run', 'run', [
            $policyBundle->bundleHash,
            $configuration->fingerprint,
            bin2hex(random_bytes(32)),
        ]);
        $runDigest = ProtectedToken::parse($runToken)->lookupDigest();

        $sourceConnection->beginReadOnlySnapshot();
        try {
            $targetConnection->beginReadOnlySnapshot();
            try {
                $source = $this->capture->captureSource(
                    $sourceConnection,
                    $runDigest,
                    $configuration->fingerprint,
                    $policyBundle->bundleHash,
                    $configuration->toolVersion,
                );
                $target = $this->capture->captureTarget(
                    $targetConnection,
                    $targetAuthority,
                    $runDigest,
                    $configuration->fingerprint,
                    $policyBundle->bundleHash,
                );
            } finally {
                $targetConnection->rollbackReadOnlySnapshot();
            }
        } finally {
            $sourceConnection->rollbackReadOnlySnapshot();
        }
        $this->assertObservedCoordinates($source, $target, $configuration);

        $prerequisites = VerifiedRunPrerequisites::fromAuthorities(
            VerifiedCohortAuthority::fromPolicyBundle($policyBundle),
            VerifiedHmacAuthority::fromSnapshots($source, $target, $this->snapshotIntegrity),
            VerifiedEnvironmentAuthority::fromSnapshots($source, $target, $this->snapshotIntegrity),
            VerifiedRemediationAuthority::fromPolicyBundle($policyBundle),
            VerifiedTargetStatePolicyAuthority::fromPolicyBundle($policyBundle),
        );
        $runManifest = $this->manifests->create(
            $source,
            $target,
            $policyBundle,
            $configuration->fingerprint,
            $prerequisites,
            [
                'capture_tool' => $configuration->toolVersion,
                'snapshot' => '3.1.0',
                'transformation' => 'not-authorized',
            ],
        );

        return DB::transaction(fn (): AuthoritativeRunCaptureResult => $this->persist(
            $source,
            $target,
            $runManifest,
            $policyBundle,
            $configuration,
            $prerequisites,
            $runToken,
        ), 3);
    }

    private function persist(
        SnapshotManifest $source,
        SnapshotManifest $target,
        RunManifest $manifest,
        VerifiedPolicyBundle $bundle,
        VerifiedCaptureConfiguration $configuration,
        VerifiedRunPrerequisites $prerequisites,
        string $runToken,
    ): AuthoritativeRunCaptureResult {
        $authority = $configuration->persistenceAuthorityReference;
        $bundleToken = $this->token('migration_run', 'contract-bundle', [$bundle->bundleHash, $bundle->version]);
        $bundleParsed = ProtectedToken::parse($bundleToken);
        $bundleTokens = $this->tokenSet(['bundle_token' => [$bundleToken, 'migration_run']]);
        $bundleContext = $this->context($configuration, $authority, ['migration_run'], operations: ['read', 'write']);
        $existingBundle = $this->runs->resolveContractBundleProtected($bundleContext, $bundle->version, $bundle->bundleHash);
        if ($existingBundle === null) {
            $bundleRecord = $this->runs->appendContractBundleProtected(
                $bundleContext,
                [
                    'bundle_version' => $bundle->version,
                    'bundle_hash' => $bundle->bundleHash,
                    'canonicalization_version' => $bundleParsed->canonicalizationVersion(),
                    'token_environment' => $bundleParsed->environment(),
                    'hmac_key_id' => $bundleParsed->keyId(),
                    'hmac_key_version' => $bundleParsed->keyVersion(),
                    'encrypted_manifest' => $this->policyManifest($bundle),
                    'integrity_checksum' => $this->hasher->hash($this->policyManifest($bundle)),
                    'access_classification' => $configuration->accessClassification,
                    'retention_classification' => $configuration->retentionClassification,
                ],
                $bundleTokens,
            );
            $bundleRecordId = (int) $bundleRecord->id;
        } else {
            $bundleRecordId = $existingBundle->recordId;
        }

        $cohortToken = $this->token('migration_run', 'cohort', [$prerequisites->cohortAuthorityReference]);
        $runTokens = $this->tokenSet([
            'run_token' => [$runToken, 'migration_run'],
            'cohort_token' => [$cohortToken, 'migration_run'],
        ]);
        $runParsed = ProtectedToken::parse($runToken);
        $runRecord = $this->runs->createRunProtected(
            $this->context($configuration, $authority, ['migration_run']),
            [
                'contract_bundle_id' => $bundleRecordId,
                'mode' => 'dry_run',
                'state' => 'NOT_STARTED',
                'configuration_fingerprint' => $configuration->fingerprint,
                'source_fingerprint' => $source->schemaFingerprint,
                'target_fingerprint' => $target->schemaFingerprint,
                'canonicalization_version' => $runParsed->canonicalizationVersion(),
                'token_environment' => $runParsed->environment(),
                'hmac_key_id' => $runParsed->keyId(),
                'hmac_key_version' => $runParsed->keyVersion(),
                'encrypted_manifest' => $manifest->toArray(),
                'manifest_checksum' => $this->hasher->hash($manifest->toArray()),
                'access_classification' => $configuration->accessClassification,
                'retention_classification' => $configuration->retentionClassification,
                'started_at' => now(),
            ],
            $runTokens,
        );
        $runId = (int) $runRecord->id;
        $sourceRecord = $this->persistSnapshot($source, 'source', $runId, $configuration, $authority);
        $targetRecord = $this->persistSnapshot($target, 'target', $runId, $configuration, $authority);

        $collisionIds = [];
        foreach ($target->protectedSetTokens as $namespace => $resultToken) {
            $collisionToken = $this->token('target_collision', 'collision', [$manifest->runToken, $namespace, $target->snapshotId]);
            $coordinateToken = $this->token('target_collision', 'collision-coordinate', [$target->authorityReference, $namespace, $target->queryHashes[$namespace]]);
            $tokens = $this->tokenSet([
                'collision_snapshot_token' => [$collisionToken, 'target_collision'],
                'collision_coordinate_token' => [$coordinateToken, 'target_collision'],
            ]);
            $record = $this->snapshots->appendTargetCollisionProtected(
                $this->context($configuration, $authority, ['target_collision'], $runId, null, (int) $targetRecord->id),
                [
                    'run_id' => $runId,
                    'target_snapshot_id' => (int) $targetRecord->id,
                    'domain' => 'patient_pilot',
                    'collision_namespace' => $namespace,
                    'schema_fingerprint' => $target->schemaFingerprint,
                    'configuration_fingerprint' => $target->configurationFingerprint,
                    'row_set_hash' => ProtectedToken::parse($resultToken)->lookupDigest(),
                    'observed_count' => $target->setCounts[$namespace],
                    'contract_version' => $bundle->version,
                    'transformation_version' => 'not-authorized',
                    'canonicalization_version' => ProtectedToken::parse($collisionToken)->canonicalizationVersion(),
                    'hmac_key_version' => ProtectedToken::parse($collisionToken)->keyVersion(),
                    'state' => 'captured',
                    'encrypted_evidence' => [
                        'query_hash' => $target->queryHashes[$namespace],
                        'protected_result_token' => $resultToken,
                        'capture_authority_seal' => $target->authoritySeal,
                    ],
                    'integrity_checksum' => $this->hasher->hash([$namespace, $resultToken, $target->authoritySeal]),
                    'access_classification' => $configuration->accessClassification,
                    'retention_classification' => $configuration->retentionClassification,
                    'captured_at' => $target->capturedAtUtc,
                ],
                $tokens,
            );
            $collisionIds[$namespace] = (int) $record->id;
        }

        return new AuthoritativeRunCaptureResult(
            $bundleRecordId,
            $runId,
            (int) $sourceRecord->id,
            (int) $targetRecord->id,
            $collisionIds,
            $manifest,
            $source,
            $target,
        );
    }

    private function persistSnapshot(SnapshotManifest $manifest, string $kind, int $runId, VerifiedCaptureConfiguration $configuration, string $authority): object
    {
        $domain = $kind === 'source' ? 'source_snapshot' : 'target_snapshot';
        $snapshotToken = $this->token($domain, $kind.'-snapshot', [$manifest->runToken, $manifest->snapshotId]);
        $coordinateToken = $this->token($domain, $kind.'-coordinate', [$manifest->schemaFingerprint, $manifest->authorityReference, $manifest->capturedAtUtc]);
        $tokens = $this->tokenSet([
            'snapshot_token' => [$snapshotToken, $domain],
            'coordinate_token' => [$coordinateToken, $domain],
        ]);
        $parsed = ProtectedToken::parse($snapshotToken);

        return $this->snapshots->appendProtected(
            $this->context($configuration, $authority, [$domain], $runId),
            [
                'run_id' => $runId,
                'snapshot_kind' => $kind,
                'domain' => 'patient_pilot',
                'database_fingerprint' => $manifest->schemaFingerprint,
                'schema_fingerprint' => $manifest->schemaFingerprint,
                'query_bundle_hash' => $this->hasher->hash($manifest->queryHashes),
                'result_hash' => $this->hasher->hash($manifest->protectedSetTokens),
                'contract_version' => $manifest->contractBundleHash,
                'transformation_version' => 'not-authorized',
                'canonicalization_version' => $parsed->canonicalizationVersion(),
                'hmac_key_version' => $parsed->keyVersion(),
                'state' => 'captured',
                'encrypted_metadata' => $manifest->toArray(),
                'integrity_checksum' => $this->hasher->hash($manifest->toArray()),
                'access_classification' => $configuration->accessClassification,
                'retention_classification' => $configuration->retentionClassification,
                'captured_at' => $manifest->capturedAtUtc,
            ],
            $tokens,
        );
    }

    private function assertConnections(MetadataConnection $source, MetadataConnection $target, VerifiedCaptureConfiguration $configuration): void
    {
        $guards = $configuration->guards;
        if ($source->name() !== $guards->sourceConnection || $source->configuredDatabase() !== 'uuhms'
            || $target->name() !== $guards->targetConnection || $target->configuredDatabase() !== $guards->targetDatabase) {
            throw new SnapshotException('FOUNDATION_AUTHORITATIVE_CAPTURE_COORDINATE_INVALID', 'Capture connections do not match the verified guard configuration.');
        }
    }

    private function assertObservedCoordinates(SnapshotManifest $source, SnapshotManifest $target, VerifiedCaptureConfiguration $configuration): void
    {
        $guards = $configuration->guards;
        foreach ([
            [$source, $guards->sourceFingerprint, $guards->sourceVersion, $guards->sourceTableCount, $guards->sourceColumnCount],
            [$target, $guards->targetFingerprint, $guards->targetVersion, $guards->targetTableCount, $guards->targetColumnCount],
        ] as [$manifest, $fingerprint, $version, $tables, $columns]) {
            if (! hash_equals($fingerprint, $manifest->schemaFingerprint)
                || ! hash_equals($version, $manifest->databaseVersion)
                || $tables !== $manifest->schemaTableCount
                || $columns !== $manifest->schemaColumnCount) {
                throw new SnapshotException('FOUNDATION_AUTHORITATIVE_CAPTURE_SCHEMA_DRIFT', 'Capture schema does not match the verified guard configuration.');
            }
        }
    }

    /** @return array<string,array{encoded_token:string,domain:string}> */
    private function tokenSet(array $definitions): array
    {
        $result = [];
        foreach ($definitions as $field => [$token, $domain]) {
            $result[$field] = ['encoded_token' => $token, 'domain' => $domain];
        }

        return $result;
    }

    /** @param list<string> $values */
    private function token(string $domain, string $purpose, array $values): string
    {
        return $this->hmac->tokenize(
            new TokenDomain($domain),
            $this->encoder->encode(array_map(
                static fn (string $value) => TypedValue::string($value),
                array_merge(['authoritative-run-capture/1', $purpose], $values),
            )),
        )->encode();
    }

    /** @return array<string,mixed> */
    private function policyManifest(VerifiedPolicyBundle $bundle): array
    {
        return [
            'version' => $bundle->version,
            'bundle_hash' => $bundle->bundleHash,
            'artifacts' => array_map(static fn ($artifact): array => [
                'path' => $artifact->path,
                'sha256' => $artifact->sha256,
                'specification_version' => $artifact->specificationVersion,
                'approval_reference' => $artifact->approvalReference,
            ], $bundle->artifacts),
        ];
    }

    /** @param list<string> $domains */
    private function context(VerifiedCaptureConfiguration $configuration, string $authority, array $domains, ?int $runId = null, ?int $sourceSnapshotId = null, ?int $targetSnapshotId = null, array $operations = ['write']): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext(
            'authoritative_run_capture', $operations, $domains, $configuration->environment,
            $runId, $sourceSnapshotId, $targetSnapshotId,
            $configuration->accessClassification, $configuration->retentionClassification, $authority,
        );
    }
}
