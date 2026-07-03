<?php

namespace Tests\Feature\Accounting;

use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\Accounting\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\FinancialReportService;
use App\Services\JournalEntryService;
use App\Services\TrialBalanceService;
use App\Services\YearEndClosingService;
use Database\Seeders\AccountingChartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 7 — Reports, closing controls, reopen workflow and the year-end closing
 * entry. Reports read posted journal lines only.
 */
class AccountingReportsClosingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountingChartSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    /** Post a balanced journal of given lines, return the posted entry. */
    private function postJournal(array $lines, ?string $date = null): JournalEntry
    {
        $entry = app(JournalEntryService::class)->createDraft([
            'entry_date' => $date ?? today()->toDateString(),
            'description' => 'Test cycle',
            'lines' => $lines,
        ]);

        return app(JournalEntryService::class)->post($entry, $this->user);
    }

    /** A full mini revenue/expense cycle so the books stay balanced. */
    private function seedRevenueAndExpense(float $revenue = 1000, float $expense = 400): void
    {
        // Dr Cash / Cr Consultation Revenue
        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => $revenue, 'credit' => 0],
            ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => $revenue],
        ]);
        // Dr Salaries Expense / Cr Cash
        $this->postJournal([
            ['account_id' => $this->accountId('5300'), 'debit' => $expense, 'credit' => 0],
            ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => $expense],
        ]);
    }

    public function test_trial_balance_debit_equals_credit(): void
    {
        $this->seedRevenueAndExpense();

        $tb = app(TrialBalanceService::class)->report();

        $this->assertTrue($tb['is_balanced']);
        $this->assertEqualsWithDelta($tb['total_debit'], $tb['total_credit'], 0.001);
    }

    public function test_general_ledger_uses_posted_entries_only(): void
    {
        // One posted, one left as draft.
        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => 500, 'credit' => 0],
            ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => 500],
        ]);
        app(JournalEntryService::class)->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Draft only',
            'lines' => [
                ['account_id' => $this->accountId('1110'), 'debit' => 999, 'credit' => 0],
                ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => 999],
            ],
        ]);

        $revenue = app(FinancialReportService::class)->profitLoss();
        // Only the posted 500 counts as revenue, not the 999 draft.
        $this->assertEqualsWithDelta(500, $revenue['revenue_total'], 0.001);
    }

    public function test_profit_loss_calculates_revenue_expenses_and_net(): void
    {
        $this->seedRevenueAndExpense(1000, 400);

        $pl = app(FinancialReportService::class)->profitLoss();

        $this->assertEqualsWithDelta(1000, $pl['revenue_total'], 0.001);
        $this->assertEqualsWithDelta(400, $pl['total_expenses'], 0.001);
        $this->assertEqualsWithDelta(600, $pl['net_profit'], 0.001);
    }

    public function test_balance_sheet_balances_with_current_year_earnings(): void
    {
        $this->seedRevenueAndExpense(1000, 400);

        $bs = app(FinancialReportService::class)->balanceSheet();

        $this->assertTrue($bs['is_balanced'], 'Balance sheet should balance: '.json_encode($bs['difference']));
        $this->assertEqualsWithDelta(600, $bs['current_year_earnings'], 0.001);
    }

    public function test_balance_sheet_includes_legacy_revenue_type_in_current_year_earnings(): void
    {
        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => 350, 'credit' => 0],
            ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => 350],
        ]);

        DB::table('accounts')
            ->whereIn('code', ['4000', '4100'])
            ->update(['type' => 'REVENUE']);

        $bs = app(FinancialReportService::class)->balanceSheet();

        $this->assertTrue($bs['is_balanced'], 'Balance sheet should include REVENUE aliases in earnings.');
        $this->assertEqualsWithDelta(350, $bs['total_assets'], 0.001);
        $this->assertEqualsWithDelta(350, $bs['current_year_earnings'], 0.001);
        $this->assertEqualsWithDelta(350, $bs['total_liabilities_equity'], 0.001);
    }

    public function test_closing_a_period_blocks_further_posting(): void
    {
        $period = AccountingPeriod::whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->first();
        app(AccountingPeriodService::class)->closePeriod($period, $this->user);

        $this->expectException(ValidationException::class);
        $this->postJournal([
            ['account_id' => $this->accountId('1110'), 'debit' => 50, 'credit' => 0],
            ['account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => 50],
        ]);
    }

    public function test_closing_fiscal_year_requires_all_periods_closed(): void
    {
        $year = FiscalYear::where('name', 'FY '.now()->year)->first();

        $this->expectException(ValidationException::class);
        app(AccountingPeriodService::class)->closeFiscalYear($year, $this->user);
    }

    public function test_reopen_period_requires_reason_and_logs(): void
    {
        $period = AccountingPeriod::whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->first();
        app(AccountingPeriodService::class)->closePeriod($period, $this->user);

        // No reason → rejected.
        try {
            app(AccountingPeriodService::class)->reopenPeriod($period->fresh(), $this->user, '   ');
            $this->fail('Expected ValidationException for missing reason.');
        } catch (ValidationException $e) {
            // expected
        }

        app(AccountingPeriodService::class)->reopenPeriod($period->fresh(), $this->user, 'Late adjustment needed');

        $this->assertSame(PeriodStatus::OPEN, $period->fresh()->status);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ACCOUNTING', 'event' => 'ACCOUNTING_PERIOD_REOPENED',
        ]);
    }

    public function test_period_cannot_be_reopened_while_fiscal_year_is_closed(): void
    {
        $year = FiscalYear::where('name', 'FY '.now()->year)->first();
        $period = $year->periods()->first();
        $year->periods()->update(['status' => PeriodStatus::CLOSED->value]);
        app(AccountingPeriodService::class)->closeFiscalYear($year->fresh(), $this->user);

        $this->expectException(ValidationException::class);
        app(AccountingPeriodService::class)->reopenPeriod($period->fresh(), $this->user, 'Need to adjust');
    }

    public function test_fiscal_year_can_be_reopened_with_reason_and_logs(): void
    {
        $year = FiscalYear::where('name', 'FY '.now()->year)->first();
        $year->periods()->update(['status' => PeriodStatus::CLOSED->value]);
        app(AccountingPeriodService::class)->closeFiscalYear($year->fresh(), $this->user);

        app(AccountingPeriodService::class)->reopenFiscalYear($year->fresh(), $this->user, 'Audit correction');

        $this->assertSame(PeriodStatus::OPEN, $year->fresh()->status);
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ACCOUNTING', 'event' => 'FISCAL_YEAR_REOPENED',
        ]);
    }

    public function test_year_end_closing_entry_is_created_once_and_moves_net_to_retained_earnings(): void
    {
        $this->seedRevenueAndExpense(1000, 400); // net profit 600
        $year = FiscalYear::where('name', 'FY '.now()->year)->first();

        $entry = app(YearEndClosingService::class)->close($year, $this->user);

        $this->assertSame(JournalEntryStatus::POSTED, $entry->status);
        $this->assertTrue($entry->is_balanced);

        // Retained Earnings (3200) credited by the net profit of 600.
        $reLine = $entry->lines->firstWhere('account_id', $this->accountId('3200'));
        $this->assertNotNull($reLine);
        $this->assertEqualsWithDelta(600, (float) $reLine->credit, 0.001);

        // Income/expense accounts are zeroed by the closing entry: P&L for postings
        // INCLUDING the closing entry nets to zero.
        $pl = app(FinancialReportService::class)->profitLoss();
        $this->assertEqualsWithDelta(0, $pl['net_profit'], 0.001);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ACCOUNTING', 'event' => 'YEAR_END_CLOSING_ENTRY_CREATED',
        ]);

        // Duplicate close is blocked.
        $this->expectException(ValidationException::class);
        app(YearEndClosingService::class)->close($year->fresh(), $this->user);
    }

    public function test_year_end_closing_entry_handles_a_net_loss(): void
    {
        $this->seedRevenueAndExpense(300, 800); // net loss 500
        $year = FiscalYear::where('name', 'FY '.now()->year)->first();

        $entry = app(YearEndClosingService::class)->close($year, $this->user);

        // Net loss → Retained Earnings debited 500.
        $reLine = $entry->lines->firstWhere('account_id', $this->accountId('3200'));
        $this->assertEqualsWithDelta(500, (float) $reLine->debit, 0.001);
        $this->assertTrue($entry->is_balanced);
    }
}
