<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountingAccountMapping;
use App\Models\AccountingPostingAttempt;
use App\Models\FinancialEntry;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\BasicAccountingBackfillService;
use App\Services\BasicAccountingPostingService;
use App\Services\ModuleService;
use Database\Seeders\AccountCategorySeeder;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\AccountingPostingTemplateSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BasicAccountingPostingBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            ModuleSeeder::class,
            AccountCategorySeeder::class,
            AccountingChartSeeder::class,
            AccountingPostingTemplateSeeder::class,
        ]);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_unapproved_entry_cannot_post(): void
    {
        $entry = $this->entry('income', approved: false);
        $this->mappings($entry, '4900');

        $result = app(BasicAccountingPostingService::class)->post($entry, $this->user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('approved', $result['error']);
        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('accounting_posting_attempts', 0);
    }

    public function test_approved_income_posts_balanced_cash_debit_and_income_credit(): void
    {
        $entry = $this->entry('income');
        $this->mappings($entry, '4900');

        $result = app(BasicAccountingPostingService::class)->post($entry, $this->user);

        $this->assertTrue($result['success'], $result['error'] ?? '');
        $journal = $result['journal']->load('lines');
        $this->assertTrue($journal->is_balanced);
        $this->assertEquals(125.50, (float) $journal->lines->firstWhere('account_id', $this->accountId('1110'))->debit);
        $this->assertEquals(125.50, (float) $journal->lines->firstWhere('account_id', $this->accountId('4900'))->credit);
        $this->assertSame('posted', $entry->fresh()->accounting_status);
    }

    public function test_approved_expense_posts_expense_debit_and_bank_credit(): void
    {
        $entry = $this->entry('expense', paymentMethod: 'bank_transfer');
        $this->mappings($entry, '5500', '1120');

        $result = app(BasicAccountingPostingService::class)->post($entry, $this->user);

        $this->assertTrue($result['success'], $result['error'] ?? '');
        $journal = $result['journal']->load('lines');
        $this->assertEquals(125.50, (float) $journal->lines->firstWhere('account_id', $this->accountId('5500'))->debit);
        $this->assertEquals(125.50, (float) $journal->lines->firstWhere('account_id', $this->accountId('1120'))->credit);
    }

    public function test_duplicate_post_is_idempotent(): void
    {
        $entry = $this->entry('income');
        $this->mappings($entry, '4900');
        $service = app(BasicAccountingPostingService::class);

        $first = $service->post($entry, $this->user);
        $second = $service->post($entry->fresh(), $this->user);

        $this->assertTrue($first['success']);
        $this->assertTrue($second['success']);
        $this->assertSame($first['journal']->id, $second['journal']->id);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('accounting_posting_attempts', 1);
    }

    public function test_missing_mapping_retains_failed_attempt_and_entry_error(): void
    {
        $entry = $this->entry('income');

        $result = app(BasicAccountingPostingService::class)->post($entry, $this->user);

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $entry->fresh()->accounting_status);
        $this->assertNotNull($entry->fresh()->accounting_error);
        $this->assertDatabaseHas('accounting_posting_attempts', [
            'source_id' => $entry->id,
            'posting_type' => 'basic_income',
            'status' => 'failed',
        ]);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_advanced_module_disabled_keeps_basic_entry_without_posting(): void
    {
        $entry = $this->entry('income');
        $this->mappings($entry, '4900');
        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $result = app(BasicAccountingPostingService::class)->post($entry, $this->user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', $result['error']);
        $this->assertSame('eligible', $entry->fresh()->accounting_status);
        $this->assertDatabaseCount('journal_entries', 0);
    }

    public function test_reversal_links_reversal_journal_and_swaps_lines(): void
    {
        $entry = $this->entry('expense');
        $this->mappings($entry, '5500');
        $service = app(BasicAccountingPostingService::class);
        $posted = $service->post($entry, $this->user);

        $result = $service->reverse($entry->fresh(), 'Wrong operating expense', $this->user);

        $this->assertTrue($result['success'], $result['error'] ?? '');
        $reversed = $entry->fresh();
        $this->assertSame('reversed', $reversed->accounting_status);
        $this->assertNotNull($reversed->reversal_journal_entry_id);
        $this->assertSame('reversed', AccountingPostingAttempt::first()->status);
        $original = $posted['journal']->load('lines');
        $reversal = JournalEntry::findOrFail($reversed->reversal_journal_entry_id)->load('lines');
        $this->assertEquals((float) $original->lines->first()->debit, (float) $reversal->lines->first()->credit);
    }

    public function test_posted_entry_cannot_be_deleted_or_silently_changed(): void
    {
        $entry = $this->entry('income');
        $this->mappings($entry, '4900');
        app(BasicAccountingPostingService::class)->post($entry, $this->user);

        $this->expectException(\InvalidArgumentException::class);
        app(AccountingService::class)->deleteEntry($entry->fresh());
    }

    public function test_backfill_dry_run_creates_no_journals_or_attempts(): void
    {
        $entry = $this->entry('income');
        $this->mappings($entry, '4900');

        $this->artisan('accounting:basic-entries-post-to-gl', [
            '--dry-run' => true,
            '--entry-id' => $entry->id,
        ])->assertSuccessful();

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('accounting_posting_attempts', 0);
    }

    public function test_batch_execution_continues_after_an_entry_failure(): void
    {
        $eligible = $this->entry('income');
        $this->mappings($eligible, '4900');
        $blocked = $this->entry('expense');

        $totals = app(BasicAccountingBackfillService::class)->execute([
            'entry_id' => [$eligible->id, $blocked->id],
        ], $this->user, 10);

        $this->assertSame(2, $totals['selected']);
        $this->assertSame(1, $totals['posted']);
        $this->assertSame(1, $totals['failed']);
        $this->assertSame('posted', $eligible->fresh()->accounting_status);
        $this->assertSame('failed', $blocked->fresh()->accounting_status);
    }

    public function test_phase_a_pages_are_permission_and_module_gated(): void
    {
        foreach (['accounting.basic.batch.view', 'accounting.posting_templates.view'] as $permission) {
            $this->user->givePermissionTo(Permission::findOrCreate($permission));
        }

        $this->get(route('admin.accounting.basic-bridge.index'))->assertOk();
        $this->get(route('admin.accounting.posting-templates.index'))->assertOk();

        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->get(route('admin.accounting.basic-bridge.index'))->assertForbidden();
        $this->get(route('admin.accounting.posting-templates.index'))->assertForbidden();
    }

    public function test_posting_and_reversal_actions_are_audited(): void
    {
        $entry = $this->entry('income');
        $this->mappings($entry, '4900');
        $service = app(BasicAccountingPostingService::class);
        $service->post($entry, $this->user);
        $service->reverse($entry->fresh(), 'Audit reversal', $this->user);

        $this->assertDatabaseHas('activity_log', ['log_name' => 'ACCOUNTING', 'event' => 'BASIC_ENTRY_POSTED_TO_GL']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'ACCOUNTING', 'event' => 'BASIC_ENTRY_POSTING_REVERSED']);
    }

    private function entry(string $type, bool $approved = true, string $paymentMethod = 'cash'): FinancialEntry
    {
        $category = AccountCategory::where('type', $type)->firstOrFail();

        return FinancialEntry::create([
            'entry_number' => 'FIN-'.strtoupper($type).'-'.uniqid(),
            'category_id' => $category->id,
            'type' => $type,
            'amount' => 125.50,
            'payment_method' => $paymentMethod,
            'description' => 'Phase A bridge test',
            'entry_date' => today(),
            'recorded_by' => $this->user->id,
            'approved_by' => $approved ? $this->user->id : null,
            'approval_status' => $approved ? 'approved' : 'pending',
            'accounting_status' => $approved ? 'eligible' : 'pending',
            'posting_version' => 1,
        ]);
    }

    private function mappings(FinancialEntry $entry, string $categoryAccountCode, string $paymentAccountCode = '1110'): void
    {
        $type = $entry->type->value;
        AccountingAccountMapping::create([
            'mapping_scope' => "basic_{$type}_category",
            'mapping_key' => 'category_id',
            'mapping_value' => (string) $entry->category_id,
            'account_id' => $this->accountId($categoryAccountCode),
            'effective_from' => '2000-01-01',
            'priority' => 10,
            'is_active' => true,
        ]);
        AccountingAccountMapping::create([
            'mapping_scope' => 'basic_payment_method',
            'mapping_key' => 'method',
            'mapping_value' => $entry->payment_method->value,
            'account_id' => $this->accountId($paymentAccountCode),
            'effective_from' => '2000-01-01',
            'priority' => 10,
            'is_active' => true,
        ]);
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }
}
