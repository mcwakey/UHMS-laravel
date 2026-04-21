<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    public function run(): void
    {
        // Map department code -> designations for that department
        $data = [
            'OPD' => [
                'Medical Officer',
                'Senior Medical Officer',
                'House Officer',
                'Physician Assistant',
                'Registered Nurse',
                'Community Health Nurse',
                'Receptionist',
            ],
            'PED' => [
                'Pediatrician',
                'Pediatric Nurse',
                'Neonatal Nurse',
            ],
            'OBG' => [
                'Obstetrician & Gynecologist',
                'Midwife',
                'Antenatal Nurse',
            ],
            'SUR' => [
                'General Surgeon',
                'Surgical Nurse',
                'Theatre Nurse',
                'Anesthesiologist',
                'Scrub Technician',
            ],
            'ORT' => [
                'Orthopedic Surgeon',
                'Orthopedic Nurse',
                'Physiotherapist',
            ],
            'EYE' => [
                'Ophthalmologist',
                'Optometrist',
                'Ophthalmic Nurse',
            ],
            'ENT' => [
                'ENT Specialist',
                'ENT Nurse',
            ],
            'DEN' => [
                'Dentist',
                'Dental Assistant',
                'Dental Nurse',
            ],
            'PSY' => [
                'Psychiatrist',
                'Psychologist',
                'Mental Health Nurse',
                'Counselor',
            ],
            'EMR' => [
                'Emergency Physician',
                'Casualty Medical Officer',
                'Emergency Nurse',
                'Paramedic',
                'Triage Nurse',
            ],
            'LAB' => [
                'Laboratory Scientist',
                'Laboratory Technician',
                'Lab Assistant',
                'Hematologist',
            ],
            'PHR' => [
                'Pharmacist',
                'Pharmacy Technician',
                'Pharmaceutical Scientist',
            ],
            'RAD' => [
                'Radiologist',
                'Radiographer',
                'Ultrasound Technician',
            ],
            'PHY' => [
                'Physiotherapist',
                'Senior Physiotherapist',
            ],
            'ANC' => [
                'Midwife',
                'Antenatal Care Nurse',
            ],
            'FPL' => [
                'Family Planning Counselor',
                'Family Planning Nurse',
            ],
        ];

        // Add cross-cutting / administrative designations (no dept)
        $adminDesignations = [
            'Hospital Administrator',
            'Medical Director',
            'Chief Nursing Officer',
            'Finance Officer',
            'Accountant',
            'Cashier',
            'Store Keeper',
            'HR Manager',
            'HR Officer',
            'IT Officer',
            'Security Officer',
            'Cleaner / Orderly',
            'Driver',
        ];

        foreach ($adminDesignations as $name) {
            Designation::firstOrCreate(
                ['name' => $name, 'department_id' => null],
                ['description' => null]
            );
        }

        foreach ($data as $deptCode => $designations) {
            $dept = Department::where('code', $deptCode)->first();

            foreach ($designations as $name) {
                Designation::firstOrCreate(
                    ['name' => $name, 'department_id' => $dept?->id],
                    ['description' => null]
                );
            }
        }
    }
}
