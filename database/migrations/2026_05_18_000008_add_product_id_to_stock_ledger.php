<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Phase 2 — unify drug ledger with product ledger by adding a product_id column
 * to stock_movements and stock_balances. Backfills values via drugs.product_id.
 *
 * drug_id is kept (will be made nullable in a follow-up phase) so legacy reports
 * keep working until pharmacy reads have switched to the product ledger.
 *
 * Uses raw DDL because Laravel 12's Blueprint introspection breaks on the older
 * MariaDB used in this XAMPP install ("Unknown column 'generation_expression'").
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureColumn('stock_movements', 'product_id',
            'ALTER TABLE stock_movements ADD COLUMN product_id BIGINT UNSIGNED NULL AFTER drug_id'
        );
        $this->ensureIndex('stock_movements', 'stock_movements_product_id_index',
            'CREATE INDEX stock_movements_product_id_index ON stock_movements (product_id)'
        );
        $this->ensureForeignKey('stock_movements', 'stock_movements_product_id_foreign',
            'ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_product_id_foreign '
            . 'FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL'
        );

        $this->ensureColumn('stock_balances', 'product_id',
            'ALTER TABLE stock_balances ADD COLUMN product_id BIGINT UNSIGNED NULL AFTER drug_id'
        );
        $this->ensureIndex('stock_balances', 'stock_balances_product_id_index',
            'CREATE INDEX stock_balances_product_id_index ON stock_balances (product_id)'
        );
        $this->ensureForeignKey('stock_balances', 'stock_balances_product_id_foreign',
            'ALTER TABLE stock_balances ADD CONSTRAINT stock_balances_product_id_foreign '
            . 'FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL'
        );

        // Backfill product_id from drugs.product_id wherever the link is already set.
        if ($this->columnExists('drugs', 'product_id')) {
            DB::statement(
                'UPDATE stock_movements sm '
                . 'INNER JOIN drugs d ON d.id = sm.drug_id '
                . 'SET sm.product_id = d.product_id '
                . 'WHERE sm.product_id IS NULL AND d.product_id IS NOT NULL'
            );

            DB::statement(
                'UPDATE stock_balances sb '
                . 'INNER JOIN drugs d ON d.id = sb.drug_id '
                . 'SET sb.product_id = d.product_id '
                . 'WHERE sb.product_id IS NULL AND d.product_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        foreach (['stock_movements', 'stock_balances'] as $table) {
            $fk = $table . '_product_id_foreign';
            $idx = $table . '_product_id_index';
            if ($this->foreignKeyExists($table, $fk)) {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$fk}");
            }
            if ($this->indexExists($table, $idx)) {
                DB::statement("DROP INDEX {$idx} ON {$table}");
            }
            if ($this->columnExists($table, 'product_id')) {
                DB::statement("ALTER TABLE {$table} DROP COLUMN product_id");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.columns '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );
        return (int) ($row->c ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );
        return (int) ($row->c ?? 0) > 0;
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.table_constraints '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? '
            . 'AND constraint_type = "FOREIGN KEY"',
            [$table, $constraint]
        );
        return (int) ($row->c ?? 0) > 0;
    }

    private function ensureColumn(string $table, string $column, string $sql): void
    {
        if (! $this->columnExists($table, $column)) {
            DB::statement($sql);
        }
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
