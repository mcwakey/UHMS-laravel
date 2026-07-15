<?php

namespace Database\Seeders;

use App\Enums\DepartmentType;
use App\Enums\Gender;
use App\Enums\UserStatus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Ensures EVERY department type has at least one department and a login user
 * attached to it, so each type's dashboard can be opened and manually tested.
 *
 * Login: <type>@uhms.local  /  password   (e.g. pharmacy@uhms.local).
 * Re-runnable: reuses existing departments per type and firstOrCreate users.
 */
class DepartmentTypeShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        // type => [department name, fallback code, role, first, last]
        $map = [
            DepartmentType::CONSULTATION->value => ['General Medicine / OPD', 'OPD',  'Doctor',             'Kwabena', 'Mensah'],
            DepartmentType::EMERGENCY->value => ['Emergency / Casualty',   'EMR',  'Emergency Doctor',   'Akosua',  'Boadu'],
            DepartmentType::INVESTIGATION->value => ['Laboratory',             'LAB',  'Lab Technician',     'Nana',    'Owusu'],
            DepartmentType::RADIOLOGY->value => ['Radiology / X-Ray',      'RAD',  'Radiologist',        'Yaa',     'Asante'],
            DepartmentType::PROCEDURE->value => ['Minor Procedures',       'PROC', 'Doctor',             'Kojo',    'Antwi'],
            DepartmentType::THEATRE->value => ['Theatre / Procedures',   'THT',  'Theatre Nurse',      'Abena',   'Adjei'],
            DepartmentType::TREATMENT->value => ['Physiotherapy',          'PHY',  'Nurse',              'Kwame',   'Tetteh'],
            DepartmentType::NURSING->value => ['Nursing Station',        'NURS', 'Triage Nurse',       'Adwoa',   'Sarpong'],
            DepartmentType::PHARMACY->value => ['Pharmacy',               'PHR',  'Pharmacist',         'Efua',    'Quaye'],
            DepartmentType::INPATIENT->value => ['Male Medical Ward',      'MMW',  'Ward Nurse',         'Kofi',    'Darko'],
            DepartmentType::MATERNITY->value => ['Maternity Ward',         'MAT',  'Ward Nurse',         'Ama',     'Agyeman'],
            DepartmentType::BLOOD_BANK->value => ['Blood Bank',             'BLD',  'Blood Bank Officer', 'Yaw',     'Frimpong'],
            DepartmentType::MORTUARY->value => ['Mortuary',               'MORT', 'Admin',              'Kwesi',   'Baah'],
            DepartmentType::AMBULANCE->value => ['Ambulance Services',     'AMB',  'Emergency Nurse',    'Esi',     'Nkrumah'],
            DepartmentType::RECORDS->value => ['Records',                'REC',  'Receptionist',       'Akua',    'Asare'],
            DepartmentType::FINANCE->value => ['Billing & Finance',      'BIL',  'Accountant',         'Kojo',    'Appiah'],
            DepartmentType::STORES->value => ['Main Stores',            'STR',  'Store Keeper',       'Fiifi',   'Acquah'],
            DepartmentType::SUPPORT->value => ['Support Services',       'SUPP', 'Admin',              'Maame',   'Owusu'],
            DepartmentType::ADMINISTRATIVE->value => ['Administration',         'ADMN', 'HR Manager',         'Yaw',     'Bonsu'],
        ];

        $hasType = Schema::hasColumn('departments', 'type');
        $hasPivot = Schema::hasTable('department_user');
        $i = 0;
        $retyped = [];

        foreach ($map as $typeValue => [$name, $code, $role, $first, $last]) {
            $i++;

            // Prefer an existing department of this type (department data is keyed by
            // type, not by our codes). Fall back to a code match, then create one for
            // any type that has no department yet (ambulance, blood bank, mortuary, …).
            $department = ($hasType ? Department::where('type', $typeValue)->orderBy('id')->first() : null)
                ?? Department::where('code', $code)->first();

            if (! $department) {
                $department = Department::create(array_filter([
                    'name' => $name,
                    'code' => $code,
                    'type' => $hasType ? $typeValue : null,
                    'status' => 'active',
                ], fn ($value) => $value !== null));
                $retyped[] = "created {$typeValue} ({$department->name})";
            } elseif ($hasType && $department->getRawOriginal('type') !== $typeValue) {
                $retyped[] = "{$department->code}: ".($department->getRawOriginal('type') ?? 'NULL')." → {$typeValue}";
                $department->forceFill(['type' => $typeValue])->save();
            }

            // Make sure the role exists before assigning it.
            Role::findOrCreate($role);

            $email = $typeValue.'@uhms.local';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'first_name' => $first,
                    'last_name' => $last,
                    'phone' => '0255'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                    'password' => Hash::make('password'),
                    'gender' => Gender::MALE,
                    'status' => UserStatus::ACTIVE,
                    'employee_id' => 'EMP-DT'.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                    'department_id' => $department->id,
                    'email_verified_at' => now(),
                ]
            );

            // Keep the primary department current even if the user already existed.
            if ($user->department_id !== $department->id) {
                $user->forceFill(['department_id' => $department->id])->save();
            }

            $user->syncRoles([$role]);

            if ($hasPivot) {
                $user->departments()->syncWithoutDetaching([
                    $department->id => ['is_primary' => true],
                ]);
            }
        }

        $this->command?->info('Seeded '.count($map).' department-type departments + login users (<type>@uhms.local / password).');
        if ($retyped !== []) {
            $this->command?->warn('Created/corrected departments: '.implode(', ', $retyped));
        }
    }
}
