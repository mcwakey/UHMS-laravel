<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\DepartmentType;
use App\Enums\ResultType;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $hasResultType = $this->hasColumn('departments', 'result_type');
        $hasStockManaged = $this->hasColumn('departments', 'is_stock_managed');
        $hasType = $this->hasColumn('departments', 'type');

        if ($hasResultType) {
            DB::table('departments')
                ->where('result_type', 'rich_text')
                ->update(['result_type' => ResultType::RICHTEXT->value]);
        }

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
            ['name' => 'Laboratory',              'code' => 'LAB', 'type' => DepartmentType::INVESTIGATION->value, 'result_type' => ResultType::PARAMETERS->value],
            ['name' => 'Radiology / X-Ray',       'code' => 'RAD', 'type' => DepartmentType::INVESTIGATION->value, 'result_type' => ResultType::RICHTEXT->value],
            ['name' => 'Ultrasound',              'code' => 'USG', 'type' => DepartmentType::INVESTIGATION->value, 'result_type' => ResultType::RICHTEXT->value],

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
            if (! $hasResultType) {
                unset($defaults['result_type']);
            }
            if (! $hasStockManaged) {
                unset($defaults['is_stock_managed']);
            }
            if (! $hasType) {
                unset($defaults['type']);
            }
            Department::firstOrCreate(
                ['code' => $dept['code']],
                $defaults
            );
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return (bool) DB::selectOne(
            'select 1 from information_schema.columns where table_schema = database() and table_name = ? and column_name = ? limit 1',
            [$table, $column]
        );
    }
}
