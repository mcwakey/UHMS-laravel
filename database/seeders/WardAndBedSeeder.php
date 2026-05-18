<?php

namespace Database\Seeders;

use App\Models\Bed;
use App\Models\Department;
use App\Models\Ward;
use Illuminate\Database\Seeder;

class WardAndBedSeeder extends Seeder
{
    public function run(): void
    {
        $wards = [
            ['name' => 'Male Medical Ward',   'code' => 'MMW', 'dept' => 'OPD', 'beds' => 10, 'floor' => '1', 'rate' => 80,  'type' => 'standard'],
            ['name' => 'Female Medical Ward', 'code' => 'FMW', 'dept' => 'OPD', 'beds' => 10, 'floor' => '1', 'rate' => 80,  'type' => 'standard'],
            ['name' => 'Pediatric Ward',      'code' => 'PDW', 'dept' => 'PED', 'beds' => 8,  'floor' => '2', 'rate' => 90,  'type' => 'pediatric'],
            ['name' => 'Maternity Ward',      'code' => 'MTW', 'dept' => 'OBG', 'beds' => 8,  'floor' => '2', 'rate' => 120, 'type' => 'standard'],
            ['name' => 'Surgical Ward',       'code' => 'SUW', 'dept' => 'SUR', 'beds' => 8,  'floor' => '3', 'rate' => 150, 'type' => 'semi_private'],
            ['name' => 'ICU',                 'code' => 'ICU', 'dept' => 'EMR', 'beds' => 4,  'floor' => '3', 'rate' => 500, 'type' => 'icu'],
            ['name' => 'Private Suite',       'code' => 'PVS', 'dept' => 'OPD', 'beds' => 4,  'floor' => '4', 'rate' => 350, 'type' => 'private'],
        ];

        foreach ($wards as $w) {
            $dept = Department::where('code', $w['dept'])->first();

            $ward = Ward::updateOrCreate(
                ['code' => $w['code']],
                [
                    'name' => $w['name'],
                    'department_id' => $dept?->id,
                    'capacity' => $w['beds'],
                    'floor' => $w['floor'],
                    'is_active' => true,
                ]
            );

            for ($i = 1; $i <= $w['beds']; $i++) {
                Bed::updateOrCreate(
                    ['ward_id' => $ward->id, 'bed_number' => sprintf('%s-%02d', $w['code'], $i)],
                    [
                        'bed_type' => $w['type'],
                        'status' => 'available',
                        'daily_rate' => $w['rate'],
                    ]
                );
            }
        }
    }
}
