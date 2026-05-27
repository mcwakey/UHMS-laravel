<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes dispensing_records.drug_stock_id nullable so that the pharmacy
     * dispense flow can write records against the product stock_balances system
     * (which holds real stock) instead of requiring a drug_stock FK row.
     *
     * Uses raw ALTER TABLE statements because MariaDB 10.1.32 cannot use
     * Laravel Blueprint on existing FK columns (Schema::getColumns() probe fails).
     */
    public function up(): void
    {
        // Drop the existing NOT-NULL FK constraint first
        DB::statement('ALTER TABLE dispensing_records DROP FOREIGN KEY dispensing_records_drug_stock_id_foreign');

        // Make the column nullable
        DB::statement('ALTER TABLE dispensing_records MODIFY COLUMN drug_stock_id BIGINT UNSIGNED NULL');

        // Re-add the FK so referential integrity is preserved for rows that do have a drug_stock_id
        DB::statement('ALTER TABLE dispensing_records ADD CONSTRAINT dispensing_records_drug_stock_id_foreign FOREIGN KEY (drug_stock_id) REFERENCES drug_stock (id) ON DELETE CASCADE');
    }

    public function down(): void
    {
        // Drop the nullable FK
        DB::statement('ALTER TABLE dispensing_records DROP FOREIGN KEY dispensing_records_drug_stock_id_foreign');

        // Restore NOT NULL (any NULL rows must be cleaned first; here we default to 0 which is safe for rollback)
        DB::statement('UPDATE dispensing_records SET drug_stock_id = 0 WHERE drug_stock_id IS NULL');
        DB::statement('ALTER TABLE dispensing_records MODIFY COLUMN drug_stock_id BIGINT UNSIGNED NOT NULL');

        // Re-add FK
        DB::statement('ALTER TABLE dispensing_records ADD CONSTRAINT dispensing_records_drug_stock_id_foreign FOREIGN KEY (drug_stock_id) REFERENCES drug_stock (id) ON DELETE CASCADE');
    }
};
