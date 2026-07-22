<?php

namespace Tests\Feature\LegacyMigration\Foundation;

require_once dirname(__DIR__, 3).'/Support/LegacyMigration/SyntheticMariaDbAllocatorHarness.php';

use App\Services\LegacyMigration\Foundation\Allocation\AllocationFaultInjector;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationFaultPoint;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationLineageResolver;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationMode;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest;
use App\Services\LegacyMigration\Foundation\Allocation\DeterministicPatientNumberAllocator;
use App\Services\LegacyMigration\Foundation\Allocation\ExistingAllocation;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelNumberReservationStore;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelTargetPatientNumberCollisionProbe;
use App\Services\LegacyMigration\Foundation\Allocation\NoopAllocationFaultInjector;
use App\Services\LegacyMigration\Foundation\Allocation\NumberingResetPeriod;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedNumberingConfiguration;
use App\Services\LegacyMigration\Foundation\Allocation\SequenceConsumption;
use App\Services\LegacyMigration\Foundation\Allocation\SequenceReconciler;
use App\Services\LegacyMigration\Foundation\Environment\HmacIdentityReferenceHasher;
use App\Services\LegacyMigration\Foundation\Environment\IdentityBoundDdlGate;
use App\Services\LegacyMigration\Foundation\Environment\LaravelPhysicalServerIdentityObserver;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerification;
use App\Services\LegacyMigration\Foundation\Environment\PhysicalServerIdentityVerifier;
use App\Services\LegacyMigration\Foundation\Installation\InstallationIdentityContract;
use App\Services\LegacyMigration\Foundation\Installation\InstallationSessionCapability;
use App\Services\LegacyMigration\Foundation\Installation\MariaDbFoundationDdlExtensionManifest;
use App\Services\LegacyMigration\Foundation\Installation\MariaDbFoundationDdlManifest;
use App\Services\LegacyMigration\Foundation\Installation\MariaDbFoundationDdlOperationExecutor;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Tests\Support\LegacyMigration\DisposableMariaDbPhysicalAttestation;
use Tests\Support\LegacyMigration\SyntheticCurrentCollisionEvidence;
use Tests\Support\LegacyMigration\SyntheticMariaDbAllocationWriteGuard;
use Tests\Support\LegacyMigration\SyntheticMariaDbCollisionNamespace;
use Tests\Support\LegacyMigration\SyntheticMariaDbConnectionLossInjector;
use Tests\Support\LegacyMigration\SyntheticMariaDbDeadlockOnceInjector;
use Tests\Support\LegacyMigration\SyntheticMariaDbFaultInjector;
use Tests\Support\LegacyMigration\SyntheticMariaDbReservationAttributes;
use Tests\Support\LegacyMigration\SyntheticMariaDbReservationProtection;
use Tests\TestCase;

final class MariaDbAllocatorVerificationTest extends TestCase
{
    private static string $schema = '';

    private static string $physicalAuthorityReference = '';

    private static int $runId = 0;

    private static int $sourceSnapshotId = 0;

    private static int $targetSnapshotId = 0;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('PHASE3B_MARIADB_VERIFICATION_ENABLED') !== 'true') {
            $this->markTestSkipped('Approved disposable MariaDB verification was not explicitly enabled.');
        }
        self::$schema = getenv('PHASE3B_MARIADB_SCHEMA') ?: '';
        if (preg_match('/\Aphase3b_allocator_[a-z0-9_]{6,40}\z/D', self::$schema) !== 1) {
            self::fail('Disposable allocator schema identity is invalid.');
        }
        self::configureAdminConnection();
        $identity = DB::connection('phase3b_allocator_admin')->selectOne('SELECT VERSION() AS version, @@server_id AS server_id, @@hostname AS hostname, @@port AS port');
        $version = (string) $identity->version;
        self::assertStringStartsWith('10.4.', $version);

        self::$physicalAuthorityReference = self::verifyPhysicalAttestation($identity);
        DB::connection('phase3b_allocator_admin')->statement('CREATE DATABASE `'.self::$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        self::configureDisposableConnection();
        $this->createSyntheticTables();
        $this->seedFoundationCoordinates();
    }

    protected function tearDown(): void
    {
        if (self::$physicalAuthorityReference !== '' && self::$schema !== '' && preg_match('/\Aphase3b_allocator_[a-z0-9_]{6,40}\z/D', self::$schema) === 1) {
            DB::purge('phase3b_allocator_verification');
            self::configureAdminConnection();
            DB::connection('phase3b_allocator_admin')->statement('DROP DATABASE `'.self::$schema.'`');
        }
        DB::purge('phase3b_allocator_admin');
        parent::tearDown();
    }

    public function test_concrete_mariadb_concurrency_rerun_rollback_collision_and_ordinal_contract(): void
    {
        $this->seedIdempotency(['one', 'two', 'same', 'fresh-2027', 'fresh-2029', 'collision', 'fault-before', 'fault-insert', 'fault-cas', 'deadlock', 'connection-loss']);

        [$mismatchedRepository, $mismatchedProtection] = SyntheticMariaDbReservationProtection::build(
            self::$runId,
            self::$sourceSnapshotId,
            self::$targetSnapshotId,
            'phase3b_allocator_admin',
        );
        $mismatchedStore = new LaravelNumberReservationStore(
            new SyntheticMariaDbReservationAttributes($this->idempotencyId('one'), self::$runId),
            'phase3b_allocator_verification',
            new SyntheticMariaDbAllocationWriteGuard('phase3b_allocator_verification'),
            new NoopAllocationFaultInjector,
            $mismatchedRepository,
            $mismatchedProtection,
        );
        try {
            ProtectedStoreAccessSession::run(self::verificationContext(), fn () => (new DeterministicPatientNumberAllocator(
                new ParentNoPriorAllocation,
                $mismatchedStore,
                new LaravelTargetPatientNumberCollisionProbe(
                    new SyntheticCurrentCollisionEvidence,
                    namespace: new SyntheticMariaDbCollisionNamespace('phase3b_allocator_verification'),
                ),
            ))->allocate($this->requestFor('one', '2026')));
            self::fail('A mismatched repository/sequence connection entered the allocator transaction.');
        } catch (\Throwable $exception) {
            self::assertStringContainsString('PATIENT-NUM-CONNECTION-BOUNDARY-MISMATCH', $exception->getMessage());
        }
        self::assertSame(0, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->count());
        self::assertSame(0, DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->count());

        $this->runConcurrent([['one', '2026'], ['two', '2026']]);
        $rows = DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->orderBy('sequence_ordinal')->get();
        self::assertSame([1, 2], $rows->pluck('sequence_ordinal')->map(fn ($v) => (int) $v)->all());
        self::assertSame(2, $rows->pluck('protected_number_token')->unique()->count());
        self::assertSame(0, DB::connection('phase3b_allocator_verification')->table('legacy_migration_protected_record_envelopes')
            ->whereNull('source_snapshot_id')->orWhereNull('target_snapshot_id')->count(), 'Every reservation envelope must bind both authoritative snapshots.');

        $this->runConcurrent([['same', '2026'], ['same', '2026']]);
        self::assertSame(3, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2026')->value('last_sequence'));
        self::assertSame(1, DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId('same'))->count());

        $conflictingSourceRejected = false;
        try {
            $sameCoreDifferentSource = new AllocationRequest(
                hash_hmac('sha256', 'source|same-but-different-lineage', 'SYNTHETIC-P3B-MARIADB-ONLY'),
                hash_hmac('sha256', 'core|same', 'SYNTHETIC-P3B-MARIADB-ONLY'),
                new PinnedNumberingConfiguration('UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 8, NumberingResetPeriod::Yearly, 'UTC', '2026'),
                AllocationMode::Commit,
            );
            ProtectedStoreAccessSession::run(self::verificationContext(), fn () => (new DeterministicPatientNumberAllocator(
                new ParentNoPriorAllocation,
                self::storeFor('same'),
                new LaravelTargetPatientNumberCollisionProbe(
                    new SyntheticCurrentCollisionEvidence,
                    namespace: new SyntheticMariaDbCollisionNamespace('phase3b_allocator_verification'),
                ),
            ))->allocate($sameCoreDifferentSource));
        } catch (\Throwable) {
            $conflictingSourceRejected = true;
        }
        self::assertTrue($conflictingSourceRejected, 'A different protected source lineage reused an existing patient-core reservation.');
        self::assertSame(3, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2026')->value('last_sequence'));

        $this->runConcurrent([['fresh-2027', '2027'], ['fresh-2029', '2029']]);
        self::assertSame(1, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2027')->value('last_sequence'));
        self::assertSame(1, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2029')->value('last_sequence'));
        self::assertSame(3, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2026')->value('last_sequence'));

        DB::connection('phase3b_allocator_verification')->table('phase3b_allocator_collision_namespace')->insert(['candidate' => 'UHMS-00000004/2026']);
        $this->expectAllocationFailure('collision', AllocationFaultPoint::BeforeReservationPersistence, injectFault: false);
        self::assertSame(3, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2026')->value('last_sequence'));

        foreach ([
            'fault-before' => AllocationFaultPoint::BeforeReservationPersistence,
            'fault-insert' => AllocationFaultPoint::AfterReservationInsertBeforeSequenceCas,
            'fault-cas' => AllocationFaultPoint::AfterSequenceCasBeforeCommit,
        ] as $seed => $point) {
            $this->expectAllocationFailure($seed, $point, injectFault: true);
            self::assertSame(3, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2026')->value('last_sequence'));
            self::assertSame(0, DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId($seed))->count());
        }

        $deadlockRequest = $this->requestFor('deadlock', '2028');
        $deadlockResult = ProtectedStoreAccessSession::run(self::verificationContext(), fn () => (new DeterministicPatientNumberAllocator(
            new ParentNoPriorAllocation,
            self::storeFor('deadlock', new SyntheticMariaDbDeadlockOnceInjector),
            new LaravelTargetPatientNumberCollisionProbe(
                new SyntheticCurrentCollisionEvidence,
                namespace: new SyntheticMariaDbCollisionNamespace('phase3b_allocator_verification'),
            ),
        ))->allocate($deadlockRequest));
        self::assertSame(1, $deadlockResult->sequenceOrdinal);
        self::assertSame(1, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2028')->value('last_sequence'));

        $connectionLossFailed = false;
        try {
            ProtectedStoreAccessSession::run(self::verificationContext(), fn () => (new DeterministicPatientNumberAllocator(
                new ParentNoPriorAllocation,
                self::storeFor('connection-loss', new SyntheticMariaDbConnectionLossInjector),
                new LaravelTargetPatientNumberCollisionProbe(
                    new SyntheticCurrentCollisionEvidence,
                    namespace: new SyntheticMariaDbCollisionNamespace('phase3b_allocator_verification'),
                ),
            ))->allocate($this->requestFor('connection-loss', '2028')));
        } catch (\Throwable) {
            $connectionLossFailed = true;
        }
        self::assertTrue($connectionLossFailed);
        self::assertSame(1, DB::connection('phase3b_allocator_verification')->table('patient_number_sequences')->where('period_key', '2028')->value('last_sequence'));
        self::assertSame(0, DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId('connection-loss'))->count());

        $consumptions = DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')
            ->where('period_key', '2026')->orderBy('sequence_ordinal')->get()->map(fn ($row) => new SequenceConsumption(
                hash_hmac('sha256', 'lineage|'.$row->idempotency_record_id, 'SYNTHETIC-P3B-MARIADB-ONLY'),
                (int) $row->sequence_ordinal,
                'committed',
            ));
        $reconciliation = (new SequenceReconciler)->reconcile(0, 3, $consumptions);
        self::assertTrue($reconciliation->passed);
        self::assertSame(0, $reconciliation->difference);
        self::assertSame(1, DB::connection('phase3b_allocator_verification')->table('phase3b_allocator_collision_namespace')->count());
        self::assertSame(17, DB::connection('phase3b_allocator_verification')->table('legacy_migration_protected_record_envelopes')->count());
        self::assertGreaterThanOrEqual(10, DB::connection('phase3b_allocator_verification')->table('legacy_migration_protected_access_audits')->count());

        $concurrencyResult = [
            'coordinate_2026_closing_ordinal' => 3,
            'coordinate_2027_closing_ordinal' => 1,
            'coordinate_2029_closing_ordinal' => 1,
            'same_lineage_reservation_count' => 1,
            'reservation_count' => 6,
            'snapshot_bound_envelope_count' => 17,
            'sealed_idempotency_parent_count' => 11,
        ];
        $rollbackResult = [
            'collision_namespace_count' => 1,
            'connection_loss_reservation_count' => DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId('connection-loss'))->count(),
            'fault_before_reservation_count' => DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId('fault-before'))->count(),
            'fault_cas_reservation_count' => DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId('fault-cas'))->count(),
            'fault_insert_reservation_count' => DB::connection('phase3b_allocator_verification')->table('legacy_migration_number_reservations')->where('idempotency_record_id', $this->idempotencyId('fault-insert'))->count(),
            'stable_2026_closing_ordinal' => 3,
        ];
        self::assertSame(0, array_sum(array_values(array_diff_key($rollbackResult, ['collision_namespace_count' => true, 'stable_2026_closing_ordinal' => true]))));
        fwrite(STDOUT, 'PHASE3B_ALLOCATOR_EXECUTION_REFERENCE=phase3b-allocator-mariadb-10.4.32-20260722-current-source'.PHP_EOL);
        fwrite(STDOUT, 'PHASE3B_ALLOCATOR_CONCURRENCY_RESULT_SHA256='.hash('sha256', json_encode($concurrencyResult, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)).PHP_EOL);
        fwrite(STDOUT, 'PHASE3B_ALLOCATOR_ROLLBACK_RESULT_SHA256='.hash('sha256', json_encode($rollbackResult, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)).PHP_EOL);
    }

    public static function configureDisposableConnection(): void
    {
        self::$schema = getenv('PHASE3B_MARIADB_SCHEMA') ?: self::$schema;
        if (self::$physicalAuthorityReference === '') {
            self::configureAdminConnection();
            self::$physicalAuthorityReference = self::verifyPhysicalAttestation(
                DB::connection('phase3b_allocator_admin')->selectOne('SELECT VERSION() AS version, @@server_id AS server_id, @@hostname AS hostname, @@port AS port'),
            );
        }
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('M', 32)),
            'database.default' => 'phase3b_allocator_verification',
            'database.connections.phase3b_allocator_verification' => self::connection(self::$schema),
            'legacy-migration.disposable_verification' => [
                'enabled' => true,
                'identity_verified' => self::$physicalAuthorityReference !== '',
                'environment_reference' => self::$physicalAuthorityReference,
                'connection' => 'phase3b_allocator_verification',
                'database' => self::$schema,
            ],
            'legacy-migration.source.connection' => 'legacy_uhms',
            'legacy-migration.target.connection' => 'mysql',
        ]);
        DB::purge('phase3b_allocator_verification');
    }

    public static function verificationContext(): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext(
            'mariadb_allocator_verification', ['write'], ['patient_number'], 'testing',
            null, null, null, 'synthetic_restricted', 'synthetic_test', self::$physicalAuthorityReference,
        );
    }

    private static function configureAdminConnection(): void
    {
        config(['database.connections.phase3b_allocator_admin' => self::connection('information_schema')]);
        DB::purge('phase3b_allocator_admin');
    }

    private static function verifyPhysicalAttestation(object $identity): string
    {
        return DisposableMariaDbPhysicalAttestation::verify(
            getenv('PHASE3B_MARIADB_DISPOSABLE_MARKER') ?: '',
            getenv('PHASE3B_MARIADB_ATTESTATION_SHA256') ?: '',
            self::$schema,
            [
                'hostname' => (string) $identity->hostname,
                'port' => (int) $identity->port,
                'server_id' => (int) $identity->server_id,
                'version' => (string) $identity->version,
            ],
        );
    }

    /** @return array<string,mixed> */
    private static function connection(string $database): array
    {
        return [
            'driver' => 'mysql', 'host' => getenv('PHASE3B_MARIADB_HOST'), 'port' => getenv('PHASE3B_MARIADB_PORT'),
            'database' => $database, 'username' => getenv('PHASE3B_MARIADB_USER'), 'password' => getenv('PHASE3B_MARIADB_PASSWORD'),
            'unix_socket' => '', 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'prefix' => '',
            'prefix_indexes' => true, 'strict' => true, 'engine' => 'InnoDB',
        ];
    }

    private function createSyntheticTables(): void
    {
        $schema = Schema::connection('phase3b_allocator_verification');
        $schema->create('migrations', function (Blueprint $table): void {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
        });
        $schema->create('migration_environment_attestations', function (Blueprint $table): void {
            $table->id();
            $table->string('server_role');
            $table->string('environment_identity');
            $table->string('attestation_version');
            $table->string('tls_peer_identity')->nullable();
            $table->boolean('active');
        });
        DB::connection('phase3b_allocator_verification')->table('migration_environment_attestations')->insert([
            'server_role' => 'disposable_verification',
            'environment_identity' => 'phase3b_allocator_loopback',
            'attestation_version' => 'synthetic-v1',
            'tls_peer_identity' => null,
            'active' => true,
        ]);

        $connection = DB::connection('phase3b_allocator_verification');
        $hasher = new HmacIdentityReferenceHasher('testing', 'allocator-identity', 'v1', hash('sha512', 'disposable-allocator-identity-material'));
        $observer = new LaravelPhysicalServerIdentityObserver('phase3b_allocator_verification', $connection, $hasher);
        $observed = $observer->observe();
        $physical = (new PhysicalServerIdentityVerifier($hasher))->observationReference($observed);
        $identity = PhysicalServerIdentityVerification::issue(
            'synthetic-v1', self::$physicalAuthorityReference, hash('sha256', 'preinstall|'.self::$schema),
            hash('sha256', 'configuration|'.self::$schema), $hasher,
            $observed->connectionInstanceReference, $physical,
        );
        $manifests = [new MariaDbFoundationDdlManifest, new MariaDbFoundationDdlExtensionManifest];
        $payloads = [];
        foreach ($manifests as $manifest) {
            $payloads[$manifest->version()] = $manifest->payloadHash();
        }
        $contract = InstallationIdentityContract::issue(
            $identity->approvedIdentityReference, $identity->connectionInstanceReference,
            $identity->structuralIdentityReference, $payloads, hash('sha256', 'empty-partial-state'), $hasher,
        );
        $session = InstallationSessionCapability::issue($contract, hash('sha256', 'allocator-install-audit'), array_keys($payloads), $hasher);
        foreach ($manifests as $manifest) {
            $executor = new MariaDbFoundationDdlOperationExecutor($connection, $manifest, $hasher);
            foreach ($manifest->operations() as $operation) {
                $executor->execute($manifest->version(), $operation->operationId, $identity, new IdentityBoundDdlGate($hasher), $session);
            }
        }
        $legacyTableCount = (int) $connection->table('information_schema.tables')
            ->where('table_schema', self::$schema)->where('table_name', 'like', 'legacy_migration_%')->count();
        self::assertSame(24, $legacyTableCount);

        $schema->create('patient_number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('prefix');
            $table->string('period_type');
            $table->string('period_key');
            $table->unsignedBigInteger('last_sequence')->default(0);
            $table->timestamps();
            $table->unique(['prefix', 'period_type', 'period_key']);
        });
        $schema->create('phase3b_allocator_collision_namespace', function (Blueprint $table): void {
            $table->id();
            $table->string('candidate')->unique();
        });
    }

    private function seedFoundationCoordinates(): void
    {
        $db = DB::connection('phase3b_allocator_verification');
        $now = now();
        $bundleId = $db->table('legacy_migration_contract_bundles')->insertGetId([
            'bundle_token' => hash('sha256', 'bundle'), 'bundle_version' => 'phase-2f/2F.1.0',
            'bundle_hash' => hash('sha256', 'bundle-hash'), 'canonicalization_version' => 'typed-length-prefix/1',
            'token_environment' => 'testing', 'hmac_key_id' => 'allocator-key', 'hmac_key_version' => 'v1',
            'encrypted_manifest' => 'synthetic', 'integrity_checksum' => hash('sha256', 'bundle-integrity'),
            'access_classification' => 'synthetic_restricted', 'retention_classification' => 'synthetic_test',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        self::$runId = (int) $db->table('legacy_migration_runs')->insertGetId([
            'run_token' => hash('sha256', 'run'), 'cohort_token' => hash('sha256', 'cohort'),
            'contract_bundle_id' => $bundleId, 'mode' => 'dry_run', 'state' => 'NOT_STARTED',
            'configuration_fingerprint' => hash('sha256', 'config'), 'source_fingerprint' => hash('sha256', 'source'),
            'target_fingerprint' => hash('sha256', 'target'), 'canonicalization_version' => 'typed-length-prefix/1',
            'token_environment' => 'testing', 'hmac_key_id' => 'allocator-key', 'hmac_key_version' => 'v1',
            'encrypted_manifest' => 'synthetic', 'manifest_checksum' => hash('sha256', 'run-integrity'),
            'access_classification' => 'synthetic_restricted', 'retention_classification' => 'synthetic_test',
            'lock_version' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        self::$sourceSnapshotId = $this->seedSnapshot('source');
        self::$targetSnapshotId = $this->seedSnapshot('target');
    }

    private function seedSnapshot(string $kind): int
    {
        return (int) DB::connection('phase3b_allocator_verification')->table('legacy_migration_snapshots')->insertGetId([
            'run_id' => self::$runId, 'snapshot_token' => hash('sha256', 'snapshot|'.$kind),
            'snapshot_kind' => $kind, 'domain' => 'patient', 'coordinate_token' => hash('sha256', 'coordinate|'.$kind),
            'database_fingerprint' => hash('sha256', 'database|'.$kind), 'schema_fingerprint' => hash('sha256', 'schema|'.$kind),
            'query_bundle_hash' => hash('sha256', 'query|'.$kind), 'result_hash' => hash('sha256', 'result|'.$kind),
            'contract_version' => 'phase-2f/2F.1.0', 'transformation_version' => 'allocator-v1',
            'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
            'state' => 'captured', 'integrity_checksum' => hash('sha256', 'snapshot-integrity|'.$kind),
            'access_classification' => 'synthetic_restricted', 'retention_classification' => 'synthetic_test',
            'captured_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @param list<string> $seeds */
    private function seedIdempotency(array $seeds): void
    {
        foreach ($seeds as $seed) {
            $sourceDigest = hash('sha256', "source|{$seed}");
            $idempotencyDigest = hash_hmac('sha256', "core|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY');
            $id = DB::connection('phase3b_allocator_verification')->table('legacy_migration_idempotency_records')->insertGetId([
                'run_id' => self::$runId,
                'source_snapshot_id' => self::$sourceSnapshotId,
                'target_snapshot_id' => self::$targetSnapshotId,
                'domain' => 'patient_core',
                'protected_source_token' => $sourceDigest,
                'idempotency_token' => $idempotencyDigest,
                'outcome_type' => 'patient_number_reservation',
                'input_fingerprint' => hash('sha256', "input|{$seed}"),
                'contract_version' => 'phase-2f/2F.1.0',
                'transformation_version' => 'allocator-v1',
                'canonicalization_version' => 'typed-length-prefix/1',
                'hmac_key_version' => 'v1',
                'state' => 'pending',
                'attempt_count' => 0,
                'integrity_checksum' => hash('sha256', "idempotency|{$seed}"),
                'access_classification' => 'synthetic_restricted',
                'retention_classification' => 'synthetic_test',
                'lock_version' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            [, $protection] = SyntheticMariaDbReservationProtection::build(self::$runId, self::$sourceSnapshotId, self::$targetSnapshotId);
            $protection->sealIdempotency((int) $id, $sourceDigest, $idempotencyDigest);
        }
    }

    /** @param list<array{string,string}> $workers */
    private function runConcurrent(array $workers): void
    {
        $start = microtime(true) + 2.0;
        $processes = [];
        foreach ($workers as [$seed, $period]) {
            $process = new Process([PHP_BINARY, 'artisan', 'test', 'tests/Feature/LegacyMigration/Foundation/MariaDbAllocatorWorkerTest.php', '--compact'], base_path(), [
                'PHASE3B_ALLOCATOR_WORKER_SEED' => $seed,
                'PHASE3B_ALLOCATOR_WORKER_PERIOD' => $period,
                'PHASE3B_ALLOCATOR_WORKER_START_AT' => (string) $start,
            ]);
            $process->setTimeout(30);
            $process->start();
            $processes[] = $process;
        }
        foreach ($processes as $process) {
            $process->wait();
            $safeFailure = preg_replace('/\(Connection:.*?\)/s', '(connection details redacted)', $process->getErrorOutput().$process->getOutput()) ?? '';
            self::assertTrue($process->isSuccessful(), 'A concurrent allocator worker failed: '.$safeFailure);
        }
    }

    private function idempotencyId(string $seed): int
    {
        return (int) DB::connection('phase3b_allocator_verification')->table('legacy_migration_idempotency_records')
            ->where('domain', 'patient_core')->where('idempotency_token', hash_hmac('sha256', "core|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY'))->value('id');
    }

    private function expectAllocationFailure(string $seed, AllocationFaultPoint $point, bool $injectFault): void
    {
        $connection = 'phase3b_allocator_verification';
        $request = new AllocationRequest(
            hash_hmac('sha256', "source|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY'),
            hash_hmac('sha256', "core|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY'),
            new PinnedNumberingConfiguration('UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 8, NumberingResetPeriod::Yearly, 'UTC', '2026'),
            AllocationMode::Commit,
        );
        $fault = $injectFault ? new SyntheticMariaDbFaultInjector($point) : null;
        $store = self::storeFor($seed, $fault);

        $failed = false;
        try {
            ProtectedStoreAccessSession::run(self::verificationContext(), fn () => (new DeterministicPatientNumberAllocator(
                new ParentNoPriorAllocation,
                $store,
                new LaravelTargetPatientNumberCollisionProbe(
                    new SyntheticCurrentCollisionEvidence,
                    namespace: new SyntheticMariaDbCollisionNamespace($connection),
                ),
            ))->allocate($request));
        } catch (\Throwable) {
            $failed = true;
        }
        self::assertTrue($failed, 'The synthetic collision/fault did not abort allocation.');
    }

    public static function storeFor(string $seed, ?AllocationFaultInjector $fault = null): LaravelNumberReservationStore
    {
        $connection = 'phase3b_allocator_verification';
        $idempotency = DB::connection($connection)->table('legacy_migration_idempotency_records')
            ->where('domain', 'patient_core')
            ->where('idempotency_token', hash_hmac('sha256', "core|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY'))
            ->first(['id', 'run_id', 'source_snapshot_id', 'target_snapshot_id']);
        if ($idempotency === null) {
            throw new \RuntimeException('Synthetic allocator idempotency coordinate is missing.');
        }
        [$repository, $protection] = SyntheticMariaDbReservationProtection::build(
            (int) $idempotency->run_id,
            (int) $idempotency->source_snapshot_id,
            (int) $idempotency->target_snapshot_id,
        );

        return new LaravelNumberReservationStore(
            new SyntheticMariaDbReservationAttributes((int) $idempotency->id, (int) $idempotency->run_id),
            $connection,
            new SyntheticMariaDbAllocationWriteGuard($connection),
            $fault ?? new NoopAllocationFaultInjector,
            $repository,
            $protection,
        );
    }

    private function requestFor(string $seed, string $period): AllocationRequest
    {
        return new AllocationRequest(
            hash_hmac('sha256', "source|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY'),
            hash_hmac('sha256', "core|{$seed}", 'SYNTHETIC-P3B-MARIADB-ONLY'),
            new PinnedNumberingConfiguration('UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 8, NumberingResetPeriod::Yearly, 'UTC', $period),
            AllocationMode::Commit,
        );
    }
}

final class ParentNoPriorAllocation implements AllocationLineageResolver
{
    public function resolveSuccessful(AllocationRequest $request): ?ExistingAllocation
    {
        return null;
    }
}
