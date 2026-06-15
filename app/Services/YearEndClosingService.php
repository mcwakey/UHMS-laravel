<?php

namespace App\Services;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Year-end closing entry.
 *
 * Closes the fiscal year's INCOME and EXPENSE accounts into Retained Earnings
 * via a single posted, balanced journal entry:
 *   - each income/expense account is zeroed by an opposite-side line
 *   - the net (profit/loss) is carried to Retained Earnings
 *
 * Retained Earnings is credited on a profit and debited on a loss. The entry is
 * dated on the fiscal year's end date and must post into an open period (so the
 * closing entry is created before the year's periods/fiscal year are closed).
 * A fiscal year can only have one (non-reversed) closing entry.
 */
class YearEndClosingService
{
    public const SOURCE_MODULE = 'YEAR_END_CLOSING';

    public function __construct(
        protected JournalEntryService $journals,
        protected AccountingSettingsService $settings,
    ) {}

    public function hasClosingEntry(FiscalYear $year): bool
    {
        return JournalEntry::query()
            ->where('source_module', self::SOURCE_MODULE)
            ->where('reference_type', FiscalYear::class)
            ->where('reference_id', $year->id)
            ->whereIn('status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::REVERSED->value])
            ->exists();
    }

    public function close(FiscalYear $year, User $user): JournalEntry
    {
        if ($this->hasClosingEntry($year)) {
            throw ValidationException::withMessages([
                'fiscal_year' => 'This fiscal year already has a year-end closing entry.',
            ]);
        }

        $retainedEarnings = $this->settings->account('retained_earnings_account_id');
        if (! $retainedEarnings) {
            throw ValidationException::withMessages([
                'fiscal_year' => 'Configure the Retained Earnings account before running the year-end close.',
            ]);
        }

        return DB::transaction(function () use ($year, $user, $retainedEarnings) {
            $balances = $this->incomeAndExpenseBalances($year);

            $lines = [];
            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($balances as $row) {
                $diff = round((float) $row->total_debit - (float) $row->total_credit, 2); // + = net debit
                if (abs($diff) < 0.005) {
                    continue;
                }

                if ($diff > 0) {
                    // Net debit balance (typical expense) → credit it to zero out.
                    $lines[] = ['account_id' => $row->account_id, 'debit' => 0, 'credit' => $diff, 'description' => 'Year-end close'];
                    $totalCredit += $diff;
                } else {
                    // Net credit balance (typical income) → debit it to zero out.
                    $lines[] = ['account_id' => $row->account_id, 'debit' => -$diff, 'credit' => 0, 'description' => 'Year-end close'];
                    $totalDebit += -$diff;
                }
            }

            if (empty($lines)) {
                throw ValidationException::withMessages([
                    'fiscal_year' => 'There is no income or expense activity to close for this fiscal year.',
                ]);
            }

            // Balance the net profit/loss into Retained Earnings.
            $net = round($totalDebit - $totalCredit, 2);
            if ($net > 0) {
                // Income (credits) exceeded expenses (debits) → profit → credit Retained Earnings.
                $lines[] = ['account_id' => $retainedEarnings->id, 'debit' => 0, 'credit' => $net, 'description' => 'Net profit to retained earnings'];
            } elseif ($net < 0) {
                $lines[] = ['account_id' => $retainedEarnings->id, 'debit' => -$net, 'credit' => 0, 'description' => 'Net loss to retained earnings'];
            }

            $entry = $this->journals->createDraft([
                'entry_date' => $year->end_date->toDateString(),
                'description' => 'Year-end closing entry for ' . $year->name,
                'source_module' => self::SOURCE_MODULE,
                'reference_type' => FiscalYear::class,
                'reference_id' => $year->id,
                'allow_control_accounts' => true,
                'lines' => $lines,
            ]);

            $entry = $this->journals->post($entry, $user);

            app(ActivityLogService::class)->log(LogModule::ACCOUNTING, 'YEAR_END_CLOSING_ENTRY_CREATED', [
                'severity' => LogSeverity::WARNING,
                'fiscal_year_id' => $year->id,
                'journal_entry_id' => $entry->id,
                'net_result' => $net,
                'result_type' => $net >= 0 ? 'profit' : 'loss',
            ], $entry, 'Year-end closing entry created for ' . $year->name);

            return $entry;
        });
    }

    /**
     * Posted income/expense account balances for the fiscal year.
     *
     * @return \Illuminate\Support\Collection<int, object{account_id:int, total_debit:string, total_credit:string}>
     */
    protected function incomeAndExpenseBalances(FiscalYear $year)
    {
        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereIn('journal_entries.status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::REVERSED->value])
            ->where('journal_entries.fiscal_year_id', $year->id)
            ->whereIn('accounts.type', [AccountType::INCOME->value, AccountType::EXPENSE->value])
            ->groupBy('journal_entry_lines.account_id')
            ->selectRaw('journal_entry_lines.account_id, SUM(journal_entry_lines.debit) as total_debit, SUM(journal_entry_lines.credit) as total_credit')
            ->get();
    }
}
