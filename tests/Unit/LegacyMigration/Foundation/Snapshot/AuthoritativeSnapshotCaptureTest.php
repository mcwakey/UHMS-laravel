<?php

namespace Tests\Unit\LegacyMigration\Foundation\Snapshot;

use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\ObservedPhysicalServerIdentity;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalTargetIdentityContract;
use App\Services\LegacyMigration\Foundation\Environment\SchemaFingerprintService;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Snapshot\AuthoritativeSnapshotCapture;
use App\Services\LegacyMigration\Foundation\Snapshot\CaptureResultProtector;
use App\Services\LegacyMigration\Foundation\Snapshot\PhysicalIdentityTargetSnapshotAuthority;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifestIntegrityService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\LegacyMigration\Foundation\Environment\FakeMetadataConnection;

final class AuthoritativeSnapshotCaptureTest extends TestCase
{
    #[Test]
    public function source_capture_directly_verifies_privileges_schema_queries_and_protects_results(): void
    {
        $connection = new FakeMetadataConnection('legacy_uhms', 'uuhms', fn (string $sql): array => $this->sourceRows($sql));
        $connection->beginReadOnlySnapshot();

        $snapshot = $this->capture()->captureSource(
            $connection,
            str_repeat('1', 64),
            str_repeat('2', 64),
            str_repeat('3', 64),
            'phase3b-test-code/1',
        );

        self::assertSame('authoritative_direct_capture', $snapshot->authority);
        self::assertSame(['source_transaction_coordinate', 'patient_root', 'patient_children', 'insurance'], array_keys($snapshot->protectedSetTokens));
        self::assertStringStartsWith('lmt1.', $snapshot->protectedSetTokens['patient_root']);
        self::assertStringNotContainsString('SYNTHETIC-ONLY-P3B', json_encode($snapshot, JSON_THROW_ON_ERROR));
        self::assertContains('SHOW GRANTS', $connection->queries);
    }

    #[Test]
    public function target_capture_requires_direct_physical_authority_and_captures_complete_collision_sets(): void
    {
        $connection = new FakeMetadataConnection('mysql', 'uhms_clean', fn (string $sql): array => $this->targetRows($sql));
        $connection->beginReadOnlySnapshot();
        $schema = (new SchemaFingerprintService)->inspectTarget($connection, 'uhms_clean');
        $authority = $this->physicalAuthority($schema, str_repeat('2', 64));

        $snapshot = $this->capture()->captureTarget(
            $connection,
            $authority,
            str_repeat('1', 64),
            str_repeat('2', 64),
            str_repeat('3', 64),
        );

        self::assertSame('authoritative_direct_capture', $snapshot->authority);
        self::assertSame([
            'target_transaction_coordinate', 'patient_namespace', 'archive_namespace', 'alias_namespace', 'contact_sets', 'insurance_memberships',
            'provider_state', 'number_configuration', 'foundation_schema', 'row_schema',
        ], array_keys($snapshot->protectedSetTokens));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $snapshot->authorityReference);
    }

    private function capture(): AuthoritativeSnapshotCapture
    {
        $versions = new CanonicalizationVersionRegistry;
        $hmac = new HmacTokenService(new ConfiguredKeyProvider([
            'key_id' => 'synthetic-p3b',
            'key_version' => 'v1',
            'key' => '789abcdef0123456BCDEFA!@#$%^&*()-+=0123456',
            'algorithm' => 'sha256',
        ]), $versions, 'testing');

        $encoder = new CanonicalTypedMessageEncoder($versions);

        return new AuthoritativeSnapshotCapture(
            new CaptureResultProtector($hmac, $encoder),
            new SnapshotManifestIntegrityService($hmac, $encoder),
        );
    }

    private function physicalAuthority(object $schema, string $configurationFingerprint): PhysicalIdentityTargetSnapshotAuthority
    {
        $hasher = new HmacIdentityReferenceHasher('testing', 'identity-key', 'v1', '789abcdef0123456BCDEFA!@#$%^&*()-+=identity');
        $host = $hasher->reference('host', 'synthetic-host');
        $server = $hasher->reference('server', 'synthetic-server');
        $network = $hasher->reference('network', 'synthetic-network');
        $foundation = $hasher->reference('foundation', 'synthetic-foundation');
        $contract = new PhysicalTargetIdentityContract(
            'synthetic-physical/1', 'mysql', 'uhms_clean', 'mysql', $schema->databaseVersion,
            $host, 3306, false, null, false, null, $server, 'disposable_non_production', $network,
            'synthetic-attestation/1', $schema->fingerprint, $schema->tableCount, $schema->columnCount,
            $foundation, $configurationFingerprint, 'synthetic-owner-approval',
        );
        $observer = new class($host, $server, $network, $schema->databaseVersion) implements PhysicalServerIdentityObserver
        {
            public function __construct(private string $host, private string $server, private string $network, private string $version) {}

            public function observe(): ObservedPhysicalServerIdentity
            {
                return new ObservedPhysicalServerIdentity('mysql', 'uhms_clean', 'mysql', $this->version, $this->host, 3306, false, null, null, $this->server, 'disposable_non_production', $this->network, 'synthetic-attestation/1');
            }
        };

        return new PhysicalIdentityTargetSnapshotAuthority(new PhysicalServerIdentityVerifier($hasher), $contract, $observer, $hasher, $foundation);
    }

    /** @return array<int,array<string,mixed>> */
    private function sourceRows(string $sql): array
    {
        if ($sql === 'SHOW GRANTS') {
            return [
                ['grant' => 'GRANT USAGE ON *.* TO `reader`@`host`'],
                ['grant' => 'GRANT SELECT, SHOW VIEW ON `uuhms`.* TO `reader`@`host`'],
            ];
        }
        if ($sql === 'SELECT CURRENT_ROLE() AS active_roles') {
            return [['active_roles' => 'NONE']];
        }
        if (str_contains($sql, '_PRIVILEGES')) {
            return [];
        }
        if (str_starts_with($sql, 'SELECT DATABASE()')) {
            return [['database_name' => 'uuhms', 'database_version' => '10.4.32-MariaDB', 'transaction_read_only' => 1]];
        }
        if (str_contains($sql, "TABLE_SCHEMA = 'uuhms'") && str_contains($sql, "TABLE_TYPE = 'BASE TABLE'")) {
            return array_map(static fn (int $ordinal): array => ['TABLE_NAME' => sprintf('table_%02d', $ordinal)], range(1, 55));
        }
        if (str_contains($sql, 'information_schema.TABLES')) {
            return array_map(static fn (int $ordinal): array => ['TABLE_NAME' => sprintf('table_%02d', $ordinal), 'TABLE_TYPE' => 'BASE TABLE'], range(1, 55));
        }
        if (str_contains($sql, 'information_schema.COLUMNS')) {
            return array_fill(0, 479, ['TABLE_NAME' => 'synthetic_table', 'COLUMN_NAME' => 'synthetic_column']);
        }
        if (str_contains($sql, '`uuhms`')) {
            return [['synthetic_key' => 'SYNTHETIC-ONLY-P3B::SOURCE']];
        }

        return [];
    }

    /** @return array<int,array<string,mixed>> */
    private function targetRows(string $sql): array
    {
        if (str_starts_with($sql, 'SELECT DATABASE()')) {
            return [['database_name' => 'uhms_clean', 'database_version' => '10.4.32-MariaDB', 'transaction_read_only' => 1]];
        }
        if (str_contains($sql, 'information_schema.TABLES')) {
            return [['TABLE_NAME' => 'migrations', 'TABLE_TYPE' => 'BASE TABLE']];
        }
        if (str_contains($sql, 'information_schema.COLUMNS')) {
            return [['TABLE_NAME' => 'migrations', 'COLUMN_NAME' => 'id', 'ORDINAL_POSITION' => 1]];
        }
        if ($sql === 'SELECT migration, batch FROM `migrations` ORDER BY id') {
            return [['migration' => 'synthetic_foundation', 'batch' => 1]];
        }
        if (str_contains($sql, 'information_schema.')) {
            return [];
        }

        return [['synthetic_key' => 'SYNTHETIC-ONLY-P3B::TARGET']];
    }
}
