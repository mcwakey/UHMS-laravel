<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use DateTimeImmutable;
use DateTimeZone;

final class CoordinatedSourceSnapshotManager
{
    private const REQUIRED_SETS = ['patient_root', 'patient_children', 'insurance'];

    public function __construct(private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher) {}

    /**
     * @param array<string, string> $queryHashes
     * @param array<string, string> $setHashes
     */
    public function create(
        string $runToken,
        SchemaObservation $source,
        string $configurationFingerprint,
        string $contractBundleHash,
        array $queryHashes,
        array $setHashes,
        DateTimeImmutable $capturedAt,
    ): SnapshotManifest {
        if ($source->connection !== GuardConfiguration::SOURCE_CONNECTION
            || $source->database !== GuardConfiguration::SOURCE_DATABASE) {
            throw new SnapshotException('FOUNDATION_SOURCE_SNAPSHOT_COORDINATE_INVALID', 'The source snapshot coordinate is not approved.');
        }

        return $this->build(
            'coordinated_source',
            $runToken,
            $source,
            $configurationFingerprint,
            $contractBundleHash,
            $queryHashes,
            $setHashes,
            self::REQUIRED_SETS,
            $capturedAt,
        );
    }

    public function assertUnchanged(SnapshotManifest $pinned, SnapshotManifest $current): void
    {
        if ($pinned->kind !== 'coordinated_source'
            || $current->kind !== 'coordinated_source'
            || ! hash_equals($pinned->snapshotId, $current->snapshotId)) {
            throw new SnapshotException('FOUNDATION_SOURCE_SNAPSHOT_DRIFT', 'Source snapshot drift invalidates the snapshot and all descendants.');
        }
    }

    /**
     * @param array<string, string> $queryHashes
     * @param array<string, string> $setHashes
     * @param array<int, string> $requiredSets
     */
    private function build(
        string $kind,
        string $runToken,
        SchemaObservation $schema,
        string $configurationFingerprint,
        string $contractBundleHash,
        array $queryHashes,
        array $setHashes,
        array $requiredSets,
        DateTimeImmutable $capturedAt,
    ): SnapshotManifest {
        $runToken = $this->hasher->assertDigest($runToken, 'run token');
        $configurationFingerprint = $this->hasher->assertDigest($configurationFingerprint, 'configuration');
        $contractBundleHash = $this->hasher->assertDigest($contractBundleHash, 'contract bundle');
        $queryHashes = $this->validatedMap($queryHashes, 'query');
        $setHashes = $this->validatedMap($setHashes, 'protected set');
        foreach ($requiredSets as $required) {
            if (! isset($setHashes[$required])) {
                throw new SnapshotException('FOUNDATION_SOURCE_SNAPSHOT_INCOMPLETE', 'The coordinated source snapshot is incomplete.');
            }
        }
        if ($queryHashes === []) {
            throw new SnapshotException('FOUNDATION_SOURCE_SNAPSHOT_INCOMPLETE', 'The coordinated source snapshot has no pinned query identities.');
        }
        $identity = [
            'kind' => $kind,
            'run_token' => $runToken,
            'schema_fingerprint' => $this->hasher->assertDigest($schema->fingerprint, 'schema'),
            'database_version' => $schema->databaseVersion,
            'configuration_fingerprint' => $configurationFingerprint,
            'contract_bundle_hash' => $contractBundleHash,
            'query_hashes' => $queryHashes,
            'set_hashes' => $setHashes,
        ];

        return new SnapshotManifest(
            snapshotId: $this->hasher->hash($identity),
            kind: $kind,
            runToken: $runToken,
            capturedAtUtc: $capturedAt->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            schemaFingerprint: $identity['schema_fingerprint'],
            databaseVersion: $schema->databaseVersion,
            configurationFingerprint: $configurationFingerprint,
            contractBundleHash: $contractBundleHash,
            queryHashes: $queryHashes,
            setHashes: $setHashes,
        );
    }

    /** @param array<string, string> $hashes @return array<string, string> */
    private function validatedMap(array $hashes, string $field): array
    {
        ksort($hashes, SORT_STRING);
        foreach ($hashes as $name => $hash) {
            if (! is_string($name) || trim($name) === '' || ! is_string($hash)) {
                throw new SnapshotException('FOUNDATION_MANIFEST_INVALID', "A {$field} manifest entry is invalid.");
            }
            $hashes[$name] = $this->hasher->assertDigest($hash, $field);
        }

        return $hashes;
    }
}
