<?php

namespace Database\Seeders\ManualTesting;

use Illuminate\Support\Facades\DB;

class ManualPayrollSeeder extends ManualTestingSeederBase
{
    public function run(): void
    {
        if (! $this->hasTable('payroll_records')) {
            return;
        }

        $employees = DB::table('employees')->where('employee_number', 'like', 'MT-EMP-%')->select('id', 'basic_salary')->get();
        if ($employees->isEmpty()) {
            return;
        }

        $periods = $this->target('payroll_periods');
        $processedBy = DB::table('users')->where('email', 'hr.user@uhms.test')->value('id');
        for ($p = 0; $p < $periods; $p++) {
            $period = 'MT-'.today()->subMonths($p)->format('Y-m');
            $runId = null;
            if ($this->hasTable('payroll_runs')) {
                $this->updateOrInsert('payroll_runs', ['pay_period' => $period], [
                    'pay_period' => $period,
                    'period_start' => today()->subMonths($p)->startOfMonth(),
                    'period_end' => today()->subMonths($p)->endOfMonth(),
                    'status' => $p % 3 === 0 ? 'approved' : 'draft',
                    'generated_by' => $processedBy,
                    'approved_by' => $p % 3 === 0 ? $processedBy : null,
                    'approved_at' => $p % 3 === 0 ? now()->subDays($p) : null,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
                $runId = DB::table('payroll_runs')->where('pay_period', $period)->value('id');
            }

            foreach ($employees as $employee) {
                $basic = (float) $employee->basic_salary;
                $allowance = round($basic * 0.12, 2);
                $gross = $basic + $allowance;
                $ssnit = round($basic * 0.055, 2);
                $tax = round(max(0, $gross - $ssnit - 500) * 0.10, 2);
                $net = $gross - $ssnit - $tax;
                $this->updateOrInsert('payroll_records', ['employee_id' => $employee->id, 'pay_period' => $period], [
                    'employee_id' => $employee->id,
                    'payroll_run_id' => $runId,
                    'pay_period' => $period,
                    'basic_salary' => $basic,
                    'allowances' => $allowance,
                    'taxable_allowances' => $allowance,
                    'gross_pay' => $gross,
                    'gross_taxable_income' => $gross,
                    'ssnit_employee' => $ssnit,
                    'ssnit_employer' => round($basic * 0.13, 2),
                    'tax' => $tax,
                    'total_deductions' => $ssnit + $tax,
                    'net_pay' => $net,
                    'status' => $p % 3 === 0 ? 'paid' : 'draft',
                    'processed_by' => $processedBy,
                    'paid_at' => $p % 3 === 0 ? now()->subDays($p) : null,
                    'created_at' => $this->now(),
                    'updated_at' => $this->now(),
                ]);
            }
        }
    }
}
