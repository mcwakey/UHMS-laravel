<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Enums\DepartmentType;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // type drives clinical routing (consultation / investigation / pharmacy / etc.).
        // result_type only applies to investigation departments and drives whether the
        // catalog of tests uses parameters (criteria) or rich-text templates.
        $departments = [
            // ── Consultation / clinical departments ──
            ['name' => 'General Medicine / OPD',  'code' => 'OPD', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Pediatrics',              'code' => 'PED', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Obstetrics & Gynecology', 'code' => 'OBG', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Surgery',                 'code' => 'SUR', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Orthopedics',             'code' => 'ORT', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Eye Clinic',              'code' => 'EYE', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'ENT',                     'code' => 'ENT', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Dental',                  'code' => 'DEN', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Psychiatry',              'code' => 'PSY', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Emergency / Casualty',    'code' => 'EMR', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Antenatal / Postnatal',   'code' => 'ANC', 'type' => DepartmentType::CONSULTATION->value],
            ['name' => 'Family Planning',         'code' => 'FPL', 'type' => DepartmentType::CONSULTATION->value],

            // ── Investigation departments ──
            ['name' => 'Laboratory',              'code' => 'LAB', 'type' => DepartmentType::INVESTIGATION->value, 'result_type' => 'parameters'],
            ['name' => 'Radiology / X-Ray',       'code' => 'RAD', 'type' => DepartmentType::INVESTIGATION->value, 'result_type' => 'rich_text'],
            ['name' => 'Ultrasound',              'code' => 'USG', 'type' => DepartmentType::INVESTIGATION->value, 'result_type' => 'rich_text'],

            // ── Treatment / procedure ──
            ['name' => 'Physiotherapy',           'code' => 'PHY', 'type' => DepartmentType::TREATMENT->value],
            ['name' => 'Theatre / Procedures',    'code' => 'THT', 'type' => DepartmentType::PROCEDURE->value],

            // ── Pharmacy ──
            ['name' => 'Pharmacy',                'code' => 'PHR', 'type' => DepartmentType::PHARMACY->value, 'is_stock_managed' => true],

            // ── Support / admin ──
            ['name' => 'Records',                 'code' => 'REC', 'type' => DepartmentType::ADMINISTRATIVE->value],
            ['name' => 'Billing',                 'code' => 'BIL', 'type' => DepartmentType::ADMINISTRATIVE->value],
        ];

        foreach ($departments as $dept) {
            $defaults = array_merge($dept, ['status' => 'active']);
            // result_type column may not exist in some test setups
            if (! Schema::hasColumn('departments', 'result_type')) {
                unset($defaults['result_type']);
            }
            if (! Schema::hasColumn('departments', 'is_stock_managed')) {
                unset($defaults['is_stock_managed']);
            }
            if (! Schema::hasColumn('departments', 'type')) {
                unset($defaults['type']);
            }
            Department::firstOrCreate(
                ['code' => $dept['code']],
                $defaults
            );
        }
    }
}
