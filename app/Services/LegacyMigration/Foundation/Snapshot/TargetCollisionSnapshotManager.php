<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\SchemaObservation;
use DateTimeImmutable;
use DateTimeZone;

final class TargetCollisionSnapshotManager
{
    private const REQUIRED_SETS = [
        'patient_namespace',
        'alias_namespace',
        'contact_sets',
        'insurance_memberships',
        'row_schema',
        'number_configuration',
    ];

    public function __construct(private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher) {}

    /**
     * @param array<string, string> $queryHashes
     * @param array<string, string> $setHashes
     */
    public function create(
        string $runToken,
        SchemaObservation $target,
        string $configurationFingerprint,
        string $contractBundleHash,
        array $queryHashes,
        array $setHashes,
        DateTimeImmutable $capturedAt,
    ): SnapshotManifest {
        if (in_array(strtolower($target->database), ['uuhms', 'uhms', 'uuhmss'], true)
            || preg_match('/(^|[_-])(prod|production)([_-]|$)/i', $target->database) === 1) {
            throw new SnapshotException('FOUNDATION_TARGET_SNAPSHOT_COORDINATE_INVALID', 'The target snapshot coordinate is forbidden.');
        }
        if ($target->connection === GuardConfiguration::SOURCE_CONNECTION) {
            throw new SnapshotException('FOUNDATION_TARGET_SNAPSHOT_COORDINATE_INVALID', 'The target snapshot cannot use the Classic source connection.');
        }

        $runToken = $this->hasher->assertDigest($runToken, 'run token');
        $configurationFingerprint = $this->hasher->assertDigest($configurationFingerprint, 'configuration');
        $contractBundleHash = $this->hasher->assertDigest($contractBundleHash, 'contract bundle');
        $queryHashes = $this->validatedMap($queryHashes, 'query');
        $setHashes = $this->validatedMap($setHashes, 'protected target set');
        foreach (self::REQUIRED_SETS as $required) {
            if (! isset($setHashes[$required])) {
                throw new SnapshotException('FOUNDATION_TARGET_SNAPSHOT_INCOMPLETE', 'The target-collision snapshot is incomplete.');
            }
        }
        if ($queryHashes === []) {
            throw new SnapshotException('FOUNDATION_TARGET_SNAPSHOT_INCOMPLETE', 'The target-collision snapshot has no pinned query identities.');
        }
        $identity = [
            'kind' => 'target_collision',
            'run_token' => $runToken,
            'schema_fingerprint' => $this->hasher->assertDigest($target->fingerprint, 'schema'),
            'database_version' => $target->databaseVersion,
            'configuration_fingerprint' => $configurationFingerprint,
            'contract_bundle_hash' => $contractBundleHash,
            'query_hashes' => $queryHashes,
            'set_hashes' => $setHashes,
        ];

        return new SnapshotManifest(
            snapshotId: $this->hasher->hash($identity),
            kind: 'target_collision',
            runToken: $runToken,
            capturedAtUtc: $capturedAt->setTimezone(new DateTimeZone('UTC'))->format(DATE_ATOM),
            schemaFingerprint: $identity['schema_fingerprint'],
            databaseVersion: $target->databaseVersion,
            configurationFingerprint: $configurationFingerprint,
            contractBundleHash: $contractBundleHash,
            queryHashes: $queryHashes,
            setHashes: $setHashes,
        );
    }

    public function assertCurrent(SnapshotManifest $pinned, SnapshotManifest $current): void
    {
        if ($pinned->kind !== 'target_collision'
            || $current->kind !== 'target_collision'
            || ! hash_equals($pinned->snapshotId, $current->snapshotId)) {
            throw new SnapshotException('FOUNDATION_TARGET_SNAPSHOT_DRIFT', 'Target row, schema, or configuration drift requires a refreshed collision snapshot.');
        }
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
