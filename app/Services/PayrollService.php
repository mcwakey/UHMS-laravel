<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\PayrollRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class PayrollService
{
    /**
     * Dual-write a payroll batch event (per pay period) to the central audit log.
     * Facility/HR-level — no patient context, no single subject.
     */
    private function logPayroll(string $event, string $description, string $payPeriod, array $metadata, LogSeverity $severity): void
    {
        try {
            app(ActivityLogService::class)->log(LogModule::SYSTEM, $event, [
                'severity' => $severity,
                'metadata' => array_merge(['pay_period' => $payPeriod], $metadata),
                'source_type' => 'payroll',
            ], null, $description);
        } catch (\Throwable $e) {
            // Logging must never break a payroll action.
        }
    }

    // Ghana PAYE Tax Brackets (Annual, 2026)
    private const TAX_BRACKETS = [
        ['limit' => 4824,  'rate' => 0.00],   // First GH₵4,824 — 0%
        ['limit' => 1320,  'rate' => 0.05],   // Next GH₵1,320 — 5%
        ['limit' => 1560,  'rate' => 0.10],   // Next GH₵1,560 — 10%
        ['limit' => 36000, 'rate' => 0.175],  // Next GH₵36,000 — 17.5%
        ['limit' => 196596, 'rate' => 0.25],  // Next GH₵196,596 — 25%
        ['limit' => PHP_FLOAT_MAX, 'rate' => 0.30], // Above — 30%
    ];

    private const SSNIT_EMPLOYEE_RATE = 0.055;  // 5.5%
    private const SSNIT_EMPLOYER_RATE = 0.13;   // 13%

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
        $employees = Employee::where('status', EmployeeStatus::ACTIVE)
            ->when($employeeIds, fn ($q) => $q->whereIn('id', $employeeIds))
            ->get();

        $records = collect();

        foreach ($employees as $employee) {
            // Skip if already processed for this period
            $existing = PayrollRecord::where('employee_id', $employee->id)
                ->where('pay_period', $payPeriod)
                ->first();

            if ($existing) {
                $records->push($existing);
                continue;
            }

            $basic = (float) $employee->basic_salary;
            $gross = $basic + $allowances;

            $ssnitEmployee = round($basic * self::SSNIT_EMPLOYEE_RATE, 2);
            $ssnitEmployer = round($basic * self::SSNIT_EMPLOYER_RATE, 2);

            // Taxable income = gross - SSNIT employee contribution (monthly)
            $taxableMonthly = $gross - $ssnitEmployee;
            $tax = $this->calculateMonthlyPAYE($taxableMonthly);

            $netPay = $gross - $ssnitEmployee - $tax - $otherDeductions;

            $record = PayrollRecord::create([
                'employee_id' => $employee->id,
                'pay_period' => $payPeriod,
                'basic_salary' => $basic,
                'allowances' => $allowances,
                'gross_pay' => $gross,
                'ssnit_employee' => $ssnitEmployee,
                'ssnit_employer' => $ssnitEmployer,
                'tax' => $tax,
                'other_deductions' => $otherDeductions,
                'net_pay' => max(0, $netPay),
                'status' => PayrollStatus::DRAFT->value,
                'processed_by' => Auth::id(),
            ]);

            $records->push($record);
        }

        if ($records->isNotEmpty()) {
            $this->logPayroll('PAYROLL_PROCESSED', "Payroll processed for {$records->count()} employee(s) — {$payPeriod}", $payPeriod, [
                'employees' => $records->count(),
                'total_net' => (float) $records->sum('net_pay'),
            ], LogSeverity::NOTICE);
        }

        return $records;
    }

    public function calculateMonthlyPAYE(float $monthlyTaxable): float
    {
        $annualTaxable = $monthlyTaxable * 12;
        $annualTax = 0;
        $remaining = $annualTaxable;

        foreach (self::TAX_BRACKETS as $bracket) {
            if ($remaining <= 0) break;

            $taxableInBracket = min($remaining, $bracket['limit']);
            $annualTax += $taxableInBracket * $bracket['rate'];
            $remaining -= $taxableInBracket;
        }

        return round($annualTax / 12, 2);
    }

    public function approvePayroll(string $payPeriod): int
    {
        $count = PayrollRecord::where('pay_period', $payPeriod)
            ->where('status', PayrollStatus::DRAFT)
            ->update(['status' => PayrollStatus::APPROVED->value]);

        if ($count > 0) {
            $this->logPayroll('PAYROLL_APPROVED', "Payroll approved for {$count} record(s) — {$payPeriod}", $payPeriod,
                ['records' => $count], LogSeverity::WARNING);
        }

        return $count;
    }

    public function markPaid(string $payPeriod): int
    {
        $count = PayrollRecord::where('pay_period', $payPeriod)
            ->where('status', PayrollStatus::APPROVED)
            ->update([
                'status' => PayrollStatus::PAID->value,
                'paid_at' => now(),
            ]);

        if ($count > 0) {
            $this->logPayroll('PAYROLL_PAID', "Payroll marked paid for {$count} record(s) — {$payPeriod}", $payPeriod,
                ['records' => $count], LogSeverity::WARNING);
        }

        return $count;
    }

    public function getPayrollSummary(string $payPeriod): array
    {
        $records = PayrollRecord::with('employee.department')
            ->where('pay_period', $payPeriod)
            ->get();

        return [
            'total_employees' => $records->count(),
            'total_basic' => $records->sum('basic_salary'),
            'total_allowances' => $records->sum('allowances'),
            'total_gross' => $records->sum('gross_pay'),
            'total_ssnit_employee' => $records->sum('ssnit_employee'),
            'total_ssnit_employer' => $records->sum('ssnit_employer'),
            'total_tax' => $records->sum('tax'),
            'total_deductions' => $records->sum('other_deductions'),
            'total_net' => $records->sum('net_pay'),
            'by_department' => $records->groupBy('employee.department.name')->map(fn ($g) => [
                'count' => $g->count(),
                'total_net' => $g->sum('net_pay'),
            ]),
        ];
    }
}
