<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\LegacyMigration\Foundation\Security\FoundationDatabaseWriteBoundary;

return new class extends Migration
{
    public function up(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaInstallAllowed(DB::connection());

        Schema::create('legacy_migration_contract_bundles', function (Blueprint $table) {
            $table->id();
            $table->char('bundle_token', 64)->unique();
            $table->string('bundle_version', 100);
            $table->char('bundle_hash', 64);
            $table->string('canonicalization_version', 100);
            $table->string('token_environment', 64);
            $table->string('hmac_key_id', 64);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_manifest');
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamps();

            $table->unique(['bundle_version', 'bundle_hash'], 'lm_contract_bundle_version_hash_uq');
        });

        Schema::create('legacy_migration_runs', function (Blueprint $table) {
            $table->id();
            $table->char('run_token', 64)->unique();
            $table->char('cohort_token', 64);
            $table->foreignId('contract_bundle_id')->constrained('legacy_migration_contract_bundles', 'id', 'lm_run_bundle_fk')->restrictOnDelete();
            $table->string('mode', 40);
            $table->string('state', 60)->default('NOT_STARTED');
            $table->char('configuration_fingerprint', 64);
            $table->char('source_fingerprint', 64);
            $table->char('target_fingerprint', 64);
            $table->string('canonicalization_version', 100);
            $table->string('token_environment', 64);
            $table->string('hmac_key_id', 64);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_manifest');
            $table->char('manifest_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['state', 'mode'], 'lm_runs_state_mode_idx');
            $table->index(['cohort_token', 'contract_bundle_id'], 'lm_runs_cohort_bundle_idx');
        });

        Schema::create('legacy_migration_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_snap_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('parent_snapshot_id')->nullable();
            $table->char('snapshot_token', 64)->unique();
            $table->string('snapshot_kind', 40);
            $table->string('domain', 100);
            $table->char('coordinate_token', 64);
            $table->char('database_fingerprint', 64);
            $table->char('schema_fingerprint', 64);
            $table->char('query_bundle_hash', 64);
            $table->char('result_hash', 64);
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->text('encrypted_metadata')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamp('captured_at');
            $table->timestamps();

            $table->unique(['run_id', 'snapshot_kind', 'domain', 'coordinate_token'], 'lm_snap_run_kind_domain_coord_uq');
            $table->unique(['id', 'run_id'], 'lm_snap_id_run_uq');
            $table->foreign(['parent_snapshot_id', 'run_id'], 'lm_snapshot_parent_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->index(['run_id', 'state'], 'lm_snap_run_state_idx');
        });

        Schema::create('legacy_migration_target_collision_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_coll_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('target_snapshot_id');
            $table->char('collision_snapshot_token', 64)->unique('lm_collision_token_uq');
            $table->string('domain', 100);
            $table->string('collision_namespace', 100);
            $table->char('collision_coordinate_token', 64);
            $table->char('schema_fingerprint', 64);
            $table->char('configuration_fingerprint', 64);
            $table->char('row_set_hash', 64);
            $table->unsignedBigInteger('observed_count');
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->text('encrypted_evidence')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamp('captured_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['run_id', 'domain', 'collision_namespace', 'collision_coordinate_token'],
                'lm_collision_run_domain_namespace_coord_uq'
            );
            $table->foreign(['target_snapshot_id', 'run_id'], 'lm_collision_snapshot_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->index(['run_id', 'state', 'expires_at'], 'lm_collision_run_state_exp_idx');
        });

        foreach (['legacy_migration_contract_bundles', 'legacy_migration_snapshots', 'legacy_migration_target_collision_snapshots'] as $table) {
            $this->protectAppendOnlyTable($table);
        }

        $this->protectRunManifest();
        $this->protectRunStateTransitions();
        $this->protectNoDeleteTable('legacy_migration_runs');
    }

    public function down(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaRemovalAllowed(DB::connection());
        Schema::dropIfExists('legacy_migration_target_collision_snapshots');
        Schema::dropIfExists('legacy_migration_snapshots');
        Schema::dropIfExists('legacy_migration_runs');
        Schema::dropIfExists('legacy_migration_contract_bundles');
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

    private function protectRunManifest(): void
    {
        $driver = DB::connection()->getDriverName();
        $columns = [
            'run_token', 'cohort_token', 'contract_bundle_id', 'mode',
            'configuration_fingerprint', 'source_fingerprint', 'target_fingerprint',
            'canonicalization_version', 'token_environment', 'hmac_key_id',
            'hmac_key_version', 'encrypted_manifest',
            'manifest_checksum', 'access_classification', 'retention_classification',
            'created_by_token',
        ];

        if ($driver === 'sqlite') {
            $changed = implode(' OR ', array_map(static fn (string $column): string => "NEW.{$column} IS NOT OLD.{$column}", $columns));
            DB::unprepared("CREATE TRIGGER lm_runs_manifest_no_update BEFORE UPDATE ON legacy_migration_runs WHEN {$changed} BEGIN SELECT RAISE(ABORT, 'immutable migration run manifest'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $changed = implode(' OR ', array_map(static fn (string $column): string => "NOT (NEW.{$column} <=> OLD.{$column})", $columns));
            DB::unprepared("CREATE TRIGGER lm_runs_manifest_no_update BEFORE UPDATE ON legacy_migration_runs FOR EACH ROW BEGIN IF {$changed} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'immutable migration run manifest'; END IF; END");
        }
    }

    private function protectNoDeleteTable(string $table): void
    {
        $driver = DB::connection()->getDriverName();
        $stem = str_replace('legacy_migration_', 'lm_', $table);

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, 'protected migration lineage'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'protected migration lineage'");
        }
    }

    private function protectRunStateTransitions(): void
    {
        $driver = DB::connection()->getDriverName();
        $edge = "(OLD.state = 'NOT_STARTED' AND NEW.state IN ('EXTRACTED','QUARANTINED')) OR "
            ."(OLD.state = 'EXTRACTED' AND NEW.state IN ('CLASSIFIED','QUARANTINED')) OR "
            ."(OLD.state = 'CLASSIFIED' AND NEW.state IN ('DRY_RUN_ACCEPTED','QUARANTINED')) OR "
            ."(OLD.state = 'DRY_RUN_ACCEPTED' AND NEW.state IN ('CORE_COMMITTING','QUARANTINED')) OR "
            ."(OLD.state = 'CORE_COMMITTING' AND NEW.state IN ('CORE_COMMITTED','ROLLBACK_REQUIRED','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'CORE_COMMITTED' AND NEW.state IN ('ALIAS_PENDING','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'ALIAS_PENDING' AND NEW.state IN ('ALIAS_COMMITTED','ALIAS_WITHHELD','ROLLBACK_REQUIRED','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'ALIAS_COMMITTED' AND NEW.state IN ('CONTACT_PENDING','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'ALIAS_WITHHELD' AND NEW.state = 'CONTACT_PENDING') OR "
            ."(OLD.state = 'CONTACT_PENDING' AND NEW.state IN ('CONTACT_COMMITTED','CONTACT_WITHHELD','ROLLBACK_REQUIRED','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'CONTACT_COMMITTED' AND NEW.state IN ('INSURANCE_HISTORY_PENDING','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'CONTACT_WITHHELD' AND NEW.state = 'INSURANCE_HISTORY_PENDING') OR "
            ."(OLD.state = 'INSURANCE_HISTORY_PENDING' AND NEW.state IN ('INSURANCE_HISTORY_COMMITTED','ROLLBACK_REQUIRED','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'INSURANCE_HISTORY_COMMITTED' AND NEW.state IN ('INSURANCE_CURRENT_PENDING','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'INSURANCE_CURRENT_PENDING' AND NEW.state IN ('INSURANCE_CURRENT_COMMITTED','INSURANCE_CURRENT_WITHHELD','ROLLBACK_REQUIRED','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'INSURANCE_CURRENT_COMMITTED' AND NEW.state IN ('RECONCILIATION_PENDING','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'INSURANCE_CURRENT_WITHHELD' AND NEW.state = 'RECONCILIATION_PENDING') OR "
            ."(OLD.state = 'RECONCILIATION_PENDING' AND NEW.state IN ('RECONCILIATION_PASSED','RECONCILIATION_FAILED','COMPENSATION_REQUIRED')) OR "
            ."(OLD.state = 'RECONCILIATION_PASSED' AND NEW.state = 'COMPLETED')";
        $invalid = "NEW.state <> OLD.state AND (NOT ({$edge}) OR NEW.lock_version <> OLD.lock_version + 1)";

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER lm_runs_state_transition BEFORE UPDATE ON legacy_migration_runs WHEN {$invalid} BEGIN SELECT RAISE(ABORT, 'invalid migration run state transition'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER lm_runs_state_transition BEFORE UPDATE ON legacy_migration_runs FOR EACH ROW BEGIN IF {$invalid} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'invalid migration run state transition'; END IF; END");
        }
    }
};
