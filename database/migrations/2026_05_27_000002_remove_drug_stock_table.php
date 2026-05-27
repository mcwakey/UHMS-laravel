<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the legacy drug_stock table and its FK column on dispensing_records.
     * Stock on-hand is now tracked exclusively via stock_balances (driven by
     * stock_movements / ProductStockMovementService). The dispensing_records
     * table only needs prescription/patient/drug references.
     *
     * MariaDB 10.1 note: Blueprint cannot introspect existing tables, so all
     * schema changes use raw DB::statement() calls.
     */
    public function up(): void
    {
        // ── 1. Remove drug_stock_id from dispensing_records ──────────────
        // Check whether the column still exists (idempotent-safe).
        $colExists = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME  = 'dispensing_records'
               AND COLUMN_NAME = 'drug_stock_id'"
        )->cnt ?? 0;

        if ($colExists) {
            // Drop the FK constraint first (ignore error if already gone).
            try {
                DB::statement('ALTER TABLE dispensing_records DROP FOREIGN KEY dispensing_records_drug_stock_id_foreign');
            } catch (\Throwable) {
                // FK already absent — safe to continue.
            }

            // Drop the column.
            DB::statement('ALTER TABLE dispensing_records DROP COLUMN drug_stock_id');
        }

        // ── 2. Drop the drug_stock table ─────────────────────────────────
        Schema::dropIfExists('drug_stock');
    }

    public function down(): void
    {
        // Restore drug_stock (empty skeleton — data is gone, this is schema-only).
        Schema::create('drug_stock', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('drug_id')->constrained('drugs')->cascadeOnDelete();
            $table->string('location', 80)->default('pharmacy');
            $table->string('batch_number', 80)->nullable();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('unit_cost', 14, 4)->default(0);
            $table->decimal('selling_price', 14, 4)->default(0);
            $table->date('expiry_date')->nullable();
            $table->string('supplier', 191)->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->date('received_date')->nullable();
            $table->unsignedBigInteger('received_by')->nullable();
            $table->decimal('reorder_level', 14, 4)->default(10);
            $table->timestamps();
        });

        // Restore nullable column + FK on dispensing_records.
        DB::statement('ALTER TABLE dispensing_records ADD COLUMN drug_stock_id BIGINT UNSIGNED NULL');
        DB::statement(
            'ALTER TABLE dispensing_records ADD CONSTRAINT dispensing_records_drug_stock_id_foreign '
            . 'FOREIGN KEY (drug_stock_id) REFERENCES drug_stock (id) ON DELETE CASCADE'
        );
    }
};
