<?php

namespace App\Services\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\GuardConfiguration;
use App\Services\LegacyMigration\Foundation\Environment\MetadataConnection;
use App\Services\LegacyMigration\Foundation\Environment\SchemaFingerprintService;
use App\Services\LegacyMigration\Foundation\Environment\SourceAccountVerifier;
use DateTimeImmutable;
use DateTimeZone;

final class AuthoritativeSnapshotCapture
{
    private const SOURCE_QUERIES = [
        'source_transaction_coordinate' => 'SELECT @@global.gtid_binlog_pos AS source_coordinate, @@global.log_bin AS binary_logging_enabled, @@session.tx_isolation AS transaction_isolation, @@session.tx_read_only AS transaction_read_only',
        'patient_root' => 'SELECT `PAT_ID`, `PatientName`, `OpdNo`, `Sex`, `DOB`, `PhoneNo`, `Work`, `Company`, `Address`, `NOK`, `NOKPhoneNo`, `NOKRel`, `Religion`, `MaritalStatus`, `BillStatus`, `Refill`, `Allergies`, `Medication`, `History`, `LastVisit`, `EditDate`, `RegDate`, `OriginalName`, `OriginalOpd` FROM `uuhms`.`patients` ORDER BY `PAT_ID`',
        'patient_children' => 'SELECT `ATT_ID`, `PAT_ID`, `AttDate` FROM `uuhms`.`attendance` ORDER BY `ATT_ID`',
        'insurance' => 'SELECT `INS_ID`, `PAT_ID`, `InsType`, `Scheme`, `MemberNo`, `Company`, `IssueDate`, `ExpiryDate`, `Plan` FROM `uuhms`.`insurance` ORDER BY `INS_ID`',
    ];

    private const TARGET_QUERIES = [
        'target_transaction_coordinate' => 'SELECT @@global.gtid_binlog_pos AS target_coordinate, @@global.log_bin AS binary_logging_enabled, @@session.tx_isolation AS transaction_isolation, @@session.tx_read_only AS transaction_read_only',
        'patient_namespace' => 'SELECT `id`, `patient_number`, `updated_at`, `deleted_at` FROM `patients` ORDER BY `id`',
        'archive_namespace' => 'SELECT `id`, `patient_id`, `patient_number`, `updated_at` FROM `archived_patients` ORDER BY `id`',
        'alias_namespace' => 'SELECT `id`, `patient_id`, `alias_type`, `normalized_alias_value`, `updated_at` FROM `patient_aliases` ORDER BY `id`',
        'contact_sets' => 'SELECT `id`, `patient_id`, `is_primary`, `updated_at` FROM `emergency_contacts` ORDER BY `id`',
        'insurance_memberships' => 'SELECT `id`, `patient_id`, `insurance_provider_id`, `membership_number`, `updated_at` FROM `patient_insurances` ORDER BY `id`',
        'provider_state' => 'SELECT `id`, `name`, `updated_at` FROM `insurance_providers` ORDER BY `id`',
        'number_configuration' => 'SELECT `id`, `prefix`, `period_type`, `period_key`, `last_sequence`, `updated_at` FROM `patient_number_sequences` ORDER BY `id`',
        'foundation_schema' => "SELECT `TABLE_NAME`, `TABLE_ROWS`, `UPDATE_TIME` FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'legacy_migration\\_%' ESCAPE '\\\\' ORDER BY `TABLE_NAME`",
        'row_schema' => "SELECT `TABLE_NAME`, `COLUMN_NAME`, `ORDINAL_POSITION`, `COLUMN_TYPE`, `IS_NULLABLE`, `COLUMN_DEFAULT`, `EXTRA` FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('patients','archived_patients','patient_aliases','emergency_contacts','patient_insurances','insurance_providers','patient_number_sequences') ORDER BY `TABLE_NAME`, `ORDINAL_POSITION`",
    ];

    public function __construct(
        private readonly CaptureResultProtector $protector,
        private readonly SnapshotManifestIntegrityService $integrity,
        private readonly SchemaFingerprintService $fingerprints = new SchemaFingerprintService,
        private readonly CanonicalManifestHasher $hasher = new CanonicalManifestHasher,
    ) {}

    public function captureSource(
        MetadataConnection $connection,
        string $runToken,
        string $configurationFingerprint,
        string $contractBundleHash,
        string $toolCodeVersion,
    ): SnapshotManifest {
        $this->assertActive($connection);
        $verification = (new SourceAccountVerifier)->verify($connection);
        $schema = $this->fingerprints->inspectSource($connection, GuardConfiguration::SOURCE_DATABASE);
        $captured = $this->captureQueries($connection, self::SOURCE_QUERIES);
        $authorityReference = $this->hasher->hash([
            'scope' => 'exact_uuhms',
            'privileges' => $verification->privileges,
            'surfaces' => $verification->inspectedSurfaces,
            'select_coverage_table_count' => $verification->selectCoverageTableCount,
            'select_coverage_mode' => $verification->selectCoverageMode,
            'query_manifest_identity' => $this->hasher->hash(self::SOURCE_QUERIES),
            'tool_code_version' => $toolCodeVersion,
            'tool_code_hash' => hash_file('sha256', __FILE__),
            'transaction' => 'repeatable_read_read_only',
        ]);

        return $this->manifest('coordinated_source', $runToken, $schema, $configurationFingerprint, $contractBundleHash, $captured, $authorityReference);
    }

    public function captureTarget(
        MetadataConnection $connection,
        PhysicalIdentityTargetSnapshotAuthority $authority,
        string $runToken,
        string $configurationFingerprint,
        string $contractBundleHash,
    ): SnapshotManifest {
        $this->assertActive($connection);
        if ($connection->name() === GuardConfiguration::SOURCE_CONNECTION
            || in_array(strtolower($connection->configuredDatabase()), ['uuhms', 'uhms', 'uuhmss'], true)
            || preg_match('/(^|[_-])(prod|production)([_-]|$)/i', $connection->configuredDatabase()) === 1) {
            throw new SnapshotException('FOUNDATION_TARGET_SNAPSHOT_COORDINATE_INVALID', 'The target snapshot coordinate is forbidden.');
        }
        $schema = $this->fingerprints->inspectTarget($connection, $connection->configuredDatabase());
        $physicalIdentityReference = $this->hasher->assertDigest(
            $authority->verify($connection, $schema, $configurationFingerprint),
            'physical target identity',
        );
        $captured = $this->captureQueries($connection, self::TARGET_QUERIES);

        return $this->manifest('target_collision', $runToken, $schema, $configurationFingerprint, $contractBundleHash, $captured, $physicalIdentityReference);
    }

    /** @param array<string, string> $queries @return array{query_hashes:array<string,string>,protected_sets:array<string,string>,set_counts:array<string,int>} */
    private function captureQueries(MetadataConnection $connection, array $queries): array
    {
        $queryHashes = [];
        $sets = [];
        $counts = [];
        foreach ($queries as $id => $sql) {
            $rows = $connection->select($sql);
            $queryHashes[$id] = hash('sha256', $sql);
            $sets[$id] = $this->protector->protect($id, $rows);
            $counts[$id] = count($rows);
        }

        return ['query_hashes' => $queryHashes, 'protected_sets' => $sets, 'set_counts' => $counts];
    }

    /** @param array{query_hashes:array<string,string>,protected_sets:array<string,string>,set_counts:array<string,int>} $captured */
    private function manifest(string $kind, string $runToken, object $schema, string $configurationFingerprint, string $contractBundleHash, array $captured, string $authorityReference): SnapshotManifest
    {
        $capturedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.uP');
        $captureNonceReference = hash('sha256', random_bytes(32));
        $identity = [
            'kind' => $kind,
            'run_token' => $this->hasher->assertDigest($runToken, 'run token'),
            'schema_fingerprint' => $this->hasher->assertDigest($schema->fingerprint, 'schema'),
            'database_version' => $schema->databaseVersion,
            'configuration_fingerprint' => $this->hasher->assertDigest($configurationFingerprint, 'configuration'),
            'contract_bundle_hash' => $this->hasher->assertDigest($contractBundleHash, 'contract bundle'),
            'query_hashes' => $captured['query_hashes'],
            'protected_set_tokens' => $captured['protected_sets'],
            'set_counts' => $captured['set_counts'],
            'captured_at_utc' => $capturedAt,
            'capture_nonce_reference' => $captureNonceReference,
            'authority_reference' => $authorityReference,
        ];

        return $this->integrity->seal(new SnapshotManifest(
            snapshotId: $this->hasher->hash($identity),
            kind: $kind,
            runToken: $identity['run_token'],
            capturedAtUtc: $capturedAt,
            schemaFingerprint: $identity['schema_fingerprint'],
            databaseVersion: $schema->databaseVersion,
            configurationFingerprint: $identity['configuration_fingerprint'],
            contractBundleHash: $identity['contract_bundle_hash'],
            queryHashes: $captured['query_hashes'],
            setHashes: [],
            authority: 'authoritative_direct_capture',
            authorityReference: $authorityReference,
            protectedSetTokens: $captured['protected_sets'],
            setCounts: $captured['set_counts'],
            schemaTableCount: $schema->tableCount,
            schemaColumnCount: $schema->columnCount,
            captureNonceReference: $captureNonceReference,
        ));
    }

    private function assertActive(MetadataConnection $connection): void
    {
        if (! $connection->readOnlySnapshotActive()) {
            throw new SnapshotException('FOUNDATION_SNAPSHOT_REQUIRED', 'An active read-only snapshot is required.');
        }
    }
}
