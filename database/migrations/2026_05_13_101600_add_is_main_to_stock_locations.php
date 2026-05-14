<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MariaDB lacks `generation_expression` in information_schema; avoid Schema::hasColumn (it queries that).
        $hasIsMain = collect(DB::select("SHOW COLUMNS FROM stock_locations LIKE 'is_main'"))->isNotEmpty();
        if (! $hasIsMain) {
            Schema::table('stock_locations', function (Blueprint $table) {
                $table->boolean('is_main')->default(false)->after('is_active');
            });
        }

        // Mark Main Store as main; link it to the Store department if one exists.
        $storeDeptId = DB::table('departments')
            ->where('type', 'store')
            ->orWhere(function ($q) {
                $q->where('name', 'like', '%Store%')->orWhere('name', 'like', '%Procurement%');
            })
            ->value('id');

        DB::table('stock_locations')
            ->where('name', 'Main Store')
            ->update([
                'is_main'       => 1,
                'department_id' => $storeDeptId,
                'updated_at'    => now(),
            ]);

        // Ensure pharmacy default location is linked to the Pharmacy department.
        $pharmDeptId = DB::table('departments')->where('type', 'pharmacy')->value('id');
        if ($pharmDeptId) {
            DB::table('stock_locations')
                ->where('name', 'Pharmacy')
                ->whereNull('department_id')
                ->update(['department_id' => $pharmDeptId, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $hasIsMain = collect(DB::select("SHOW COLUMNS FROM stock_locations LIKE 'is_main'"))->isNotEmpty();
        if ($hasIsMain) {
            Schema::table('stock_locations', function (Blueprint $table) {
                $table->dropColumn('is_main');
            });
        }
    }
};
