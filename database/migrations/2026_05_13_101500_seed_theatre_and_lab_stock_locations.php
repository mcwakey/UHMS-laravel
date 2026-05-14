<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Theatre Store: needed for ConsumableUsageService when recording usage
        // for procedure_request sources.
        $existing = DB::table('stock_locations')->where('name', 'Theatre Store')->exists();
        if (! $existing) {
            $deptId = DB::table('departments')->where('name', 'like', 'Theatre%')->value('id');
            DB::table('stock_locations')->insert([
                'name'          => 'Theatre Store',
                'type'          => 'theatre',
                'department_id' => $deptId,
                'is_active'     => 1,
                'notes'         => 'Default store for theatre/procedure consumables.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // Lab Store: ensure a lab location exists for investigation consumables.
        $existingLab = DB::table('stock_locations')->where('type', 'lab')->exists();
        if (! $existingLab) {
            $labDeptId = DB::table('departments')->where('name', 'like', 'Laboratory%')->value('id');
            DB::table('stock_locations')->insert([
                'name'          => 'Lab Store',
                'type'          => 'lab',
                'department_id' => $labDeptId,
                'is_active'     => 1,
                'notes'         => 'Default store for investigation consumables.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('stock_locations')
            ->whereIn('name', ['Theatre Store', 'Lab Store'])
            ->delete();
    }
};
