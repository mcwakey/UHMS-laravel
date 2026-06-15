<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\HrPolicySetting;
use App\Models\PayrollRecord;
use App\Models\PayrollRun;
use App\Models\PayrollTaxCalculation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollDraftService
{
    public function __construct(private PayrollTaxCalculationService $tax, private ActivityLogService $log) {}

    public function generate(string $period): PayrollRun
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return DB::transaction(function () use ($period, $start, $end) {
            $run = PayrollRun::firstOrCreate(['pay_period' => $period], ['period_start' => $start, 'period_end' => $end, 'status' => 'draft', 'generated_by' => Auth::id()]);
            if ($run->status !== 'draft') {
                throw ValidationException::withMessages(['pay_period' => 'Only draft payroll runs may be regenerated.']);
            }

            Employee::where('status', EmployeeStatus::ACTIVE->value)->each(function (Employee $employee) use ($run, $period, $start, $end) {
                $attendance = EmployeeAttendance::where('employee_id', $employee->id)->whereBetween('date', [$start, $end])->get();
                if ($attendance->isEmpty()) {
                    throw ValidationException::withMessages(['attendance' => "No processed attendance exists for {$employee->full_name} in {$period}."]);
                }
                if ($attendance->where('review_status', '!=', 'approved')->isNotEmpty()) {
                    throw ValidationException::withMessages(['attendance' => "Attendance for {$employee->full_name} must be approved before payroll generation."]);
                }

                $basic = (float) $employee->basic_salary;
                $taxableAllowances = collect($employee->default_allowances ?? [])->where('taxable', true)->sum('amount');
                $nonTaxableAllowances = collect($employee->default_allowances ?? [])->where('taxable', false)->sum('amount');
                $otherDeductions = collect($employee->default_deductions ?? [])->where('pre_tax', false)->sum('amount');
                $otherPreTax = collect($employee->default_deductions ?? [])->where('pre_tax', true)->sum('amount');
                $absenceDays = $attendance->where('status', 'absent')->count() + $attendance->where('status', 'half_day')->count() * .5;
                $attendanceDeduction = round(($basic / max(1, (int) HrPolicySetting::value('payroll.unpaid_absence_daily_rate_basis', 30))) * $absenceDays, 2);
                $employeePension = round($basic * ((float) $employee->employee_ssnit_rate / 100), 2);
                $employerPension = round($basic * ((float) $employee->employer_ssnit_rate / 100), 2);
                $grossTaxable = $basic + $taxableAllowances;
                $gross = $grossTaxable + $nonTaxableAllowances;
                $tax = $this->tax->calculate($employee, $grossTaxable, $employeePension + $otherPreTax, (float) $employee->tax_relief_amount, $start);
                $totalDeductions = $employeePension + $otherPreTax + $tax['tax_amount'] + $otherDeductions + $attendanceDeduction;

                $record = PayrollRecord::updateOrCreate(['employee_id' => $employee->id, 'pay_period' => $period], [
                    'payroll_run_id' => $run->id, 'basic_salary' => $basic, 'overtime_pay' => 0, 'allowances' => $taxableAllowances + $nonTaxableAllowances,
                    'taxable_allowances' => $taxableAllowances, 'non_taxable_allowances' => $nonTaxableAllowances, 'gross_pay' => $gross,
                    'gross_taxable_income' => $grossTaxable, 'ssnit_employee' => $employeePension, 'ssnit_employer' => $employerPension,
                    'other_pre_tax_deductions' => $otherPreTax, 'tax_reliefs' => $employee->tax_relief_amount, 'chargeable_income' => $tax['chargeable_income'],
                    'tax' => $tax['tax_amount'], 'other_deductions' => $otherDeductions, 'attendance_deductions' => $attendanceDeduction,
                    'total_deductions' => $totalDeductions, 'net_pay' => max(0, $gross - $totalDeductions), 'status' => 'draft',
                    'processed_by' => Auth::id(), 'calculation_snapshot' => ['attendance' => ['approved_records' => $attendance->count(), 'absence_days' => $absenceDays], 'tax' => $tax],
                ]);

                PayrollTaxCalculation::updateOrCreate(['payroll_record_id' => $record->id], [
                    'payroll_run_id' => $run->id, 'employee_id' => $employee->id, 'payroll_tax_table_id' => $tax['tax_table_id'],
                    'gross_taxable_income' => $grossTaxable, 'pre_tax_deductions' => $employeePension + $otherPreTax, 'reliefs_total' => $employee->tax_relief_amount,
                    'chargeable_income' => $tax['chargeable_income'], 'tax_amount' => $tax['tax_amount'], 'calculation_snapshot_json' => $tax,
                    'calculated_at' => now(), 'calculated_by' => Auth::id(),
                ]);
            });

            $this->log->log('SYSTEM', 'PAYROLL_DRAFT_GENERATED', ['source_type' => 'payroll_run', 'source_id' => $run->id, 'metadata' => ['pay_period' => $period, 'records' => $run->records()->count()]], $run);
            return $run->fresh('records');
        });
    }
}
