<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualRadiologySeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('service_catalog')) {
            return;
        }

        $departmentId = DB::table('departments')->where('name', 'X-Ray Unit')->value('id');
        foreach (['X-Ray Chest', 'X-Ray Limb', 'Obstetric Ultrasound', 'Abdominal Ultrasound', 'CT Head', 'CT Abdomen'] as $index => $name) {
            $this->updateOrInsert('service_catalog', ['code' => $this->ref('RADSVC', $index + 1, 3)], [
                'name' => $name.' Manual',
                'code' => $this->ref('RADSVC', $index + 1, 3),
                'description' => 'Manual radiology service for imaging dashboard testing.',
                'category' => 'radiology',
                'price' => 80 + ($index * 60),
                'department_id' => $departmentId,
                'department_type' => 'radiology',
                'is_active' => true,
                'is_billable' => true,
                'overall_result_type' => 'free_text',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }
}
