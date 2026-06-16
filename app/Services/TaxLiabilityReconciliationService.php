<?php

namespace App\Services;

use App\Models\PayrollRecord;
use App\Models\PayrollStatutorySettlement;
use App\Models\PayrollTaxCalculation;
use Carbon\Carbon;

class TaxLiabilityReconciliationService extends AbstractReconciliationDomainService
{
    public function calculatePaye(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('paye_payable_account_id');
        $rows = PayrollTaxCalculation::query()
            ->whereHas('payrollRun', fn ($query) => $query
                ->where('accounting_status', 'posted')
                ->whereDate('period_end', '<=', $asOf))
            ->get();
        $settled = $this->settled(PayrollStatutorySettlement::TYPE_PAYE, $asOf);
        $subledger = max(0, round((float) $rows->sum('tax_amount') - $settled, 2));
        $gl = $this->glBalance($account, $asOf);

        return $this->partialLiabilityResult(
            'paye_liability_control',
            'PAYE',
            'Posted PAYE liability before statutory settlements',
            $account,
            $subledger,
            $gl,
            $rows->count(),
            $settled,
            'PAYE liability and remittance settlements are available through Phase E2.',
        );
    }

    public function calculatePension(Carbon $periodStart, Carbon $periodEnd, Carbon $asOf): array
    {
        $account = $this->account('pension_payable_account_id');
        $rows = PayrollRecord::query()
            ->whereHas('payrollRun', fn ($query) => $query
                ->where('accounting_status', 'posted')
                ->whereDate('period_end', '<=', $asOf))
            ->get();
        $settled = $this->settled(PayrollStatutorySettlement::TYPE_PENSION, $asOf);
        $subledger = max(0, round((float) $rows->sum(fn ($row) => (float) $row->ssnit_employee + (float) $row->ssnit_employer) - $settled, 2));
        $gl = $this->glBalance($account, $asOf);

        return $this->partialLiabilityResult(
            'pension_liability_control',
            'PENSION',
            'Posted employee and employer pension liability before settlements',
            $account,
            $subledger,
            $gl,
            $rows->count(),
            $settled,
            'Pension / SSNIT liability and remittance settlements are available through Phase E2.',
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
        float $settled,
        string $reason,
    ): array {
        $classification = ! $account ? 'mapping_issue' : (abs($subledger - $gl) < 0.01 ? 'balanced' : 'unposted_source');
        $items = [$this->item($sourceType, null, $reference, $description, $account, $subledger, $gl, $classification, [
            'record_count' => $recordCount,
            'phase_dependency' => $reason,
        ])];

        return $this->result(
            'available',
            $subledger,
            $gl,
            $items,
            ['record_count' => $recordCount, 'statutory_settled_amount' => round($settled, 2)],
            ['account_id' => $account?->id, 'balance' => $gl],
            $reason,
        );
    }

    private function settled(string $type, Carbon $asOf): float
    {
        return round((float) PayrollStatutorySettlement::query()
            ->where('liability_type', $type)
            ->where('status', 'posted')
            ->whereDate('settlement_date', '<=', $asOf)
            ->sum('amount'), 2);
    }
}
