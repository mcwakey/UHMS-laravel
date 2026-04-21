<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\Specialty;
use Illuminate\Database\Seeder;

class SpecialtySeeder extends Seeder
{
    public function run(): void
    {
        // specialty name => [dept_code, [service_codes to link]]
        $specialties = [
            'General Medicine' => [
                'dept' => 'OPD',
                'services' => ['OPD-CON', 'OPD-FUP', 'OPD-BPC', 'OPD-BGT', 'OPD-WD', 'OPD-INJ'],
            ],
            'Pediatrics' => [
                'dept' => 'PED',
                'services' => ['PED-CON', 'PED-IMM', 'PED-GM', 'PED-NEO'],
            ],
            'Obstetrics & Gynecology' => [
                'dept' => 'OBG',
                'services' => ['OBG-CON', 'OBG-ANC', 'OBG-USS', 'OBG-PAP', 'OBG-FPC'],
            ],
            'General Surgery' => [
                'dept' => 'SUR',
                'services' => ['SUR-CON', 'SUR-MIN', 'SUR-IND', 'SUR-SUT'],
            ],
            'Orthopedics & Trauma' => [
                'dept' => 'ORT',
                'services' => ['ORT-CON', 'ORT-POP', 'ORT-FRC'],
            ],
            'Ophthalmology' => [
                'dept' => 'EYE',
                'services' => ['EYE-CON', 'EYE-VAT', 'EYE-ED', 'EYE-FBR'],
            ],
            'Ear, Nose & Throat' => [
                'dept' => 'ENT',
                'services' => ['ENT-CON', 'ENT-SYR', 'ENT-TSW'],
            ],
            'Dentistry' => [
                'dept' => 'DEN',
                'services' => ['DEN-CON', 'DEN-EXT', 'DEN-SP', 'DEN-FIL'],
            ],
            'Psychiatry & Mental Health' => [
                'dept' => 'PSY',
                'services' => ['PSY-CON', 'PSY-CNS'],
            ],
            'Emergency Medicine' => [
                'dept' => 'EMR',
                'services' => ['EMR-CON', 'EMR-TRI', 'EMR-RES', 'EMR-OXY'],
            ],
            'Clinical Laboratory' => [
                'dept' => 'LAB',
                'services' => ['LAB-FBC', 'LAB-MAL', 'LAB-URI', 'LAB-LFT', 'LAB-RFT'],
            ],
            'Radiology & Imaging' => [
                'dept' => 'RAD',
                'services' => [],
            ],
            'Physiotherapy & Rehabilitation' => [
                'dept' => 'PHY',
                'services' => [],
            ],
            'Midwifery & Antenatal Care' => [
                'dept' => 'ANC',
                'services' => ['OBG-ANC'],
            ],
            'Family Planning' => [
                'dept' => 'FPL',
                'services' => ['OBG-FPC'],
            ],
        ];

        foreach ($specialties as $name => $config) {
            $dept = Department::where('code', $config['dept'])->first();

            $specialty = Specialty::firstOrCreate(
                ['name' => $name],
                [
                    'department_id' => $dept?->id,
                    'is_active'     => true,
                ]
            );

            // Link services
            if (!empty($config['services'])) {
                $serviceIds = ServiceCatalog::whereIn('code', $config['services'])
                    ->pluck('id')
                    ->toArray();

                if ($serviceIds) {
                    $specialty->services()->syncWithoutDetaching($serviceIds);
                }
            }
        }
    }
}
