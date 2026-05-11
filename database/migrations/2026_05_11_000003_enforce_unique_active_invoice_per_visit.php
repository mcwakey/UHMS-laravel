<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enforces "one active invoice per visit" via a generated column that is unique
 * for non-cancelled invoices. MariaDB does NOT support partial unique indexes
 * directly, but it supports unique on generated columns.
 *
 * Strategy: add a virtual column `active_visit_id` that equals visit_id when
 * status NOT IN ('cancelled', 'refunded'), else NULL — then make it unique.
 * Multiple NULLs are allowed in a UNIQUE index, so cancelled invoices don't block.
 *
 * Safe fallback: if the generated-column approach fails (older MariaDB), we just
 * create a regular index. The application-level guard in InvoiceService prevents
 * duplicates either way.
 */
return new class extends Migration {
    public function up(): void
    {
        // First, deduplicate: keep the newest active invoice per visit, cancel others.
        DB::statement("
            UPDATE invoices i
            JOIN (
                SELECT visit_id, MAX(id) AS keep_id
                FROM invoices
                WHERE status NOT IN ('cancelled', 'refunded')
                GROUP BY visit_id
                HAVING COUNT(*) > 1
            ) d ON d.visit_id = i.visit_id
            SET i.status = 'cancelled', i.notes = CONCAT(COALESCE(i.notes,''), ' [auto-deduped 2026_05_11]')
            WHERE i.id <> d.keep_id
              AND i.status NOT IN ('cancelled', 'refunded')
        ");

        // Add the virtual generated column + unique index.
        try {
            DB::statement("
                ALTER TABLE invoices
                ADD COLUMN active_visit_id BIGINT
                GENERATED ALWAYS AS (
                    CASE WHEN status IN ('cancelled','refunded') THEN NULL ELSE visit_id END
                ) VIRTUAL
            ");
            DB::statement("CREATE UNIQUE INDEX uq_invoices_active_visit ON invoices(active_visit_id)");
        } catch (\Throwable $e) {
            // Fallback: regular non-unique index (app-level guard remains authoritative).
            try {
                DB::statement("CREATE INDEX idx_invoices_visit_id ON invoices(visit_id)");
            } catch (\Throwable $e2) {
                // Already indexed — ignore.
            }
        }
    }

    public function down(): void
    {
        try { DB::statement("DROP INDEX uq_invoices_active_visit ON invoices"); } catch (\Throwable $e) {}
        try { DB::statement("ALTER TABLE invoices DROP COLUMN active_visit_id"); } catch (\Throwable $e) {}
    }
};
