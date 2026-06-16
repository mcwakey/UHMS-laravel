<?php

namespace App\Services;

use App\Enums\Accounting\AccountType;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Carbon;

class CashFlowStatementService
{
    public function direct(array $filters = []): array
    {
        $allCashEquivalentIds = Account::query()
            ->where(fn ($query) => $query->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->pluck('id')
            ->all();

        $cashAccounts = Account::query()
            ->where(fn ($query) => $query->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->when($filters['account_id'] ?? null, fn ($query, $id) => $query->where('id', $id))
            ->orderBy('code')
            ->get();

        $cashAccountIds = $cashAccounts->pluck('id')->all();
        $opening = $this->openingBalance($cashAccountIds, $filters['date_from'] ?? null);

        $sections = [
            'operating' => ['label' => 'Operating activities', 'rows' => [], 'inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'investing' => ['label' => 'Investing activities', 'rows' => [], 'inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'financing' => ['label' => 'Financing activities', 'rows' => [], 'inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
            'other' => ['label' => 'Other cash adjustments', 'rows' => [], 'inflows' => 0.0, 'outflows' => 0.0, 'net' => 0.0],
        ];

        if ($cashAccounts->isEmpty()) {
            return $this->payload($filters, $cashAccounts, $sections, $opening);
        }

        $entries = JournalEntry::query()
            ->ledgerAffecting()
            ->whereHas('lines', fn ($query) => $query->whereIn('account_id', $cashAccountIds))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('entry_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('entry_date', '<=', $date))
            ->with(['lines.account'])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $cashLines = $entry->lines->whereIn('account_id', $cashAccountIds);
            $movement = round($cashLines->sum(fn (JournalEntryLine $line) => (float) $line->debit - (float) $line->credit), 2);

            if (abs($movement) < 0.005) {
                continue;
            }

            $counterpartLines = $entry->lines->reject(fn (JournalEntryLine $line) => in_array($line->account_id, $cashAccountIds, true));
            $sectionKey = $this->classify($entry, $counterpartLines, $allCashEquivalentIds);
            $cashAccountNames = $cashLines
                ->map(fn (JournalEntryLine $line) => $line->account?->display_name)
                ->filter()
                ->unique()
                ->values()
                ->implode(', ');
            $counterpartNames = $counterpartLines
                ->map(fn (JournalEntryLine $line) => $line->account?->display_name)
                ->filter()
                ->unique()
                ->values()
                ->implode(', ');

            $row = [
                'date' => $entry->entry_date?->toDateString(),
                'journal' => $entry->journal_number,
                'description' => $entry->description,
                'reference' => $entry->reference_number,
                'source' => $entry->source_module,
                'cash_accounts' => $cashAccountNames,
                'counterpart_accounts' => $counterpartNames ?: 'Cash transfer',
                'inflow' => $movement > 0 ? $movement : 0.0,
                'outflow' => $movement < 0 ? abs($movement) : 0.0,
                'net' => $movement,
            ];

            $sections[$sectionKey]['rows'][] = $row;
            $sections[$sectionKey]['inflows'] = round($sections[$sectionKey]['inflows'] + $row['inflow'], 2);
            $sections[$sectionKey]['outflows'] = round($sections[$sectionKey]['outflows'] + $row['outflow'], 2);
            $sections[$sectionKey]['net'] = round($sections[$sectionKey]['net'] + $movement, 2);
        }

        return $this->payload($filters, $cashAccounts, $sections, $opening);
    }

    private function openingBalance(array $cashAccountIds, ?string $dateFrom): float
    {
        if (empty($cashAccountIds) || empty($dateFrom)) {
            return 0.0;
        }

        $priorDate = Carbon::parse($dateFrom)->subDay()->toDateString();

        $totals = JournalEntryLine::query()
            ->whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn ($query) => $query->ledgerAffecting()->whereDate('entry_date', '<=', $priorDate))
            ->selectRaw('SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->first();

        return round((float) ($totals->debit_total ?? 0) - (float) ($totals->credit_total ?? 0), 2);
    }

    private function classify(JournalEntry $entry, $counterpartLines, array $allCashEquivalentIds): string
    {
        if ($entry->source_module === 'OPENING_BALANCE') {
            return 'other';
        }

        $accounts = $counterpartLines->pluck('account')->filter();

        if ($accounts->isNotEmpty() && $accounts->every(fn (Account $account) => in_array($account->id, $allCashEquivalentIds, true))) {
            return 'other';
        }

        if ($accounts->contains(fn (Account $account) => $account->type === AccountType::EQUITY || $account->subtype === 'NON_CURRENT_LIABILITY')) {
            return 'financing';
        }

        if ($accounts->contains(fn (Account $account) => $account->type === AccountType::ASSET && $account->subtype === 'NON_CURRENT_ASSET' && ! $account->is_cash_account && ! $account->is_bank_account)) {
            return 'investing';
        }

        return 'operating';
    }

    private function payload(array $filters, $cashAccounts, array $sections, float $opening): array
    {
        $netChange = round(collect($sections)->sum('net'), 2);
        $closing = round($opening + $netChange, 2);

        return [
            'method' => 'Direct',
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'accounts' => $cashAccounts,
            'sections' => $sections,
            'opening_balance' => $opening,
            'net_change' => $netChange,
            'closing_balance' => $closing,
            'reconciles' => abs(($opening + $netChange) - $closing) < 0.005,
        ];
    }
}
