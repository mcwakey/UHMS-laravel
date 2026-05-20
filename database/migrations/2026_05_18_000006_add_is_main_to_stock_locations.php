<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_locations', function (Blueprint $table) {
            if (! $this->columnExists('stock_locations', 'is_main')) {
                $table->boolean('is_main')->default(false)->after('is_active');
            }
        });

        // Mark one Main Store row.
        $existing = DB::table('stock_locations')
            ->where('name', 'Main Store')
            ->orWhere('type', 'store')
            ->orderBy('id')
            ->first();

        if ($existing) {
            DB::table('stock_locations')->where('id', $existing->id)->update(['is_main' => 1]);
        } else {
            DB::table('stock_locations')->insert([
                'name'       => 'Main Store',
                'type'       => 'store',
                'is_active'  => 1,
                'is_main'    => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Ensure only one is_main row exists.
        $mainIds = DB::table('stock_locations')
            ->where('is_main', 1)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        if (count($mainIds) > 1) {
            $keep = array_shift($mainIds);
            DB::table('stock_locations')->whereIn('id', $mainIds)->update(['is_main' => 0]);
        }
    }

    public function down(): void
    {
        Schema::table('stock_locations', function (Blueprint $table) {
            if ($this->columnExists('stock_locations', 'is_main')) {
                $table->dropColumn('is_main');
            }
        });
    }

    private function columnExists(string $table, string $column): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return Schema::hasColumn($table, $column);
        }

        $db = DB::connection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$db, $table, $column]
        );

        return (int) ($row->c ?? 0) > 0;
    }
};
