<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MariaDB compatibility: avoid Schema::hasColumn (uses generation_expression
        // which is not available on MariaDB). Use raw SHOW COLUMNS instead.
        if (! $this->columnExists('drugs', 'opening_stock')) {
            Schema::table('drugs', function (Blueprint $table) {
                $table->decimal('opening_stock', 14, 4)->default(0)->after('price');
            });
        }

        if (! $this->columnExists('drugs', 'reorder_level')) {
            Schema::table('drugs', function (Blueprint $table) {
                $table->decimal('reorder_level', 14, 4)->default(0)->after('opening_stock');
            });
        }
    }

    public function down(): void
    {
        if ($this->columnExists('drugs', 'opening_stock')) {
            Schema::table('drugs', function (Blueprint $table) {
                $table->dropColumn('opening_stock');
            });
        }
        if ($this->columnExists('drugs', 'reorder_level')) {
            Schema::table('drugs', function (Blueprint $table) {
                $table->dropColumn('reorder_level');
            });
        }
    }

    protected function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $rows = DB::select("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
        return ! empty($rows);
    }
};
