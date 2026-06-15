<?php

namespace App\Services;

use App\Models\PayrollRecord;
use App\Models\PayrollTaxCalculation;
use Carbon\Carbon;

class TaxLiabilityReconciliationService extends AbstractReconciliationDomainService
{
    public function calculatePaye(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('paye_payable_account_id');
        $rows = PayrollTaxCalculation::query()
            ->whereHas('payrollRun', fn ($query) => $query
                ->whereIn('status', ['approved', 'posted'])
                ->whereDate('period_end', '<=', $asOf))
            ->get();
        $subledger = round((float) $rows->sum('tax_amount'), 2);
        $gl = $this->glBalance($account, $asOf);

        return $this->partialLiabilityResult(
            'paye_liability_control',
            'PAYE',
            'Calculated PAYE liability before statutory settlements',
            $account,
            $subledger,
            $gl,
            $rows->count(),
            'PAYE settlement records and authoritative payroll posting are deferred to Phases E and I.',
        );
    }

    public function calculatePension(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('pension_payable_account_id');
        $rows = PayrollRecord::query()
            ->whereHas('payrollRun', fn ($query) => $query
                ->whereIn('status', ['approved', 'posted'])
                ->whereDate('period_end', '<=', $asOf))
            ->get();
        $subledger = round((float) $rows->sum(fn ($row) => (float) $row->ssnit_employee + (float) $row->ssnit_employer), 2);
        $gl = $this->glBalance($account, $asOf);

        return $this->partialLiabilityResult(
            'pension_liability_control',
            'PENSION',
            'Calculated employee and employer pension liability before settlements',
            $account,
            $subledger,
            $gl,
            $rows->count(),
            'Pension settlement records and authoritative payroll posting are deferred to Phases E and I.',
        );
    }

    public function calculate(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        return $this->calculatePaye($periodStart, $periodEnd, $asOf);
    }

    private function partialLiabilityResult(
        string $sourceType,
        string $reference,
        string $description,
        $account,
        float $subledger,
        float $gl,
        int $recordCount,
        string $reason,
    ): array {
        $classification = ! $account ? 'mapping_issue' : (abs($subledger - $gl) < 0.01 ? 'balanced' : 'unposted_source');
        $items = [$this->item($sourceType, null, $reference, $description, $account, $subledger, $gl, $classification, [
            'record_count' => $recordCount,
            'phase_dependency' => $reason,
        ])];

        return $this->result(
            'partially_available',
            $subledger,
            $gl,
            $items,
            ['record_count' => $recordCount, 'settlements_available' => false],
            ['account_id' => $account?->id, 'balance' => $gl],
            $reason,
        );
    }
}
