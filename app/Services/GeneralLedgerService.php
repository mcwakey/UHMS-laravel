<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntryLine;

class GeneralLedgerService
{
    public function report(Account $account, array $filters = []): array
    {
        $from = $filters['date_from'] ?? null;

        $opening = 0.0;
        if ($from) {
            $opening = $this->balanceBefore($account, $from, $filters);
        }

        $running = $opening;
        $lines = JournalEntryLine::query()
            ->with(['journalEntry', 'department'])
            ->where('account_id', $account->id)
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
            ->map(function ($line) use ($account, &$running) {
                $running += $this->signedAmount($account, (float) $line->debit, (float) $line->credit);

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

    protected function balanceBefore(Account $account, string $date, array $filters): float
    {
        $rows = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('journalEntry', function ($query) use ($date, $filters) {
                $query->ledgerAffecting()
                    ->whereDate('entry_date', '<', $date)
                    ->when($filters['source_module'] ?? null, fn ($q, $source) => $q->where('source_module', $source));
            })
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->selectRaw('SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->first();

        return $this->signedAmount($account, (float) ($rows->debit_total ?? 0), (float) ($rows->credit_total ?? 0));
    }

    protected function signedAmount(Account $account, float $debit, float $credit): float
    {
        return $account->normal_balance->value === 'debit'
            ? $debit - $credit
            : $credit - $debit;
    }
}
