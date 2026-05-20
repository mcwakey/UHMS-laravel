<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->unsignedBigInteger('drug_id')->nullable()->change();
                $table->index(['product_id', 'stock_location_id'], 'stock_movements_product_location_index');
            });

            Schema::table('stock_balances', function (Blueprint $table) {
                $table->unsignedBigInteger('drug_id')->nullable()->change();
                $table->index(['product_id', 'stock_location_id'], 'stock_balances_product_location_index');
            });

            return;
        }

        foreach (['stock_movements', 'stock_balances'] as $table) {
            $drugFk = $table . '_drug_id_foreign';

            if ($this->foreignKeyExists($table, $drugFk)) {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$drugFk}");
            }

            if (! $this->columnIsNullable($table, 'drug_id')) {
                DB::statement("ALTER TABLE {$table} MODIFY drug_id BIGINT UNSIGNED NULL");
            }

            $this->ensureForeignKey(
                $table,
                $drugFk,
                "ALTER TABLE {$table} ADD CONSTRAINT {$drugFk} FOREIGN KEY (drug_id) REFERENCES drugs(id) ON DELETE SET NULL"
            );
        }

        $this->ensureIndex(
            'stock_balances',
            'stock_balances_product_location_index',
            'CREATE INDEX stock_balances_product_location_index ON stock_balances (product_id, stock_location_id)'
        );

        $this->ensureIndex(
            'stock_movements',
            'stock_movements_product_location_index',
            'CREATE INDEX stock_movements_product_location_index ON stock_movements (product_id, stock_location_id)'
        );
    }

    public function down(): void
    {
        foreach (['stock_balances', 'stock_movements'] as $table) {
            $index = $table . '_product_location_index';
            if ($this->indexExists($table, $index)) {
                DB::statement("DROP INDEX {$index} ON {$table}");
            }
        }
    }

    private function columnIsNullable(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT IS_NULLABLE AS nullable FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );

        return strtoupper((string) ($row->nullable ?? 'NO')) === 'YES';
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = "FOREIGN KEY"',
            [$table, $constraint]
        );

        return (int) ($row->c ?? 0) > 0;
    }

    private function ensureIndex(string $table, string $index, string $sql): void
    {
        if (! $this->indexExists($table, $index)) {
            DB::statement($sql);
        }
    }

    private function ensureForeignKey(string $table, string $constraint, string $sql): void
    {
        if (! $this->foreignKeyExists($table, $constraint)) {
            DB::statement($sql);
        }
    }
};
