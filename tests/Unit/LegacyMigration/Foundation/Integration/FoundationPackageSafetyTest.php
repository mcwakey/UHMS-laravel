<?php

namespace Tests\Unit\LegacyMigration\Foundation\Integration;

use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAuthority;
use App\Services\LegacyMigration\Foundation\Security\FoundationDatabaseWriteBoundary;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordRotationAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\UnavailableProtectedRecordRotationAuthority;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class FoundationPackageSafetyTest extends TestCase
{
    #[Test]
    public function configuration_defaults_are_inert_and_exactly_uuhms(): void
    {
        self::assertFalse(config('legacy-migration.enabled'));
        self::assertTrue(config('legacy-migration.reject_production'));
        self::assertSame('legacy_uhms', config('legacy-migration.source.connection'));
        self::assertSame('uuhms', config('legacy-migration.source.database'));
        self::assertTrue(config('legacy-migration.execution.dry_run_only'));
        self::assertFalse(config('legacy-migration.execution.commit_authorized'));
        self::assertFalse(config('legacy-migration.execution.cohort_b_selection_enabled'));
        self::assertFalse(config('legacy-migration.execution.importer_execution_enabled'));
        self::assertFalse(config('legacy-migration.execution.production_enabled'));
        self::assertFalse(config('legacy-migration.foundation.installation_journal_enabled'));
        self::assertFalse(config('legacy-migration.protected_store.population_enabled'));
        self::assertFalse(config('legacy-migration.recovery.persistent_journal_enabled'));
        self::assertFalse(config('legacy-migration.snapshots.authoritative_capture_enabled'));
        self::assertFalse(config('legacy-migration.evaluator.authoritative_recorders_bound'));
        self::assertFalse(config('legacy-migration.isolation.application_bindings.bindings_verified'));
        self::assertFalse(config('legacy-migration.retention.schedule_resolved'));
        self::assertFalse(config('legacy-migration.retention.purge_execution_enabled'));
        self::assertSame('external_reference', config('legacy-migration.key_provider.provider'));
        self::assertSame([], config('legacy-migration.key_provider.references'));
    }

    #[Test]
    public function direct_mariadb_migrations_are_denied_in_favour_of_the_identity_bound_manifest_installer(): void
    {
        $connection = Mockery::mock(ConnectionInterface::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LM-SEC-FOUNDATION-MANIFEST-INSTALL-REQUIRED');
        FoundationDatabaseWriteBoundary::assertSchemaInstallAllowed($connection);
    }

    #[Test]
    public function only_the_six_approved_foundation_commands_exist(): void
    {
        $commands = array_values(array_filter(
            array_keys(Artisan::all()),
            fn (string $name): bool => str_starts_with($name, 'legacy-migration:foundation-')
                || $name === 'legacy-migration:verify-source-account'
                || $name === 'legacy-migration:privacy-scan',
        ));
        sort($commands);

        self::assertSame([
            'legacy-migration:foundation-preflight',
            'legacy-migration:foundation-reconcile',
            'legacy-migration:foundation-recovery-audit',
            'legacy-migration:foundation-status',
            'legacy-migration:privacy-scan',
            'legacy-migration:verify-source-account',
        ], $commands);
    }

    #[Test]
    public function no_domain_importer_or_business_table_migration_was_introduced(): void
    {
        $foundation = $this->contentsUnder(app_path('Services/LegacyMigration/Foundation'))
            .$this->contentsUnder(app_path('Console/Commands/LegacyMigration'));

        self::assertDoesNotMatchRegularExpression('/class\s+\w*(?:Patient|Staff|Insurance|Reference)Importer\b/i', $foundation);
        self::assertStringNotContainsString('run-patient-pilot', strtolower($foundation));
        self::assertStringNotContainsString('import-patients', strtolower($foundation));
        self::assertStringNotContainsString('migrate-patients', strtolower($foundation));

        $migrations = '';
        foreach (glob(database_path('migrations/2026_07_22_00011*.php')) ?: [] as $file) {
            $contents = (string) file_get_contents($file);
            $migrations .= $contents;
            preg_match_all("/Schema::create\('([^']+)'/", $contents, $matches);
            foreach ($matches[1] as $table) {
                self::assertStringStartsWith('legacy_migration_', $table);
            }
        }
        self::assertStringNotContainsString("Schema::create('patients'", $migrations);
        self::assertStringNotContainsString("Schema::table('patients'", $migrations);
    }

    #[Test]
    public function shared_protected_authority_loads_pinned_external_key_manifest_for_recovery_and_capture(): void
    {
        $recoveryReference = hash('sha256', 'SYNTHETIC-ONLY-P3B::recovery-authority');
        $captureReference = hash('sha256', 'SYNTHETIC-ONLY-P3B::capture-authority');
        $manifest = json_encode([
            'manifest_version' => 'P3B-KEY-REFS-1',
            'references' => [[
                'key_id' => 'migration-hmac', 'version' => 'v1', 'secret_reference' => 'vault://synthetic/migration',
                'environment' => 'testing',
                'domains' => ['idempotency', 'migration_run', 'source_snapshot', 'target_snapshot', 'target_collision', 'recovery', 'migration_audit'],
                'activated_at' => '2020-01-01T00:00:00Z', 'rotation_authority' => 'SYNTHETIC-AUTHORITY',
                'revoked' => false, 'active_for_signing' => true,
            ]],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $path = tempnam(sys_get_temp_dir(), 'p3b-provider-refs-');
        self::assertIsString($path);
        file_put_contents($path, $manifest);
        config([
            'legacy-migration.key_provider.references' => [],
            'legacy-migration.key_provider.reference_manifest' => $path,
            'legacy-migration.key_provider.reference_manifest_hash' => hash('sha256', $manifest),
            'legacy-migration.snapshots.authoritative_capture_enabled' => true,
            'legacy-migration.snapshots.persistence_authority_reference' => $captureReference,
            'legacy-migration.protected_store.access_classification' => 'protected',
            'legacy-migration.retention.protected' => 'migration-lineage',
        ]);
        $this->app->instance(RecoveryJournalAuthority::class, new class($recoveryReference) implements RecoveryJournalAuthority
        {
            public function __construct(private string $reference) {}

            public function environment(): string
            {
                return 'testing';
            }

            public function authorityReference(): string
            {
                return $this->reference;
            }

            public function accessClassification(): string
            {
                return 'protected';
            }

            public function retentionClassification(): string
            {
                return 'migration-lineage';
            }

            public function contractVersion(): string
            {
                return 'synthetic-contract/1';
            }

            public function transformationVersion(): string
            {
                return 'not-authorized';
            }

            public function canonicalizationVersion(): string
            {
                return 'typed-length-prefix/1';
            }

            public function expectedContractBundleHash(): string
            {
                return str_repeat('a', 64);
            }
        });
        $this->app->forgetInstance(ProtectedStoreAccessAuthority::class);

        try {
            $authority = $this->app->make(ProtectedStoreAccessAuthority::class);
            $authority->assertOperationContext(
                new ProtectedStoreOperationContext('foundation_recovery', ['write'], ['recovery'], 'testing', null, null, null, 'protected', 'migration-lineage', $recoveryReference),
                'write', 'recovery',
            );
            $authority->assertOperationContext(
                new ProtectedStoreOperationContext('authoritative_run_capture', ['write'], ['target_collision'], 'testing', null, null, null, 'protected', 'migration-lineage', $captureReference),
                'write', 'target_collision',
            );
            self::addToAssertionCount(2);
        } finally {
            @unlink($path);
        }
    }

    #[Test]
    public function unresolved_rotation_configuration_resolves_an_explicit_fail_closed_authority(): void
    {
        config([
            'legacy-migration.key_provider.rotation_authority_reference' => null,
            'legacy-migration.key_provider.approved_rotation_reasons' => [],
        ]);
        $this->app->forgetInstance(ProtectedRecordRotationAuthority::class);

        $this->assertInstanceOf(
            UnavailableProtectedRecordRotationAuthority::class,
            $this->app->make(ProtectedRecordRotationAuthority::class),
        );
    }

    private function contentsUnder(string $directory): string
    {
        $contents = '';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $contents .= (string) file_get_contents($file->getPathname());
            }
        }

        return $contents;
    }
}
