<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Emergency Store: separate stock location for the Emergency Unit
        // so that consumables consumed in ER are deducted from ER stock,
        // not from the Main Store / Ward / Theatre stores.
        $existing = DB::table('stock_locations')->where('type', 'emergency')->exists();
        if ($existing) {
            return;
        }

        $deptId = DB::table('departments')
            ->where('code', 'EMR')
            ->orWhere('name', 'like', 'Emergency%')
            ->value('id');

        DB::table('stock_locations')->insert([
            'name'          => 'Emergency Store',
            'type'          => 'emergency',
            'department_id' => $deptId,
            'is_active'     => 1,
            'notes'         => 'Default store for Emergency Unit consumables.',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('stock_locations')
            ->where('type', 'emergency')
            ->where('name', 'Emergency Store')
            ->delete();
    }
};
