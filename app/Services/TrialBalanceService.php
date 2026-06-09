<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

class TrialBalanceService
{
    public function report(array $filters = []): array
    {
        $rows = JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->whereHas('journalEntry', function ($query) use ($filters) {
                $query->ledgerAffecting()
                    ->when($filters['fiscal_year_id'] ?? null, fn ($q, $id) => $q->where('fiscal_year_id', $id))
                    ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
                    ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('entry_date', '<=', $date));
            })
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->groupBy('account_id')
            ->with('account')
            ->get();

        $accounts = Account::query()
            ->byType($filters['account_type'] ?? null)
            ->whereIn('id', $rows->pluck('account_id'))
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        $items = $rows
            ->filter(fn ($row) => $accounts->has($row->account_id))
            ->map(function ($row) use ($accounts) {
                $account = $accounts->get($row->account_id);
                $debit = round((float) $row->debit_total, 2);
                $credit = round((float) $row->credit_total, 2);
                $balance = $account->normal_balance->value === 'debit'
                    ? $debit - $credit
                    : $credit - $debit;

                return [
                    'account' => $account,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => round($balance, 2),
                ];
            })
            ->sortBy(fn ($row) => $row['account']->code)
            ->values();

        return [
            'rows' => $items,
            'total_debit' => round($items->sum('debit'), 2),
            'total_credit' => round($items->sum('credit'), 2),
            'is_balanced' => abs($items->sum('debit') - $items->sum('credit')) < 0.005,
        ];
    }
}
