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
            'legacy_migration_contract_bundles',
            'legacy_migration_runs',
            'legacy_migration_snapshots',
            'legacy_migration_target_collision_snapshots',
        ]);

        Schema::create('legacy_migration_crosswalks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_xw_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('source_snapshot_id');
            $table->unsignedBigInteger('target_snapshot_id')->nullable();
            $table->string('domain', 100);
            $table->char('protected_source_token', 64);
            $table->char('protected_target_token', 64)->nullable();
            $table->char('patient_root_token', 64)->nullable();
            $table->char('active_coordinate_token', 64)->nullable();
            $table->boolean('is_active')->default(false);
            $table->string('branch', 60);
            $table->string('state', 60);
            $table->string('patient_number_action', 100)->default('none');
            $table->char('patient_number_result_token', 64)->nullable();
            $table->char('existing_target_evidence_token', 64)->nullable();
            $table->char('run_provenance_token', 64)->nullable();
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->char('idempotency_token', 64);
            $table->text('encrypted_mapping_payload')->nullable();
            $table->text('encrypted_authoritative_rule_ids')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->string('revocation_state', 40)->default('not_revoked');
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['domain', 'idempotency_token'], 'lm_xwalk_domain_idem_uq');
            $table->unique(
                ['domain', 'protected_source_token', 'active_coordinate_token'],
                'lm_xwalk_active_source_coordinate_uq'
            );
            $table->index(['run_id', 'state'], 'lm_xwalk_run_state_idx');
            $table->index(['domain', 'protected_target_token'], 'lm_xwalk_domain_target_idx');
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_xwalk_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->foreign(['target_snapshot_id', 'run_id'], 'lm_xwalk_target_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
        });

        Schema::create('legacy_migration_remediations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_remed_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('source_snapshot_id');
            $table->string('domain', 100);
            $table->char('protected_source_token', 64);
            $table->char('remediation_token', 64)->unique();
            $table->char('supersedes_token', 64)->nullable();
            $table->string('evidence_type', 100);
            $table->char('evidence_issuer_token', 64);
            $table->char('reviewer_token', 64);
            $table->string('approval_state', 60);
            $table->boolean('has_unresolved_conflict')->default(true);
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->text('encrypted_values');
            $table->text('encrypted_evidence_metadata')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(
                ['domain', 'protected_source_token', 'remediation_token'],
                'lm_remed_domain_source_token_uq'
            );
            $table->index(['approval_state', 'revoked_at', 'valid_until'], 'lm_remed_approval_validity_idx');
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_remed_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
        });

        Schema::create('legacy_migration_provenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_prov_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('source_snapshot_id');
            $table->string('domain', 100);
            $table->char('protected_source_token', 64);
            $table->char('protected_target_token', 64)->nullable();
            $table->char('patient_root_token', 64)->nullable();
            $table->char('subchain_token', 64)->nullable();
            $table->char('outcome_coordinate_token', 64);
            $table->string('source_query_id', 150);
            $table->char('query_hash', 64);
            $table->string('target_outcome', 60);
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->text('encrypted_raw_payload')->nullable();
            $table->text('encrypted_field_dispositions');
            $table->text('encrypted_outcome_metadata')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamps();

            $table->unique(
                ['run_id', 'domain', 'protected_source_token', 'outcome_coordinate_token'],
                'lm_prov_run_domain_source_outcome_uq'
            );
            $table->index(['patient_root_token', 'subchain_token'], 'lm_prov_root_subchain_idx');
            $table->index(['run_id', 'target_outcome'], 'lm_prov_run_outcome_idx');
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_prov_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
        });

        Schema::create('legacy_migration_quarantine_roots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_qroot_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('source_snapshot_id');
            $table->unsignedBigInteger('parent_root_id')->nullable();
            $table->string('root_domain', 100);
            $table->char('root_token', 64);
            $table->char('subchain_token', 64)->nullable();
            $table->char('chain_coordinate_token', 64);
            $table->string('blocking_scope', 60);
            $table->string('current_disposition', 60);
            $table->char('manual_review_owner_token', 64);
            $table->timestamp('manual_review_due_at')->nullable();
            $table->char('release_approval_token', 64)->nullable();
            $table->unsignedSmallInteger('topological_rank');
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->text('encrypted_release_conditions')->nullable();
            $table->text('encrypted_dependency_edges')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->char('updated_by_token', 64)->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(
                ['root_domain', 'root_token', 'source_snapshot_id'],
                'lm_quar_one_root_per_coordinate_uq'
            );
            $table->unique(['id', 'run_id', 'source_snapshot_id'], 'lm_quar_parent_coordinate_uq');
            $table->foreign(
                ['parent_root_id', 'run_id', 'source_snapshot_id'],
                'lm_quar_parent_coordinate_fk'
            )->references(['id', 'run_id', 'source_snapshot_id'])
                ->on('legacy_migration_quarantine_roots')
                ->restrictOnDelete();
            $table->index(['run_id', 'current_disposition'], 'lm_quar_run_disposition_idx');
            $table->index(['parent_root_id', 'topological_rank'], 'lm_quar_parent_rank_idx');
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_qroot_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
        });

        Schema::create('legacy_migration_quarantine_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quarantine_root_id')->constrained('legacy_migration_quarantine_roots', 'id', 'lm_qexc_root_fk')->restrictOnDelete();
            $table->string('exception_code', 150);
            $table->unsignedSmallInteger('precedence_ordinal');
            $table->boolean('is_primary')->default(false);
            $table->char('primary_guard_token', 64)->nullable();
            $table->string('rule_version', 100);
            $table->string('evidence_version', 100);
            $table->char('idempotency_token', 64);
            $table->string('state', 60);
            $table->text('encrypted_evidence')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamps();

            $table->unique(['quarantine_root_id', 'precedence_ordinal'], 'lm_quar_exc_root_order_uq');
            $table->unique(['quarantine_root_id', 'idempotency_token'], 'lm_quar_exc_root_idem_uq');
            $table->index('primary_guard_token', 'lm_quar_exc_primary_guard_idx');
            $table->index(['exception_code', 'state'], 'lm_quar_exc_code_state_idx');
        });

        Schema::create('legacy_migration_reconciliation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_recon_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('source_snapshot_id');
            $table->unsignedBigInteger('target_snapshot_id')->nullable();
            $table->char('cohort_token', 64);
            $table->string('domain', 100);
            $table->string('contract_id', 150);
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->boolean('mandatory')->default(true);
            $table->boolean('measurement_complete')->default(false);
            $table->decimal('difference', 30, 8)->nullable();
            $table->decimal('tolerance', 30, 8)->default(0);
            $table->string('acceptance_result', 60);
            $table->string('state', 60);
            $table->char('evidence_bundle_hash', 64);
            $table->char('idempotency_token', 64)->unique();
            $table->char('approval_token', 64)->nullable();
            $table->text('encrypted_population_definition');
            $table->text('encrypted_expected_equation');
            $table->text('encrypted_measured_values')->nullable();
            $table->text('encrypted_exception_refs')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamps();

            $table->unique(
                ['run_id', 'cohort_token', 'contract_id', 'evidence_bundle_hash'],
                'lm_recon_coordinate_evidence_uq'
            );
            $table->index(['run_id', 'acceptance_result'], 'lm_recon_run_result_idx');
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_recon_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->foreign(['target_snapshot_id', 'run_id'], 'lm_recon_target_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
        });

        Schema::create('legacy_migration_audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_audit_run_fk')->restrictOnDelete();
            $table->string('domain', 100);
            $table->string('event_type', 150);
            $table->char('protected_source_token', 64)->nullable();
            $table->char('protected_target_token', 64)->nullable();
            $table->char('snapshot_token', 64)->nullable();
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('hmac_key_version', 100);
            $table->string('state', 60);
            $table->char('event_token', 64)->unique();
            $table->text('encrypted_event_payload')->nullable();
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('actor_token', 64)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['run_id', 'event_type', 'occurred_at'], 'lm_audit_run_type_time_idx');
        });

        $this->addCrosswalkActiveConstraint();
        $this->addCrosswalkActiveSourceKey();
        $this->addQuarantinePrimaryConstraint();
        $this->addQuarantinePrimaryRootKey();
        $this->protectQuarantineRootCompleteness();
        $this->addReconciliationConstraints();

        foreach (['legacy_migration_provenance_records', 'legacy_migration_quarantine_exceptions', 'legacy_migration_reconciliation_results', 'legacy_migration_audit_events'] as $table) {
            $this->protectAppendOnlyTable($table);
        }

        foreach (['legacy_migration_crosswalks', 'legacy_migration_remediations', 'legacy_migration_quarantine_roots'] as $table) {
            $this->protectNoDeleteTable($table);
        }

        $this->protectImmutableColumns('legacy_migration_crosswalks', [
            'run_id', 'source_snapshot_id', 'target_snapshot_id', 'domain',
            'protected_source_token', 'protected_target_token', 'patient_root_token',
            'branch', 'patient_number_action',
            'patient_number_result_token', 'existing_target_evidence_token',
            'run_provenance_token', 'contract_version', 'transformation_version',
            'canonicalization_version', 'hmac_key_version', 'idempotency_token',
            'encrypted_mapping_payload', 'encrypted_authoritative_rule_ids',
            'access_classification', 'retention_classification', 'created_by_token',
        ]);
        $this->protectImmutableColumns('legacy_migration_remediations', [
            'run_id', 'source_snapshot_id', 'domain', 'protected_source_token',
            'remediation_token', 'supersedes_token', 'evidence_type',
            'evidence_issuer_token', 'reviewer_token', 'valid_from',
            'contract_version', 'transformation_version', 'canonicalization_version',
            'hmac_key_version', 'encrypted_values', 'encrypted_evidence_metadata',
            'access_classification', 'retention_classification', 'created_by_token',
        ]);
        $this->protectImmutableColumns('legacy_migration_quarantine_roots', [
            'run_id', 'source_snapshot_id', 'parent_root_id', 'root_domain',
            'root_token', 'subchain_token', 'chain_coordinate_token', 'blocking_scope',
            'manual_review_owner_token', 'topological_rank', 'contract_version',
            'transformation_version', 'canonicalization_version', 'hmac_key_version',
            'encrypted_dependency_edges', 'access_classification',
            'retention_classification', 'created_by_token',
        ]);
    }

    public function down(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaRemovalAllowed(DB::connection());
        Schema::dropIfExists('legacy_migration_audit_events');
        Schema::dropIfExists('legacy_migration_reconciliation_results');
        Schema::dropIfExists('legacy_migration_quarantine_exceptions');
        Schema::dropIfExists('legacy_migration_quarantine_roots');
        Schema::dropIfExists('legacy_migration_provenance_records');
        Schema::dropIfExists('legacy_migration_remediations');
        Schema::dropIfExists('legacy_migration_crosswalks');
    }

    private function addCrosswalkActiveConstraint(): void
    {
        $driver = DB::connection()->getDriverName();
        $invalid = '(NEW.is_active = 1 AND NEW.active_coordinate_token IS NULL) OR (NEW.is_active = 0 AND NEW.active_coordinate_token IS NOT NULL)';

        if ($driver === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $name = strtolower("lm_xwalk_active_{$operation}");
                DB::unprepared("CREATE TRIGGER {$name} BEFORE {$operation} ON legacy_migration_crosswalks WHEN {$invalid} BEGIN SELECT RAISE(ABORT, 'invalid active crosswalk coordinate'); END");
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE legacy_migration_crosswalks ADD CONSTRAINT lm_xwalk_active_ck CHECK ((is_active = 1 AND active_coordinate_token IS NOT NULL) OR (is_active = 0 AND active_coordinate_token IS NULL))');
        }
    }

    private function addCrosswalkActiveSourceKey(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement("ALTER TABLE legacy_migration_crosswalks ADD COLUMN active_source_guard_key TEXT GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN domain || ':' || protected_source_token ELSE NULL END) STORED");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE legacy_migration_crosswalks ADD COLUMN active_source_guard_key VARCHAR(191) GENERATED ALWAYS AS (CASE WHEN is_active = 1 THEN CONCAT(domain, ':', protected_source_token) ELSE NULL END) STORED");
        } else {
            throw new RuntimeException('Unsupported database driver for active crosswalk enforcement.');
        }

        DB::statement('CREATE UNIQUE INDEX lm_xwalk_one_active_source_uq ON legacy_migration_crosswalks (active_source_guard_key)');
    }

    private function addReconciliationConstraints(): void
    {
        $driver = DB::connection()->getDriverName();
        $invalid = "(NEW.acceptance_result = 'passed' AND (NEW.measurement_complete <> 1 OR NEW.difference IS NULL OR NEW.difference <> 0 OR NEW.tolerance <> 0)) OR (NEW.mandatory = 1 AND NEW.measurement_complete = 0 AND NEW.acceptance_result <> 'blocked_not_measured')";

        if ($driver === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $name = strtolower("lm_recon_verdict_{$operation}");
                DB::unprepared("CREATE TRIGGER {$name} BEFORE {$operation} ON legacy_migration_reconciliation_results WHEN {$invalid} BEGIN SELECT RAISE(ABORT, 'invalid reconciliation verdict'); END");
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE legacy_migration_reconciliation_results ADD CONSTRAINT lm_recon_pass_ck CHECK (acceptance_result <> 'passed' OR (measurement_complete = 1 AND difference IS NOT NULL AND difference = 0 AND tolerance = 0))");
            DB::statement("ALTER TABLE legacy_migration_reconciliation_results ADD CONSTRAINT lm_recon_missing_ck CHECK (mandatory = 0 OR measurement_complete = 1 OR acceptance_result = 'blocked_not_measured')");
        }
    }

    private function addQuarantinePrimaryConstraint(): void
    {
        $driver = DB::connection()->getDriverName();
        $invalid = '(NEW.is_primary = 1 AND NEW.primary_guard_token IS NULL) OR (NEW.is_primary = 0 AND NEW.primary_guard_token IS NOT NULL)';

        if ($driver === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $name = strtolower("lm_quar_primary_{$operation}");
                DB::unprepared("CREATE TRIGGER {$name} BEFORE {$operation} ON legacy_migration_quarantine_exceptions WHEN {$invalid} BEGIN SELECT RAISE(ABORT, 'invalid primary quarantine guard'); END");
            }

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE legacy_migration_quarantine_exceptions ADD CONSTRAINT lm_quar_primary_ck CHECK ((is_primary = 1 AND primary_guard_token IS NOT NULL) OR (is_primary = 0 AND primary_guard_token IS NULL))');
        }
    }

    private function addQuarantinePrimaryRootKey(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('ALTER TABLE legacy_migration_quarantine_exceptions ADD COLUMN primary_root_guard_key INTEGER GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN quarantine_root_id ELSE NULL END) STORED');
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE legacy_migration_quarantine_exceptions ADD COLUMN primary_root_guard_key BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN quarantine_root_id ELSE NULL END) STORED');
        } else {
            throw new RuntimeException('Unsupported database driver for quarantine primary enforcement.');
        }

        DB::statement('CREATE UNIQUE INDEX lm_quar_one_primary_root_uq ON legacy_migration_quarantine_exceptions (primary_root_guard_key)');
    }

    private function protectQuarantineRootCompleteness(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER lm_quar_root_build_only BEFORE INSERT ON legacy_migration_quarantine_roots WHEN NEW.current_disposition <> 'building' OR NEW.state <> 'building' BEGIN SELECT RAISE(ABORT, 'quarantine root must be built atomically'); END");
            DB::unprepared("CREATE TRIGGER lm_quar_root_seal_primary BEFORE UPDATE ON legacy_migration_quarantine_roots WHEN OLD.current_disposition = 'building' AND NEW.current_disposition <> 'building' AND (SELECT COUNT(*) FROM legacy_migration_quarantine_exceptions WHERE quarantine_root_id = OLD.id AND is_primary = 1) <> 1 BEGIN SELECT RAISE(ABORT, 'quarantine root requires exactly one primary exception'); END");
            DB::unprepared("CREATE TRIGGER lm_quar_root_no_rebuild BEFORE UPDATE ON legacy_migration_quarantine_roots WHEN OLD.current_disposition <> 'building' AND NEW.current_disposition = 'building' BEGIN SELECT RAISE(ABORT, 'sealed quarantine root cannot re-enter building state'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER lm_quar_root_build_only BEFORE INSERT ON legacy_migration_quarantine_roots FOR EACH ROW BEGIN IF NEW.current_disposition <> 'building' OR NEW.state <> 'building' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quarantine root must be built atomically'; END IF; END");
            DB::unprepared("CREATE TRIGGER lm_quar_root_seal_primary BEFORE UPDATE ON legacy_migration_quarantine_roots FOR EACH ROW BEGIN IF OLD.current_disposition = 'building' AND NEW.current_disposition <> 'building' AND (SELECT COUNT(*) FROM legacy_migration_quarantine_exceptions WHERE quarantine_root_id = OLD.id AND is_primary = 1) <> 1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quarantine root requires exactly one primary exception'; END IF; END");
            DB::unprepared("CREATE TRIGGER lm_quar_root_no_rebuild BEFORE UPDATE ON legacy_migration_quarantine_roots FOR EACH ROW BEGIN IF OLD.current_disposition <> 'building' AND NEW.current_disposition = 'building' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'sealed quarantine root cannot re-enter building state'; END IF; END");
        }
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
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, 'protected migration lineage'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'protected migration lineage'");
        }
    }

    /** @param list<string> $columns */
    private function protectImmutableColumns(string $table, array $columns): void
    {
        $driver = DB::connection()->getDriverName();
        $stem = str_replace('legacy_migration_', 'lm_', $table);

        if ($driver === 'sqlite') {
            $changed = implode(' OR ', array_map(static fn (string $column): string => "NEW.{$column} IS NOT OLD.{$column}", $columns));
            DB::unprepared("CREATE TRIGGER {$stem}_lineage_no_update BEFORE UPDATE ON {$table} WHEN {$changed} BEGIN SELECT RAISE(ABORT, 'immutable migration lineage'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $changed = implode(' OR ', array_map(static fn (string $column): string => "NOT (NEW.{$column} <=> OLD.{$column})", $columns));
            DB::unprepared("CREATE TRIGGER {$stem}_lineage_no_update BEFORE UPDATE ON {$table} FOR EACH ROW BEGIN IF {$changed} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'immutable migration lineage'; END IF; END");
        }
    }
};
