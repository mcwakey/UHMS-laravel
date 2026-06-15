<?php

namespace App\Services;

use App\Models\PayrollRun;
use LogicException;

class PayrollAccountingService
{
    public function prepareJournal(PayrollRun $run): array
    {
        if (! in_array($run->status, ['approved', 'posted'], true)) {
            throw new LogicException('Only approved payroll can be prepared for accounting.');
        }
        return [
            'debits' => ['salaries_expense' => (float) $run->records()->sum('gross_pay'), 'employer_pension_expense' => (float) $run->records()->sum('ssnit_employer')],
            'credits' => ['payroll_payable' => (float) $run->records()->sum('net_pay'), 'paye_tax_payable' => (float) $run->records()->sum('tax'), 'pension_payable' => (float) $run->records()->sum('ssnit_employee') + (float) $run->records()->sum('ssnit_employer')],
        ];
    }
}
