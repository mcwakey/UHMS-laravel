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
            'legacy_migration_runs',
            'legacy_migration_snapshots',
            'legacy_migration_audit_events',
        ]);

        Schema::create('legacy_migration_protected_key_references', function (Blueprint $table) {
            $table->id();
            $table->string('token_environment', 64);
            $table->string('domain', 100);
            $table->string('hmac_key_id', 64);
            $table->string('hmac_key_version', 100);
            $table->string('secret_reference', 255);
            $table->timestamp('activated_at');
            $table->timestamp('retired_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->string('rotation_authority_reference', 160);
            $table->boolean('revoked')->default(false);
            $table->boolean('active_for_signing')->default(false);
            $table->char('integrity_checksum', 64);
            $table->timestamps();

            $table->unique(['token_environment', 'domain', 'hmac_key_id', 'hmac_key_version'], 'lm_key_ref_env_domain_id_version_uq');
            $table->index(['token_environment', 'domain', 'active_for_signing'], 'lm_key_ref_active_idx');
        });

        Schema::create('legacy_migration_protected_record_envelopes', function (Blueprint $table) {
            $table->id();
            $table->string('record_type', 160);
            $table->unsignedBigInteger('record_id');
            $table->unsignedInteger('generation')->default(1);
            $table->unsignedBigInteger('supersedes_envelope_id')->nullable();
            $table->foreignId('run_id')->nullable()->constrained('legacy_migration_runs', 'id', 'lm_envelope_run_fk')->restrictOnDelete();
            $table->foreignId('source_snapshot_id')->nullable()->constrained('legacy_migration_snapshots', 'id', 'lm_envelope_source_snapshot_fk')->restrictOnDelete();
            $table->foreignId('target_snapshot_id')->nullable()->constrained('legacy_migration_snapshots', 'id', 'lm_envelope_target_snapshot_fk')->restrictOnDelete();
            $table->string('domain', 100);
            $table->char('protected_token', 64);
            $table->string('token_environment', 64);
            $table->string('hmac_key_id', 64);
            $table->string('hmac_key_version', 100);
            $table->string('canonicalization_version', 100);
            $table->char('record_integrity_reference', 64);
            $table->text('encrypted_token_envelope');
            $table->text('encrypted_token_set');
            $table->text('encrypted_integrity_seal');
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->string('state', 40)->default('active');
            $table->timestamp('sealed_at');
            $table->timestamps();

            $table->unique(['record_type', 'record_id', 'generation'], 'lm_envelope_record_generation_uq');
            $table->unique(['domain', 'protected_token', 'record_type', 'record_id', 'generation'], 'lm_envelope_token_record_uq');
            $table->index(['run_id', 'source_snapshot_id', 'target_snapshot_id'], 'lm_envelope_coordinate_idx');
            $table->index('supersedes_envelope_id', 'lm_envelope_supersedes_idx');
        });

        Schema::create('legacy_migration_protected_token_rotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('old_envelope_id')->constrained('legacy_migration_protected_record_envelopes', 'id', 'lm_rotation_old_envelope_fk')->restrictOnDelete();
            $table->foreignId('new_envelope_id')->constrained('legacy_migration_protected_record_envelopes', 'id', 'lm_rotation_new_envelope_fk')->restrictOnDelete();
            $table->foreignId('run_id')->nullable()->constrained('legacy_migration_runs', 'id', 'lm_rotation_run_fk')->restrictOnDelete();
            $table->foreignId('source_snapshot_id')->nullable()->constrained('legacy_migration_snapshots', 'id', 'lm_rotation_source_snapshot_fk')->restrictOnDelete();
            $table->foreignId('target_snapshot_id')->nullable()->constrained('legacy_migration_snapshots', 'id', 'lm_rotation_target_snapshot_fk')->restrictOnDelete();
            $table->string('old_hmac_key_id', 64);
            $table->string('old_hmac_key_version', 100);
            $table->string('new_hmac_key_id', 64);
            $table->string('new_hmac_key_version', 100);
            $table->string('rotation_reason', 160);
            $table->string('rotation_authority_reference', 160);
            $table->boolean('old_token_verified');
            $table->boolean('new_token_verified');
            $table->string('state', 40);
            $table->char('integrity_checksum', 64);
            $table->timestamp('rotated_at');
            $table->timestamps();

            $table->unique(['old_envelope_id', 'new_envelope_id'], 'lm_rotation_old_new_uq');
        });

        Schema::create('legacy_migration_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_reference', 160);
            $table->string('policy_version', 100);
            $table->string('access_classification', 100);
            $table->string('retention_classification', 100);
            $table->unsignedInteger('minimum_retention_days');
            $table->boolean('legal_hold')->default(false);
            $table->boolean('operational_hold')->default(false);
            $table->timestamp('review_at');
            $table->boolean('purge_enabled')->default(false);
            $table->string('purge_authority_reference', 160)->nullable();
            $table->string('owner_approval_reference', 160)->nullable();
            $table->boolean('retain_tombstone')->default(true);
            $table->boolean('active')->default(false);
            $table->char('integrity_checksum', 64);
            $table->timestamps();

            $table->unique(['policy_reference', 'policy_version'], 'lm_retention_policy_ref_version_uq');
            $table->index(['access_classification', 'retention_classification', 'active'], 'lm_retention_class_active_idx');
        });

        Schema::create('legacy_migration_protected_purge_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('envelope_id')->constrained('legacy_migration_protected_record_envelopes', 'id', 'lm_purge_envelope_fk')->restrictOnDelete();
            $table->foreignId('retention_policy_id')->constrained('legacy_migration_retention_policies', 'id', 'lm_purge_policy_fk')->restrictOnDelete();
            $table->string('request_reference', 160)->unique('lm_purge_request_ref_uq');
            $table->string('requested_by_authority_reference', 160);
            $table->string('authorized_by_authority_reference', 160)->nullable();
            $table->string('state', 60)->default('blocked_pending_owner_policy');
            $table->boolean('integrity_verified')->default(false);
            $table->boolean('lineage_preservation_verified')->default(false);
            $table->boolean('tombstone_required')->default(true);
            $table->char('aggregate_tombstone_hash', 64)->nullable();
            $table->char('integrity_checksum', 64);
            $table->timestamp('requested_at');
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_migration_protected_access_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->nullable()->constrained('legacy_migration_runs', 'id', 'lm_access_audit_run_fk')->restrictOnDelete();
            $table->foreignId('envelope_id')->nullable()->constrained('legacy_migration_protected_record_envelopes', 'id', 'lm_access_audit_envelope_fk')->restrictOnDelete();
            $table->string('purpose', 100);
            $table->string('operation', 60);
            $table->string('record_type', 160);
            $table->string('domain', 100);
            $table->string('authority_reference', 160);
            $table->string('result_code', 100);
            $table->char('event_checksum', 64);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['run_id', 'purpose', 'operation'], 'lm_access_audit_run_purpose_op_idx');
        });

        foreach ([
            'legacy_migration_protected_key_references',
            'legacy_migration_protected_record_envelopes',
            'legacy_migration_protected_token_rotations',
            'legacy_migration_retention_policies',
            'legacy_migration_protected_purge_requests',
            'legacy_migration_protected_access_audits',
        ] as $table) {
            $this->protectAppendOnly($table);
        }
    }

    public function down(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaRemovalAllowed(DB::connection());
        // Destructive rollback is intentionally unavailable outside isolated tests.
        Schema::dropIfExists('legacy_migration_protected_access_audits');
        Schema::dropIfExists('legacy_migration_protected_purge_requests');
        Schema::dropIfExists('legacy_migration_retention_policies');
        Schema::dropIfExists('legacy_migration_protected_token_rotations');
        Schema::dropIfExists('legacy_migration_protected_record_envelopes');
        Schema::dropIfExists('legacy_migration_protected_key_references');
    }

    private function protectAppendOnly(string $table): void
    {
        $driver = DB::connection()->getDriverName();
        $stem = substr(str_replace('legacy_migration_', 'lm_', $table), 0, 50);
        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER {$stem}_no_update BEFORE UPDATE ON {$table} BEGIN SELECT RAISE(ABORT, 'append-only protected lifecycle'); END");
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} BEGIN SELECT RAISE(ABORT, 'append-only protected lifecycle'); END");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER {$stem}_no_update BEFORE UPDATE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only protected lifecycle'");
            DB::unprepared("CREATE TRIGGER {$stem}_no_delete BEFORE DELETE ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only protected lifecycle'");
        }
    }
};
