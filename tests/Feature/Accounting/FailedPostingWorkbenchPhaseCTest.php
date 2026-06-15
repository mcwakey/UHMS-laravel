<?php

namespace Tests\Feature\Accounting;

use App\Enums\Accounting\JournalEntryStatus;
use App\Models\Account;
use App\Models\AccountCategory;
use App\Models\AccountingAccountMapping;
use App\Models\AccountingPostingAttempt;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAdjustment;
use App\Models\FinancialEntry;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\User;
use App\Services\AccountingCloseReadinessService;
use App\Services\AccountingPostingAttemptService;
use App\Services\AccountingPostingSourceLinkService;
use App\Services\BankAccountService;
use App\Services\BankReconciliationAdjustmentPostingService;
use App\Services\BankReconciliationService;
use App\Services\FailedPostingWorkbenchService;
use App\Services\JournalEntryService;
use App\Services\ModuleService;
use Database\Seeders\AccountCategorySeeder;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\AccountingPostingTemplateSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FailedPostingWorkbenchPhaseCTest extends TestCase
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

    public function test_index_and_show_are_permission_protected(): void
    {
        $attempt = $this->unknownFailure();

        $this->get(route('admin.accounting.failed-postings.index'))->assertForbidden();
        $this->get(route('admin.accounting.failed-postings.show', $attempt))->assertForbidden();
    }

    public function test_index_lists_failed_attempt_and_detail_shows_timeline(): void
    {
        $this->grant('accounting.failed_postings.view');
        $attempt = $this->unknownFailure();

        $this->get(route('admin.accounting.failed-postings.index'))
            ->assertOk()
            ->assertSee($attempt->source_module)
            ->assertSee($attempt->error_code);
        $this->get(route('admin.accounting.failed-postings.show', $attempt))
            ->assertOk()
            ->assertSee('ACCOUNTING_POSTING_ATTEMPT_FAILED')
            ->assertSee($attempt->error_message);
    }

    public function test_unsupported_retry_retains_failed_state_and_new_error_event(): void
    {
        $attempt = $this->unknownFailure();

        $result = app(FailedPostingWorkbenchService::class)->retry($attempt, $this->user);

        $this->assertFalse($result->supported);
        $this->assertSame('failed', $attempt->fresh()->status);
        $this->assertSame(2, $attempt->fresh()->attempt_count);
        $this->assertDatabaseHas('accounting_posting_attempt_events', [
            'accounting_posting_attempt_id' => $attempt->id,
            'error_code' => 'UNSUPPORTED_SOURCE_TYPE',
        ]);
    }

    public function test_retry_supported_basic_entry_posts_through_phase_a(): void
    {
        $entry = $this->basicEntry();
        $attempt = app(\App\Services\BasicAccountingPostingService::class)->post($entry, $this->user)['entry'];
        $postingAttempt = AccountingPostingAttempt::where('source_id', $entry->id)->firstOrFail();
        $this->basicMappings($entry);

        $result = app(FailedPostingWorkbenchService::class)->retry($postingAttempt, $this->user);

        $this->assertTrue($result->success, $result->message ?? '');
        $this->assertSame('posted', $postingAttempt->fresh()->status);
        $this->assertSame('posted', $entry->fresh()->accounting_status);
        $this->assertDatabaseCount('journal_entries', 1);
    }

    public function test_retry_supported_bank_adjustment_posts_through_phase_b(): void
    {
        $adjustment = $this->approvedBankAdjustment();
        $attempt = $this->failedAttempt($adjustment, 'BANK_RECONCILIATION', 'bank_adjustment');

        $result = app(FailedPostingWorkbenchService::class)->retry($attempt, $this->user);

        $this->assertTrue($result->success, $result->message ?? '');
        $this->assertSame('posted', $attempt->fresh()->status);
        $this->assertNotNull($adjustment->fresh()->journal_entry_id);
        $this->assertDatabaseCount('journal_entries', 1);
    }

    public function test_duplicate_retry_does_not_duplicate_journal(): void
    {
        $adjustment = $this->approvedBankAdjustment();
        $attempt = $this->failedAttempt($adjustment, 'BANK_RECONCILIATION', 'bank_adjustment');
        $service = app(FailedPostingWorkbenchService::class);
        $service->retry($attempt, $this->user);

        try {
            $service->retry($attempt->fresh(), $this->user);
        } catch (ValidationException) {
            // Posted attempts are deliberately rejected before the source handler.
        }

        $this->assertDatabaseCount('journal_entries', 1);
    }

    public function test_batch_retry_continues_after_unsupported_attempt(): void
    {
        $unknown = $this->unknownFailure();
        $entry = $this->basicEntry();
        app(\App\Services\BasicAccountingPostingService::class)->post($entry, $this->user);
        $basicAttempt = AccountingPostingAttempt::where('source_id', $entry->id)->firstOrFail();
        $this->basicMappings($entry);

        $summary = app(FailedPostingWorkbenchService::class)->retrySelected([$unknown->id, $basicAttempt->id], $this->user);

        $this->assertSame(2, $summary['retried']);
        $this->assertSame(1, $summary['posted']);
        $this->assertSame(1, $summary['unsupported']);
        $this->assertSame('posted', $basicAttempt->fresh()->status);
    }

    public function test_manual_resolution_requires_note_and_evidence(): void
    {
        $attempt = $this->unknownFailure();

        $this->expectException(ValidationException::class);
        app(FailedPostingWorkbenchService::class)->resolve($attempt, [
            'resolution_type' => 'other',
            'resolution_note' => '',
            'resolution_reference' => '',
            'resolution_evidence' => '',
        ], $this->user);
    }

    public function test_corrected_by_manual_journal_requires_linked_journal(): void
    {
        $attempt = $this->unknownFailure();

        $this->expectException(ValidationException::class);
        app(FailedPostingWorkbenchService::class)->resolve($attempt, [
            'resolution_type' => 'corrected_by_manual_journal',
            'resolution_note' => 'Corrected outside the source workflow.',
            'resolution_reference' => 'FIN-REVIEW-1',
            'resolution_evidence' => 'Finance review completed.',
        ], $this->user);
    }

    public function test_manual_resolution_links_corrective_journal_and_remains_visible(): void
    {
        $attempt = $this->unknownFailure();
        $journal = $this->postedJournal();

        app(FailedPostingWorkbenchService::class)->resolve($attempt, [
            'resolution_type' => 'corrected_by_manual_journal',
            'resolution_note' => 'Corrected through approved manual journal.',
            'resolution_reference' => 'FIN-REVIEW-2',
            'resolution_evidence' => 'Signed review attached in document register.',
            'resolution_journal_entry_id' => $journal->id,
        ], $this->user);

        $this->assertDatabaseHas('accounting_posting_attempts', [
            'id' => $attempt->id,
            'status' => 'resolved',
            'resolution_journal_entry_id' => $journal->id,
        ]);
        $this->assertTrue(app(FailedPostingWorkbenchService::class)->query(['status' => 'resolved'])->whereKey($attempt->id)->exists());
    }

    public function test_waive_requires_reason_and_materiality_note(): void
    {
        $attempt = $this->unknownFailure();

        $this->expectException(ValidationException::class);
        app(FailedPostingWorkbenchService::class)->waive($attempt, [
            'waive_reason' => 'Non-ledger informational item.',
            'materiality_note' => '',
        ], $this->user);
    }

    public function test_posted_attempt_cannot_be_waived(): void
    {
        $attempt = $this->unknownFailure();
        $journal = $this->postedJournal();
        app(AccountingPostingAttemptService::class)->posted($attempt, $journal, $this->user);

        $this->expectException(ValidationException::class);
        app(FailedPostingWorkbenchService::class)->waive($attempt->fresh(), [
            'waive_reason' => 'Not allowed.',
            'materiality_note' => 'Immaterial.',
        ], $this->user);
    }

    public function test_waive_route_requires_elevated_permission(): void
    {
        $this->grant('accounting.failed_postings.view');
        $attempt = $this->unknownFailure();

        $this->post(route('admin.accounting.failed-postings.waive', $attempt), [
            'waive_reason' => 'Reviewed.',
            'materiality_note' => 'Below approved threshold.',
        ])->assertForbidden();
    }

    public function test_waived_attempt_appears_separately_in_close_readiness(): void
    {
        $attempt = $this->unknownFailure();
        app(FailedPostingWorkbenchService::class)->waive($attempt, [
            'waive_reason' => 'No ledger impact.',
            'materiality_note' => 'Zero amount source.',
        ], $this->user);

        $summary = app(AccountingCloseReadinessService::class)->summary(today()->subDay(), today()->addDay(), $this->user);

        $this->assertSame(0, $summary['unresolved_failed_postings']);
        $this->assertSame(1, $summary['waived_postings']);
        $this->assertSame(0, $summary['unsupported_failed_postings']);
    }

    public function test_close_readiness_reports_unresolved_unsupported_and_oldest_failure(): void
    {
        $this->unknownFailure();

        $summary = app(AccountingCloseReadinessService::class)->summary(today()->subDay(), today()->addDay(), $this->user);

        $this->assertSame(1, $summary['unresolved_failed_postings']);
        $this->assertSame(1, $summary['unsupported_failed_postings']);
        $this->assertNotNull($summary['oldest_unresolved_failure']);
    }

    public function test_unknown_source_link_falls_back_without_crashing(): void
    {
        $this->assertNull(app(AccountingPostingSourceLinkService::class)->link($this->unknownFailure()));
    }

    public function test_module_middleware_blocks_direct_workbench_routes(): void
    {
        $this->grant('accounting.failed_postings.view');
        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->get(route('admin.accounting.failed-postings.index'))->assertForbidden();
    }

    public function test_retry_resolve_and_waive_user_actions_are_audited(): void
    {
        $unsupported = $this->unknownFailure();
        app(FailedPostingWorkbenchService::class)->retry($unsupported, $this->user);
        app(FailedPostingWorkbenchService::class)->resolve($unsupported->fresh(), [
            'resolution_type' => 'not_required_after_review',
            'resolution_note' => 'Reviewed and not required.',
            'resolution_reference' => 'REVIEW-3',
            'resolution_evidence' => 'Approved exception register.',
        ], $this->user);
        $waived = $this->unknownFailure('OTHER');
        app(FailedPostingWorkbenchService::class)->waive($waived, [
            'waive_reason' => 'No accounting impact.',
            'materiality_note' => 'Nil value.',
        ], $this->user);

        foreach (['FAILED_POSTING_RETRY_REQUESTED', 'FAILED_POSTING_RETRY_FAILED', 'FAILED_POSTING_RESOLVED', 'FAILED_POSTING_WAIVED'] as $event) {
            $this->assertDatabaseHas('activity_log', ['log_name' => 'ACCOUNTING', 'event' => $event]);
        }
    }

    private function unknownFailure(string $module = 'UNKNOWN'): AccountingPostingAttempt
    {
        return $this->failedAttempt(Account::where('code', '4900')->firstOrFail(), $module, 'unknown_posting_'.strtolower($module).'_'.uniqid());
    }

    private function failedAttempt(object $source, string $module, string $postingType): AccountingPostingAttempt
    {
        $attempts = app(AccountingPostingAttemptService::class);
        $attempt = $attempts->pending($module, $source, $postingType, actor: $this->user);
        $attempt = $attempts->processing($attempt, $this->user);
        return $attempts->failed($attempt, 'Original retained failure', 'ORIGINAL_FAILURE', actor: $this->user);
    }

    private function basicEntry(): FinancialEntry
    {
        $category = AccountCategory::where('type', 'income')->firstOrFail();
        return FinancialEntry::create([
            'entry_number' => 'FIN-PC-'.uniqid(),
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 125.50,
            'payment_method' => 'cash',
            'description' => 'Phase C retry test',
            'entry_date' => today(),
            'recorded_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approval_status' => 'approved',
            'accounting_status' => 'eligible',
            'posting_version' => 1,
        ]);
    }

    private function basicMappings(FinancialEntry $entry): void
    {
        foreach ([
            ['basic_income_category', 'category_id', (string) $entry->category_id, '4900'],
            ['basic_payment_method', 'method', 'cash', '1110'],
        ] as [$scope, $key, $value, $code]) {
            AccountingAccountMapping::create([
                'mapping_scope' => $scope,
                'mapping_key' => $key,
                'mapping_value' => $value,
                'account_id' => Account::where('code', $code)->value('id'),
                'effective_from' => '2000-01-01',
                'priority' => 10,
                'is_active' => true,
            ]);
        }
    }

    private function approvedBankAdjustment(): BankReconciliationAdjustment
    {
        $bank = app(BankAccountService::class)->create([
            'name' => 'Phase C Bank',
            'bank_name' => 'Test Bank',
            'account_number' => '1234567890',
            'currency' => 'GHS',
            'gl_account_id' => Account::where('code', '1120')->value('id'),
            'opening_date' => today()->startOfMonth()->toDateString(),
            'opening_balance' => 0,
            'is_active' => true,
        ], $this->user);
        $reconciliation = app(BankReconciliationService::class)->prepare($bank, [
            'period_start' => today()->startOfMonth()->toDateString(),
            'period_end' => today()->endOfMonth()->toDateString(),
            'statement_opening_balance' => 0,
            'statement_closing_balance' => 0,
        ], $this->user);
        $service = app(BankReconciliationAdjustmentPostingService::class);
        $adjustment = $service->propose($reconciliation, [
            'type' => 'bank_charge',
            'amount' => 25,
            'account_id' => Account::where('code', '5900')->value('id'),
            'description' => 'Phase C retry',
        ], $this->user);
        return $service->approve($adjustment, $this->user);
    }

    private function postedJournal(): JournalEntry
    {
        $service = app(JournalEntryService::class);
        $journal = $service->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Phase C corrective journal',
            'lines' => [
                ['account_id' => Account::where('code', '1110')->value('id'), 'debit' => 10, 'credit' => 0],
                ['account_id' => Account::where('code', '4900')->value('id'), 'debit' => 0, 'credit' => 10],
            ],
        ]);
        return $service->post($journal, $this->user);
    }

    private function grant(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            $this->user->givePermissionTo(Permission::findOrCreate($permission));
        }
    }
}
