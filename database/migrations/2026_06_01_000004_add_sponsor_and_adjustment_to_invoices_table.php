<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links invoices to a corporate sponsor and records the non-cash adjustment
 * total (credit notes + write-offs) so the header can show a true balance.
 */
return new class extends Migration
{
    private array $columns = [
        'sponsor_id'        => 'BIGINT UNSIGNED NULL DEFAULT NULL',
        'adjustment_amount' => 'DECIMAL(12,2) NOT NULL DEFAULT 0',
    ];

    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('invoices', function (Blueprint $table) {
                if (! Schema::hasColumn('invoices', 'sponsor_id')) {
                    $table->unsignedBigInteger('sponsor_id')->nullable()->after('patient_id');
                }
                if (! Schema::hasColumn('invoices', 'adjustment_amount')) {
                    $table->decimal('adjustment_amount', 12, 2)->default(0)->after('discount_amount');
                }
            });

            return;
        }

        foreach ($this->columns as $name => $definition) {
            if (! $this->mysqlColumnExists('invoices', $name)) {
                DB::statement("ALTER TABLE `invoices` ADD COLUMN `{$name}` {$definition}");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('invoices', function (Blueprint $table) {
                foreach (array_keys($this->columns) as $name) {
                    if (Schema::hasColumn('invoices', $name)) {
                        $table->dropColumn($name);
                    }
                }
            });

            return;
        }

        foreach (array_keys($this->columns) as $name) {
            if ($this->mysqlColumnExists('invoices', $name)) {
                DB::statement("ALTER TABLE `invoices` DROP COLUMN `{$name}`");
            }
        }
    }

    private function mysqlColumnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [$table, $column]
        );

        return $row && (int) $row->cnt > 0;
    }
};
