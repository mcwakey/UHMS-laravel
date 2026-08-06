<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 14R.8 — additive lineage link from a snapshot to its completion
 * occurrence.
 *
 * Deliberately NULLABLE and deliberately NOT backfilled. Existing snapshots
 * were captured before occurrences existed; inventing an occurrence id for them
 * would fabricate a historical fact. They keep their legacy
 * `route:{id}@{completed_at}` reference and remain fully readable.
 *
 * The column is envelope metadata only. `payload_hash` is computed over the
 * clinical projection payload alone, so adding this changes no existing hash
 * and no existing payload.
 *
 * The existing `(consultation_route_id, completion_reference)` unique index is
 * retained unchanged and continues to be the idempotency guard — only the
 * FORMAT of the reference changes for new snapshots, from a timestamp to
 * `occ:{ulid}`. That is a versioned idempotency reference, not a schema break.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_maternity_snapshots', function (Blueprint $table) {
            $table->foreignId('completion_occurrence_id')->nullable()->after('previous_snapshot_id')
                ->constrained('consultation_completion_occurrences', indexName: 'cms_occurrence_fk')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite cannot reverse this one. It refuses to drop a column that
        // still appears in a foreign-key definition, it cannot drop a
        // constraint by name, and no PRAGMA changes either fact (verified on
        // 3.49). The only route is rewriting `sqlite_master` or rebuilding the
        // table by hand — neither belongs in a migration that touches an
        // append-only clinical table, where a partially-applied rebuild would
        // be far worse than an unreversed column.
        //
        // Tests never take this path: RefreshDatabase migrates from scratch.
        // Production is MySQL/MariaDB, which reverses cleanly below.
        if ($driver === 'sqlite') {
            throw new RuntimeException(
                'This migration cannot be rolled back on SQLite: SQLite cannot drop a '
                .'foreign-key-referenced column. Use `php artisan migrate:fresh` on SQLite, '
                .'or roll back on MySQL/MariaDB where the reversal is supported.'
            );
        }

        Schema::table('consultation_maternity_snapshots', function (Blueprint $table) {
            // Drop the constraint by the EXPLICIT short name used in up().
            // `dropConstrainedForeignId()` would emit Laravel's conventional
            // name
            // (`consultation_maternity_snapshots_completion_occurrence_id_foreign`
            // — 65 chars), which was never created and exceeds MariaDB's
            // 64-char identifier limit. That limit is precisely why the short
            // name exists, so the drop has to match it.
            $table->dropForeign('cms_occurrence_fk');
            $table->dropColumn('completion_occurrence_id');
        });
    }
};
