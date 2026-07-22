<?php

use App\Services\LegacyMigration\Foundation\Security\FoundationDatabaseWriteBoundary;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaInstallAllowed(DB::connection(), [
            'legacy_migration_crosswalks',
            'legacy_migration_quarantine_roots',
            'legacy_migration_reconciliation_results',
            'legacy_migration_audit_events',
        ]);

        Schema::create('legacy_migration_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_idem_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('source_snapshot_id');
            $table->unsignedBigInteger('target_snapshot_id')->nullable();
            $table->string('domain', 100);
            $table->char('protected_source_token', 64)->nullable();
            $table->char('protected_target_token', 64)->nullable();
            $table->char('idempotency_token', 64);
            $table->string('outcome_type', 100);
            $table->char('input_fingerprint', 64);
            $table->char('outcome_fingerprint', 64)->nullable();
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('encrypted_outcome_payload')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['domain', 'idempotency_token'], 'lm_idem_domain_token_uq');
            $table->unique(['id', 'run_id'], 'lm_idem_id_run_uq');
            $table->index(['run_id', 'state'], 'lm_idem_run_state_idx');
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_idem_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->foreign(['target_snapshot_id', 'run_id'], 'lm_idem_target_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
        });

        Schema::create('legacy_migration_atomic_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_intent_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('idempotency_record_id');
            $table->string('domain', 100);
            $table->char('protected_source_token', 64)->nullable();
            $table->char('protected_target_token', 64)->nullable();
            $table->char('intent_token', 64)->unique();
            $table->string('unit_name', 100);
            $table->string('expected_prior_state', 60);
            $table->string('state', 60);
            $table->unsignedInteger('attempt');
            $table->char('transaction_token', 64)->nullable();
            $table->char('write_set_hash', 64)->nullable();
            $table->char('zero_write_evidence_hash', 64)->nullable();
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_write_set_refs')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['idempotency_record_id', 'unit_name', 'attempt'], 'lm_intent_idem_unit_attempt_uq');
            $table->unique(['id', 'run_id'], 'lm_intent_id_run_uq');
            $table->unique(['id', 'idempotency_record_id', 'run_id'], 'lm_intent_id_idem_run_uq');
            $table->index(['run_id', 'state'], 'lm_intent_run_state_idx');
            $table->foreign(['idempotency_record_id', 'run_id'], 'lm_intent_idem_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_idempotency_records')->restrictOnDelete();
        });

        Schema::create('legacy_migration_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_ckpt_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('idempotency_record_id');
            $table->unsignedBigInteger('atomic_intent_id')->nullable();
            $table->string('domain', 100);
            $table->char('dependency_chain_token', 64);
            $table->string('stage', 100);
            $table->string('attempt_contract_version', 100);
            $table->string('expected_prior_state', 60);
            $table->string('state', 60);
            $table->char('input_fingerprint', 64);
            $table->char('output_fingerprint', 64);
            $table->char('transaction_evidence_hash', 64);
            $table->char('write_set_hash', 64)->nullable();
            $table->char('reconciliation_bundle_hash', 64);
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_durable_fact_refs')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamps();

            $table->unique(
                ['run_id', 'dependency_chain_token', 'stage', 'attempt_contract_version'],
                'lm_checkpoint_compatible_stage_uq'
            );
            $table->unique(['idempotency_record_id', 'stage'], 'lm_checkpoint_idem_stage_uq');
            $table->index(['run_id', 'state'], 'lm_checkpoint_run_state_idx');
            $table->foreign(['idempotency_record_id', 'run_id'], 'lm_checkpoint_idem_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_idempotency_records')->restrictOnDelete();
            $table->foreign(['atomic_intent_id', 'idempotency_record_id', 'run_id'], 'lm_checkpoint_intent_idem_run_fk')
                ->references(['id', 'idempotency_record_id', 'run_id'])->on('legacy_migration_atomic_intents')->restrictOnDelete();
        });

        Schema::create('legacy_migration_compensation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_comp_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('atomic_intent_id');
            $table->string('domain', 100);
            $table->char('protected_source_token', 64)->nullable();
            $table->char('protected_target_token', 64)->nullable();
            $table->char('compensation_token', 64)->unique();
            $table->string('unit_name', 100);
            $table->string('action_type', 100);
            $table->string('recovery_classification', 100);
            $table->string('state', 60);
            $table->boolean('operator_review_required')->default(true);
            $table->char('approval_token', 64)->nullable();
            $table->char('before_evidence_hash', 64);
            $table->char('after_evidence_hash', 64)->nullable();
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_action_payload')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['atomic_intent_id', 'compensation_token'], 'lm_comp_intent_token_uq');
            $table->index(['run_id', 'state'], 'lm_comp_run_state_idx');
            $table->foreign(['atomic_intent_id', 'run_id'], 'lm_comp_intent_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_atomic_intents')->restrictOnDelete();
        });

        Schema::create('legacy_migration_number_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_num_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('idempotency_record_id');
            $table->string('domain', 100)->default('patient_number');
            $table->char('protected_source_token', 64);
            $table->char('numbering_coordinate_token', 64);
            $table->unsignedBigInteger('sequence_ordinal');
            $table->char('protected_number_token', 64)->unique('lm_number_protected_token_uq');
            $table->char('configuration_fingerprint', 64);
            $table->string('period_key', 100);
            $table->string('timezone', 100);
            $table->string('state', 60);
            $table->string('consumption_classification', 60);
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_number_payload');
            $table->text('encrypted_explanation')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique('idempotency_record_id', 'lm_number_idem_uq');
            $table->unique(['numbering_coordinate_token', 'sequence_ordinal'], 'lm_number_coordinate_ordinal_uq');
            $table->index(['run_id', 'state'], 'lm_number_run_state_idx');
            $table->foreign(['idempotency_record_id', 'run_id'], 'lm_number_idem_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_idempotency_records')->restrictOnDelete();
        });

        foreach (['legacy_migration_checkpoints', 'legacy_migration_compensation_records'] as $table) {
            $this->protectAppendOnlyTable($table);
        }

        foreach (['legacy_migration_idempotency_records', 'legacy_migration_atomic_intents', 'legacy_migration_number_reservations'] as $table) {
            $this->protectNoDeleteTable($table);
        }

        $this->protectImmutableColumns('legacy_migration_idempotency_records', [
            'run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain',
            'protected_source_token', 'protected_target_token', 'idempotency_token',
            'outcome_type', 'input_fingerprint', 'contract_version',
            'transformation_version', 'canonicalization_version', 'hmac_key_version',
            'access_classification', 'retention_classification', 'created_by_token',
        ]);
        $this->protectImmutableColumns('legacy_migration_atomic_intents', [
            'run_id', 'idempotency_record_id', 'domain', 'protected_source_token',
            'protected_target_token', 'intent_token', 'unit_name',
            'expected_prior_state', 'attempt', 'contract_version',
            'transformation_version', 'canonicalization_version', 'hmac_key_version',
            'access_classification', 'retention_classification', 'created_by_token',
        ]);
        $this->protectImmutableColumns('legacy_migration_number_reservations', [
            'run_id', 'idempotency_record_id', 'domain', 'protected_source_token',
            'numbering_coordinate_token', 'sequence_ordinal', 'protected_number_token',
            'configuration_fingerprint', 'period_key', 'timezone', 'contract_version',
            'transformation_version', 'canonicalization_version', 'hmac_key_version',
            'encrypted_number_payload', 'access_classification',
            'retention_classification', 'created_by_token',
        ]);
    }

    public function down(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaRemovalAllowed(DB::connection());
        Schema::dropIfExists('legacy_migration_number_reservations');
        Schema::dropIfExists('legacy_migration_compensation_records');
        Schema::dropIfExists('legacy_migration_checkpoints');
        Schema::dropIfExists('legacy_migration_atomic_intents');
        Schema::dropIfExists('legacy_migration_idempotency_records');
    }

    private function protectAppendOnlyTable(string $table): void
    {
        $driver = DB::connection()->getDriverName();
        $stem = str_replace('legacy_migration_', 'lm_', $table);

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER {$stem}_no_update BEFORE UPDATE ON {$table} BEGIN SELECT RAISE(ABORT, 'append-only migration evidence'); END");
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, 'append-only migration evidence'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER {$stem}_no_update BEFORE UPDATE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only migration evidence'");
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only migration evidence'");
        }
    }

    private function protectNoDeleteTable(string $table): void
    {
        $driver = DB::connection()->getDriverName();
        $stem = str_replace('legacy_migration_', 'lm_', $table);

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, 'protected migration recovery state'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'protected migration recovery state'");
        }
    }

    /** @param list<string> $columns */
    private function protectImmutableColumns(string $table, array $columns): void
    {
        $driver = DB::connection()->getDriverName();
        $stem = str_replace('legacy_migration_', 'lm_', $table);

        if ($driver === 'sqlite') {
            $changed = implode(' OR ', array_map(static fn (string $column): string => "NEW.{$column} IS NOT OLD.{$column}", $columns));
            DB::unprepared("CREATE TRIGGER {$stem}_lineage_no_update BEFORE UPDATE ON {$table} WHEN {$changed} BEGIN SELECT RAISE(ABORT, 'immutable migration recovery coordinate'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $changed = implode(' OR ', array_map(static fn (string $column): string => "NOT (NEW.{$column} <=> OLD.{$column})", $columns));
            DB::unprepared("CREATE TRIGGER {$stem}_lineage_no_update BEFORE UPDATE ON {$table} FOR EACH ROW BEGIN IF {$changed} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'immutable migration recovery coordinate'; END IF; END");
        }
    }
};
