<?php

namespace Tests\Feature\LegacyMigration\Foundation;

use App\Models\LegacyMigration\AtomicIntent;
use App\Models\LegacyMigration\Checkpoint;
use App\Models\LegacyMigration\MigrationAuditEvent;
use App\Models\LegacyMigration\RecoveryJournalEntry;
use App\Models\LegacyMigration\Snapshot;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationMode;
use App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest;
use App\Services\LegacyMigration\Foundation\Allocation\ExistingAllocation;
use App\Services\LegacyMigration\Foundation\Allocation\LaravelCrosswalkAllocationLineageResolver;
use App\Services\LegacyMigration\Foundation\Allocation\NumberReservationStore;
use App\Services\LegacyMigration\Foundation\Allocation\NumberingResetPeriod;
use App\Services\LegacyMigration\Foundation\Allocation\PinnedNumberingConfiguration;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicIntentDescriptor;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicRecoveryCoordinator;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicRecoveryJournal;
use App\Services\LegacyMigration\Foundation\Recovery\AtomicUnit;
use App\Services\LegacyMigration\Foundation\Recovery\AuthorityBoundRecoveryJournalAttributeFactory;
use App\Services\LegacyMigration\Foundation\Recovery\CompareAndSetStateStore;
use App\Services\LegacyMigration\Foundation\Recovery\ConfiguredRecoveryJournalAuthority;
use App\Services\LegacyMigration\Foundation\Recovery\CrashBoundary;
use App\Services\LegacyMigration\Foundation\Recovery\LaravelAtomicRecoveryJournal;
use App\Services\LegacyMigration\Foundation\Recovery\LaravelCompareAndSetStateStore;
use App\Services\LegacyMigration\Foundation\Recovery\MigrationState;
use App\Services\LegacyMigration\Foundation\Recovery\MonotonicStateMachine;
use App\Services\LegacyMigration\Foundation\Recovery\ProtectedRecoveryStore;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryDisposition;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryException;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAttributeFactory;
use App\Services\LegacyMigration\Foundation\Recovery\RecoveryJournalAuthority;
use App\Services\LegacyMigration\Foundation\Recovery\StateSnapshot;
use App\Services\LegacyMigration\Foundation\Runtime\ExecutionMode;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRunActivationAuthority;
use App\Services\LegacyMigration\Foundation\Runtime\MigrationRuntimeRequest;
use App\Services\LegacyMigration\Foundation\Runtime\ProtectedMigrationRuntimeAudit;
use App\Services\LegacyMigration\Foundation\Security\CanonicalizationVersionRegistry;
use App\Services\LegacyMigration\Foundation\Security\CanonicalTypedMessageEncoder;
use App\Services\LegacyMigration\Foundation\Security\LegacyMigrationSecurityFactory;
use App\Services\LegacyMigration\Foundation\Security\PinnedProtectedStoreAccessAuthority;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeFactory;
use App\Services\LegacyMigration\Foundation\Security\ProtectedRecordEnvelopeVerifier;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessDeniedException;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessGuard;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreAccessSession;
use App\Services\LegacyMigration\Foundation\Security\ProtectedStoreOperationContext;
use App\Services\LegacyMigration\Foundation\Security\ProtectedToken;
use App\Services\LegacyMigration\Foundation\Security\TokenDomain;
use App\Services\LegacyMigration\Foundation\Security\TypedValue;
use App\Services\LegacyMigration\Foundation\Storage\CompareAndSet;
use App\Services\LegacyMigration\Foundation\Storage\CrosswalkRepository;
use App\Services\LegacyMigration\Foundation\Storage\IdempotencyRepository;
use App\Services\LegacyMigration\Foundation\Storage\MigrationAuditRepository;
use App\Services\LegacyMigration\Foundation\Storage\NumberReservationRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedEvidenceRepository;
use App\Services\LegacyMigration\Foundation\Storage\ProtectedRecordSecurityRepository;
use App\Services\LegacyMigration\Foundation\Storage\ReconciliationRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryJournalRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryRepository;
use App\Services\LegacyMigration\Foundation\Storage\RunManifestRepository;
use App\Services\LegacyMigration\Foundation\Storage\SnapshotRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use Closure;

final class DurableRecoveryJournalTest extends TestCase
{
    private string $databaseFile;

    private AuthorityBoundRecoveryJournalAttributeFactory $attributes;

    private ProtectedRecordSecurityRepository $security;

    private ProtectedRecoveryStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        $workerDatabase = getenv('PHASE3B_RECOVERY_PROCESS_DATABASE') ?: '';
        $this->databaseFile = $workerDatabase !== '' ? $workerDatabase : tempnam(sys_get_temp_dir(), 'uhms-p3b-recovery-');
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('R', 32)),
            'database.default' => 'phase3b_recovery',
            'database.connections.phase3b_recovery' => ['driver' => 'sqlite', 'database' => $this->databaseFile, 'prefix' => '', 'foreign_key_constraints' => true],
            'legacy-migration' => $this->configuration(),
        ]);
        putenv('LEGACY_MIGRATION_TEST_RECOVERY_KEY='.self::fixtureKeyMaterial());
        DB::purge('phase3b_recovery');
        DB::connection('phase3b_recovery')->statement('PRAGMA foreign_keys = ON');
        if ((getenv('PHASE3B_RECOVERY_PROCESS_MODE') ?: '') !== 'recover') {
            foreach ([
                '2026_07_22_000110_create_legacy_migration_run_foundation_tables.php',
                '2026_07_22_000111_create_legacy_migration_protected_store_tables.php',
                '2026_07_22_000112_create_legacy_migration_recovery_tables.php',
                '2026_07_22_000114_create_legacy_migration_protected_lifecycle_tables.php',
                '2026_07_22_000115_create_legacy_migration_recovery_journal.php',
            ] as $file) {
                (require database_path("migrations/{$file}"))->up();
            }
        }
        $this->buildBoundary();
    }

    protected function tearDown(): void
    {
        ProtectedStoreAccessSession::reset();
        putenv('LEGACY_MIGRATION_TEST_RECOVERY_KEY');
        DB::purge('phase3b_recovery');
        if ((getenv('PHASE3B_RECOVERY_PROCESS_DATABASE') ?: '') === '' && isset($this->databaseFile) && is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }
        parent::tearDown();
    }

    #[Test]
    #[DataProvider('durableCrashBoundaries')]
    public function all_ten_boundaries_recover_with_current_keyed_envelopes_and_audits(
        CrashBoundary $boundary,
        MigrationState $priorState,
        AtomicUnit $unit,
        RecoveryDisposition $expected,
        bool $checkpointRepair,
    ): void {
        $descriptor = $this->seedCoordinate($priorState, $unit);
        $journal = new LaravelAtomicRecoveryJournal($this->store);
        $journal->transaction(fn () => $journal->resolveOrCreateIntent($descriptor));
        $this->seedDurableFacts($descriptor, $boundary);
        $durableCounts = $this->durableFactCounts();

        DB::disconnect('phase3b_recovery');
        DB::reconnect('phase3b_recovery');
        DB::connection('phase3b_recovery')->statement('PRAGMA foreign_keys = ON');
        $this->buildBoundary();
        $decision = (new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store)))
            ->recover($descriptor, $boundary);

        self::assertSame($expected, $decision->disposition);
        self::assertSame(1, DB::table('legacy_migration_recovery_journal_entries')->count());
        self::assertSame((int) $checkpointRepair, DB::table('legacy_migration_checkpoints')->count());
        self::assertSame($durableCounts, $this->durableFactCounts(), 'Recovery replayed or changed a durable unit.');
        $this->assertEveryRecoveryRecordProtected();

        DB::disconnect('phase3b_recovery');
        DB::reconnect('phase3b_recovery');
        $this->buildBoundary();
        $again = (new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store)))
            ->recover($descriptor, $boundary);
        self::assertSame($decision->disposition, $again->disposition);
        self::assertSame(1, DB::table('legacy_migration_recovery_journal_entries')->count());
        self::assertFalse(DB::getSchemaBuilder()->hasTable('patients'));
    }

    #[Test]
    public function every_cas_transition_reseals_the_intent_and_seals_its_journal_entry(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::NotStarted, AtomicUnit::PatientCore);
        (new LaravelAtomicRecoveryJournal($this->store))->transaction(
            fn () => (new LaravelAtomicRecoveryJournal($this->store))->resolveOrCreateIntent($descriptor),
        );
        $machine = new MonotonicStateMachine(new LaravelCompareAndSetStateStore($this->store));
        $first = $machine->transition(new StateSnapshot($descriptor->intentToken, MigrationState::NotStarted, 0, 0), MigrationState::Extracted);
        $second = $machine->transition($first, MigrationState::Classified);

        self::assertSame(2, $second->version);
        self::assertSame(3, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', AtomicIntent::class)->count());
        self::assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', RecoveryJournalEntry::class)->count());
        self::assertSame(2, DB::table('legacy_migration_recovery_journal_entries')->count());
        $this->assertEveryRecoveryRecordProtected();
    }

    #[Test]
    public function direct_adapter_and_repository_reject_illegal_state_edges_without_mutation(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::NotStarted, AtomicUnit::PatientCore);
        $journal = new LaravelAtomicRecoveryJournal($this->store);
        $journal->transaction(fn () => $journal->resolveOrCreateIntent($descriptor));
        $intentId = (int) DB::table('legacy_migration_atomic_intents')->where('intent_token', $descriptor->intentToken)->value('id');
        $runId = (int) DB::table('legacy_migration_atomic_intents')->where('id', $intentId)->value('run_id');

        try {
            (new LaravelCompareAndSetStateStore($this->store))->compareAndSet(
                $descriptor->intentToken,
                MigrationState::NotStarted,
                0,
                0,
                MigrationState::Completed,
                1,
            );
            self::fail('The concrete CAS adapter accepted an illegal state jump.');
        } catch (RecoveryException $exception) {
            self::assertStringContainsString('RECOVERY-INVALID-TRANSITION', $exception->getMessage());
        }

        try {
            (new RecoveryRepository(new CompareAndSet($this->security), $this->security))->advanceIntentProtected(
                new ProtectedStoreOperationContext(
                    'foundation_recovery', ['transition'], ['recovery'], 'testing', $runId,
                    null, null, 'protected', 'migration-lineage', 'SYNTHETIC-RECOVERY-AUTHORITY', ['state'],
                ),
                $intentId,
                MigrationState::NotStarted->value,
                0,
                MigrationState::Completed->value,
            );
            self::fail('The protected repository accepted an illegal state jump.');
        } catch (RecoveryException $exception) {
            self::assertStringContainsString('RECOVERY-INVALID-TRANSITION', $exception->getMessage());
        }

        self::assertSame(MigrationState::NotStarted->value, DB::table('legacy_migration_atomic_intents')->where('id', $intentId)->value('state'));
        self::assertSame(0, DB::table('legacy_migration_atomic_intents')->where('id', $intentId)->value('lock_version'));
        self::assertSame(1, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', AtomicIntent::class)->where('record_id', $intentId)->count());
    }

    #[Test]
    public function direct_adapter_rejects_terminal_exit_unchanged_state_and_invalid_attempt_increment(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::NotStarted, AtomicUnit::PatientCore);
        $journal = new LaravelAtomicRecoveryJournal($this->store);
        $journal->transaction(fn () => $journal->resolveOrCreateIntent($descriptor));
        $adapter = new LaravelCompareAndSetStateStore($this->store);

        foreach ([
            [MigrationState::NotStarted, MigrationState::NotStarted, 1],
            [MigrationState::Completed, MigrationState::Extracted, 1],
        ] as [$from, $to, $attempt]) {
            try {
                $adapter->compareAndSet($descriptor->intentToken, $from, 0, 0, $to, $attempt);
                self::fail('The concrete CAS adapter accepted an illegal edge.');
            } catch (RecoveryException $exception) {
                self::assertStringContainsString('RECOVERY-INVALID-TRANSITION', $exception->getMessage());
            }
        }

        self::assertNull($adapter->compareAndSet(
            $descriptor->intentToken,
            MigrationState::NotStarted,
            0,
            0,
            MigrationState::Extracted,
            2,
        ));
        self::assertSame(MigrationState::NotStarted->value, DB::table('legacy_migration_atomic_intents')->where('intent_token', $descriptor->intentToken)->value('state'));
    }

    #[Test]
    public function sealed_decision_replay_rejects_durable_facts_added_after_the_decision(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::Classified, AtomicUnit::PatientCore);
        $coordinator = new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store));
        $decision = $coordinator->recover($descriptor, CrashBoundary::BeforeNumberAllocation);
        self::assertTrue($decision->targetWritesPermitted);

        $this->seedDurableFacts($descriptor, CrashBoundary::AfterCoreCommitBeforeCheckpoint);
        DB::disconnect('phase3b_recovery');
        DB::reconnect('phase3b_recovery');
        $this->buildBoundary();

        try {
            (new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store)))
                ->recover($descriptor, CrashBoundary::BeforeNumberAllocation);
            self::fail('A stale recovery decision was replayed after durable evidence changed.');
        } catch (RecoveryException $exception) {
            self::assertStringContainsString('RECOVERY-RECORDED-EVIDENCE-CHANGED', $exception->getMessage());
        }
        self::assertSame(1, DB::table('legacy_migration_recovery_journal_entries')->count());
    }

    #[Test]
    public function reservation_coordinate_verifies_source_run_snapshots_and_bundle_envelopes(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::Classified, AtomicUnit::PatientCore);
        $idempotency = DB::table('legacy_migration_idempotency_records')
            ->where('idempotency_token', $descriptor->idempotencyToken)
            ->first(['protected_source_token', 'source_snapshot_id']);
        self::assertNotNull($idempotency);

        $coordinate = $this->store->transaction(fn () => $this->store->reservationCoordinate(
            $descriptor->idempotencyToken,
            (string) $idempotency->protected_source_token,
        ));
        self::assertNotNull($coordinate->hint->targetSnapshotId);
        self::assertSame('patient_core', $coordinate->domain);

        $configuration = $this->configuration();
        $configuration['allocation'] = [
            'protected_reservations_enabled' => true,
            'authority_verified' => true,
            'connection' => 'phase3b_recovery',
            'authority_reference' => 'SYNTHETIC-RECOVERY-AUTHORITY',
            'access_classification' => 'protected',
            'retention_classification' => 'migration-lineage',
            'transformation_version' => 'foundation-recovery/v1',
        ];
        $configuration['hmac']['domains'][] = 'number_reservation';
        $configuration['key_provider']['references'][0]['domains'][] = 'number_reservation';
        $tokens = LegacyMigrationSecurityFactory::make($configuration, 'testing', static fn () => self::fixtureKeyMaterial());
        $factory = new \App\Services\LegacyMigration\Foundation\Allocation\AuthorityBoundReservationFactory(
            $tokens,
            new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1')),
            $this->store,
            $configuration,
            'testing',
        );
        $request = new \App\Services\LegacyMigration\Foundation\Allocation\AllocationRequest(
            (string) $idempotency->protected_source_token,
            $descriptor->idempotencyToken,
            new \App\Services\LegacyMigration\Foundation\Allocation\PinnedNumberingConfiguration(
                'UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 8,
                \App\Services\LegacyMigration\Foundation\Allocation\NumberingResetPeriod::Yearly, 'UTC', '2026',
            ),
            \App\Services\LegacyMigration\Foundation\Allocation\AllocationMode::DryRun,
        );
        $context = $factory->context($request);
        self::assertSame($coordinate->hint->sourceSnapshotId, $context->sourceSnapshotId());
        self::assertSame($coordinate->hint->targetSnapshotId, $context->targetSnapshotId());
        self::assertSame($coordinate->hint->idempotencyRecordId, $factory->make($request, 'UHMS-00000001/2026', 1)['idempotency_record_id']);
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $factory->expectedProtectedSourceToken($request));
        self::assertTrue(app()->bound(\App\Services\LegacyMigration\Foundation\Allocation\NumberReservationStore::class));

        $disabled = $configuration;
        $disabled['allocation']['authority_verified'] = false;
        try {
            (new \App\Services\LegacyMigration\Foundation\Allocation\AuthorityBoundReservationFactory(
                $tokens,
                new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1')),
                $this->store,
                $disabled,
                'testing',
            ))->context($request);
            self::fail('The production reservation factory was usable without verified allocation authority.');
        } catch (\App\Services\LegacyMigration\Foundation\Allocation\AllocationException $exception) {
            self::assertStringContainsString('PATIENT-NUM-PROTECTED-AUTHORITY-NOT-ENABLED', $exception->getMessage());
        }

        try {
            $this->store->transaction(fn () => $this->store->reservationCoordinate(
                $descriptor->idempotencyToken,
                $this->digest('different-source-lineage'),
            ));
            self::fail('A different protected source lineage was accepted.');
        } catch (\Throwable $exception) {
            self::assertStringContainsString('incompatible lineage', $exception->getMessage());
        }

        self::assertGreaterThanOrEqual(5, DB::table('legacy_migration_protected_access_audits')
            ->where('operation', 'read')->where('result_code', 'authorized')->count());
    }

    #[Test]
    public function allocation_crosswalk_resolver_rejects_unsealed_and_raw_tampered_lineage_with_audit(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::Classified, AtomicUnit::PatientCore);
        $this->seedDurableFacts($descriptor, CrashBoundary::AfterCoreCommitBeforeCheckpoint);
        $row = DB::table('legacy_migration_crosswalks')->first();
        self::assertNotNull($row);
        $request = new AllocationRequest(
            (string) $row->protected_source_token,
            (string) $row->idempotency_token,
            new PinnedNumberingConfiguration('UHMS', '{PREFIX}-{SEQUENCE}/{YEAR}', 8, NumberingResetPeriod::Yearly, 'UTC', '2026'),
            AllocationMode::Commit,
        );
        $resolver = new LaravelCrosswalkAllocationLineageResolver(
            new SyntheticExistingReservationStore,
            $this->security,
            $this->attributes,
            'recovery',
            'phase3b_recovery',
        );
        self::assertNotNull($resolver->resolveSuccessful($request));

        try {
            DB::table('legacy_migration_crosswalks')->where('id', $row->id)->update(['contract_version' => 'SYNTHETIC-TAMPERED-CONTRACT']);
            self::fail('The immutable crosswalk row accepted a raw lineage mutation.');
        } catch (QueryException $exception) {
            self::assertStringContainsString('immutable migration lineage', $exception->getMessage());
        }
        $denied = DB::table('legacy_migration_protected_access_audits')->where('result_code', 'denied_or_tampered')->count();
        $tamperedEnvelope = (array) DB::table('legacy_migration_protected_record_envelopes')
            ->where('record_type', \App\Models\LegacyMigration\Crosswalk::class)->where('record_id', $row->id)
            ->orderByDesc('generation')->first();
        unset($tamperedEnvelope['id']);
        $tamperedEnvelope['generation'] = ((int) $tamperedEnvelope['generation']) + 1;
        $tamperedEnvelope['encrypted_integrity_seal'] = 'SYNTHETIC-INVALID-ENVELOPE-SEAL';
        DB::table('legacy_migration_protected_record_envelopes')->insert($tamperedEnvelope);
        try {
            $resolver->resolveSuccessful($request);
            self::fail('A raw-tampered crosswalk authorized allocation lineage.');
        } catch (\Throwable $exception) {
            self::assertNotSame('', $exception->getMessage());
        }
        self::assertSame($denied + 1, DB::table('legacy_migration_protected_access_audits')->where('result_code', 'denied_or_tampered')->count());

        $unsealedSource = $this->digest('unsealed-crosswalk-source');
        $unsealedCore = $this->digest('unsealed-crosswalk-core');
        $copy = (array) $row;
        unset($copy['id'], $copy['active_source_guard_key']);
        $copy['protected_source_token'] = $unsealedSource;
        $copy['protected_target_token'] = $this->digest('unsealed-crosswalk-target');
        $copy['active_coordinate_token'] = $this->digest('unsealed-crosswalk-coordinate');
        $copy['idempotency_token'] = $unsealedCore;
        $unsealedId = DB::table('legacy_migration_crosswalks')->insertGetId($copy);
        try {
            $resolver->resolveSuccessful(new AllocationRequest($unsealedSource, $unsealedCore, $request->configuration, AllocationMode::Commit));
            self::fail('An unsealed raw crosswalk authorized allocation lineage.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            self::assertStringContainsString('LM-SEC-STORE-ENVELOPE-MISSING-001', $exception->getMessage());
        }
        self::assertSame(0, DB::table('legacy_migration_protected_access_audits')
            ->where('record_type', \App\Models\LegacyMigration\Crosswalk::class)
            ->where('record_id', $unsealedId)->where('result_code', 'authorized')->count());

        $before = DB::table('legacy_migration_crosswalks')->count();
        try {
            (new LaravelCrosswalkAllocationLineageResolver(
                new SyntheticExistingReservationStore,
                $this->security,
                $this->attributes,
                'recovery',
                'sqlite',
            ))->resolveSuccessful($request);
            self::fail('A crosswalk query connection outside the protected reservation boundary was accepted.');
        } catch (\App\Services\LegacyMigration\Foundation\Allocation\AllocationException $exception) {
            self::assertStringContainsString('PATIENT-NUM-CROSSWALK-CONNECTION-MISMATCH', $exception->getMessage());
        }
        self::assertSame($before, DB::table('legacy_migration_crosswalks')->count());
    }

    #[Test]
    public function all_ten_boundaries_recover_after_an_abrupt_process_exit_and_fresh_boot(): void
    {
        foreach (self::durableCrashBoundaries() as $name => [$boundary, $state, $unit, $expected, $checkpoint]) {
            $database = tempnam(sys_get_temp_dir(), 'uhms-p3b-process-recovery-');
            $environment = [
                'PHASE3B_RECOVERY_PROCESS_DATABASE' => $database,
                'PHASE3B_RECOVERY_PROCESS_BOUNDARY' => $boundary->value,
                'PHASE3B_RECOVERY_PROCESS_STATE' => $state->value,
                'PHASE3B_RECOVERY_PROCESS_UNIT' => $unit->value,
                'PHASE3B_RECOVERY_PROCESS_EXPECTED' => $expected->value,
                'PHASE3B_RECOVERY_PROCESS_CHECKPOINT' => $checkpoint ? '1' : '0',
            ];
            $seed = $this->recoveryWorker($environment + ['PHASE3B_RECOVERY_PROCESS_MODE' => 'seed']);
            $seed->run();
            self::assertSame(73, $seed->getExitCode(), "{$name}: the seed process did not terminate at the injected crash boundary: ".$this->safeProcessOutput($seed));

            $recover = $this->recoveryWorker($environment + ['PHASE3B_RECOVERY_PROCESS_MODE' => 'recover']);
            $recover->run();
            self::assertTrue($recover->isSuccessful(), "{$name}: fresh-process recovery failed: ".$this->safeProcessOutput($recover));
            self::assertFileExists($database);
            unlink($database);
        }
    }

    #[Test]
    public function process_worker_persists_or_recovers_one_boundary(): void
    {
        $mode = getenv('PHASE3B_RECOVERY_PROCESS_MODE') ?: '';
        if (! in_array($mode, ['seed', 'recover'], true)) {
            $this->markTestSkipped('This worker is invoked only by the process-interruption recovery proof.');
        }
        $boundary = CrashBoundary::from((string) getenv('PHASE3B_RECOVERY_PROCESS_BOUNDARY'));
        if ($mode === 'seed') {
            $descriptor = $this->seedCoordinate(
                MigrationState::from((string) getenv('PHASE3B_RECOVERY_PROCESS_STATE')),
                AtomicUnit::from((string) getenv('PHASE3B_RECOVERY_PROCESS_UNIT')),
            );
            $journal = new LaravelAtomicRecoveryJournal($this->store);
            $journal->transaction(fn () => $journal->resolveOrCreateIntent($descriptor));
            $this->seedDurableFacts($descriptor, $boundary);
            DB::connection('phase3b_recovery')->getPdo()->exec('PRAGMA wal_checkpoint(FULL)');
            exit(73);
        }

        $intent = DB::table('legacy_migration_atomic_intents')->first();
        $idempotency = DB::table('legacy_migration_idempotency_records')->where('id', $intent->idempotency_record_id)->first();
        self::assertNotNull($intent);
        self::assertNotNull($idempotency);
        $descriptor = new AtomicIntentDescriptor(
            (string) $intent->intent_token,
            (string) $idempotency->idempotency_token,
            AtomicUnit::from((string) $intent->unit_name),
            MigrationState::from((string) $intent->expected_prior_state),
            (int) $intent->attempt,
            (string) $idempotency->input_fingerprint,
        );
        $before = $this->durableFactCounts();
        $decision = (new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store)))->recover($descriptor, $boundary);
        self::assertSame((string) getenv('PHASE3B_RECOVERY_PROCESS_EXPECTED'), $decision->disposition->value);
        self::assertSame((int) getenv('PHASE3B_RECOVERY_PROCESS_CHECKPOINT'), DB::table('legacy_migration_checkpoints')->count());
        self::assertSame($before, $this->durableFactCounts());
        self::assertSame(1, DB::table('legacy_migration_recovery_journal_entries')->count());
    }

    #[Test]
    public function protected_runtime_audit_proves_readiness_and_seals_semantic_events(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::Classified, AtomicUnit::PatientCore);
        $row = DB::table('legacy_migration_idempotency_records')->where('idempotency_token', $descriptor->idempotencyToken)->first(['run_id', 'target_snapshot_id']);
        self::assertNotNull($row);
        $runToken = (string) DB::table('legacy_migration_runs')->where('id', $row->run_id)->value('run_token');
        $targetToken = (string) DB::table('legacy_migration_snapshots')->where('id', $row->target_snapshot_id)->value('snapshot_token');
        $configuration = $this->configuration();
        $configuration['runtime_audit'] = [
            'enabled' => true,
            'authority_reference' => 'SYNTHETIC-RUNTIME-AUDIT-AUTHORITY',
            'access_classification' => 'protected',
            'retention_classification' => 'migration-lineage',
        ];
        $audit = new ProtectedMigrationRuntimeAudit(
            new MigrationAuditRepository($this->security),
            $this->security,
            LegacyMigrationSecurityFactory::make($configuration, 'testing', static fn () => self::fixtureKeyMaterial()),
            new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1')),
            $configuration,
            'testing',
            'phase3b_recovery',
        );
        $request = new MigrationRuntimeRequest(MigrationRunActivationAuthority::syntheticForTests(
            $runToken, $targetToken, ExecutionMode::DryRun,
        ));
        $session = $audit->start($request);
        $audit->record($session, 'RESTORATION_SUCCEEDED');

        self::assertSame(2, DB::table('legacy_migration_audit_events')->count());
        self::assertSame(2, DB::table('legacy_migration_protected_record_envelopes')->where('record_type', MigrationAuditEvent::class)->count());
        self::assertGreaterThanOrEqual(2, DB::table('legacy_migration_protected_access_audits')->where('operation', 'write')->count());
    }

    #[Test]
    public function container_binding_is_concrete_but_remains_blocked_without_verified_configuration(): void
    {
        foreach ([RecoveryJournalAttributeFactory::class, ProtectedRecoveryStore::class, AtomicRecoveryJournal::class, CompareAndSetStateStore::class] as $abstract) {
            app()->forgetInstance($abstract);
        }
        self::assertInstanceOf(AuthorityBoundRecoveryJournalAttributeFactory::class, app(RecoveryJournalAttributeFactory::class));
        self::assertInstanceOf(LaravelAtomicRecoveryJournal::class, app(AtomicRecoveryJournal::class));

        config(['legacy-migration.recovery.authority_verified' => false]);
        foreach ([RecoveryJournalAttributeFactory::class, ProtectedRecoveryStore::class, AtomicRecoveryJournal::class, RecoveryJournalAuthority::class] as $abstract) {
            app()->forgetInstance($abstract);
        }
        $this->expectException(RecoveryException::class);
        app(AtomicRecoveryJournal::class);
    }

    #[Test]
    public function unguarded_repository_and_direct_model_access_fail_closed(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::Classified, AtomicUnit::PatientCore);
        (new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store)))
            ->recover($descriptor, CrashBoundary::BeforeNumberAllocation);

        try {
            RecoveryJournalEntry::query()->firstOrFail();
            self::fail('Direct protected journal access was allowed.');
        } catch (ProtectedStoreAccessDeniedException $exception) {
            self::assertStringContainsString('DIRECT-MODEL-READ', $exception->getMessage());
        }

        $this->expectException(ProtectedStoreAccessDeniedException::class);
        (new RecoveryJournalRepository)->readProtected(
            new ProtectedStoreOperationContext('foundation_recovery', ['read'], ['migration_audit'], 'testing', 1, 1, 1, 'protected', 'migration-lineage', 'SYNTHETIC-RECOVERY-AUTHORITY'),
            1,
        );
    }

    #[Test]
    public function recovery_journal_remains_append_only_with_foundation_only_foreign_keys(): void
    {
        $descriptor = $this->seedCoordinate(MigrationState::Classified, AtomicUnit::PatientCore);
        (new AtomicRecoveryCoordinator(new LaravelAtomicRecoveryJournal($this->store)))
            ->recover($descriptor, CrashBoundary::BeforeNumberAllocation);
        foreach (DB::select("PRAGMA foreign_key_list('legacy_migration_recovery_journal_entries')") as $foreignKey) {
            self::assertStringStartsWith('legacy_migration_', $foreignKey->table);
            self::assertSame('RESTRICT', strtoupper($foreignKey->on_delete));
        }
        try {
            DB::table('legacy_migration_recovery_journal_entries')->update(['decision_state' => 'COMPLETED']);
            self::fail('Recovery journal update bypassed append-only protection.');
        } catch (QueryException) {
            self::addToAssertionCount(1);
        }
        $this->expectException(QueryException::class);
        DB::table('legacy_migration_recovery_journal_entries')->delete();
    }

    /** @return iterable<string,array{CrashBoundary,MigrationState,AtomicUnit,RecoveryDisposition,bool}> */
    public static function durableCrashBoundaries(): iterable
    {
        yield 'PILOT-RESUME-001' => [CrashBoundary::BeforeNumberAllocation, MigrationState::Classified, AtomicUnit::PatientCore, RecoveryDisposition::RetryFromClassified, false];
        yield 'PILOT-RESUME-002' => [CrashBoundary::AfterNumberAllocationBeforePatientCommit, MigrationState::CoreCommitting, AtomicUnit::PatientCore, RecoveryDisposition::ReuseExactAllocation, false];
        yield 'PILOT-RESUME-003' => [CrashBoundary::AfterPatientCommitBeforeCrosswalkCommit, MigrationState::CoreCommitting, AtomicUnit::PatientCore, RecoveryDisposition::CompensationRequired, false];
        yield 'PILOT-RESUME-004' => [CrashBoundary::AfterCoreCommitBeforeCheckpoint, MigrationState::CoreCommitted, AtomicUnit::PatientCore, RecoveryDisposition::RepairCheckpointOnly, true];
        yield 'PILOT-RESUME-005' => [CrashBoundary::DuringAliasCreation, MigrationState::AliasPending, AtomicUnit::OpdAlias, RecoveryDisposition::ResumeAliasUnit, false];
        yield 'PILOT-RESUME-006' => [CrashBoundary::DuringContactCreation, MigrationState::ContactPending, AtomicUnit::EmergencyContact, RecoveryDisposition::ResumeContactUnit, false];
        yield 'PILOT-RESUME-007' => [CrashBoundary::DuringInsuranceHistory, MigrationState::InsuranceHistoryPending, AtomicUnit::InsuranceHistory, RecoveryDisposition::ResumeMissingHistoryRows, false];
        yield 'PILOT-RESUME-008' => [CrashBoundary::DuringInsuranceCurrent, MigrationState::InsuranceCurrentPending, AtomicUnit::InsuranceCurrent, RecoveryDisposition::ResumeCurrentGroup, false];
        yield 'PILOT-RESUME-009' => [CrashBoundary::AfterTargetWritesBeforeReconciliation, MigrationState::ReconciliationPending, AtomicUnit::PatientCore, RecoveryDisposition::RepairReconciliationOnly, false];
        yield 'PILOT-RESUME-010' => [CrashBoundary::AfterReconciliationBeforeCompletion, MigrationState::ReconciliationPassed, AtomicUnit::PatientCore, RecoveryDisposition::RepairCompletionOnly, false];
    }

    private function buildBoundary(): void
    {
        $configuration = $this->configuration();
        $authority = ConfiguredRecoveryJournalAuthority::fromConfiguration($configuration, 'testing');
        $versions = new CanonicalizationVersionRegistry([$authority->canonicalizationVersion()], $authority->canonicalizationVersion());
        $encoder = new CanonicalTypedMessageEncoder($versions);
        $tokens = LegacyMigrationSecurityFactory::make($configuration, 'testing', static fn () => self::fixtureKeyMaterial());
        $domains = ['migration_run', 'idempotency', 'source_snapshot', 'target_snapshot', 'target_collision', 'recovery', 'migration_audit'];
        $pinned = new PinnedProtectedStoreAccessAuthority(
            'testing', $domains, 'recovery-key', 'v1', $authority->canonicalizationVersion(), 'artifact_integrity',
            [
                'foundation_recovery' => $this->policy(['read', 'write', 'transition'], 'SYNTHETIC-RECOVERY-AUTHORITY'),
                'synthetic_seed' => $this->policy(['read', 'write', 'transition'], 'SYNTHETIC-SEED-AUTHORITY'),
                'foundation_runtime_audit' => $this->policy(['read', 'write'], 'SYNTHETIC-RUNTIME-AUDIT-AUTHORITY'),
            ],
        );
        $guard = new ProtectedStoreAccessGuard($pinned, $tokens, $encoder);
        $this->security = new ProtectedRecordSecurityRepository(
            new ProtectedRecordEnvelopeFactory($guard),
            new ProtectedRecordEnvelopeVerifier($guard),
            connection: 'phase3b_recovery',
        );
        $this->attributes = new AuthorityBoundRecoveryJournalAttributeFactory($authority, $tokens, $encoder);
        $recovery = new RecoveryRepository(new CompareAndSet($this->security), $this->security);
        $this->store = new ProtectedRecoveryStore(
            $this->attributes,
            $recovery,
            new RecoveryJournalRepository($this->security),
            $this->security,
            'phase3b_recovery',
        );
    }

    private function seedCoordinate(MigrationState $state, AtomicUnit $unit): AtomicIntentDescriptor
    {
        $tokens = LegacyMigrationSecurityFactory::make($this->configuration(), 'testing', static fn () => self::fixtureKeyMaterial());
        $encoder = new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1'));
        $token = fn (string $domain, string $label): string => $tokens->tokenize(new TokenDomain($domain), $encoder->encode([TypedValue::string('synthetic-recovery-seed/v1'), TypedValue::string($label)]))->encode();
        $bundleEncoded = $token('migration_run', 'bundle');
        $bundle = (new RunManifestRepository(security: $this->security))->appendContractBundleProtected(
            $this->seedContext(['write'], ['migration_run']),
            [
                'bundle_version' => 'phase-2f/2F.1.0', 'bundle_hash' => $this->digest('approved-bundle'),
                'canonicalization_version' => 'typed-length-prefix/1', 'token_environment' => 'testing',
                'hmac_key_id' => 'recovery-key', 'hmac_key_version' => 'v1',
                'encrypted_manifest' => ['synthetic' => true], 'integrity_checksum' => $this->digest('bundle-integrity'),
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
            ],
            ['bundle_token' => ['encoded_token' => $bundleEncoded, 'domain' => 'migration_run']],
        );
        $runTokens = [
            'run_token' => ['encoded_token' => $token('migration_run', 'run'), 'domain' => 'migration_run'],
            'cohort_token' => ['encoded_token' => $token('migration_run', 'cohort'), 'domain' => 'migration_run'],
        ];
        $run = (new RunManifestRepository(security: $this->security))->createRunProtected(
            $this->seedContext(['write'], ['migration_run']),
            [
                'contract_bundle_id' => $bundle->id, 'mode' => 'dry_run', 'state' => 'NOT_STARTED',
                'configuration_fingerprint' => $this->digest('configuration'), 'source_fingerprint' => $this->digest('source'),
                'target_fingerprint' => $this->digest('target'), 'canonicalization_version' => 'typed-length-prefix/1',
                'token_environment' => 'testing', 'hmac_key_id' => 'recovery-key', 'hmac_key_version' => 'v1',
                'encrypted_manifest' => ['synthetic' => true], 'manifest_checksum' => $this->digest('run-integrity'),
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage', 'lock_version' => 0,
            ],
            $runTokens,
        );
        $snapshots = new SnapshotRepository($this->security);
        $source = $this->seedSnapshot($snapshots, (int) $run->id, 'source', $token);
        $target = $this->seedSnapshot($snapshots, (int) $run->id, 'target', $token);
        $idempotencyEncoded = $token('idempotency', 'idempotency');
        $idempotencyTokens = [
            'protected_source_token' => ['encoded_token' => $token('recovery', 'source-row'), 'domain' => 'recovery'],
            'idempotency_token' => ['encoded_token' => $idempotencyEncoded, 'domain' => 'idempotency'],
        ];
        $input = $this->digest('input');
        (new IdempotencyRepository(security: $this->security))->resolveOrCreateProtected(
            $this->seedContext(['write', 'read'], ['idempotency'], (int) $run->id, (int) $source->id, (int) $target->id, ['state']),
            [
                'run_id' => $run->id, 'source_snapshot_id' => $source->id, 'target_snapshot_id' => $target->id,
                'domain' => 'patient_core', 'outcome_type' => 'foundation_recovery', 'input_fingerprint' => $input,
                'contract_version' => 'phase-2f/2F.1.0', 'transformation_version' => 'foundation-recovery/v1',
                'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1',
                'state' => $state->value, 'attempt_count' => 0, 'integrity_checksum' => $this->digest('idempotency-integrity'),
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage', 'lock_version' => 0,
            ],
            $idempotencyTokens,
        );
        $idempotencyDigest = ProtectedToken::parse($idempotencyEncoded)->lookupDigest();
        $intent = $this->attributes->intentToken($idempotencyDigest, $unit, $state, 1, $input);

        return new AtomicIntentDescriptor($intent, $idempotencyDigest, $unit, $state, 1, $input);
    }

    private function seedSnapshot(SnapshotRepository $repository, int $runId, string $kind, \Closure $token): Snapshot
    {
        $domain = $kind.'_snapshot';
        $tokenSet = [
            'snapshot_token' => ['encoded_token' => $token($domain, $kind.'-snapshot'), 'domain' => $domain],
            'coordinate_token' => ['encoded_token' => $token($domain, $kind.'-coordinate'), 'domain' => $domain],
        ];

        return $repository->appendProtected(
            $this->seedContext(['write'], [$domain], $runId),
            [
                'run_id' => $runId, 'snapshot_kind' => $kind, 'domain' => 'patient_core',
                'database_fingerprint' => $this->digest($kind.'-database'), 'schema_fingerprint' => $this->digest($kind.'-schema'),
                'query_bundle_hash' => $this->digest($kind.'-query'), 'result_hash' => $this->digest($kind.'-result'),
                'contract_version' => 'phase-2f/2F.1.0', 'transformation_version' => 'foundation-recovery/v1',
                'canonicalization_version' => 'typed-length-prefix/1', 'hmac_key_version' => 'v1', 'state' => 'captured',
                'encrypted_metadata' => ['synthetic' => true], 'integrity_checksum' => $this->digest($kind.'-integrity'),
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage', 'captured_at' => now(),
            ],
            $tokenSet,
        );
    }

    private function seedDurableFacts(AtomicIntentDescriptor $descriptor, CrashBoundary $boundary): void
    {
        $row = DB::table('legacy_migration_idempotency_records')
            ->where('idempotency_token', $descriptor->idempotencyToken)
            ->first(['id', 'run_id', 'source_snapshot_id', 'target_snapshot_id']);
        self::assertNotNull($row);

        if ($boundary === CrashBoundary::AfterNumberAllocationBeforePatientCommit) {
            $this->seedReservation($descriptor, $row);

            return;
        }

        if (! in_array($boundary, [
            CrashBoundary::AfterCoreCommitBeforeCheckpoint,
            CrashBoundary::AfterTargetWritesBeforeReconciliation,
            CrashBoundary::AfterReconciliationBeforeCompletion,
        ], true)) {
            return;
        }

        $source = $this->seedEncodedToken('recovery', 'source-row');
        $target = $this->encodedToken('recovery', 'fact-target|'.$descriptor->intentToken);
        $context = $this->seedContext(
            ['write', 'read'],
            ['recovery', 'idempotency'],
            (int) $row->run_id,
            (int) $row->source_snapshot_id,
            (int) $row->target_snapshot_id,
        );
        (new CrosswalkRepository($this->security))->activateProtected($context, [
            'run_id' => $row->run_id,
            'source_snapshot_id' => $row->source_snapshot_id,
            'target_snapshot_id' => $row->target_snapshot_id,
            'domain' => 'recovery',
            'branch' => 'created_new',
            'state' => 'committed',
            'patient_number_action' => 'none',
            'contract_version' => 'phase-2f/2F.1.0',
            'transformation_version' => 'foundation-recovery/v1',
            'canonicalization_version' => 'typed-length-prefix/1',
            'hmac_key_version' => 'v1',
            'encrypted_mapping_payload' => ['synthetic' => true],
            'integrity_checksum' => $this->digest('crosswalk|'.$descriptor->intentToken),
            'access_classification' => 'protected',
            'retention_classification' => 'migration-lineage',
            'revocation_state' => 'not_revoked',
        ], [
            'protected_source_token' => ['encoded_token' => $source, 'domain' => 'recovery'],
            'protected_target_token' => ['encoded_token' => $target, 'domain' => 'recovery'],
            'active_coordinate_token' => ['encoded_token' => $this->encodedToken('recovery', 'active|'.$descriptor->intentToken), 'domain' => 'recovery'],
            'idempotency_token' => ['encoded_token' => $this->seedEncodedToken('idempotency', 'idempotency'), 'domain' => 'idempotency'],
        ]);
        $provenanceContext = $this->seedContext(
            ['write', 'read'], ['recovery'], (int) $row->run_id, (int) $row->source_snapshot_id,
        );
        (new ProtectedEvidenceRepository($this->security))->appendProvenanceProtected($provenanceContext, [
            'run_id' => $row->run_id,
            'source_snapshot_id' => $row->source_snapshot_id,
            'domain' => 'recovery',
            'source_query_id' => 'SYNTHETIC-RECOVERY-FACT',
            'query_hash' => $this->digest('query|'.$descriptor->intentToken),
            'target_outcome' => 'committed',
            'contract_version' => 'phase-2f/2F.1.0',
            'transformation_version' => 'foundation-recovery/v1',
            'canonicalization_version' => 'typed-length-prefix/1',
            'hmac_key_version' => 'v1',
            'state' => 'committed',
            'encrypted_field_dispositions' => ['synthetic' => true],
            'integrity_checksum' => $this->digest('provenance|'.$descriptor->intentToken),
            'access_classification' => 'protected',
            'retention_classification' => 'migration-lineage',
        ], [
            'protected_source_token' => ['encoded_token' => $source, 'domain' => 'recovery'],
            'protected_target_token' => ['encoded_token' => $target, 'domain' => 'recovery'],
            'outcome_coordinate_token' => ['encoded_token' => $this->encodedToken('recovery', 'outcome|'.$descriptor->intentToken), 'domain' => 'recovery'],
        ]);

        if ($boundary !== CrashBoundary::AfterTargetWritesBeforeReconciliation) {
            (new ReconciliationRepository($this->security))->appendProtected($context, [
                'run_id' => $row->run_id,
                'source_snapshot_id' => $row->source_snapshot_id,
                'target_snapshot_id' => $row->target_snapshot_id,
                'domain' => 'recovery',
                'contract_id' => 'SYNTHETIC-RECOVERY-RECON',
                'contract_version' => 'phase-2f/2F.1.0',
                'transformation_version' => 'foundation-recovery/v1',
                'canonicalization_version' => 'typed-length-prefix/1',
                'hmac_key_version' => 'v1',
                'mandatory' => true,
                'measurement_complete' => true,
                'difference' => '0',
                'tolerance' => '0',
                'acceptance_result' => 'passed',
                'state' => 'RECONCILIATION_PASSED',
                'evidence_bundle_hash' => $this->digest('reconciliation|'.$descriptor->intentToken),
                'encrypted_population_definition' => ['synthetic' => true],
                'encrypted_expected_equation' => ['expected' => 1],
                'encrypted_measured_values' => ['observed' => 1],
                'integrity_checksum' => $this->digest('recon-integrity|'.$descriptor->intentToken),
                'access_classification' => 'protected',
                'retention_classification' => 'migration-lineage',
            ], [
                'cohort_token' => ['encoded_token' => $this->encodedToken('recovery', 'cohort|'.$descriptor->intentToken), 'domain' => 'recovery'],
                'idempotency_token' => ['encoded_token' => $this->seedEncodedToken('idempotency', 'idempotency'), 'domain' => 'idempotency'],
            ]);
        }
    }

    private function seedReservation(AtomicIntentDescriptor $descriptor, object $row): void
    {
        (new NumberReservationRepository(
            $this->security,
            new \App\Services\LegacyMigration\Foundation\Allocation\RecoveryReservationCoordinateContextFactory($this->attributes),
        ))->reserveProtected(
            $this->seedContext(['write', 'read'], ['recovery'], (int) $row->run_id),
            [
                'run_id' => $row->run_id,
                'idempotency_record_id' => $row->id,
                'domain' => 'patient_number',
                'sequence_ordinal' => 1,
                'configuration_fingerprint' => $this->digest('number-config'),
                'period_key' => '2026',
                'timezone' => 'UTC',
                'state' => 'reserved',
                'consumption_classification' => 'explained',
                'contract_version' => 'phase-2f/2F.1.0',
                'transformation_version' => 'foundation-recovery/v1',
                'canonicalization_version' => 'typed-length-prefix/1',
                'hmac_key_version' => 'v1',
                'encrypted_number_payload' => ['synthetic' => true],
                'integrity_checksum' => $this->digest('reservation|'.$descriptor->intentToken),
                'access_classification' => 'protected',
                'retention_classification' => 'migration-lineage',
                'lock_version' => 0,
            ],
            [
                'protected_source_token' => ['encoded_token' => $this->encodedToken('recovery', 'reservation-source|'.$descriptor->intentToken), 'domain' => 'recovery'],
                'numbering_coordinate_token' => ['encoded_token' => $this->encodedToken('recovery', 'reservation-coordinate|'.$descriptor->intentToken), 'domain' => 'recovery'],
                'protected_number_token' => ['encoded_token' => $this->encodedToken('recovery', 'reservation-number|'.$descriptor->intentToken), 'domain' => 'recovery'],
            ],
        );
    }

    /** @return array<string,int> */
    private function durableFactCounts(): array
    {
        return [
            'crosswalks' => DB::table('legacy_migration_crosswalks')->count(),
            'provenance' => DB::table('legacy_migration_provenance_records')->count(),
            'reconciliation' => DB::table('legacy_migration_reconciliation_results')->count(),
            'reservations' => DB::table('legacy_migration_number_reservations')->count(),
        ];
    }

    /** @param array<string,string> $environment */
    private function recoveryWorker(array $environment): Process
    {
        $process = new Process([
            PHP_BINARY,
            'artisan',
            'test',
            'tests/Feature/LegacyMigration/Foundation/DurableRecoveryJournalTest.php',
            '--filter=process_worker_persists_or_recovers_one_boundary',
            '--compact',
        ], base_path(), $environment);
        $process->setTimeout(45);

        return $process;
    }

    private function safeProcessOutput(Process $process): string
    {
        $output = $process->getErrorOutput().$process->getOutput();

        return substr(preg_replace('/\(Connection:.*?\)/s', '(connection details redacted)', $output) ?? '', 0, 2000);
    }

    private function encodedToken(string $domain, string $label): string
    {
        $tokens = LegacyMigrationSecurityFactory::make($this->configuration(), 'testing', static fn () => self::fixtureKeyMaterial());
        $encoder = new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1'));

        return $tokens->tokenize(
            new TokenDomain($domain),
            $encoder->encode([TypedValue::string('synthetic-recovery-fact/v1'), TypedValue::string($label)]),
        )->encode();
    }

    private function seedEncodedToken(string $domain, string $label): string
    {
        $tokens = LegacyMigrationSecurityFactory::make($this->configuration(), 'testing', static fn () => self::fixtureKeyMaterial());
        $encoder = new CanonicalTypedMessageEncoder(new CanonicalizationVersionRegistry(['typed-length-prefix/1'], 'typed-length-prefix/1'));

        return $tokens->tokenize(
            new TokenDomain($domain),
            $encoder->encode([TypedValue::string('synthetic-recovery-seed/v1'), TypedValue::string($label)]),
        )->encode();
    }

    private function assertEveryRecoveryRecordProtected(): void
    {
        foreach ([AtomicIntent::class => 'legacy_migration_atomic_intents', Checkpoint::class => 'legacy_migration_checkpoints', RecoveryJournalEntry::class => 'legacy_migration_recovery_journal_entries'] as $type => $table) {
            $unsealed = DB::table($table.' as records')
                ->leftJoin('legacy_migration_protected_record_envelopes as envelopes', fn ($join) => $join->on('envelopes.record_id', '=', 'records.id')->where('envelopes.record_type', '=', $type))
                ->whereNull('envelopes.id')->count();
            self::assertSame(0, $unsealed, "{$type} has an unsealed durable record.");
        }
        self::assertSame(0, DB::table('legacy_migration_protected_record_envelopes')->whereNull('encrypted_integrity_seal')->count());
        self::assertSame(0, DB::table('legacy_migration_protected_access_audits')->whereNull('event_checksum')->count());
    }

    /** @return array<string,list<string>> */
    private function policy(array $operations, string $authority): array
    {
        return ['operations' => $operations, 'authority_references' => [$authority], 'access_classifications' => ['protected'], 'retention_classifications' => ['migration-lineage']];
    }

    /** @param list<string> $operations @param list<string> $domains @param list<string> $fields */
    private function seedContext(array $operations, array $domains, ?int $run = null, ?int $source = null, ?int $target = null, array $fields = []): ProtectedStoreOperationContext
    {
        return new ProtectedStoreOperationContext('synthetic_seed', $operations, $domains, 'testing', $run, $source, $target, 'protected', 'migration-lineage', 'SYNTHETIC-SEED-AUTHORITY', $fields);
    }

    /** @return array<string,mixed> */
    private function configuration(): array
    {
        return [
            'versions' => ['contract_bundle' => 'phase-2f/2F.1.0', 'canonicalization' => 'typed-length-prefix/1'],
            'hmac' => ['domains' => ['migration_run', 'idempotency', 'source_snapshot', 'target_snapshot', 'target_collision', 'recovery', 'migration_audit', 'artifact_integrity']],
            'key_provider' => ['provider' => 'external_reference', 'references' => [[
                'key_id' => 'recovery-key', 'version' => 'v1', 'secret_reference' => 'env://LEGACY_MIGRATION_TEST_RECOVERY_KEY',
                'environment' => 'testing', 'domains' => ['migration_run', 'idempotency', 'source_snapshot', 'target_snapshot', 'target_collision', 'recovery', 'migration_audit', 'artifact_integrity'],
                'activated_at' => '2026-01-01T00:00:00+00:00', 'verification_expires_at' => '2027-01-01T00:00:00+00:00',
                'rotation_authority' => 'SYNTHETIC-ROTATION', 'revoked' => false, 'active_for_signing' => true,
            ]]],
            'protected_store' => ['population_enabled' => true, 'full_envelope_required' => true, 'keyed_integrity_required' => true, 'purpose_scoped_access_required' => true, 'direct_model_access_allowed' => false, 'access_policy_reference' => 'SYNTHETIC-RECOVERY-AUTHORITY'],
            'phase2f_policy' => ['expected_bundle_hash' => $this->digest('approved-bundle')],
            'recovery' => [
                'persistent_journal_enabled' => true, 'connection' => 'phase3b_recovery', 'compare_and_set_required' => true,
                'authority_verified' => true, 'authority_reference' => 'SYNTHETIC-RECOVERY-AUTHORITY',
                'access_classification' => 'protected', 'retention_classification' => 'migration-lineage',
                'transformation_version' => 'foundation-recovery/v1',
            ],
        ];
    }

    private function digest(string $value): string
    {
        return hash('sha256', 'SYNTHETIC-P3B-RECOVERY::'.$value);
    }

    private static function fixtureKeyMaterial(): string
    {
        return hash('sha512', __METHOD__.'|independently-generated-synthetic-material');
    }
}

final class SyntheticExistingReservationStore implements NumberReservationStore
{
    public function connectionName(): string
    {
        return 'phase3b_recovery';
    }

    public function find(AllocationRequest $request): ?ExistingAllocation
    {
        return new ExistingAllocation(
            $request->protectedSourceToken,
            $request->patientCoreKey,
            $request->configuration->fingerprint(),
            $request->configuration->periodKey,
            $request->configuration->format(1),
            1,
            true,
        );
    }

    public function withLockedCoordinate(PinnedNumberingConfiguration $configuration, Closure $operation): mixed
    {
        return $operation();
    }

    public function nextOrdinal(PinnedNumberingConfiguration $configuration): int
    {
        throw new \LogicException('Synthetic lineage resolver must not allocate.');
    }

    public function persist(AllocationRequest $request, string $number, int $ordinal): ExistingAllocation
    {
        throw new \LogicException('Synthetic lineage resolver must not persist.');
    }
}
