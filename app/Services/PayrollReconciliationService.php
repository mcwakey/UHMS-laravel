<?php

namespace App\Services;

use App\Models\PayrollRun;
use Carbon\Carbon;

class PayrollReconciliationService extends AbstractReconciliationDomainService
{
    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('payroll_payable_account_id');
        $runs = PayrollRun::query()
            ->with(['records', 'settlements' => fn ($query) => $query->where('status', 'posted')])
            ->where('accounting_status', 'posted')
            ->whereDate('period_end', '<=', $asOf)
            ->get();
        $subledger = round((float) $runs->sum(function (PayrollRun $run) {
            $net = (float) $run->records->sum('net_pay');
            $settled = (float) $run->settlements->sum('amount');

            return max(0, $net - $settled);
        }), 2);
        $gl = $this->glBalance($account, $asOf);
        $classification = ! $account ? 'mapping_issue' : (abs($subledger - $gl) < 0.01 ? 'balanced' : 'unposted_source');
        $items = [
            $this->item('payroll_liability_control', null, 'PAYROLL', 'Approved unpaid payroll', $account, $subledger, $gl, $classification, [
                'run_count' => $runs->count(),
                'posted_settlement_count' => $runs->sum(fn (PayrollRun $run) => $run->settlements->count()),
            ]),
        ];

        return $this->result(
            'available',
            $subledger,
            $gl,
            $items,
            ['posted_payroll_runs' => $runs->count()],
            ['account_id' => $account?->id, 'balance' => $gl],
        );
    }
}
