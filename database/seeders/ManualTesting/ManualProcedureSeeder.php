<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualProcedureSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('service_catalog')) {
            return;
        }

        $departmentId = DB::table('departments')->where('name', 'Main Theatre')->value('id');
        foreach (['Minor Dressing', 'Incision and Drainage', 'Suturing', 'Caesarean Section', 'Appendectomy', 'Hernia Repair', 'Endoscopy', 'Wound Debridement'] as $index => $name) {
            $this->updateOrInsert('service_catalog', ['code' => $this->ref('PROCSVC', $index + 1, 3)], [
                'name' => $name.' Manual',
                'code' => $this->ref('PROCSVC', $index + 1, 3),
                'description' => 'Manual procedure service for theatre workflow testing.',
                'category' => 'procedure',
                'price' => 120 + ($index * 90),
                'department_id' => $departmentId,
                'department_type' => 'theatre',
                'is_active' => true,
                'is_billable' => true,
                'requires_rendering_tracking' => true,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        }
    }
}
