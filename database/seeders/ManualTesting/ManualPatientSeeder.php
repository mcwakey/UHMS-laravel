<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualPatientSeeder extends ManualTestingSeederBase
{
    private array $male = ['Kwame', 'Kofi', 'Yaw', 'Kojo', 'Fiifi', 'Daniel', 'Samuel', 'Michael'];
    private array $female = ['Akua', 'Ama', 'Abena', 'Adwoa', 'Afia', 'Grace', 'Mercy', 'Esther'];
    private array $last = ['Mensah', 'Asante', 'Owusu', 'Boateng', 'Appiah', 'Osei', 'Darko', 'Koomson'];

    public function run(): void
    {
        if (! $this->hasTable('patients')) {
            return;
        }

        $existing = $this->countManual('patients', 'patient_number');
        $target = $this->target('patients');
        if ($existing >= $target) {
            return;
        }

        $rows = [];
        for ($i = $existing + 1; $i <= $target; $i++) {
            $gender = $i % 2 === 0 ? 'male' : 'female';
            $first = $gender === 'male' ? $this->male[$i % count($this->male)] : $this->female[$i % count($this->female)];
            $last = $this->last[$i % count($this->last)];
            $dob = today()->subYears(match ($i % 6) {
                0 => 4,
                1 => 17,
                2 => 29,
                3 => 44,
                4 => 67,
                default => 35,
            })->subDays($i % 365);

            $rows[] = [
                'patient_number' => $this->ref('PAT', $i),
                'first_name' => $first,
                'last_name' => $last,
                'other_names' => $i % 5 === 0 ? 'Manual' : null,
                'date_of_birth' => $dob,
                'gender' => $gender,
                'blood_group' => ['A+', 'B+', 'O+', 'AB-', 'A-'][$i % 5],
                'marital_status' => ['single', 'married', 'divorced', 'widowed'][$i % 4],
                'religion' => ['Christianity', 'Islam', 'Traditional', 'None'][$i % 4],
                'phone' => '024'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'phone_secondary' => $i % 4 === 0 ? '+23355'.str_pad((string) $i, 7, '0', STR_PAD_LEFT) : null,
                'email' => strtolower("mt.patient{$i}@uhms.test"),
                'ghana_card_number' => 'MT-GHA-'.str_pad((string) $i, 9, '0', STR_PAD_LEFT),
                'occupation' => ['Trader', 'Teacher', 'Farmer', 'Driver', 'Student', 'Nurse'][$i % 6],
                'address' => "Manual Test House {$i}",
                'city' => ['Accra', 'Kumasi', 'Cape Coast', 'Tamale'][$i % 4],
                'town' => ['Madina', 'Adum', 'Kasoa', 'Ho'][$i % 4],
                'region' => ['Greater Accra', 'Ashanti', 'Central', 'Volta'][$i % 4],
                'digital_address' => 'MT-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'-'.str_pad((string) (($i * 7) % 9999), 4, '0', STR_PAD_LEFT),
                'allergies' => $i % 8 === 0 ? 'Penicillin' : null,
                'chronic_conditions' => $i % 9 === 0 ? 'Hypertension' : null,
                'status' => $i % 31 === 0 ? 'inactive' : 'active',
                'is_active' => $i % 31 !== 0,
                'is_temporary' => $i % 23 === 0,
                'temporary_reason' => $i % 23 === 0 ? 'Manual emergency folder' : null,
                'registered_by' => DB::table('users')->where('email', 'reception.user@uhms.test')->value('id'),
                'created_at' => $this->pastDate($i),
                'updated_at' => $this->now(),
            ];
        }

        $this->insert('patients', $rows);
        $this->seedPatientInsurances();
    }

    private function seedPatientInsurances(): void
    {
        if (! $this->hasTable('patient_insurances')) {
            return;
        }

        $providerIds = DB::table('insurance_providers')->where('code', 'like', '%-MT')->pluck('id')->values();
        if ($providerIds->isEmpty()) {
            return;
        }

        $patients = DB::table('patients')->where('patient_number', 'like', 'MT-PAT-%')->pluck('id')->values();
        $rows = [];
        foreach ($patients as $index => $patientId) {
            if ($index % 3 !== 0) {
                continue;
            }

            $rows[] = [
                'patient_id' => $patientId,
                'insurance_provider_id' => $providerIds[$index % $providerIds->count()],
                'member_type' => 'holder',
                'membership_number' => $this->ref('POL', $index + 1),
                'policy_number' => $this->ref('PLC', $index + 1),
                'ccc_code' => $index % 2 === 0 ? 'CCC'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT) : null,
                'start_date' => today()->subMonths(6),
                'expiry_date' => $index % 7 === 0 ? today()->subDays(5) : today()->addMonths(9),
                'is_primary' => true,
                'is_active' => $index % 7 !== 0,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }

        foreach ($rows as $row) {
            $this->updateOrInsert('patient_insurances', ['membership_number' => $row['membership_number']], $row);
        }
    }
}
