<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // [role, first, last, email, phone, gender, dept_code, designation_name, specialty_name|null]
        $users = [
            [
                'role'        => 'Doctor',
                'first_name'  => 'Kwame',
                'last_name'   => 'Asante',
                'email'       => 'doctor@uhms.local',
                'phone'       => '0241000001',
                'gender'      => Gender::MALE,
                'dept'        => 'OPD',
                'designation' => 'Medical Officer',
                'specialty'   => 'General Medicine',
            ],
            [
                'role'        => 'Doctor',
                'first_name'  => 'Abena',
                'last_name'   => 'Mensah',
                'email'       => 'pediatrician@uhms.local',
                'phone'       => '0241000002',
                'gender'      => Gender::FEMALE,
                'dept'        => 'PED',
                'designation' => 'Pediatrician',
                'specialty'   => 'Pediatrics',
            ],
            [
                'role'        => 'Doctor',
                'first_name'  => 'Kofi',
                'last_name'   => 'Owusu',
                'email'       => 'surgeon@uhms.local',
                'phone'       => '0241000003',
                'gender'      => Gender::MALE,
                'dept'        => 'SUR',
                'designation' => 'General Surgeon',
                'specialty'   => 'General Surgery',
            ],
            [
                'role'        => 'Doctor',
                'first_name'  => 'Efua',
                'last_name'   => 'Boateng',
                'email'       => 'obgyn@uhms.local',
                'phone'       => '0241000004',
                'gender'      => Gender::FEMALE,
                'dept'        => 'OBG',
                'designation' => 'Obstetrician & Gynecologist',
                'specialty'   => 'Obstetrics & Gynecology',
            ],
            [
                'role'        => 'Nurse',
                'first_name'  => 'Ama',
                'last_name'   => 'Darko',
                'email'       => 'nurse@uhms.local',
                'phone'       => '0241000005',
                'gender'      => Gender::FEMALE,
                'dept'        => 'OPD',
                'designation' => 'Registered Nurse',
                'specialty'   => null,
            ],
            [
                'role'        => 'Nurse',
                'first_name'  => 'Yaw',
                'last_name'   => 'Amponsah',
                'email'       => 'nurse2@uhms.local',
                'phone'       => '0241000006',
                'gender'      => Gender::MALE,
                'dept'        => 'EMR',
                'designation' => 'Emergency Nurse',
                'specialty'   => null,
            ],
            [
                'role'        => 'Receptionist',
                'first_name'  => 'Akua',
                'last_name'   => 'Frimpong',
                'email'       => 'receptionist@uhms.local',
                'phone'       => '0241000007',
                'gender'      => Gender::FEMALE,
                'dept'        => 'OPD',
                'designation' => 'Receptionist',
                'specialty'   => null,
            ],
            [
                'role'        => 'Lab Technician',
                'first_name'  => 'Nana',
                'last_name'   => 'Osei',
                'email'       => 'labtech@uhms.local',
                'phone'       => '0241000008',
                'gender'      => Gender::MALE,
                'dept'        => 'LAB',
                'designation' => 'Laboratory Technician',
                'specialty'   => 'Clinical Laboratory',
            ],
            [
                'role'        => 'Pharmacist',
                'first_name'  => 'Adjoa',
                'last_name'   => 'Agyeman',
                'email'       => 'pharmacist@uhms.local',
                'phone'       => '0241000009',
                'gender'      => Gender::FEMALE,
                'dept'        => 'PHR',
                'designation' => 'Pharmacist',
                'specialty'   => null,
            ],
            [
                'role'        => 'Accountant',
                'first_name'  => 'Kojo',
                'last_name'   => 'Appiah',
                'email'       => 'accountant@uhms.local',
                'phone'       => '0241000010',
                'gender'      => Gender::MALE,
                'dept'        => null,
                'designation' => 'Accountant',
                'specialty'   => null,
            ],
            [
                'role'        => 'Claims Officer',
                'first_name'  => 'Adwoa',
                'last_name'   => 'Nyarko',
                'email'       => 'claims@uhms.local',
                'phone'       => '0241000011',
                'gender'      => Gender::FEMALE,
                'dept'        => null,
                'designation' => 'Finance Officer',
                'specialty'   => null,
            ],
            [
                'role'        => 'Store Keeper',
                'first_name'  => 'Fiifi',
                'last_name'   => 'Acquah',
                'email'       => 'storekeeper@uhms.local',
                'phone'       => '0241000012',
                'gender'      => Gender::MALE,
                'dept'        => null,
                'designation' => 'Store Keeper',
                'specialty'   => null,
            ],
            [
                'role'        => 'HR Manager',
                'first_name'  => 'Maame',
                'last_name'   => 'Sarpong',
                'email'       => 'hr@uhms.local',
                'phone'       => '0241000013',
                'gender'      => Gender::FEMALE,
                'dept'        => null,
                'designation' => 'HR Manager',
                'specialty'   => null,
            ],
            [
                'role'        => 'Admin',
                'first_name'  => 'Yaw',
                'last_name'   => 'Bonsu',
                'email'       => 'manager@uhms.local',
                'phone'       => '0241000014',
                'gender'      => Gender::MALE,
                'dept'        => null,
                'designation' => 'Hospital Administrator',
                'specialty'   => null,
            ],
        ];

        foreach ($users as $i => $data) {
            $dept        = $data['dept'] ? Department::where('code', $data['dept'])->first() : null;
            $designation = $data['designation']
                ? Designation::where('name', $data['designation'])->first()
                : null;

            $empNumber = 'EMP-' . str_pad($i + 2, 4, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name'     => $data['first_name'],
                    'last_name'      => $data['last_name'],
                    'phone'          => $data['phone'],
                    'password'       => Hash::make('password'),
                    'gender'         => $data['gender'],
                    'status'         => UserStatus::ACTIVE,
                    'employee_id'    => $empNumber,
                    'department_id'  => $dept?->id,
                    'designation_id' => $designation?->id,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['role']]);

            // Attach specialty for doctors / lab
            if ($data['specialty']) {
                $specialty = Specialty::where('name', $data['specialty'])->first();
                if ($specialty) {
                    $user->specialties()->syncWithoutDetaching([$specialty->id]);
                }
            }
        }
    }
}
