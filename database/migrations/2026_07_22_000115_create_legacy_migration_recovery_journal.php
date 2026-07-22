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
            'legacy_migration_idempotency_records',
            'legacy_migration_atomic_intents',
        ]);

        Schema::table('legacy_migration_atomic_intents', function (Blueprint $table): void {
            $table->unsignedInteger('transition_attempt_count')->default(0)->after('attempt');
        });

        Schema::create('legacy_migration_recovery_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('run_id')->constrained('legacy_migration_runs', 'id', 'lm_rj_run_fk')->restrictOnDelete();
            $table->unsignedBigInteger('atomic_intent_id');
            $table->unsignedBigInteger('idempotency_record_id');
            $table->unsignedBigInteger('source_snapshot_id');
            $table->unsignedBigInteger('target_snapshot_id')->nullable();
            $table->unsignedBigInteger('contract_bundle_id');
            $table->char('journal_token', 64)->unique('lm_rj_token_uq');
            $table->string('event_type', 60);
            $table->string('crash_boundary', 60)->nullable();
            $table->string('recovery_disposition', 100)->nullable();
            $table->string('unit_name', 100);
            $table->string('expected_state', 60);
            $table->string('observed_state', 60);
            $table->string('decision_state', 60);
            $table->unsignedInteger('expected_lock_version');
            $table->unsignedInteger('resulting_lock_version');
            $table->unsignedInteger('expected_attempt_count');
            $table->unsignedInteger('resulting_attempt_count');
            $table->boolean('target_writes_permitted')->default(false);
            $table->boolean('operator_review_required')->default(false);
            $table->char('input_fingerprint', 64);
            $table->char('source_snapshot_fingerprint', 64);
            $table->char('target_snapshot_fingerprint', 64)->nullable();
            $table->char('contract_bundle_hash', 64);
            $table->char('transaction_evidence_hash', 64);
            $table->char('write_set_hash', 64);
            $table->char('crosswalk_evidence_hash', 64);
            $table->char('provenance_evidence_hash', 64);
            $table->char('reconciliation_evidence_hash', 64);
            $table->char('checkpoint_evidence_hash', 64);
            $table->char('compensation_evidence_hash', 64);
            $table->string('contract_version', 100);
            $table->string('transformation_version', 100);
            $table->string('canonicalization_version', 100);
            $table->string('token_environment', 64);
            $table->string('hmac_key_id', 64);
            $table->string('hmac_key_version', 100);
            $table->text('encrypted_evidence');
            $table->char('integrity_checksum', 64);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->char('created_by_token', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['atomic_intent_id', 'event_type', 'crash_boundary', 'resulting_lock_version'],
                'lm_rj_intent_event_boundary_version_uq',
            );
            $table->index(['run_id', 'event_type', 'created_at'], 'lm_rj_run_event_time_idx');
            $table->foreign(['atomic_intent_id', 'idempotency_record_id', 'run_id'], 'lm_rj_intent_idem_run_fk')
                ->references(['id', 'idempotency_record_id', 'run_id'])->on('legacy_migration_atomic_intents')->restrictOnDelete();
            $table->foreign(['source_snapshot_id', 'run_id'], 'lm_rj_source_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->foreign(['target_snapshot_id', 'run_id'], 'lm_rj_target_run_fk')
                ->references(['id', 'run_id'])->on('legacy_migration_snapshots')->restrictOnDelete();
            $table->foreign('contract_bundle_id', 'lm_rj_bundle_fk')
                ->references('id')->on('legacy_migration_contract_bundles')->restrictOnDelete();
        });

        $this->protectAppendOnlyJournal();
        $this->protectAtomicIntentTransitions();
    }

    public function down(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaRemovalAllowed(DB::connection());
        Schema::dropIfExists('legacy_migration_recovery_journal_entries');
        Schema::table('legacy_migration_atomic_intents', function (Blueprint $table): void {
            $table->dropColumn('transition_attempt_count');
        });
    }

    private function protectAppendOnlyJournal(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER lm_rj_no_update BEFORE UPDATE ON legacy_migration_recovery_journal_entries BEGIN SELECT RAISE(ABORT, 'append-only recovery journal'); END");
            DB::unprepared("CREATE TRIGGER lm_rj_no_delete BEFORE DELETE ON legacy_migration_recovery_journal_entries BEGIN SELECT RAISE(ABORT, 'append-only recovery journal'); END");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER lm_rj_no_update BEFORE UPDATE ON legacy_migration_recovery_journal_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only recovery journal'");
            DB::unprepared("CREATE TRIGGER lm_rj_no_delete BEFORE DELETE ON legacy_migration_recovery_journal_entries FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only recovery journal'");
        }
    }

    private function protectAtomicIntentTransitions(): void
    {
        $edges = [
            'NOT_STARTED' => ['EXTRACTED', 'QUARANTINED'],
            'EXTRACTED' => ['CLASSIFIED', 'QUARANTINED'],
            'CLASSIFIED' => ['DRY_RUN_ACCEPTED', 'QUARANTINED'],
            'DRY_RUN_ACCEPTED' => ['CORE_COMMITTING', 'QUARANTINED'],
            'CORE_COMMITTING' => ['CORE_COMMITTED', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
            'CORE_COMMITTED' => ['ALIAS_PENDING', 'COMPENSATION_REQUIRED'],
            'ALIAS_PENDING' => ['ALIAS_COMMITTED', 'ALIAS_WITHHELD', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
            'ALIAS_COMMITTED' => ['CONTACT_PENDING', 'COMPENSATION_REQUIRED'],
            'ALIAS_WITHHELD' => ['CONTACT_PENDING'],
            'CONTACT_PENDING' => ['CONTACT_COMMITTED', 'CONTACT_WITHHELD', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
            'CONTACT_COMMITTED' => ['INSURANCE_HISTORY_PENDING', 'COMPENSATION_REQUIRED'],
            'CONTACT_WITHHELD' => ['INSURANCE_HISTORY_PENDING'],
            'INSURANCE_HISTORY_PENDING' => ['INSURANCE_HISTORY_COMMITTED', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
            'INSURANCE_HISTORY_COMMITTED' => ['INSURANCE_CURRENT_PENDING', 'COMPENSATION_REQUIRED'],
            'INSURANCE_CURRENT_PENDING' => ['INSURANCE_CURRENT_COMMITTED', 'INSURANCE_CURRENT_WITHHELD', 'ROLLBACK_REQUIRED', 'COMPENSATION_REQUIRED'],
            'INSURANCE_CURRENT_COMMITTED' => ['RECONCILIATION_PENDING', 'COMPENSATION_REQUIRED'],
            'INSURANCE_CURRENT_WITHHELD' => ['RECONCILIATION_PENDING'],
            'RECONCILIATION_PENDING' => ['RECONCILIATION_PASSED', 'RECONCILIATION_FAILED', 'COMPENSATION_REQUIRED'],
            'RECONCILIATION_PASSED' => ['COMPLETED'],
        ];
        $allowed = implode(' OR ', array_map(
            static fn (string $from, array $to): string => "(OLD.state = '{$from}' AND NEW.state IN ('".implode("','", $to)."'))",
            array_keys($edges),
            array_values($edges),
        ));
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER lm_intent_state_transition_guard BEFORE UPDATE ON legacy_migration_atomic_intents WHEN (NEW.state IS NOT OLD.state OR NEW.lock_version IS NOT OLD.lock_version OR NEW.transition_attempt_count IS NOT OLD.transition_attempt_count) AND NOT (NEW.lock_version = OLD.lock_version + 1 AND NEW.transition_attempt_count = OLD.transition_attempt_count + 1 AND ({$allowed})) BEGIN SELECT RAISE(ABORT, 'invalid recovery state transition'); END");

            return;
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER lm_intent_state_transition_guard BEFORE UPDATE ON legacy_migration_atomic_intents FOR EACH ROW BEGIN IF NOT (NEW.state <=> OLD.state) OR NEW.lock_version <> OLD.lock_version OR NEW.transition_attempt_count <> OLD.transition_attempt_count THEN IF NOT (NEW.lock_version = OLD.lock_version + 1 AND NEW.transition_attempt_count = OLD.transition_attempt_count + 1 AND ({$allowed})) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'invalid recovery state transition'; END IF; END IF; END");
        }
    }
};
