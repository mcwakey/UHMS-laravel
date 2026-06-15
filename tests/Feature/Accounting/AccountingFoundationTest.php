<?php

namespace Tests\Feature\Accounting;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalEntryStatus;
use App\Enums\Accounting\NormalBalance;
use App\Enums\Accounting\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingPeriodService;
use App\Services\JournalEntryService;
use Database\Seeders\AccountingChartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 1 — Accounting Foundation: chart of accounts, fiscal calendar and the
 * journal-entry engine (validation, posting, reversal, period locks, logging).
 */
class AccountingFoundationTest extends TestCase
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

    private function journals(): JournalEntryService
    {
        return app(JournalEntryService::class);
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    /** A balanced, non-control manual entry: Dr Salaries / Cr Cash. */
    private function balancedLines(float $amount = 100): array
    {
        return [
            ['account_id' => $this->accountId('5300'), 'debit' => $amount, 'credit' => 0],
            ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => $amount],
        ];
    }

    public function test_account_can_be_created(): void
    {
        $account = Account::create([
            'code' => '9100', 'name' => 'Test Asset', 'type' => AccountType::ASSET,
            'normal_balance' => AccountType::ASSET->normalBalance(), 'is_active' => true,
        ]);

        $this->assertDatabaseHas('accounts', ['code' => '9100', 'name' => 'Test Asset']);
        $this->assertTrue($account->is_active);
    }

    public function test_duplicate_account_code_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        Account::create([
            'code' => '1110', 'name' => 'Duplicate Cash', 'type' => AccountType::ASSET,
            'normal_balance' => NormalBalance::DEBIT,
        ]);
    }

    public function test_account_normal_balance_is_set_correctly(): void
    {
        $this->assertSame(NormalBalance::DEBIT, Account::where('code', '1110')->first()->normal_balance);   // asset
        $this->assertSame(NormalBalance::CREDIT, Account::where('code', '4100')->first()->normal_balance);  // income
        $this->assertSame(NormalBalance::DEBIT, Account::where('code', '5300')->first()->normal_balance);   // expense
        $this->assertSame(NormalBalance::CREDIT, Account::where('code', '2110')->first()->normal_balance);  // liability
    }

    public function test_parent_and_child_accounts_work(): void
    {
        $parent = Account::where('code', '1100')->first();   // Cash and Bank
        $child = Account::where('code', '1110')->first();    // Cash on Hand

        $this->assertSame($parent->id, $child->parent_id);
        $this->assertTrue($parent->children->contains('id', $child->id));
    }

    public function test_fiscal_year_can_be_created(): void
    {
        $year = app(AccountingPeriodService::class)->createFiscalYear([
            'name' => 'FY 2099', 'start_date' => '2099-01-01', 'end_date' => '2099-12-31',
        ], $this->user);

        $this->assertDatabaseHas('fiscal_years', ['name' => 'FY 2099', 'status' => PeriodStatus::OPEN->value]);
        $this->assertTrue($year->status === PeriodStatus::OPEN);
    }

    public function test_accounting_period_can_be_created(): void
    {
        $year = FiscalYear::create([
            'name' => 'FY 2099', 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => PeriodStatus::OPEN,
        ]);

        $period = app(AccountingPeriodService::class)->createPeriod([
            'fiscal_year_id' => $year->id, 'name' => 'Jan 2099',
            'start_date' => '2099-01-01', 'end_date' => '2099-01-31',
        ], $this->user);

        $this->assertDatabaseHas('accounting_periods', ['id' => $period->id, 'fiscal_year_id' => $year->id]);
    }

    public function test_period_must_be_inside_fiscal_year(): void
    {
        $year = FiscalYear::create([
            'name' => 'FY 2099', 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => PeriodStatus::OPEN,
        ]);

        $this->expectException(ValidationException::class);
        app(AccountingPeriodService::class)->createPeriod([
            'fiscal_year_id' => $year->id, 'name' => 'Out of range',
            'start_date' => '2100-01-01', 'end_date' => '2100-01-31',
        ], $this->user);
    }

    public function test_draft_journal_can_be_created(): void
    {
        $entry = $this->journals()->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Test draft',
            'lines' => $this->balancedLines(),
        ]);

        $this->assertSame(JournalEntryStatus::DRAFT, $entry->status);
        $this->assertCount(2, $entry->lines);
    }

    public function test_unbalanced_journal_cannot_be_posted(): void
    {
        $this->expectException(ValidationException::class);
        $this->journals()->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Unbalanced',
            'lines' => [
                ['account_id' => $this->accountId('5300'), 'debit' => 100, 'credit' => 0],
                ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 60],
            ],
        ]);
    }

    public function test_journal_with_one_line_cannot_be_posted(): void
    {
        $this->expectException(ValidationException::class);
        $this->journals()->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Single line',
            'lines' => [['account_id' => $this->accountId('5300'), 'debit' => 100, 'credit' => 0]],
        ]);
    }

    public function test_journal_line_cannot_have_both_debit_and_credit(): void
    {
        $this->expectException(ValidationException::class);
        $this->journals()->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Both sides',
            'lines' => [
                ['account_id' => $this->accountId('5300'), 'debit' => 100, 'credit' => 100],
                ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 100],
            ],
        ]);
    }

    public function test_journal_line_cannot_have_neither_debit_nor_credit(): void
    {
        $this->expectException(ValidationException::class);
        $this->journals()->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Empty amounts',
            'lines' => [
                ['account_id' => $this->accountId('5300'), 'debit' => 0, 'credit' => 0],
                ['account_id' => $this->accountId('1110'), 'debit' => 0, 'credit' => 0],
            ],
        ]);
    }

    public function test_balanced_journal_can_be_posted(): void
    {
        $entry = $this->journals()->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Balanced',
            'lines' => $this->balancedLines(),
        ]);

        $posted = $this->journals()->post($entry, $this->user);

        $this->assertSame(JournalEntryStatus::POSTED, $posted->status);
        $this->assertNotNull($posted->posted_at);
        $this->assertTrue($posted->is_balanced);
    }

    public function test_posted_journal_cannot_be_edited(): void
    {
        $entry = $this->journals()->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'To post', 'lines' => $this->balancedLines(),
        ]);
        $this->journals()->post($entry, $this->user);

        $this->expectException(ValidationException::class);
        $this->journals()->updateDraft($entry->fresh(), [
            'entry_date' => today()->toDateString(), 'description' => 'Edited', 'lines' => $this->balancedLines(200),
        ]);
    }

    public function test_posted_journal_can_be_reversed_and_swaps_debit_and_credit(): void
    {
        $entry = $this->journals()->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Reversible', 'lines' => $this->balancedLines(150),
        ]);
        $this->journals()->post($entry, $this->user);

        $reversal = $this->journals()->reverse($entry->fresh(), 'Correcting an error', $this->user);

        $this->assertSame(JournalEntryStatus::REVERSED, $entry->fresh()->status);
        $this->assertSame($entry->id, $reversal->reversed_entry_id);

        // Original Dr 5300 150 / Cr 1110 150 → reversal Cr 5300 150 / Dr 1110 150.
        $origDebitLine = $entry->fresh()->lines->firstWhere('account_id', $this->accountId('5300'));
        $revSalaryLine = $reversal->lines->firstWhere('account_id', $this->accountId('5300'));
        $this->assertEquals(150, (float) $origDebitLine->debit);
        $this->assertEquals(150, (float) $revSalaryLine->credit);
        $this->assertEquals(0, (float) $revSalaryLine->debit);

        // Reversal is itself balanced.
        $this->assertEqualsWithDelta($reversal->total_debit, $reversal->total_credit, 0.001);
    }

    public function test_reversal_requires_a_reason(): void
    {
        $entry = $this->journals()->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Reversible', 'lines' => $this->balancedLines(),
        ]);
        $this->journals()->post($entry, $this->user);

        $this->expectException(ValidationException::class);
        $this->journals()->reverse($entry->fresh(), '   ', $this->user);
    }

    public function test_posting_into_closed_period_is_blocked(): void
    {
        $period = AccountingPeriod::query()
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->first();
        app(AccountingPeriodService::class)->closePeriod($period, $this->user);

        $this->expectException(ValidationException::class);
        $this->journals()->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Into closed period', 'lines' => $this->balancedLines(),
        ]);
    }

    public function test_posting_into_closed_fiscal_year_is_blocked(): void
    {
        $year = FiscalYear::where('name', 'FY ' . now()->year)->first();
        $year->periods()->update(['status' => PeriodStatus::CLOSED->value]);
        app(AccountingPeriodService::class)->closeFiscalYear($year, $this->user);

        $this->expectException(ValidationException::class);
        $this->journals()->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Into closed year', 'lines' => $this->balancedLines(),
        ]);
    }

    public function test_accounting_actions_are_logged(): void
    {
        $entry = $this->journals()->createDraft([
            'entry_date' => today()->toDateString(), 'description' => 'Logged', 'lines' => $this->balancedLines(),
        ]);
        $this->journals()->post($entry, $this->user);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ACCOUNTING',
            'event' => 'JOURNAL_ENTRY_POSTED',
            'subject_type' => JournalEntry::class,
            'subject_id' => $entry->id,
        ]);
    }
}
