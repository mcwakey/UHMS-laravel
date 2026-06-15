<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAdjustment;
use App\Models\BankStatementImport;
use App\Models\BankStatementLine;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\User;
use App\Services\BankAccountService;
use App\Services\BankMatchSuggestionService;
use App\Services\BankReconciliationAdjustmentPostingService;
use App\Services\BankReconciliationService;
use App\Services\BankStatementImportService;
use App\Services\JournalEntryService;
use App\Services\ModuleService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BankReconciliationPhaseBTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const PERMISSIONS = [
        'accounting.bank_accounts.view', 'accounting.bank_accounts.manage',
        'accounting.bank_statements.import', 'accounting.bank_statements.view', 'accounting.bank_statements.reject',
        'accounting.bank_reconciliation.view', 'accounting.bank_reconciliation.manage', 'accounting.bank_reconciliation.match',
        'accounting.bank_reconciliation.approve', 'accounting.bank_reconciliation.reopen', 'accounting.bank_reconciliation.reverse',
        'accounting.bank_adjustments.propose', 'accounting.bank_adjustments.approve', 'accounting.bank_adjustments.post',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed([ModuleSeeder::class, AccountingChartSeeder::class]);

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        $this->user = User::factory()->create();
        $this->user->givePermissionTo(self::PERMISSIONS);
        $this->actingAs($this->user);
    }

    /* ── Helpers ─────────────────────────────────────────────────── */

    private function bankGl(): Account
    {
        return Account::where('code', '1120')->firstOrFail();
    }

    private function bankAccount(bool $active = true): BankAccount
    {
        $account = app(BankAccountService::class)->create([
            'name' => 'Main Operating', 'bank_name' => 'GCB Bank', 'currency' => 'GHS',
            'gl_account_id' => $this->bankGl()->id, 'account_number' => '1234567890', 'opening_balance' => 0,
        ], $this->user);

        if (! $active) {
            $account->update(['is_active' => false]);
        }

        return $account->refresh();
    }

    private function csv(): string
    {
        return "Date,Reference,Description,Debit,Credit\n"
            . "2026-06-02,INV001,Patient deposit,,1500.00\n"
            . "2026-06-05,CHQ22,Supplier cheque,200.00,\n"
            . "notadate,,,,\n";
    }

    private function mapping(): array
    {
        return [
            'columns' => [
                'transaction_date' => 'date', 'reference' => 'reference', 'description' => 'description',
                'debit' => 'debit', 'credit' => 'credit',
            ],
            'date_format' => 'Y-m-d', 'has_header' => true,
        ];
    }

    private function importStatement(BankAccount $account): BankStatementImport
    {
        return app(BankStatementImportService::class)->import(
            $account, $this->csv(), $this->mapping(),
            ['original_filename' => 'june.csv', 'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
             'opening_balance' => 0, 'closing_balance' => 1300],
            $this->user,
        );
    }

    private function postedBankJournal(string $side, float $amount, string $date = '2026-06-03'): JournalEntry
    {
        $bank = $this->bankGl()->id;
        $other = Account::where('code', '4900')->value('id');
        $lines = $side === 'debit'
            ? [['account_id' => $bank, 'debit' => $amount, 'credit' => 0], ['account_id' => $other, 'debit' => 0, 'credit' => $amount]]
            : [['account_id' => $bank, 'debit' => 0, 'credit' => $amount], ['account_id' => $other, 'debit' => $amount, 'credit' => 0]];

        $entry = app(JournalEntryService::class)->createDraft([
            'entry_date' => $date, 'description' => 'Bank fixture', 'reference_number' => 'INV001', 'lines' => $lines,
        ]);

        return app(JournalEntryService::class)->post($entry, $this->user);
    }

    private function preparedReconciliation(BankAccount $account, float $statementClosing = 0): BankReconciliation
    {
        return app(BankReconciliationService::class)->prepare($account, [
            'period_start' => '2026-06-01', 'period_end' => '2026-06-30',
            'statement_opening_balance' => 0, 'statement_closing_balance' => $statementClosing,
        ], $this->user);
    }

    /* ── Tests ───────────────────────────────────────────────────── */

    public function test_bank_account_is_created_with_gl_mapping_and_masked_number(): void
    {
        $account = $this->bankAccount();

        $this->assertDatabaseHas('bank_accounts', ['id' => $account->id, 'gl_account_id' => $this->bankGl()->id]);
        $this->assertStringEndsWith('7890', $account->account_number_masked);
        $this->assertNotEquals('1234567890', $account->account_number_masked);
        $this->assertNotNull($account->account_number_hash);
    }

    public function test_unauthorized_user_cannot_manage_bank_accounts(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('admin.accounting.bank.accounts.index'))
            ->assertForbidden();
    }

    public function test_duplicate_bank_statement_file_is_rejected(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);

        $this->expectException(ValidationException::class);
        $this->importStatement($account);
    }

    public function test_csv_preview_writes_no_statement_lines(): void
    {
        $account = $this->bankAccount();

        $preview = app(BankStatementImportService::class)->preview($account, $this->csv(), $this->mapping(), $this->user);

        $this->assertSame(0, BankStatementLine::count());
        $this->assertCount(2, $preview['rows']);
        $this->assertCount(1, $preview['errors']);
    }

    public function test_csv_import_creates_statement_lines(): void
    {
        $account = $this->bankAccount();
        $import = $this->importStatement($account);

        $this->assertSame(2, $import->line_count);
        $this->assertSame(2, BankStatementLine::where('bank_account_id', $account->id)->count());
        $this->assertEqualsWithDelta(1500.0, (float) $import->total_credit, 0.001);
        $this->assertEqualsWithDelta(200.0, (float) $import->total_debit, 0.001);
    }

    public function test_invalid_csv_rows_are_reported(): void
    {
        $account = $this->bankAccount();
        $preview = app(BankStatementImportService::class)->preview($account, $this->csv(), $this->mapping(), $this->user);

        $this->assertSame(3, $preview['errors'][0]['line']);
    }

    public function test_duplicate_statement_lines_are_skipped(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);

        // A second file with one repeated line and one new line.
        $csv = "Date,Reference,Description,Debit,Credit\n"
            . "2026-06-02,INV001,Patient deposit,,1500.00\n"   // duplicate
            . "2026-06-09,INV777,New deposit,,400.00\n";        // new

        $import = app(BankStatementImportService::class)->import($account, $csv, $this->mapping(),
            ['original_filename' => 'june2.csv'], $this->user);

        $this->assertSame(1, $import->line_count);
    }

    public function test_match_suggestion_finds_exact_amount_and_reference(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);
        $this->postedBankJournal('debit', 1500.00); // matches the +1500 credit statement line, ref INV001

        $reconciliation = $this->preparedReconciliation($account, 1300);
        $line = BankStatementLine::where('credit_amount', 1500)->firstOrFail();

        $suggestions = app(BankMatchSuggestionService::class)->suggestForLine($reconciliation, $line);

        $this->assertNotEmpty($suggestions);
        $this->assertEqualsWithDelta(1500.0, $suggestions[0]['matched_amount'], 0.001);
        $this->assertGreaterThanOrEqual(90.0, $suggestions[0]['confidence_score']);
    }

    public function test_manual_match_links_statement_line_to_journal_line(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);
        $journal = $this->postedBankJournal('debit', 1500.00);
        $bookLine = $journal->lines->firstWhere('account_id', $this->bankGl()->id);

        $reconciliation = $this->preparedReconciliation($account, 1300);
        $line = BankStatementLine::where('credit_amount', 1500)->firstOrFail();

        app(BankReconciliationService::class)->match(
            $reconciliation, $line, \App\Models\JournalEntryLine::class, $bookLine->id, 1500.00, 'manual', $this->user,
        );

        $this->assertSame('matched', $line->refresh()->match_status);
        $this->assertDatabaseHas('bank_reconciliation_matches', [
            'bank_statement_line_id' => $line->id, 'matchable_id' => $bookLine->id, 'status' => 'active',
        ]);
    }

    public function test_statement_line_cannot_be_overmatched(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);
        $journal = $this->postedBankJournal('debit', 5000.00);
        $bookLine = $journal->lines->firstWhere('account_id', $this->bankGl()->id);
        $reconciliation = $this->preparedReconciliation($account, 1300);
        $line = BankStatementLine::where('credit_amount', 1500)->firstOrFail();

        $this->expectException(ValidationException::class);
        app(BankReconciliationService::class)->match(
            $reconciliation, $line, \App\Models\JournalEntryLine::class, $bookLine->id, 1500.01, 'manual', $this->user,
        );
    }

    public function test_match_can_be_reversed_before_approval(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);
        $journal = $this->postedBankJournal('debit', 1500.00);
        $bookLine = $journal->lines->firstWhere('account_id', $this->bankGl()->id);
        $reconciliation = $this->preparedReconciliation($account, 1300);
        $line = BankStatementLine::where('credit_amount', 1500)->firstOrFail();

        $match = app(BankReconciliationService::class)->match(
            $reconciliation, $line, \App\Models\JournalEntryLine::class, $bookLine->id, 1500.00, 'manual', $this->user,
        );
        app(BankReconciliationService::class)->unmatch($match, 'mistake', $this->user);

        $this->assertSame('reversed', $match->refresh()->status);
        $this->assertSame('unmatched', $line->refresh()->match_status);
    }

    public function test_approved_reconciliation_locks_matches(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);
        $journal = $this->postedBankJournal('debit', 1500.00);
        $bookLine = $journal->lines->firstWhere('account_id', $this->bankGl()->id);
        $reconciliation = $this->preparedReconciliation($account, 1300);
        $line = BankStatementLine::where('credit_amount', 1500)->firstOrFail();

        $match = app(BankReconciliationService::class)->match(
            $reconciliation, $line, \App\Models\JournalEntryLine::class, $bookLine->id, 1500.00, 'manual', $this->user,
        );
        app(BankReconciliationService::class)->approve($reconciliation, $this->user, 0.0, true);

        $this->expectException(ValidationException::class);
        app(BankReconciliationService::class)->unmatch($match, 'too late', $this->user);
    }

    public function test_bank_charge_adjustment_posts_balanced_journal(): void
    {
        $account = $this->bankAccount();
        $reconciliation = $this->preparedReconciliation($account, 0);
        $expense = Account::where('code', '5200')->value('id');

        $service = app(BankReconciliationAdjustmentPostingService::class);
        $adj = $service->propose($reconciliation, ['type' => 'bank_charge', 'amount' => 50, 'account_id' => $expense], $this->user);
        $service->approve($adj, $this->user);
        $adj = $service->post($adj, $this->user);

        $this->assertSame('posted', $adj->status);
        $journal = JournalEntry::find($adj->journal_entry_id);
        $this->assertNotNull($journal);
        $this->assertEqualsWithDelta((float) $journal->lines->sum('debit'), (float) $journal->lines->sum('credit'), 0.001);
        $this->assertEqualsWithDelta(50.0, (float) $journal->lines->where('account_id', $expense)->sum('debit'), 0.001);
        $this->assertEqualsWithDelta(50.0, (float) $journal->lines->where('account_id', $this->bankGl()->id)->sum('credit'), 0.001);
    }

    public function test_interest_income_adjustment_posts_balanced_journal(): void
    {
        $account = $this->bankAccount();
        $reconciliation = $this->preparedReconciliation($account, 0);
        $income = Account::where('code', '4900')->value('id');

        $service = app(BankReconciliationAdjustmentPostingService::class);
        $adj = $service->propose($reconciliation, ['type' => 'interest_income', 'amount' => 30, 'account_id' => $income], $this->user);
        $service->approve($adj, $this->user);
        $adj = $service->post($adj, $this->user);

        $journal = JournalEntry::find($adj->journal_entry_id);
        $this->assertEqualsWithDelta(30.0, (float) $journal->lines->where('account_id', $this->bankGl()->id)->sum('debit'), 0.001);
        $this->assertEqualsWithDelta(30.0, (float) $journal->lines->where('account_id', $income)->sum('credit'), 0.001);
    }

    public function test_reconciliation_calculates_statement_book_difference(): void
    {
        $account = $this->bankAccount();
        $reconciliation = $this->preparedReconciliation($account, 500); // no book entries, no outstanding

        $this->assertEqualsWithDelta(500.0, (float) $reconciliation->difference, 0.001);
    }

    public function test_reconciliation_cannot_approve_with_unexplained_difference(): void
    {
        $account = $this->bankAccount();
        $reconciliation = $this->preparedReconciliation($account, 500);

        $this->expectException(ValidationException::class);
        app(BankReconciliationService::class)->approve($reconciliation, $this->user, 0.0, false);
    }

    public function test_reconciliation_can_approve_when_difference_is_zero(): void
    {
        $account = $this->bankAccount();
        $reconciliation = $this->preparedReconciliation($account, 0);

        $approved = app(BankReconciliationService::class)->approve($reconciliation, $this->user, 0.0, false);

        $this->assertSame('approved', $approved->status);
        $this->assertNotNull($approved->approved_at);
    }

    public function test_reopening_requires_permission_and_reason(): void
    {
        $account = $this->bankAccount();
        $reconciliation = $this->preparedReconciliation($account, 0);
        app(BankReconciliationService::class)->approve($reconciliation, $this->user, 0.0, false);

        $this->expectException(ValidationException::class);
        app(BankReconciliationService::class)->reopen($reconciliation, '', $this->user);
    }

    public function test_activity_log_records_import_and_match_and_approval_events(): void
    {
        $account = $this->bankAccount();
        $this->importStatement($account);
        $reconciliation = $this->preparedReconciliation($account, 0);
        app(BankReconciliationService::class)->approve($reconciliation, $this->user, 0.0, false);

        $this->assertDatabaseHas('activity_log', ['event' => 'BANK_STATEMENT_IMPORTED']);
        $this->assertDatabaseHas('activity_log', ['event' => 'BANK_RECONCILIATION_PREPARED']);
        $this->assertDatabaseHas('activity_log', ['event' => 'BANK_RECONCILIATION_APPROVED']);
    }

    public function test_module_middleware_blocks_routes_when_advanced_accounting_disabled(): void
    {
        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->get(route('admin.accounting.bank.accounts.index'))->assertForbidden();
    }
}
