<?php

namespace Tests\Feature\LegacyMigration\Foundation;

use App\Models\LegacyMigration\ContractBundle;
use App\Models\LegacyMigration\IdempotencyRecord;
use App\Models\LegacyMigration\MigrationRun;
use App\Models\LegacyMigration\QuarantineException;
use App\Models\LegacyMigration\Snapshot;
use App\Services\LegacyMigration\Foundation\Storage\CrosswalkRepository;
use App\Services\LegacyMigration\Foundation\Storage\IdempotencyRepository;
use App\Services\LegacyMigration\Foundation\Storage\MigrationStateTransitions;
use App\Services\LegacyMigration\Foundation\Storage\QuarantineRepository;
use App\Services\LegacyMigration\Foundation\Storage\ReconciliationRepository;
use App\Services\LegacyMigration\Foundation\Storage\RecoveryRepository;
use App\Services\LegacyMigration\Foundation\Storage\RunManifestRepository;
use App\Services\LegacyMigration\Foundation\Storage\SnapshotRepository;
use App\Services\LegacyMigration\Foundation\Storage\StorageIntegrityException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationSchemaTest extends TestCase
{
    /** @var list<object> */
    private array $foundationMigrations = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName(), 'Foundation safety tests must use isolated SQLite.');
        config(['app.key' => 'base64:'.base64_encode(str_repeat('S', 32))]);
        DB::statement('PRAGMA foreign_keys = ON');

        foreach ([
            '2026_07_22_000110_create_legacy_migration_run_foundation_tables.php',
            '2026_07_22_000111_create_legacy_migration_protected_store_tables.php',
            '2026_07_22_000112_create_legacy_migration_recovery_tables.php',
        ] as $file) {
            $migration = require database_path("migrations/{$file}");
            $migration->up();
            $this->foundationMigrations[] = $migration;
        }
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->foundationMigrations) as $migration) {
            $migration->down();
        }

        parent::tearDown();
    }

    public function test_foundation_tables_exist_without_business_table_foreign_keys(): void
    {
        foreach ([
            'legacy_migration_runs',
            'legacy_migration_snapshots',
            'legacy_migration_target_collision_snapshots',
            'legacy_migration_crosswalks',
            'legacy_migration_remediations',
            'legacy_migration_provenance_records',
            'legacy_migration_quarantine_roots',
            'legacy_migration_quarantine_exceptions',
            'legacy_migration_reconciliation_results',
            'legacy_migration_audit_events',
            'legacy_migration_idempotency_records',
            'legacy_migration_checkpoints',
            'legacy_migration_atomic_intents',
            'legacy_migration_compensation_records',
            'legacy_migration_number_reservations',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table));

            foreach (DB::select("PRAGMA foreign_key_list('{$table}')") as $foreignKey) {
                $this->assertStringStartsWith('legacy_migration_', $foreignKey->table);
                $this->assertSame('RESTRICT', strtoupper($foreignKey->on_delete));
            }
        }

        $this->assertFalse(Schema::hasTable('patients'));
    }

    public function test_manifest_payload_is_encrypted_hidden_and_append_only(): void
    {
        [$bundle] = $this->foundationCoordinate();

        $raw = DB::table('legacy_migration_contract_bundles')->where('id', $bundle->id)->value('encrypted_manifest');

        $this->assertStringNotContainsString('SYNTHETIC-ONLY-P3', $raw);
        $this->assertArrayNotHasKey('encrypted_manifest', $bundle->toArray());
        $this->assertArrayNotHasKey('bundle_token', $bundle->toArray());

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_contract_bundles')->where('id', $bundle->id)->update(['bundle_version' => 'changed']);
    }

    public function test_run_manifest_is_immutable_while_compare_and_set_state_remains_available(): void
    {
        [, $run] = $this->foundationCoordinate();

        try {
            DB::table('legacy_migration_runs')->where('id', $run->id)->update(['manifest_checksum' => $this->token('tampered')]);
            $this->fail('The database accepted a run-manifest mutation.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $advanced = (new RunManifestRepository)->advance($run, 'NOT_STARTED', 0, 'EXTRACTED');
        $this->assertSame('EXTRACTED', $advanced->state);
    }

    public function test_crosswalk_enforces_one_active_mapping_per_source_coordinate(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $repository = new CrosswalkRepository;
        $attributes = $this->crosswalkAttributes($run, $source, $target);
        $first = $repository->activate($attributes);

        $this->assertTrue($first->is_active);
        $this->assertArrayNotHasKey('protected_source_token', $first->toArray());
        $this->assertArrayNotHasKey('active_source_guard_key', $first->toArray());

        $this->expectException(QueryException::class);
        $repository->activate(array_replace($attributes, [
            'idempotency_token' => $this->token('other-idempotency'),
            'active_coordinate_token' => $this->token('caller-controlled-other-coordinate'),
        ]));
    }

    public function test_quarantine_has_one_root_and_one_primary_exception(): void
    {
        [, $run, $source] = $this->foundationCoordinate();
        $repository = new QuarantineRepository;
        $root = $repository->createRoot(
            $this->quarantineRootAttributes($run, $source),
            $this->quarantineExceptionAttributes('primary-exception', 0)
        );

        $this->assertSame(1, QuarantineException::query()->where('quarantine_root_id', $root->id)->where('is_primary', true)->count());

        $this->expectException(QueryException::class);
        QuarantineException::query()->create($this->quarantineExceptionAttributes('second-primary', 1) + [
            'quarantine_root_id' => $root->id,
            'is_primary' => true,
            'primary_guard_token' => $this->token('caller-controlled-other-primary'),
        ]);
    }

    public function test_reconciliation_nonzero_pass_and_missing_measurement_are_database_impossible(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $valid = $this->reconciliationAttributes($run, $source, $target);
        $stored = (new ReconciliationRepository)->append($valid);

        $this->assertSame('passed', $stored->acceptance_result);

        try {
            DB::table('legacy_migration_reconciliation_results')->insert(
                array_replace($this->rawReconciliationAttributes($valid, 'nonzero-bypass'), ['difference' => '1.00000000'])
            );
            $this->fail('The database accepted a nonzero passing reconciliation.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_reconciliation_results')->insert(
            array_replace($this->rawReconciliationAttributes($valid, 'missing-bypass'), [
                'measurement_complete' => false,
                'difference' => null,
                'acceptance_result' => 'failed',
            ])
        );
    }

    public function test_idempotency_and_checkpoint_cardinality_reject_incompatible_duplicates(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $repository = new IdempotencyRepository;
        $attributes = $this->idempotencyAttributes($run, $source, $target);
        $record = $repository->resolveOrCreate($attributes);

        $this->assertTrue($record->is($repository->resolveOrCreate($attributes)));

        try {
            $repository->resolveOrCreate(array_replace($attributes, ['input_fingerprint' => $this->token('conflicting-input')]));
            $this->fail('Conflicting idempotency lineage was accepted.');
        } catch (StorageIntegrityException) {
            $this->assertTrue(true);
        }

        $intent = (new RecoveryRepository)->createIntent($this->intentAttributes($run, $record));
        $checkpoint = $this->checkpointAttributes($run, $record, $intent->id);
        (new RecoveryRepository)->appendCheckpoint($checkpoint);

        $this->expectException(QueryException::class);
        (new RecoveryRepository)->appendCheckpoint(array_replace($checkpoint, ['integrity_checksum' => $this->token('different-checksum')]));
    }

    public function test_idempotency_reuse_requires_every_snapshot_token_and_version_coordinate(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $repository = new IdempotencyRepository;
        $attributes = $this->idempotencyAttributes($run, $source, $target);
        $repository->resolveOrCreate($attributes);

        $this->expectException(StorageIntegrityException::class);
        $repository->resolveOrCreate(array_replace($attributes, [
            'protected_source_token' => $this->token('different-source'),
            'transformation_version' => 'incompatible-transform-v2',
        ]));
    }

    public function test_checkpoint_cannot_mix_an_intent_with_another_idempotency_lineage(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $idempotency = new IdempotencyRepository;
        $recordA = $idempotency->resolveOrCreate($this->idempotencyAttributes($run, $source, $target));
        $recordB = $idempotency->resolveOrCreate(array_replace(
            $this->idempotencyAttributes($run, $source, $target),
            [
                'idempotency_token' => $this->token('idempotency-b'),
                'protected_source_token' => $this->token('source-b'),
                'input_fingerprint' => $this->token('input-b'),
            ],
        ));
        $recovery = new RecoveryRepository;
        $intentA = $recovery->createIntent($this->intentAttributes($run, $recordA));

        $this->expectException(QueryException::class);
        $recovery->appendCheckpoint($this->checkpointAttributes($run, $recordB, $intentA->id));
    }

    public function test_run_state_uses_exact_24_state_compare_and_set_model(): void
    {
        [, $run] = $this->foundationCoordinate();
        $repository = new RunManifestRepository;

        $this->assertCount(24, MigrationStateTransitions::states());
        $advanced = $repository->advance($run, 'NOT_STARTED', 0, 'EXTRACTED');
        $this->assertSame('EXTRACTED', $advanced->state);
        $this->assertSame(1, $advanced->lock_version);

        $this->expectException(StorageIntegrityException::class);
        $repository->advance($run, 'NOT_STARTED', 0, 'COMPLETED');
    }

    public function test_raw_sql_cannot_bypass_the_run_state_machine_or_lock_version(): void
    {
        [, $run] = $this->foundationCoordinate();

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_runs')->where('id', $run->id)->update([
            'state' => 'COMPLETED',
            'lock_version' => 99,
            'updated_at' => now(),
        ]);
    }

    public function test_compare_and_set_rejects_caller_override_of_state_coordinates(): void
    {
        [, $run] = $this->foundationCoordinate();

        $this->expectException(StorageIntegrityException::class);
        (new RunManifestRepository)->advance($run, 'NOT_STARTED', 0, 'EXTRACTED', [
            'state' => 'COMPLETED',
            'lock_version' => 99,
        ]);
    }

    public function test_foundation_foreign_keys_reject_cross_run_snapshot_coordinates(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $otherRun = $run->replicate();
        $otherRun->run_token = $this->token('other-run');
        $otherRun->cohort_token = $this->token('other-cohort');
        $otherRun->manifest_checksum = $this->token('other-run-manifest');
        $otherRun->save();

        $this->expectException(QueryException::class);
        (new CrosswalkRepository)->activate($this->crosswalkAttributes($otherRun, $source, $target));
    }

    public function test_protected_lineage_rejects_raw_coordinate_mutation_and_deletion(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $crosswalk = (new CrosswalkRepository)->activate($this->crosswalkAttributes($run, $source, $target));

        try {
            DB::table('legacy_migration_crosswalks')->where('id', $crosswalk->id)->update([
                'protected_source_token' => $this->token('tampered-source'),
            ]);
            $this->fail('The database accepted a protected lineage-coordinate mutation.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_crosswalks')->where('id', $crosswalk->id)->delete();
    }

    public function test_quarantine_authoritative_root_identity_cannot_be_changed_by_a_caller_coordinate(): void
    {
        [, $run, $source] = $this->foundationCoordinate();
        $repository = new QuarantineRepository;
        $repository->createRoot(
            $this->quarantineRootAttributes($run, $source),
            $this->quarantineExceptionAttributes('primary-a', 0),
        );

        $this->expectException(QueryException::class);
        $repository->createRoot(
            array_replace($this->quarantineRootAttributes($run, $source), [
                'chain_coordinate_token' => $this->token('caller-other-chain'),
            ]),
            $this->quarantineExceptionAttributes('primary-b', 0),
        );
    }

    public function test_run_and_recovery_state_cannot_be_deleted_through_raw_queries(): void
    {
        [, $run, $source, $target] = $this->foundationCoordinate();
        $record = (new IdempotencyRepository)->resolveOrCreate($this->idempotencyAttributes($run, $source, $target));

        try {
            DB::table('legacy_migration_idempotency_records')->where('id', $record->id)->delete();
            $this->fail('The database accepted deletion of recovery state.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->expectException(QueryException::class);
        DB::table('legacy_migration_runs')->where('id', $run->id)->delete();
    }

    /** @return array{ContractBundle, MigrationRun, Snapshot, Snapshot} */
    private function foundationCoordinate(): array
    {
        $runRepository = new RunManifestRepository;
        $bundle = $runRepository->appendContractBundle([
            'bundle_token' => $this->token('bundle'),
            'bundle_version' => 'SYNTHETIC-P3-1',
            'bundle_hash' => $this->token('bundle-hash'),
            'canonicalization_version' => 'synthetic-canon-v1',
            'token_environment' => 'testing',
            'hmac_key_id' => 'synthetic-key',
            'hmac_key_version' => 'synthetic-key-v1',
            'encrypted_manifest' => ['fixture' => 'SYNTHETIC-ONLY-P3'],
            'integrity_checksum' => $this->token('bundle-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ]);
        $run = $runRepository->createRun([
            'run_token' => $this->token('run'),
            'cohort_token' => $this->token('cohort'),
            'contract_bundle_id' => $bundle->id,
            'mode' => 'dry_run',
            'state' => 'NOT_STARTED',
            'configuration_fingerprint' => $this->token('configuration'),
            'source_fingerprint' => $this->token('source-fingerprint'),
            'target_fingerprint' => $this->token('target-fingerprint'),
            'canonicalization_version' => 'synthetic-canon-v1',
            'token_environment' => 'testing',
            'hmac_key_id' => 'synthetic-key',
            'hmac_key_version' => 'synthetic-key-v1',
            'encrypted_manifest' => ['fixture' => 'SYNTHETIC-ONLY-P3'],
            'manifest_checksum' => $this->token('manifest-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ]);
        $snapshotRepository = new SnapshotRepository;
        $source = $snapshotRepository->append($this->snapshotAttributes($run, 'source'));
        $target = $snapshotRepository->append($this->snapshotAttributes($run, 'target'));

        return [$bundle, $run, $source, $target];
    }

    /** @return array<string, mixed> */
    private function snapshotAttributes(MigrationRun $run, string $kind): array
    {
        return [
            'run_id' => $run->id,
            'snapshot_token' => $this->token("{$kind}-snapshot"),
            'snapshot_kind' => $kind,
            'domain' => 'synthetic_foundation',
            'coordinate_token' => $this->token("{$kind}-coordinate"),
            'database_fingerprint' => $this->token("{$kind}-database"),
            'schema_fingerprint' => $this->token("{$kind}-schema"),
            'query_bundle_hash' => $this->token("{$kind}-query"),
            'result_hash' => $this->token("{$kind}-result"),
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'state' => 'captured',
            'encrypted_metadata' => ['fixture' => 'SYNTHETIC-ONLY-P3'],
            'integrity_checksum' => $this->token("{$kind}-snapshot-checksum"),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
            'captured_at' => now(),
        ];
    }

    /** @return array<string, mixed> */
    private function crosswalkAttributes(MigrationRun $run, Snapshot $source, Snapshot $target): array
    {
        return [
            'run_id' => $run->id,
            'source_snapshot_id' => $source->id,
            'target_snapshot_id' => $target->id,
            'domain' => 'patient',
            'protected_source_token' => $this->token('source-row'),
            'protected_target_token' => $this->token('target-row'),
            'active_coordinate_token' => $this->token('active-coordinate'),
            'branch' => 'explicit_existing_target',
            'state' => 'active',
            'patient_number_action' => 'existing_target_unchanged',
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'idempotency_token' => $this->token('crosswalk-idempotency'),
            'encrypted_mapping_payload' => ['fixture' => 'SYNTHETIC-ONLY-P3'],
            'integrity_checksum' => $this->token('crosswalk-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    /** @return array<string, mixed> */
    private function quarantineRootAttributes(MigrationRun $run, Snapshot $source): array
    {
        return [
            'run_id' => $run->id,
            'source_snapshot_id' => $source->id,
            'root_domain' => 'patient',
            'root_token' => $this->token('quarantine-root'),
            'chain_coordinate_token' => $this->token('quarantine-coordinate'),
            'blocking_scope' => 'patient_root',
            'current_disposition' => 'held',
            'manual_review_owner_token' => $this->token('review-owner'),
            'topological_rank' => 10,
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'state' => 'held',
            'integrity_checksum' => $this->token('quarantine-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    /** @return array<string, mixed> */
    private function quarantineExceptionAttributes(string $label, int $ordinal): array
    {
        return [
            'exception_code' => 'SYNTHETIC-EXCEPTION',
            'precedence_ordinal' => $ordinal,
            'rule_version' => 'synthetic-rule-v1',
            'evidence_version' => 'synthetic-evidence-v1',
            'idempotency_token' => $this->token($label),
            'state' => 'held',
            'encrypted_evidence' => ['fixture' => 'SYNTHETIC-ONLY-P3'],
            'integrity_checksum' => $this->token("{$label}-checksum"),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    /** @return array<string, mixed> */
    private function reconciliationAttributes(MigrationRun $run, Snapshot $source, Snapshot $target): array
    {
        return [
            'run_id' => $run->id,
            'source_snapshot_id' => $source->id,
            'target_snapshot_id' => $target->id,
            'cohort_token' => $this->token('cohort'),
            'domain' => 'patient',
            'contract_id' => 'SYNTHETIC-RECON-001',
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'mandatory' => true,
            'measurement_complete' => true,
            'difference' => '0.00000000',
            'tolerance' => '0.00000000',
            'acceptance_result' => 'passed',
            'state' => 'RECONCILIATION_PASSED',
            'evidence_bundle_hash' => $this->token('evidence-bundle'),
            'idempotency_token' => $this->token('reconciliation-idempotency'),
            'encrypted_population_definition' => ['fixture' => 'SYNTHETIC-ONLY-P3'],
            'encrypted_expected_equation' => ['left' => 1, 'right' => 1],
            'encrypted_measured_values' => ['left' => 1, 'right' => 1],
            'integrity_checksum' => $this->token('reconciliation-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    /** @param array<string, mixed> $valid @return array<string, mixed> */
    private function rawReconciliationAttributes(array $valid, string $label): array
    {
        $raw = $valid;
        foreach (['encrypted_population_definition', 'encrypted_expected_equation', 'encrypted_measured_values'] as $field) {
            $raw[$field] = 'SYNTHETIC-CIPHERTEXT';
        }
        $raw['contract_id'] = strtoupper($label);
        $raw['evidence_bundle_hash'] = $this->token("{$label}-evidence");
        $raw['idempotency_token'] = $this->token("{$label}-idempotency");
        $raw['integrity_checksum'] = $this->token("{$label}-checksum");
        $raw['created_at'] = now();
        $raw['updated_at'] = now();

        return $raw;
    }

    /** @return array<string, mixed> */
    private function idempotencyAttributes(MigrationRun $run, Snapshot $source, Snapshot $target): array
    {
        return [
            'run_id' => $run->id,
            'source_snapshot_id' => $source->id,
            'target_snapshot_id' => $target->id,
            'domain' => 'patient_core',
            'protected_source_token' => $this->token('source-row'),
            'idempotency_token' => $this->token('core-idempotency'),
            'outcome_type' => 'patient_core',
            'input_fingerprint' => $this->token('input-fingerprint'),
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'state' => 'NOT_STARTED',
            'integrity_checksum' => $this->token('idempotency-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    /** @return array<string, mixed> */
    private function intentAttributes(MigrationRun $run, IdempotencyRecord $record): array
    {
        return [
            'run_id' => $run->id,
            'idempotency_record_id' => $record->id,
            'domain' => 'patient_core',
            'intent_token' => $this->token('intent'),
            'unit_name' => 'A_PATIENT_CORE',
            'expected_prior_state' => 'CLASSIFIED',
            'state' => 'CORE_COMMITTING',
            'attempt' => 1,
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'integrity_checksum' => $this->token('intent-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    /** @return array<string, mixed> */
    private function checkpointAttributes(MigrationRun $run, IdempotencyRecord $record, int $intentId): array
    {
        return [
            'run_id' => $run->id,
            'idempotency_record_id' => $record->id,
            'atomic_intent_id' => $intentId,
            'domain' => 'patient_core',
            'dependency_chain_token' => $this->token('dependency-chain'),
            'stage' => 'CORE_COMMITTED',
            'attempt_contract_version' => 'SYNTHETIC-P3-1',
            'expected_prior_state' => 'CORE_COMMITTING',
            'state' => 'CORE_COMMITTED',
            'input_fingerprint' => $this->token('input-fingerprint'),
            'output_fingerprint' => $this->token('output-fingerprint'),
            'transaction_evidence_hash' => $this->token('transaction-evidence'),
            'write_set_hash' => $this->token('write-set'),
            'reconciliation_bundle_hash' => $this->token('checkpoint-reconciliation'),
            'contract_version' => 'SYNTHETIC-P3-1',
            'transformation_version' => 'synthetic-transform-v1',
            'canonicalization_version' => 'synthetic-canon-v1',
            'hmac_key_version' => 'synthetic-key-v1',
            'integrity_checksum' => $this->token('checkpoint-checksum'),
            'access_classification' => 'synthetic-protected',
            'retention_classification' => 'synthetic-test',
        ];
    }

    private function token(string $label): string
    {
        return hash('sha256', "SYNTHETIC-ONLY-P3::{$label}");
    }
}
