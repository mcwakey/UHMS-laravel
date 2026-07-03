<?php

namespace App\Services;

use App\Enums\Accounting\AccountType;
use App\Models\Account;
use App\Models\Department;
use App\Models\JournalEntryLine;
use Illuminate\Support\Carbon;

/**
 * GL-derived financial statements (Phase 7): Profit & Loss, Balance Sheet,
 * Cashbook, and Revenue/Expense by department. All totals come from POSTED
 * journal entry lines only (`ledgerAffecting`) — never drafts/cancelled.
 */
class FinancialReportService
{
    /**
     * Net movement per account for a date range / fiscal year.
     * Returns Collection keyed by account_id => {debit, credit}.
     */
    private function lineTotals(array $filters, bool $cumulative = false): \Illuminate\Support\Collection
    {
        return JournalEntryLine::query()
            ->selectRaw('account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->whereHas('journalEntry', function ($q) use ($filters, $cumulative) {
                $q->ledgerAffecting()
                    ->when(! $cumulative && ($filters['date_from'] ?? null), fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
                    ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '<=', $d))
                    ->when(! $cumulative && ($filters['fiscal_year_id'] ?? null), fn ($q, $id) => $q->where('fiscal_year_id', $id));
            })
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');
    }

    /** Signed balance for an account given its type (revenue/equity/liability = credit-normal). */
    private function signedBalance(Account $account, float $debit, float $credit): float
    {
        return $account->normal_balance->value === 'credit'
            ? round($credit - $debit, 2)
            : round($debit - $credit, 2);
    }

    /* ===================== Profit & Loss ===================== */

    public function profitLoss(array $filters = []): array
    {
        $totals = $this->lineTotals($filters);
        $accounts = Account::query()->whereRaw('UPPER(type) IN (?, ?)', [AccountType::INCOME->value, AccountType::EXPENSE->value])
            ->whereIn('id', $totals->keys())->orderBy('code')->get();

        $sections = [
            'revenue' => ['label' => __('accounting.revenue'), 'rows' => [], 'total' => 0.0],
            'cogs' => ['label' => __('accounting.cost_of_goods_sold'), 'rows' => [], 'total' => 0.0],
            'operating' => ['label' => __('accounting.operating_expenses'), 'rows' => [], 'total' => 0.0],
            'admin' => ['label' => __('accounting.administrative_expenses'), 'rows' => [], 'total' => 0.0],
            'finance' => ['label' => __('accounting.finance_costs'), 'rows' => [], 'total' => 0.0],
        ];

        foreach ($accounts as $account) {
            $t = $totals->get($account->id);
            $amount = $this->signedBalance($account, (float) $t->debit_total, (float) $t->credit_total);
            if (abs($amount) < 0.005) {
                continue;
            }

            $type = $this->accountType($account);
            $key = match (true) {
                $type === AccountType::INCOME->value => 'revenue',
                $account->subtype === 'COST_OF_SALES' => 'cogs',
                $account->subtype === 'ADMIN_EXPENSE' => 'admin',
                $account->subtype === 'FINANCE_COST' => 'finance',
                default => 'operating',
            };
            $sections[$key]['rows'][] = ['code' => $account->code, 'name' => $account->name, 'amount' => $amount];
            $sections[$key]['total'] = round($sections[$key]['total'] + $amount, 2);
        }

        $revenue = $sections['revenue']['total'];
        $cogs = $sections['cogs']['total'];
        $grossProfit = round($revenue - $cogs, 2);
        $totalExpenses = round($cogs + $sections['operating']['total'] + $sections['admin']['total'] + $sections['finance']['total'], 2);
        $netProfit = round($revenue - $totalExpenses, 2);

        return [
            'sections' => $sections,
            'revenue_total' => $revenue,
            'cogs_total' => $cogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];
    }

    /** Net profit only (for balance sheet current-year earnings). */
    public function netProfit(array $filters = []): float
    {
        return $this->profitLoss($filters)['net_profit'];
    }

    /* ===================== Balance Sheet ===================== */

    public function balanceSheet(array $filters = []): array
    {
        $asOf = ! empty($filters['date_to']) ? Carbon::parse($filters['date_to']) : Carbon::today();
        $totals = $this->lineTotals(['date_to' => $asOf->toDateString()], cumulative: true);

        $accounts = Account::query()
            ->whereRaw('UPPER(type) IN (?, ?, ?)', [AccountType::ASSET->value, AccountType::LIABILITY->value, AccountType::EQUITY->value])
            ->whereIn('id', $totals->keys())->orderBy('code')->get();

        $groups = [
            'assets' => ['label' => __('accounting.assets'), 'rows' => [], 'total' => 0.0],
            'liabilities' => ['label' => __('accounting.liabilities'), 'rows' => [], 'total' => 0.0],
            'equity' => ['label' => __('accounting.equity'), 'rows' => [], 'total' => 0.0],
        ];

        foreach ($accounts as $account) {
            $t = $totals->get($account->id);
            $amount = $this->signedBalance($account, (float) $t->debit_total, (float) $t->credit_total);
            if (abs($amount) < 0.005) {
                continue;
            }
            $key = match ($this->accountType($account)) {
                AccountType::ASSET->value => 'assets',
                AccountType::LIABILITY->value => 'liabilities',
                default => 'equity',
            };
            $groups[$key]['rows'][] = ['code' => $account->code, 'name' => $account->name, 'subtype' => $account->subtype, 'amount' => $amount];
            $groups[$key]['total'] = round($groups[$key]['total'] + $amount, 2);
        }

        // Current-year earnings (P&L for the year to as_of) added to equity if not yet closed.
        $yearStart = $asOf->copy()->startOfYear()->toDateString();
        $currentEarnings = $this->netProfit(['date_from' => $yearStart, 'date_to' => $asOf->toDateString()]);
        if (abs($currentEarnings) >= 0.005) {
            $groups['equity']['rows'][] = ['code' => '—', 'name' => 'Current Year Earnings', 'subtype' => 'EQUITY', 'amount' => $currentEarnings];
            $groups['equity']['total'] = round($groups['equity']['total'] + $currentEarnings, 2);
        }

        $assets = $groups['assets']['total'];
        $liabEquity = round($groups['liabilities']['total'] + $groups['equity']['total'], 2);

        return [
            'as_of' => $asOf->toDateString(),
            'groups' => $groups,
            'total_assets' => $assets,
            'total_liabilities_equity' => $liabEquity,
            'current_year_earnings' => $currentEarnings,
            'is_balanced' => abs($assets - $liabEquity) < 0.01,
            'difference' => round($assets - $liabEquity, 2),
        ];
    }

    /* ===================== Cashbook ===================== */

    public function cashbook(array $filters = []): array
    {
        $cashAccounts = Account::query()
            ->where(fn ($q) => $q->where('is_cash_account', true)->orWhere('is_bank_account', true))
            ->when($filters['account_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->orderBy('code')->get();

        $accountIds = $cashAccounts->pluck('id');

        // Opening balance (before date_from) per the cash/bank accounts.
        $opening = 0.0;
        if (! empty($filters['date_from'])) {
            $priorTotals = $this->lineTotals(['date_to' => Carbon::parse($filters['date_from'])->subDay()->toDateString()], cumulative: true);
            foreach ($cashAccounts as $a) {
                if ($t = $priorTotals->get($a->id)) {
                    $opening += $this->signedBalance($a, (float) $t->debit_total, (float) $t->credit_total);
                }
            }
        }
        $opening = round($opening, 2);

        $lines = JournalEntryLine::query()
            ->whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($q) use ($filters) {
                $q->ledgerAffecting()
                    ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
                    ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '<=', $d));
            })
            ->with(['journalEntry:id,journal_number,entry_date,reference_number,source_module,description', 'account:id,code,name'])
            ->get()
            ->sortBy(fn ($l) => [optional($l->journalEntry)->entry_date, $l->journalEntry?->id])
            ->values();

        $running = $opening;
        $rows = [];
        $in = 0.0;
        $out = 0.0;
        foreach ($lines as $l) {
            $moneyIn = round((float) $l->debit, 2);
            $moneyOut = round((float) $l->credit, 2);
            $running = round($running + $moneyIn - $moneyOut, 2);
            $in += $moneyIn;
            $out += $moneyOut;
            $rows[] = [
                'date' => optional($l->journalEntry?->entry_date)->format('d M Y'),
                'journal' => $l->journalEntry?->journal_number,
                'account' => $l->account?->code.' '.$l->account?->name,
                'description' => $l->description ?: $l->journalEntry?->description,
                'reference' => $l->journalEntry?->reference_number,
                'source' => $l->journalEntry?->source_module,
                'money_in' => $moneyIn,
                'money_out' => $moneyOut,
                'balance' => $running,
            ];
        }

        return [
            'accounts' => $cashAccounts,
            'opening_balance' => $opening,
            'closing_balance' => $running,
            'total_in' => round($in, 2),
            'total_out' => round($out, 2),
            'net_movement' => round($in - $out, 2),
            'rows' => $rows,
        ];
    }

    /* ===================== Revenue / Expense by Department ===================== */

    public function byDepartment(string $accountType, array $filters = []): array
    {
        $rows = JournalEntryLine::query()
            ->selectRaw('department_id, account_id, SUM(debit) as debit_total, SUM(credit) as credit_total')
            ->whereHas('account', fn ($q) => $q->whereRaw('UPPER(type) = ?', [strtoupper($accountType)]))
            ->whereHas('journalEntry', function ($q) use ($filters) {
                $q->ledgerAffecting()
                    ->when($filters['date_from'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '>=', $d))
                    ->when($filters['date_to'] ?? null, fn ($q, $d) => $q->whereDate('entry_date', '<=', $d));
            })
            ->groupBy('department_id', 'account_id')
            ->with('account:id,code,name,normal_balance')
            ->get();

        $departments = Department::pluck('name', 'id');
        $byDept = [];
        $grand = 0.0;

        foreach ($rows as $row) {
            $account = $row->account;
            if (! $account) {
                continue;
            }
            $amount = $this->signedBalance($account, (float) $row->debit_total, (float) $row->credit_total);
            if (abs($amount) < 0.005) {
                continue;
            }
            $deptName = $row->department_id ? ($departments[$row->department_id] ?? 'Dept #'.$row->department_id) : 'Unassigned';
            $byDept[$deptName] ??= ['department' => $deptName, 'total' => 0.0, 'accounts' => []];
            $byDept[$deptName]['accounts'][] = ['code' => $account->code, 'name' => $account->name, 'amount' => $amount];
            $byDept[$deptName]['total'] = round($byDept[$deptName]['total'] + $amount, 2);
            $grand = round($grand + $amount, 2);
        }

        usort($byDept, fn ($a, $b) => $b['total'] <=> $a['total']);

        return ['departments' => array_values($byDept), 'grand_total' => $grand, 'date_from' => $filters['date_from'] ?? null, 'date_to' => $filters['date_to'] ?? null];
    }

    private function accountType(Account $account): string
    {
        $type = $account->getRawOriginal('type') ?? $account->type;

        return $type instanceof AccountType
            ? $type->value
            : strtoupper((string) $type);
    }
}
