<?php

namespace Tests\Unit\LegacyMigration\Foundation\Installation;

use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use App\Services\LegacyMigration\Foundation\Installation\DdlObjectType;
use App\Services\LegacyMigration\Foundation\Installation\FoundationInstallationCompositionService;
use App\Services\LegacyMigration\Foundation\Installation\InstallationIdentityContract;
use App\Services\LegacyMigration\Foundation\Installation\InstallationSessionCapability;
use App\Services\LegacyMigration\Foundation\Installation\MariaDbFoundationDdlExtensionManifest;
use App\Services\LegacyMigration\Foundation\Installation\MariaDbFoundationDdlManifest;
use App\Services\LegacyMigration\Foundation\Installation\MariaDbFoundationDdlOperationExecutor;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MariaDbFoundationDdlManifestTest extends TestCase
{
    public function test_manifest_is_versioned_integrity_checked_complete_and_create_only(): void
    {
        $manifest = new MariaDbFoundationDdlManifest;
        $operations = $manifest->operations();

        $this->assertSame(MariaDbFoundationDdlManifest::PAYLOAD_HASH, $manifest->payloadHash());
        $this->assertCount(59, $operations);
        $this->assertCount(17, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::Table));
        $this->assertCount(38, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::Trigger));
        $this->assertCount(4, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::MigrationLedger));
        $this->assertCount(59, array_unique(array_column($operations, 'operationId')));

        foreach ($operations as $operation) {
            if ($operation->type === DdlObjectType::MigrationLedger) {
                $this->assertNull($operation->sql);
            } else {
                $this->assertMatchesRegularExpression('/\ACREATE\s+(TABLE|TRIGGER)\s+/i', (string) $operation->sql);
                $this->assertStringNotContainsString(' DROP ', strtoupper((string) $operation->sql));
            }
        }
    }

    public function test_extension_manifest_covers_lifecycle_and_durable_recovery_objects(): void
    {
        $manifest = new MariaDbFoundationDdlExtensionManifest;
        $operations = $manifest->operations();

        $this->assertSame('cffa2f553a3eeb89116f2242b1aac1a1ffc57085de0b267bb9bb6f217f660d20', $manifest->payloadHash());
        $this->assertCount(25, $operations);
        $this->assertCount(7, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::Table));
        $this->assertCount(15, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::Trigger));
        $this->assertCount(1, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::Column));
        $this->assertCount(2, array_filter($operations, static fn ($item): bool => $item->type === DdlObjectType::MigrationLedger));
        $this->assertCount(25, array_unique(array_column($operations, 'operationId')));
    }

    public function test_direct_executor_invocation_rejects_session_from_another_key_before_sql(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $hasher = new HmacIdentityReferenceHasher('testing', 'identity-key', 'v1', str_repeat('a1B2c3D4', 8));
        $other = new HmacIdentityReferenceHasher('testing', 'identity-key-2', 'v2', str_repeat('z9Y8x7W6', 8));
        $identity = PhysicalServerIdentityVerification::issue('v1', hash('sha256', 'identity'), hash('sha256', 'schema'), hash('sha256', 'config'), $hasher);
        $manifest = new MariaDbFoundationDdlManifest;
        $contract = InstallationIdentityContract::issue(
            $identity->approvedIdentityReference,
            $identity->connectionInstanceReference,
            $identity->structuralIdentityReference,
            [$manifest->version() => $manifest->payloadHash()],
            hash('sha256', 'partial'),
            $other,
        );
        $session = InstallationSessionCapability::issue($contract, hash('sha256', 'audit'), ['P3B-DDL-1'], $other);
        $executor = new MariaDbFoundationDdlOperationExecutor($connection, $manifest, $hasher);

        $this->expectException(RuntimeException::class);
        $executor->execute($manifest->version(), $manifest->operations()[0]->operationId, $identity, new IdentityBoundDdlGate($hasher), $session);
    }

    public function test_direct_executor_invocation_rejects_gate_from_another_key_before_sql(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $hasher = new HmacIdentityReferenceHasher('testing', 'identity-key', 'v1', str_repeat('a1B2c3D4', 8));
        $other = new HmacIdentityReferenceHasher('testing', 'identity-key-2', 'v2', str_repeat('z9Y8x7W6', 8));
        $identity = PhysicalServerIdentityVerification::issue('v1', hash('sha256', 'identity'), hash('sha256', 'schema'), hash('sha256', 'config'), $hasher);
        $manifest = new MariaDbFoundationDdlManifest;
        $contract = InstallationIdentityContract::issue(
            $identity->approvedIdentityReference,
            $identity->connectionInstanceReference,
            $identity->structuralIdentityReference,
            [$manifest->version() => $manifest->payloadHash()],
            hash('sha256', 'partial'),
            $hasher,
        );
        $session = InstallationSessionCapability::issue($contract, hash('sha256', 'audit'), ['P3B-DDL-1'], $hasher);
        $executor = new MariaDbFoundationDdlOperationExecutor($connection, $manifest, $hasher);

        $this->expectException(RuntimeException::class);
        $executor->execute($manifest->version(), $manifest->operations()[0]->operationId, $identity, new IdentityBoundDdlGate($other), $session);
    }

    public function test_executor_contract_requires_identity_gate_and_installation_session(): void
    {
        $method = new \ReflectionMethod(MariaDbFoundationDdlOperationExecutor::class, 'execute');

        $this->assertSame(5, $method->getNumberOfRequiredParameters());
        $this->assertSame(PhysicalServerIdentityVerification::class, (string) $method->getParameters()[2]->getType());
        $this->assertSame(IdentityBoundDdlGate::class, (string) $method->getParameters()[3]->getType());
        $this->assertSame(InstallationSessionCapability::class, (string) $method->getParameters()[4]->getType());
    }

    public function test_installation_session_cannot_replay_on_a_second_connection_instance(): void
    {
        $hasher = new HmacIdentityReferenceHasher('testing', 'identity-key', 'v1', str_repeat('a1B2c3D4', 8));
        $first = PhysicalServerIdentityVerification::issue(
            'v1', hash('sha256', 'identity'), hash('sha256', 'schema'), hash('sha256', 'config'), $hasher,
            hash('sha256', 'connection-one'), hash('sha256', 'physical'),
        );
        $second = PhysicalServerIdentityVerification::issue(
            'v1', hash('sha256', 'identity'), hash('sha256', 'schema'), hash('sha256', 'config'), $hasher,
            hash('sha256', 'connection-two'), hash('sha256', 'physical'),
        );
        $manifest = new MariaDbFoundationDdlManifest;
        $contract = InstallationIdentityContract::issue(
            $first->approvedIdentityReference,
            $first->connectionInstanceReference,
            $first->structuralIdentityReference,
            [$manifest->version() => $manifest->payloadHash()],
            hash('sha256', 'partial'),
            $hasher,
        );
        $session = InstallationSessionCapability::issue($contract, hash('sha256', 'audit'), [$manifest->version()], $hasher);

        $this->expectException(RuntimeException::class);
        $session->assertAuthentic($manifest->version(), $second, $hasher);
    }

    public function test_composition_consumes_all_three_disabled_installation_gates_before_connection_use(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $service = new FoundationInstallationCompositionService($connection, static fn (): string => str_repeat('a1B2c3D4', 8));

        foreach (['schema_writes_enabled', 'installation_journal_enabled', 'partial_repair_authorized'] as $disabled) {
            $foundation = [
                'schema_writes_enabled' => true,
                'installation_journal_enabled' => true,
                'partial_repair_authorized' => true,
                'installation_manifest_version' => 'phase-3b/foundation-ddl/1',
            ];
            $foundation[$disabled] = false;
            try {
                $service->install(['foundation' => $foundation], 'testing');
                $this->fail("Disabled gate {$disabled} was ignored.");
            } catch (RuntimeException $exception) {
                $this->assertSame('Foundation installation and repair remain disabled.', $exception->getMessage());
            }
        }
    }

    public function test_malformed_or_destructive_sql_is_refused_before_database_execution(): void
    {
        foreach (['CREAT TABLE broken', 'DROP TABLE legacy_migration_runs', 'ALTER TABLE `patients` ADD `unsafe` INT'] as $sql) {
            try {
                MariaDbFoundationDdlOperationExecutor::approvedCreateSql($sql);
                $this->fail('Malformed or out-of-scope SQL was accepted.');
            } catch (RuntimeException $exception) {
                $this->assertSame('Foundation DDL operation is not an approved create operation.', $exception->getMessage());
            }
        }
    }
}
