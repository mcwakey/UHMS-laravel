<?php

namespace App\Services;

use App\Models\PayrollRecord;
use Carbon\Carbon;

class PayrollReconciliationService extends AbstractReconciliationDomainService
{
    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('payroll_payable_account_id');
        $rows = PayrollRecord::query()
            ->whereHas('payrollRun', fn ($query) => $query
                ->whereIn('status', ['approved', 'posted'])
                ->whereDate('period_end', '<=', $asOf))
            ->whereNull('paid_at')
            ->get();
        $subledger = round((float) $rows->sum('net_pay'), 2);
        $gl = $this->glBalance($account, $asOf);
        $classification = ! $account ? 'mapping_issue' : (abs($subledger - $gl) < 0.01 ? 'balanced' : 'unposted_source');
        $items = [
            $this->item('payroll_liability_control', null, 'PAYROLL', 'Approved unpaid payroll', $account, $subledger, $gl, $classification, [
                'record_count' => $rows->count(),
                'phase_dependency' => 'Accounting Phase E payroll posting and settlement',
            ]),
        ];

        return $this->result(
            'partially_available',
            $subledger,
            $gl,
            $items,
            ['approved_unpaid_records' => $rows->count()],
            ['account_id' => $account?->id, 'balance' => $gl],
            'Payroll calculation exists, but authoritative GL posting and settlement are deferred to Phase E.',
        );
    }
}
