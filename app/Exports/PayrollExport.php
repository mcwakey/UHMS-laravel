<?php

namespace App\Exports;

use App\Models\PayrollRecord;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class PayrollExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = PayrollRecord::with(['employee.department'])
            ->latest('pay_period');

        if (!empty($this->filters['pay_period'])) {
            $query->where('pay_period', $this->filters['pay_period']);
        }
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Employee',
            'Department',
            'Pay Period',
            'Basic Salary',
            'Allowances',
            'Gross Pay',
            'SSNIT (Employee)',
            'SSNIT (Employer)',
            'Tax',
            'Other Deductions',
            'Net Pay',
            'Status',
        ];
    }

    public function map($record): array
    {
        return [
            $record->employee?->first_name . ' ' . $record->employee?->last_name,
            $record->employee?->department?->name ?? '—',
            $record->pay_period,
            number_format($record->basic_salary, 2),
            number_format($record->allowances, 2),
            number_format($record->gross_pay, 2),
            number_format($record->ssnit_employee, 2),
            number_format($record->ssnit_employer, 2),
            number_format($record->tax, 2),
            number_format($record->other_deductions, 2),
            number_format($record->net_pay, 2),
            ucfirst($record->status->value ?? $record->status),
        ];
    }

    public function title(): string
    {
        return 'Payroll Summary';
    }
}
