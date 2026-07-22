<?php

namespace Tests\Feature\LegacyMigration\Foundation;

use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\ObservedPhysicalServerIdentity;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalTargetIdentityContract;
use App\Services\LegacyMigration\Foundation\Environment\SchemaFingerprintService;
use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeDryRunEvaluator;
use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeRecorderEvidence;
use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeRecorderEvidenceProvider;
use App\Services\LegacyMigration\Foundation\Reconciliation\AuthoritativeRecorderSession;
use App\Services\LegacyMigration\Foundation\Reconciliation\MeasurementIntegrityService;
use App\Services\LegacyMigration\Foundation\Reconciliation\ObservedEmptyCohortAggregateProvider;
use App\Services\LegacyMigration\Foundation\Reporting\AggregateDryRunReportBuilder;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Services\LegacyMigration\Foundation\Runtime\SideEffectCounter;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\ConfiguredKeyProvider;
use App\Services\LegacyMigration\Foundation\Security\HmacTokenService;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\TokenDomainRegistry;
use App\Services\LegacyMigration\Foundation\Snapshot\AuthoritativeRunCaptureCoordinator;
use App\Services\LegacyMigration\Foundation\Snapshot\AuthoritativeSnapshotCapture;
use App\Services\LegacyMigration\Foundation\Snapshot\CaptureResultProtector;
use App\Services\LegacyMigration\Foundation\Snapshot\PhysicalIdentityTargetSnapshotAuthority;
use App\Services\LegacyMigration\Foundation\Snapshot\RunManifestService;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifest;
use App\Services\LegacyMigration\Foundation\Snapshot\SnapshotManifestIntegrityService;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedCaptureConfiguration;
use App\Services\LegacyMigration\Foundation\Snapshot\VerifiedEnvironmentAuthority;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use App\Services\LegacyMigration\Foundation\Storage\RunManifestRepository;
use App\Services\LegacyMigration\Foundation\Storage\SnapshotRepository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\LegacyMigration\Phase2FPolicyFixture;
use Tests\TestCase;
use Tests\Unit\LegacyMigration\Foundation\Environment\FakeMetadataConnection;

final class AuthoritativeRunCaptureCoordinatorTest extends TestCase
{
    /** @var list<object> */
    private array $migrations = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('C', 32))]);
        DB::statement('PRAGMA foreign_keys = ON');
        foreach ([
            '2026_07_22_000110_create_legacy_migration_run_foundation_tables.php',
            '2026_07_22_000111_create_legacy_migration_protected_store_tables.php',
            '2026_07_22_000112_create_legacy_migration_recovery_tables.php',
            '2026_07_22_000114_create_legacy_migration_protected_lifecycle_tables.php',
        ] as $file) {
            $migration = require database_path('migrations/'.$file);
            $migration->up();
            $this->migrations[] = $migration;
        }
    }

    protected function tearDown(): void
    {
        ProtectedStoreAccessSession::reset();
        foreach (array_reverse($this->migrations) as $migration) {
            $migration->down();
        }
        parent::tearDown();
    }

    public function test_direct_verified_capture_is_atomically_protected_and_reuses_exact_policy_bundle(): void
    {
        [$tokens, $encoder, $integrity] = $this->crypto();
        $source = $this->sourceConnection();
        $target = $this->targetConnection();
        $target->beginReadOnlySnapshot();
        $targetSchema = (new SchemaFingerprintService)->inspectTarget($target, 'uhms_clean');
        $target->rollbackReadOnlySnapshot();
        $configuration = VerifiedCaptureConfiguration::fromRepositoryConfiguration('testing', $this->configuration($targetSchema));
        $physicalAuthority = $this->physicalAuthority($targetSchema, $configuration->fingerprint);
        $capture = new AuthoritativeSnapshotCapture(new CaptureResultProtector($tokens, $encoder), $integrity);

        $source->beginReadOnlySnapshot();
        $target->beginReadOnlySnapshot();
        $sourceProof = $capture->captureSource($source, str_repeat('7', 64), $configuration->fingerprint, Phase2FPolicyFixture::load()->bundleHash, $configuration->toolVersion);
        $targetProof = $capture->captureTarget($target, $physicalAuthority, str_repeat('7', 64), $configuration->fingerprint, Phase2FPolicyFixture::load()->bundleHash);
        $target->rollbackReadOnlySnapshot();
        $source->rollbackReadOnlySnapshot();
        VerifiedEnvironmentAuthority::fromSnapshots($sourceProof, $targetProof, $integrity);
        $persistenceAuthority = hash('sha256', 'SYNTHETIC-ONLY-P3B::snapshot-persistence-authority');

        $domains = ['migration_run', 'source_snapshot', 'target_snapshot', 'target_collision'];
        $accessAuthority = new PinnedProtectedStoreAccessAuthority(
            'testing', $domains, 'migration-hmac', 'v1', 'typed-length-prefix/1', 'artifact_integrity',
            ['authoritative_run_capture' => [
                'operations' => ['read', 'write'],
                'authority_references' => [$persistenceAuthority],
                'access_classifications' => ['protected'],
                'retention_classifications' => ['migration-lineage'],
            ]],
        );
        $guard = new ProtectedStoreAccessGuard($accessAuthority, $tokens, $encoder);
        $security = new ProtectedRecordSecurityRepository(new ProtectedRecordEnvelopeFactory($guard), new ProtectedRecordEnvelopeVerifier($guard));
        $coordinator = new AuthoritativeRunCaptureCoordinator(
            $capture, $integrity, new RunManifestService($integrity),
            new RunManifestRepository(security: $security), new SnapshotRepository($security), $tokens, $encoder,
        );

        $recorder = AuthoritativeRecorderSession::begin(
            new SideEffectCounter,
            DB::connection(),
            $integrity,
            new ObservedEmptyCohortAggregateProvider,
        );
        $first = $coordinator->captureAndPersist($source, $target, $physicalAuthority, Phase2FPolicyFixture::load(), $configuration);
        $target->beginReadOnlySnapshot();
        $targetAfter = $capture->captureTarget(
            $target, $physicalAuthority, $first->runManifest->runToken, $configuration->fingerprint,
            Phase2FPolicyFixture::load()->bundleHash,
        );
        $target->rollbackReadOnlySnapshot();
        $evidence = $recorder->finish($first->sourceSnapshotManifest, $first->targetSnapshotManifest, $targetAfter);
        $evaluation = (new AuthoritativeDryRunEvaluator(new MeasurementIntegrityService($tokens, $encoder)))
            ->evaluateRecorded(Phase2FPolicyFixture::load(), Phase2FPolicyFixture::load()->bundleHash, $evidence);
        $second = $coordinator->captureAndPersist($source, $target, $physicalAuthority, Phase2FPolicyFixture::load(), $configuration);

        self::assertSame($first->contractBundleRecordId, $second->contractBundleRecordId);
        self::assertCount(10, $first->collisionRecordIds);
        self::assertSame(1, DB::table('legacy_migration_contract_bundles')->count());
        self::assertSame(2, DB::table('legacy_migration_runs')->count());
        self::assertSame(4, DB::table('legacy_migration_snapshots')->count());
        self::assertSame(20, DB::table('legacy_migration_target_collision_snapshots')->count());
        self::assertSame(27, DB::table('legacy_migration_protected_record_envelopes')->count());
        self::assertSame(1, $evidence->observations['protected_repository_write_delta']['legacy_migration_runs']);
        self::assertSame(0, $evidence->observations['runtime_denied_attempts']);
        self::assertSame('DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED', $evaluation->verdict);
        self::assertCount(476, $evaluation->measurements);
        self::assertSame([], $evaluation->missing);
        self::assertSame([], $evaluation->failed);
        self::assertFalse($evaluation->commitAuthorized());
        $report = (new AggregateDryRunReportBuilder)->renderAuthoritative($evaluation);
        self::assertSame('DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED', $report['verdict']);
        self::assertFalse($report['commit_authorized']);
        self::assertSame(0, $report['missing_measurement_count']);
        self::assertSame(0, $report['failed_measurement_count']);
        self::assertSame(0, $report['source_write_count']);
        self::assertSame(0, $report['target_write_count']);
        $this->assertAcceptedCommandReport($evidence, new AuthoritativeDryRunEvaluator(new MeasurementIntegrityService($tokens, $encoder)));
        self::assertFalse($source->readOnlySnapshotActive());
        self::assertFalse($target->readOnlySnapshotActive());
    }

    public function test_missing_trusted_aggregate_adapter_blocks_all_detailed_measurements(): void
    {
        $evaluation = $this->evaluateSyntheticRecorder(providerBound: false);

        self::assertSame('BLOCKED_PREREQUISITE', $evaluation->verdict);
        self::assertCount(135, $evaluation->missing);
        self::assertSame([], $evaluation->failed);
        self::assertFalse($evaluation->commitAuthorized());
    }

    public function test_nonzero_runtime_attempt_fails_complete_evidence(): void
    {
        $evaluation = $this->evaluateSyntheticRecorder(runtimeAttempt: true);

        self::assertSame('FAIL_CLOSED', $evaluation->verdict);
        self::assertSame([], $evaluation->missing);
        self::assertContains('PILOT-DRY-008:notifications', $evaluation->failed);
        self::assertFalse($evaluation->commitAuthorized());
    }

    public function test_target_before_after_mismatch_fails_complete_evidence(): void
    {
        $evaluation = $this->evaluateSyntheticRecorder(targetMismatch: true);

        self::assertSame('FAIL_CLOSED', $evaluation->verdict);
        self::assertSame([], $evaluation->missing);
        self::assertContains('PILOT-DRY-008:target_writes', $evaluation->failed);
        self::assertContains('PILOT-RECON-008:dry_run_target_writes', $evaluation->failed);
        self::assertFalse($evaluation->commitAuthorized());
    }

    /** @return array{HmacTokenService,CanonicalTypedMessageEncoder,SnapshotManifestIntegrityService} */
    private function crypto(): array
    {
        $versions = new CanonicalizationVersionRegistry;
        $encoder = new CanonicalTypedMessageEncoder($versions);
        $tokens = new HmacTokenService(new ConfiguredKeyProvider([
            'key_id' => 'migration-hmac', 'key_version' => 'v1',
            'key' => '789abcdef0123456BCDEFA!@#$%^&*()-+=0123456', 'algorithm' => 'sha256',
        ]), $versions, 'testing', new TokenDomainRegistry([
            'artifact_integrity', 'migration_run', 'source_snapshot', 'target_snapshot', 'target_collision',
        ]));

        return [$tokens, $encoder, new SnapshotManifestIntegrityService($tokens, $encoder)];
    }

    private function sourceConnection(): FakeMetadataConnection
    {
        $manifest = json_decode(file_get_contents(base_path('docs/legacy-migration/evidence/CLASSIC_SCHEMA_MANIFEST.json')), true, 512, JSON_THROW_ON_ERROR);

        return new FakeMetadataConnection('legacy_uhms', 'uuhms', static function (string $sql) use ($manifest): array {
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
                return array_map(static fn (array $row): array => ['TABLE_NAME' => $row['TABLE_NAME']], $manifest['tables']);
            }
            if (str_contains($sql, 'information_schema.TABLES')) {
                return array_map(static function (array $row): array {
                    unset($row['EXACT_ROW_COUNT']);

                    return $row;
                }, $manifest['tables']);
            }
            if (str_contains($sql, 'information_schema.COLUMNS')) {
                return $manifest['columns'];
            }
            if (str_contains($sql, 'information_schema.STATISTICS')) {
                return $manifest['indexes'];
            }
            if (str_contains($sql, 'information_schema.TABLE_CONSTRAINTS')) {
                return [];
            }
            if (str_contains($sql, 'information_schema.KEY_COLUMN_USAGE')) {
                return $manifest['constraint_evidence']['foreign_key_columns'];
            }
            if (str_contains($sql, 'information_schema.REFERENTIAL_CONSTRAINTS')) {
                return $manifest['constraint_evidence']['referential_constraints'];
            }
            if (preg_match('/FROM `uuhms`\.`(?:patients|attendance|insurance)`/', $sql) === 1) {
                return [];
            }
            if (str_contains($sql, '`uuhms`')) {
                return [['synthetic_key' => 'SYNTHETIC-ONLY-P3B::SOURCE']];
            }

            return [];
        });
    }

    private function targetConnection(): FakeMetadataConnection
    {
        return new FakeMetadataConnection('mysql', 'uhms_clean', static function (string $sql): array {
            if (str_starts_with($sql, 'SELECT DATABASE()')) {
                return [['database_name' => 'uhms_clean', 'database_version' => '10.4.32-MariaDB', 'transaction_read_only' => 1]];
            }
            if (str_contains($sql, 'TABLE_NAME, TABLE_TYPE, ENGINE')) {
                return [['TABLE_NAME' => 'migrations', 'TABLE_TYPE' => 'BASE TABLE', 'ENGINE' => 'InnoDB', 'TABLE_COLLATION' => 'utf8mb4_unicode_ci', 'CREATE_OPTIONS' => '', 'TABLE_COMMENT' => '']];
            }
            if (str_contains($sql, 'COLUMN_NAME, ORDINAL_POSITION')) {
                return [['TABLE_NAME' => 'migrations', 'COLUMN_NAME' => 'id', 'ORDINAL_POSITION' => 1, 'COLUMN_DEFAULT' => null, 'IS_NULLABLE' => 'NO', 'DATA_TYPE' => 'bigint', 'COLUMN_TYPE' => 'bigint unsigned', 'CHARACTER_MAXIMUM_LENGTH' => null, 'NUMERIC_PRECISION' => 20, 'NUMERIC_SCALE' => 0, 'DATETIME_PRECISION' => null, 'CHARACTER_SET_NAME' => null, 'COLLATION_NAME' => null, 'COLUMN_KEY' => 'PRI', 'EXTRA' => 'auto_increment', 'GENERATION_EXPRESSION' => '', 'COLUMN_COMMENT' => '']];
            }
            if ($sql === 'SELECT migration, batch FROM `migrations` ORDER BY id') {
                return [['migration' => 'synthetic', 'batch' => 1]];
            }
            if (str_contains($sql, 'information_schema.')) {
                return [];
            }

            return [['synthetic_key' => 'SYNTHETIC-ONLY-P3B::TARGET']];
        });
    }

    private function configuration(object $target): array
    {
        return [
            'enabled' => true, 'reject_production' => true, 'allowed_environments' => ['testing'],
            'guards' => [
                'approved_environments' => ['testing'],
                'source' => ['connection' => 'legacy_uhms', 'database' => 'uuhms', 'version' => '10.4.32-MariaDB', 'fingerprint' => '150fcf4783fcb8bdc25f7e17fe0ece5050955f0c68e7dd03651ee8bd58498977', 'table_count' => 55, 'column_count' => 479],
                'target' => ['connection' => 'mysql', 'connection_allow_list' => ['mysql'], 'database' => 'uhms_clean', 'version' => $target->databaseVersion, 'fingerprint' => $target->fingerprint, 'table_count' => $target->tableCount, 'column_count' => $target->columnCount],
            ],
            'execution' => ['dry_run_only' => true, 'commit_authorized' => false, 'cohort_b_selection_enabled' => false, 'importer_execution_enabled' => false, 'production_enabled' => false],
            'snapshots' => ['authoritative_capture_enabled' => true, 'physical_target_authority_required' => true, 'protected_persistence_required' => true, 'tool_version' => 'phase3b-synthetic/1', 'persistence_authority_reference' => hash('sha256', 'SYNTHETIC-ONLY-P3B::snapshot-persistence-authority')],
            'protected_store' => ['access_classification' => 'protected'],
            'retention' => ['protected' => 'migration-lineage'],
        ];
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

    private function evaluateSyntheticRecorder(bool $providerBound = true, bool $runtimeAttempt = false, bool $targetMismatch = false): object
    {
        [$tokens, , $integrity] = $this->crypto();
        $counter = new SideEffectCounter;
        $recorder = AuthoritativeRecorderSession::begin(
            $counter,
            DB::connection(),
            $integrity,
            $providerBound ? new ObservedEmptyCohortAggregateProvider : null,
        );
        if ($runtimeAttempt) {
            $counter->record(ProhibitedSubsystem::Notifications);
        }

        $run = hash('sha256', 'SYNTHETIC-ONLY-P3B::RECORDER-RUN');
        $sourceSets = ['source_transaction_coordinate', 'patient_root', 'patient_children', 'insurance'];
        $targetSets = [
            'target_transaction_coordinate', 'patient_namespace', 'archive_namespace', 'alias_namespace',
            'contact_sets', 'insurance_memberships', 'provider_state', 'number_configuration',
            'foundation_schema', 'row_schema',
        ];
        $source = $this->sealedRecorderManifest($integrity, 'coordinated_source', $run, hash('sha256', 'source'), $sourceSets, 'source');
        $targetBefore = $this->sealedRecorderManifest($integrity, 'target_collision', $run, hash('sha256', 'target-before'), $targetSets, 'target');
        $targetAfter = $this->sealedRecorderManifest(
            $integrity,
            'target_collision',
            $run,
            hash('sha256', 'target-after'),
            $targetSets,
            $targetMismatch ? 'changed-target' : 'target',
        );
        $evidence = $recorder->finish($source, $targetBefore, $targetAfter);

        return (new AuthoritativeDryRunEvaluator(new MeasurementIntegrityService($tokens, new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry))))
            ->evaluateRecorded(Phase2FPolicyFixture::load(), Phase2FPolicyFixture::load()->bundleHash, $evidence);
    }

    /** @param list<string> $sets */
    private function sealedRecorderManifest(
        SnapshotManifestIntegrityService $integrity,
        string $kind,
        string $run,
        string $snapshotId,
        array $sets,
        string $tokenSeed,
    ): SnapshotManifest {
        $queryHashes = [];
        $tokens = [];
        $counts = [];
        foreach ($sets as $set) {
            $queryHashes[$set] = hash('sha256', 'query:'.$set);
            $tokens[$set] = 'protected:'.hash('sha256', $tokenSeed.':'.$set);
            $counts[$set] = 0;
        }

        return $integrity->seal(new SnapshotManifest(
            $snapshotId,
            $kind,
            $run,
            $kind === 'coordinated_source' ? '2026-07-22T00:00:00.000000+00:00' : '2026-07-22T00:00:01.000000+00:00',
            hash('sha256', $kind.':schema'),
            '10.4.32-MariaDB',
            hash('sha256', 'configuration'),
            Phase2FPolicyFixture::load()->bundleHash,
            $queryHashes,
            [],
            'authoritative_direct_capture',
            hash('sha256', $kind.':authority'),
            $tokens,
            '',
            $counts,
            1,
            1,
            hash('sha256', $snapshotId.':nonce'),
        ));
    }

    private function assertAcceptedCommandReport(
        AuthoritativeRecorderEvidence $evidence,
        AuthoritativeDryRunEvaluator $evaluator,
    ): void {
        $bundle = Phase2FPolicyFixture::load();
        $approval = 'synthetic independently reviewed policy authority';
        $manifestPath = tempnam(sys_get_temp_dir(), 'phase2f-policy-');
        if (! is_string($manifestPath)) {
            self::fail('Synthetic external policy manifest could not be created.');
        }
        file_put_contents($manifestPath, json_encode([
            'manifest_version' => 'phase2f-policy-manifest/1',
            'artifacts' => Phase2FPolicyFixture::expected(),
            'expected_bundle_hash' => $bundle->bundleHash,
            'approval_reference' => $approval,
        ], JSON_THROW_ON_ERROR));

        config([
            'legacy-migration.evaluator.authoritative_recorders_bound' => true,
            'legacy-migration.phase2f_policy.artifact_manifest' => $manifestPath,
            'legacy-migration.phase2f_policy.expected_bundle_hash' => $bundle->bundleHash,
            'legacy-migration.phase2f_policy.approval_reference' => $approval,
        ]);
        app()->instance(AuthoritativeDryRunEvaluator::class, $evaluator);
        app()->instance(AuthoritativeRecorderEvidenceProvider::class, new class($evidence) implements AuthoritativeRecorderEvidenceProvider
        {
            public function __construct(private readonly AuthoritativeRecorderEvidence $evidence) {}

            public function capture(): AuthoritativeRecorderEvidence
            {
                return $this->evidence;
            }
        });

        try {
            self::assertSame(0, Artisan::call('legacy-migration:foundation-reconcile'));
            $output = Artisan::output();
            self::assertStringContainsString('"verdict":"DRY_RUN_ACCEPTED_NOT_COMMIT_AUTHORIZED"', $output);
            self::assertStringContainsString('"commit_authorized":false', $output);
            self::assertStringContainsString('"source_write_count":0', $output);
            self::assertStringContainsString('"target_write_count":0', $output);
        } finally {
            unlink($manifestPath);
        }
    }
}
