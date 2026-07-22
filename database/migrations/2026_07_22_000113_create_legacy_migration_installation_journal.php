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
            'legacy_migration_crosswalks',
            'legacy_migration_idempotency_records',
        ]);

        Schema::create('legacy_migration_installation_journal', function (Blueprint $table) {
            $table->id();
            $table->string('installation_version', 100);
            $table->char('target_identity_reference', 64);
            $table->string('object_coordinate', 191);
            $table->char('expected_definition_hash', 64);
            $table->char('observed_definition_hash', 64)->nullable();
            $table->string('state', 60);
            $table->unsignedInteger('attempt');
            $table->char('audit_reference', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['installation_version', 'target_identity_reference', 'object_coordinate', 'attempt', 'state'],
                'lm_install_journal_coordinate_state_uq'
            );
            $table->index(['target_identity_reference', 'state'], 'lm_install_journal_target_state_idx');
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::unprepared("CREATE TRIGGER lm_install_journal_no_update BEFORE UPDATE ON legacy_migration_installation_journal BEGIN SELECT RAISE(ABORT, 'append-only migration installation journal'); END");
            DB::unprepared("CREATE TRIGGER lm_install_journal_no_delete BEFORE DELETE ON legacy_migration_installation_journal BEGIN SELECT RAISE(ABORT, 'append-only migration installation journal'); END");
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared("CREATE TRIGGER lm_install_journal_no_update BEFORE UPDATE ON legacy_migration_installation_journal FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only migration installation journal'");
            DB::unprepared("CREATE TRIGGER lm_install_journal_no_delete BEFORE DELETE ON legacy_migration_installation_journal FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'append-only migration installation journal'");
        }
    }

    public function down(): void
    {
        FoundationDatabaseWriteBoundary::assertSchemaRemovalAllowed(DB::connection());
        Schema::dropIfExists('legacy_migration_installation_journal');
    }
};
