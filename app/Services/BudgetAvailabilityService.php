<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetCommitment;
use App\Models\BudgetLine;
use App\Models\BudgetRevision;
use App\Models\BudgetRevisionLine;
use App\Models\BudgetTransfer;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;

class BudgetAvailabilityService
{
    public function available(int $fiscalYearId, ?int $departmentId, int $accountId): array
    {
        $budgetIds = Budget::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->whereIn('status', [Budget::STATUS_APPROVED, Budget::STATUS_ACTIVE])
            ->pluck('id');

        $approved = (float) BudgetLine::query()
            ->whereIn('budget_id', $budgetIds)
            ->where('account_id', $accountId)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId), fn ($query) => $query->whereNull('department_id'))
            ->sum('amount');

        $revisions = (float) BudgetRevisionLine::query()
            ->where('account_id', $accountId)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId), fn ($query) => $query->whereNull('department_id'))
            ->whereHas('revision', fn ($query) => $query
                ->whereIn('budget_id', $budgetIds)
                ->where('status', BudgetRevision::STATUS_APPROVED))
            ->sum('amount_delta');

        $transferIn = (float) BudgetTransfer::query()
            ->whereIn('budget_id', $budgetIds)
            ->where('status', BudgetTransfer::STATUS_APPROVED)
            ->where('to_account_id', $accountId)
            ->when($departmentId, fn ($query) => $query->where('to_department_id', $departmentId), fn ($query) => $query->whereNull('to_department_id'))
            ->sum('amount');

        $transferOut = (float) BudgetTransfer::query()
            ->whereIn('budget_id', $budgetIds)
            ->where('status', BudgetTransfer::STATUS_APPROVED)
            ->where('from_account_id', $accountId)
            ->when($departmentId, fn ($query) => $query->where('from_department_id', $departmentId), fn ($query) => $query->whereNull('from_department_id'))
            ->sum('amount');

        $actual = $this->actuals($fiscalYearId, $departmentId, $accountId);
        $commitments = (float) BudgetCommitment::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('account_id', $accountId)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId), fn ($query) => $query->whereNull('department_id'))
            ->whereIn('status', [BudgetCommitment::STATUS_ACTIVE, BudgetCommitment::STATUS_PARTIALLY_RELEASED])
            ->sum('remaining_amount');

        $adjusted = round($approved + $revisions + $transferIn - $transferOut, 2);

        return [
            'fiscal_year_id' => $fiscalYearId,
            'department_id' => $departmentId,
            'account_id' => $accountId,
            'approved_budget' => round($approved, 2),
            'revision_delta' => round($revisions, 2),
            'transfer_in' => round($transferIn, 2),
            'transfer_out' => round($transferOut, 2),
            'adjusted_budget' => $adjusted,
            'actual' => round($actual, 2),
            'open_commitments' => round($commitments, 2),
            'available' => round($adjusted - $actual - $commitments, 2),
        ];
    }

    public function summary(?int $fiscalYearId = null): array
    {
        $fiscalYearId ??= FiscalYear::query()->latest('start_date')->value('id');
        if (! $fiscalYearId) {
            return ['rows' => [], 'totals' => []];
        }

        $dimensions = BudgetLine::query()
            ->whereHas('budget', fn ($query) => $query
                ->where('fiscal_year_id', $fiscalYearId)
                ->whereIn('status', [Budget::STATUS_APPROVED, Budget::STATUS_ACTIVE]))
            ->select('department_id', 'account_id')
            ->distinct()
            ->with(['department:id,name', 'account:id,code,name'])
            ->get();

        $rows = $dimensions->map(fn (BudgetLine $line) => array_merge(
            $this->available($fiscalYearId, $line->department_id, $line->account_id),
            [
                'department' => $line->department?->name ?? 'Unassigned',
                'account' => trim(($line->account?->code ?? '') . ' ' . ($line->account?->name ?? '')),
            ]
        ))->values();

        return [
            'fiscal_year_id' => $fiscalYearId,
            'rows' => $rows,
            'totals' => [
                'adjusted_budget' => round($rows->sum('adjusted_budget'), 2),
                'actual' => round($rows->sum('actual'), 2),
                'open_commitments' => round($rows->sum('open_commitments'), 2),
                'available' => round($rows->sum('available'), 2),
            ],
        ];
    }

    private function actuals(int $fiscalYearId, ?int $departmentId, int $accountId): float
    {
        $row = JournalEntryLine::query()
            ->where('account_id', $accountId)
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId), fn ($query) => $query->whereNull('department_id'))
            ->whereHas('journalEntry', fn ($query) => $query->ledgerAffecting()->where('fiscal_year_id', $fiscalYearId))
            ->selectRaw('SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->first();

        return round((float) ($row->debit_total ?? 0) - (float) ($row->credit_total ?? 0), 2);
    }
}
