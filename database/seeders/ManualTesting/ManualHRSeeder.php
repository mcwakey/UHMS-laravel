<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualHRSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('employees')) {
            return;
        }

        $existing = $this->countManual('employees', 'employee_number');
        $target = $this->target('employees');
        if ($existing >= $target) {
            return;
        }

        $users = DB::table('users')->where('email', 'like', '%@uhms.test')->select('id', 'first_name', 'last_name', 'email', 'phone', 'department_id')->get()->values();
        if ($users->isEmpty()) {
            return;
        }

        $rows = [];
        for ($i = $existing + 1; $i <= $target; $i++) {
            $user = $users[($i - 1) % $users->count()];
            $salary = [1200, 2500, 4500, 8500, 15000][$i % 5];
            $rows[] = [
                'employee_number' => $this->ref('EMP', $i),
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'date_of_birth' => today()->subYears(24 + ($i % 36)),
                'phone' => $user->phone ?: '055'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'email' => $user->email,
                'address' => 'Manual staff address',
                'department_id' => $user->department_id,
                'position' => ['Doctor', 'Nurse', 'Technician', 'Officer', 'Manager'][$i % 5],
                'hire_date' => today()->subMonths(6 + ($i % 48)),
                'basic_salary' => $salary,
                'salary_type' => 'monthly',
                'payment_method' => $i % 3 === 0 ? 'mobile_money' : 'bank',
                'mobile_money_number' => '055'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'bank_name' => 'Manual Test Bank',
                'bank_account' => 'MT'.str_pad((string) $i, 10, '0', STR_PAD_LEFT),
                'ssnit_number' => 'MTSSNIT'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'tin_number' => 'MTTIN'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'tax_identification_number' => 'MTTIN'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'status' => 'active',
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
        }
        $this->insert('employees', $rows);
        $this->seedAttendance();
    }

    private function seedAttendance(): void
    {
        if (! $this->hasTable('employee_attendance') || $this->countManual('employee_attendance', 'notes') > 0) {
            return;
        }

        $employees = DB::table('employees')->where('employee_number', 'like', 'MT-EMP-%')->pluck('id');
        $rows = [];
        foreach ($employees as $employeeIndex => $employeeId) {
            for ($day = 0; $day < 20; $day++) {
                $status = $day % 9 === 0 ? 'absent' : ($day % 7 === 0 ? 'late' : 'present');
                $rows[] = [
                    'employee_id' => $employeeId,
                    'date' => today()->subDays($day),
                    'clock_in' => $status === 'absent' ? null : ($status === 'late' ? '09:20:00' : '08:00:00'),
                    'clock_out' => $status === 'absent' ? null : '17:00:00',
                    'hours_worked' => $status === 'absent' ? 0 : 8,
                    'status' => $status,
                    'notes' => 'MT-MANUAL attendance seed',
                    'source' => 'manual_test',
                    'late_minutes' => $status === 'late' ? 80 : 0,
                    'overtime_minutes' => ($employeeIndex + $day) % 6 === 0 ? 60 : 0,
                    'review_status' => $status === 'absent' ? 'pending' : 'approved',
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ];
            }
        }
        $this->insert('employee_attendance', $rows);
    }
}
