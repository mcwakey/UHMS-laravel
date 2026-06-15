<?php

namespace App\Services;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\PayrollRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Backward-compatible payroll query facade.
 *
 * New calculations are delegated to PayrollDraftService and
 * PayrollTaxCalculationService so there is one auditable calculation path.
 */
class PayrollService
{
    public function __construct(private PayrollDraftService $draftService, private PayrollTaxCalculationService $taxService) {}

    public function listPayroll(array $filters = []): LengthAwarePaginator
    {
        return PayrollRecord::with(['employee.department', 'processedByUser'])
            ->when($filters['pay_period'] ?? null, fn ($q, $p) => $q->byPeriod($p))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->byStatus($s))
            ->when($filters['department_id'] ?? null, fn ($q, $d) => $q->whereHas('employee', fn ($eq) => $eq->where('department_id', $d)))
            ->latest()
            ->paginate(20);
    }

    public function processPayroll(string $payPeriod, ?array $employeeIds = null, float $allowances = 0, float $otherDeductions = 0): Collection
    {
        return $this->draftService->generate($payPeriod)->records;
    }

    public function calculateMonthlyPAYE(float $monthlyTaxable): float
    {
        $employee = new Employee(['tax_residency_status' => 'resident', 'paye_exempt' => false]);
        return $this->taxService->calculate($employee, $monthlyTaxable)['tax_amount'];
    }

    public function approvePayroll(string $payPeriod): int
    {
        throw new \LogicException('Use PayrollApprovalService review and approve workflow.');
    }

    public function markPaid(string $payPeriod): int
    {
        $count = PayrollRecord::where('pay_period', $payPeriod)
            ->where('status', PayrollStatus::APPROVED->value)
            ->update(['status' => PayrollStatus::PAID->value, 'paid_at' => now()]);

        if ($count > 0) {
            app(ActivityLogService::class)->log(LogModule::SYSTEM, 'PAYROLL_PAID', [
                'severity' => LogSeverity::WARNING,
                'metadata' => ['pay_period' => $payPeriod, 'records' => $count],
                'source_type' => 'payroll',
            ]);
        }

        return $count;
    }

    public function getPayrollSummary(string $payPeriod): array
    {
        $records = PayrollRecord::with('employee.department')->where('pay_period', $payPeriod)->get();
        return [
            'total_employees' => $records->count(),
            'total_basic' => $records->sum('basic_salary'),
            'total_allowances' => $records->sum('allowances'),
            'total_gross' => $records->sum('gross_pay'),
            'total_ssnit_employee' => $records->sum('ssnit_employee'),
            'total_ssnit_employer' => $records->sum('ssnit_employer'),
            'total_tax' => $records->sum('tax'),
            'total_deductions' => $records->sum('other_deductions') + $records->sum('attendance_deductions'),
            'total_net' => $records->sum('net_pay'),
            'by_department' => $records->groupBy('employee.department.name')->map(fn ($group) => ['count' => $group->count(), 'total_net' => $group->sum('net_pay')]),
        ];
    }
}
