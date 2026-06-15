<?php

namespace App\Services;

use App\Models\PayrollPayslip;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PayslipGenerationService
{
    public function __construct(private ActivityLogService $log) {}

    public function generateFor(PayrollRun $run): void
    {
        if ($run->status !== 'approved') throw ValidationException::withMessages(['status' => 'Payslips require approved payroll.']);
        foreach ($run->records()->with('employee.department')->get() as $record) {
            PayrollPayslip::updateOrCreate(['payroll_record_id' => $record->id], [
                'payroll_run_id' => $run->id, 'employee_id' => $record->employee_id, 'status' => 'generated',
                'generated_at' => now(), 'generated_by' => Auth::id(),
                'snapshot_json' => ['employee' => ['name' => $record->employee->full_name, 'number' => $record->employee->employee_number, 'department' => $record->employee->department?->name, 'position' => $record->employee->position], 'payroll' => $record->toArray()],
            ]);
        }
    }

    public function release(PayrollPayslip $payslip): PayrollPayslip
    {
        if ($payslip->record->status->value !== 'approved' && $payslip->record->status->value !== 'paid') {
            throw ValidationException::withMessages(['status' => 'Payslip cannot be released before payroll approval.']);
        }
        $payslip->update(['status' => 'released', 'released_at' => now(), 'released_by' => Auth::id()]);
        $this->log->log('SYSTEM', 'PAYSLIP_RELEASED', ['source_type' => 'payroll_payslip', 'source_id' => $payslip->id], $payslip);
        return $payslip->fresh();
    }
}
