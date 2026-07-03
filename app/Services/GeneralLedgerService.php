<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;

class GeneralLedgerService
{
    public function report(Account $account, array $filters = []): array
    {
        $from = $filters['date_from'] ?? null;
        $accountIds = $this->accountTreeIds($account);

        $opening = 0.0;
        if ($from) {
            $opening = $this->balanceBefore($accountIds, $from, $filters);
        }

        $running = $opening;
        $lines = JournalEntryLine::query()
            ->with(['journalEntry', 'department', 'account'])
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($query) use ($filters) {
                $query->ledgerAffecting()
                    ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('entry_date', '>=', $date))
                    ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('entry_date', '<=', $date))
                    ->when($filters['source_module'] ?? null, fn ($q, $source) => $q->where('source_module', $source));
            })
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get()
            ->map(function ($line) use (&$running) {
                $running += $this->signedAmount($line->account, (float) $line->debit, (float) $line->credit);

                return [
                    'line' => $line,
                    'running_balance' => round($running, 2),
                ];
            });

        return [
            'account' => $account,
            'opening_balance' => round($opening, 2),
            'rows' => $lines,
            'closing_balance' => round($running, 2),
        ];
    }

    protected function balanceBefore(array $accountIds, string $date, array $filters): float
    {
        $rows = JournalEntryLine::query()
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($query) use ($date, $filters) {
                $query->ledgerAffecting()
                    ->whereDate('entry_date', '<', $date)
                    ->when($filters['source_module'] ?? null, fn ($q, $source) => $q->where('source_module', $source));
            })
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->selectRaw('account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->groupBy('account_id')
            ->get();

        $accounts = Account::query()
            ->whereIn('id', $rows->pluck('account_id'))
            ->get()
            ->keyBy('id');

        return $rows->sum(function ($row) use ($accounts) {
            $account = $accounts->get($row->account_id);

            return $account
                ? $this->signedAmount($account, (float) $row->debit_total, (float) $row->credit_total)
                : 0.0;
        });
    }

    protected function signedAmount(Account $account, float $debit, float $credit): float
    {
        return $account->normal_balance->value === 'debit'
            ? $debit - $credit
            : $credit - $debit;
    }

    protected function accountTreeIds(Account $account): array
    {
        $ids = [$account->id];
        $frontier = [$account->id];

        while ($frontier !== []) {
            $children = Account::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $children = array_values(array_diff($children, $ids));
            if ($children === []) {
                break;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }

        return $ids;
    }
}
