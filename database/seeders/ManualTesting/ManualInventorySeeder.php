<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualInventorySeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('stock_locations')) {
            return;
        }

        $departmentId = DB::table('departments')->where('name', 'Main Pharmacy')->value('id');
        $this->updateOrInsert('stock_locations', ['name' => 'MT Manual Pharmacy Store'], [
            'name' => 'Manual Pharmacy Store',
            'name' => 'MT Manual Pharmacy Store',
            'department_id' => $departmentId,
            'type' => 'pharmacy',
            'is_main' => true,
            'is_active' => true,
            'notes' => 'MT-MANUAL stock location seed',
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }
}
