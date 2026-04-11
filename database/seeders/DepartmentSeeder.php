<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'General Medicine / OPD', 'code' => 'OPD'],
            ['name' => 'Pediatrics', 'code' => 'PED'],
            ['name' => 'Obstetrics & Gynecology', 'code' => 'OBG'],
            ['name' => 'Surgery', 'code' => 'SUR'],
            ['name' => 'Orthopedics', 'code' => 'ORT'],
            ['name' => 'Eye Clinic', 'code' => 'EYE'],
            ['name' => 'ENT', 'code' => 'ENT'],
            ['name' => 'Dental', 'code' => 'DEN'],
            ['name' => 'Psychiatry', 'code' => 'PSY'],
            ['name' => 'Emergency / Casualty', 'code' => 'EMR'],
            ['name' => 'Laboratory', 'code' => 'LAB'],
            ['name' => 'Pharmacy', 'code' => 'PHR'],
            ['name' => 'Radiology / X-Ray', 'code' => 'RAD'],
            ['name' => 'Physiotherapy', 'code' => 'PHY'],
            ['name' => 'Antenatal / Postnatal', 'code' => 'ANC'],
            ['name' => 'Family Planning', 'code' => 'FPL'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['code' => $dept['code']],
                array_merge($dept, ['status' => 'active'])
            );
        }
    }
}
