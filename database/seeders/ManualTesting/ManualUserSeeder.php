<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class ManualUserSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('users')) {
            return;
        }

        $departments = DB::table('departments')->where('code', 'like', 'MT-DEP-%')->pluck('id')->values();
        $accounts = [
            ['admin.manual@uhms.test', 'Manual', 'Admin', 'Super Admin', null],
            ['doctor.opd@uhms.test', 'Opd', 'Doctor', 'Doctor', 'General OPD'],
            ['nurse.ward@uhms.test', 'Ward', 'Nurse', 'Ward Nurse', 'Male Ward'],
            ['lab.tech@uhms.test', 'Lab', 'Technician', 'Lab Technician', 'Main Laboratory'],
            ['radiology.user@uhms.test', 'Radiology', 'User', 'Radiologist', 'X-Ray Unit'],
            ['pharmacy.user@uhms.test', 'Pharmacy', 'User', 'Pharmacist', 'Main Pharmacy'],
            ['accounts.user@uhms.test', 'Accounts', 'User', 'Accountant', 'Accounts'],
            ['billing.user@uhms.test', 'Billing', 'Officer', 'Billing Officer', 'Billing Office'],
            ['claims.user@uhms.test', 'Claims', 'Officer', 'Claims Officer', 'Claims Office'],
            ['hr.user@uhms.test', 'Hr', 'Officer', 'HR Officer', 'HR Office'],
            ['reception.user@uhms.test', 'Reception', 'User', 'Receptionist', 'Reception'],
            ['store.user@uhms.test', 'Store', 'Officer', 'Store Keeper', 'Store Pharmacy'],
            ['emergency.user@uhms.test', 'Emergency', 'Doctor', 'Emergency Doctor', 'Emergency / Casualty'],
            ['theatre.user@uhms.test', 'Theatre', 'Nurse', 'Theatre Nurse', 'Main Theatre'],
        ];

        for ($i = count($accounts); $i < $this->target('users'); $i++) {
            $deptId = $departments->isNotEmpty() ? $departments[$i % $departments->count()] : null;
            $accounts[] = ["manual.user".($i + 1).'@uhms.test', 'Manual', 'User'.($i + 1), 'Staff', $deptId];
        }

        foreach ($accounts as $index => [$email, $first, $last, $role, $departmentRef]) {
            $departmentId = is_numeric($departmentRef)
                ? $departmentRef
                : DB::table('departments')->where('name', $departmentRef)->value('id');

            $this->updateOrInsert('users', ['email' => $email], [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'email_verified_at' => $this->now(),
                'password' => Hash::make('password'),
                'phone' => '055'.str_pad((string) ($index + 1), 7, '0', STR_PAD_LEFT),
                'gender' => $index % 2 === 0 ? 'male' : 'female',
                'status' => 'active',
                'employee_id' => $this->ref('USR', $index + 1),
                'department_id' => $departmentId,
                'remember_token' => null,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);

            $userId = DB::table('users')->where('email', $email)->value('id');
            if ($userId && class_exists(Role::class)) {
                $roleModel = Role::findOrCreate($role, 'web');
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $roleModel->id,
                    'model_type' => \App\Models\User::class,
                    'model_id' => $userId,
                ], []);
            }

            if ($userId && $departmentId && $this->hasTable('department_user')) {
                DB::table('department_user')->updateOrInsert([
                    'department_id' => $departmentId,
                    'user_id' => $userId,
                ], [
                    'is_primary' => true,
                    'role_context' => strtolower(str_replace(' ', '_', $role)),
                    'starts_at' => today()->subMonths(3),
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }
    }
}
