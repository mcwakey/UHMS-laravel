<?php

namespace Tests\Feature\Accounting;

use App\Exceptions\AccountingAccountMappingNotFoundException;
use App\Models\Account;
use App\Models\AccountingAccountMapping;
use App\Models\AccountingPostingAttempt;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\Module;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\AccountingAccountMappingService;
use App\Services\AccountingCloseReadinessService;
use App\Services\AccountingPostingAttemptService;
use App\Services\AccountingPostingService;
use App\Services\JournalEntryService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingPhase0ControlsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([ModuleSeeder::class, AccountingChartSeeder::class]);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function source(): Account
    {
        return Account::where('code', '4900')->firstOrFail();
    }

    private function balancedLines(float $amount = 100): array
    {
        return [
            ['account_id' => Account::where('code', '1110')->value('id'), 'debit' => $amount, 'credit' => 0],
            ['account_id' => Account::where('code', '4900')->value('id'), 'debit' => 0, 'credit' => $amount],
        ];
    }

    private function postedJournal(string $description = 'Phase 0 fixture'): JournalEntry
    {
        $journal = app(JournalEntryService::class)->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => $description,
            'lines' => $this->balancedLines(),
        ]);

        return app(JournalEntryService::class)->post($journal, $this->user);
    }

    public function test_posting_attempt_lifecycle_records_processing_posted_and_events(): void
    {
        $service = app(AccountingPostingAttemptService::class);
        $attempt = $service->pending('TEST', $this->source(), 'recognition', actor: $this->user);
        $attempt = $service->processing($attempt, $this->user);

        $this->assertSame('processing', $attempt->status);
        $this->assertSame(1, $attempt->attempt_count);

        $attempt = $service->posted($attempt, $this->postedJournal(), $this->user);

        $this->assertSame('posted', $attempt->status);
        $this->assertNotNull($attempt->journal_entry_id);
        $this->assertDatabaseHas('accounting_posting_attempt_events', [
            'accounting_posting_attempt_id' => $attempt->id,
            'event_type' => 'ACCOUNTING_POSTING_ATTEMPT_POSTED',
        ]);
    }

    public function test_failed_retry_retains_prior_error_history(): void
    {
        $service = app(AccountingPostingAttemptService::class);
        $attempt = $service->processing(
            $service->pending('TEST', $this->source(), 'failure', actor: $this->user),
            $this->user,
        );
        $attempt = $service->failed($attempt, 'First failure', 'FIRST', actor: $this->user);
        $attempt = $service->processing($attempt, $this->user);
        $attempt = $service->failed($attempt, 'Second failure', 'SECOND', actor: $this->user);

        $this->assertSame('Second failure', $attempt->error_message);
        $this->assertSame(2, $attempt->attempt_count);
        $this->assertDatabaseHas('accounting_posting_attempt_events', [
            'accounting_posting_attempt_id' => $attempt->id,
            'error_code' => 'FIRST',
            'error_message' => 'First failure',
        ]);
        $this->assertDatabaseHas('accounting_posting_attempt_events', [
            'accounting_posting_attempt_id' => $attempt->id,
            'error_code' => 'SECOND',
            'error_message' => 'Second failure',
        ]);
    }

    public function test_posted_attempt_cannot_be_relinked_to_another_journal(): void
    {
        $service = app(AccountingPostingAttemptService::class);
        $attempt = $service->processing(
            $service->pending('TEST', $this->source(), 'immutable', actor: $this->user),
            $this->user,
        );
        $attempt = $service->posted($attempt, $this->postedJournal('Original'), $this->user);

        $this->expectException(ValidationException::class);
        $service->posted($attempt, $this->postedJournal('Different'), $this->user);
    }

    public function test_resolve_and_waive_require_actor_and_reason(): void
    {
        $service = app(AccountingPostingAttemptService::class);
        $failed = $service->failed(
            $service->pending('TEST', $this->source(), 'resolve', actor: $this->user),
            'Needs review',
            actor: $this->user,
        );

        $this->expectException(ValidationException::class);
        $service->waive($failed, $this->user, ' ');
    }

    public function test_same_posting_identity_returns_existing_journal(): void
    {
        $service = app(AccountingPostingService::class);
        $first = $service->postFromSource('TEST', $this->source(), $this->balancedLines(), [
            'posting_type' => 'recognition',
            'description' => 'Idempotent source posting',
        ]);
        $second = $service->postFromSource('TEST', $this->source(), $this->balancedLines(), [
            'posting_type' => 'recognition',
            'description' => 'Idempotent source posting',
        ]);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, JournalEntry::where('idempotency_key', $first->idempotency_key)->count());
        $this->assertSame(1, AccountingPostingAttempt::where('idempotency_key', $first->idempotency_key)->count());
    }

    public function test_mapping_resolves_by_date_and_highest_priority(): void
    {
        $cash = Account::where('code', '1110')->first();
        $bank = Account::where('code', '1120')->first();
        AccountingAccountMapping::create([
            'mapping_scope' => 'payment',
            'mapping_key' => 'method',
            'mapping_value' => 'cash',
            'account_id' => $cash->id,
            'effective_from' => '2026-01-01',
            'priority' => 10,
            'is_active' => true,
        ]);
        $winner = AccountingAccountMapping::create([
            'mapping_scope' => 'payment',
            'mapping_key' => 'method',
            'mapping_value' => 'cash',
            'account_id' => $bank->id,
            'effective_from' => '2026-01-01',
            'priority' => 20,
            'is_active' => true,
        ]);

        $resolved = app(AccountingAccountMappingService::class)
            ->resolve('payment', 'method', 'cash', '2026-06-15');

        $this->assertSame($bank->id, $resolved['account']->id);
        $this->assertSame($winner->id, $resolved['mapping']->id);
        $this->assertStringContainsString('priority 20', $resolved['explanation']);
    }

    public function test_equal_priority_and_specificity_mapping_conflict_fails_loudly(): void
    {
        foreach (['1110', '1120'] as $code) {
            AccountingAccountMapping::create([
                'mapping_scope' => 'payment',
                'mapping_key' => 'method',
                'mapping_value' => 'card',
                'account_id' => Account::where('code', $code)->value('id'),
                'effective_from' => '2026-01-01',
                'priority' => 10,
                'is_active' => true,
            ]);
        }

        $this->expectException(AccountingAccountMappingNotFoundException::class);
        app(AccountingAccountMappingService::class)->resolve('payment', 'method', 'card', '2026-06-15');
    }

    public function test_missing_mapping_throws_controlled_exception(): void
    {
        $this->expectException(AccountingAccountMappingNotFoundException::class);
        app(AccountingAccountMappingService::class)->resolve('tax', 'type', 'missing', '2026-06-15');
    }

    public function test_close_readiness_lists_failed_waived_and_posted_attempts(): void
    {
        $service = app(AccountingPostingAttemptService::class);
        $failed = $service->failed(
            $service->pending('BILLING', $this->source(), 'failed-summary', actor: $this->user),
            'Failure',
            actor: $this->user,
        );
        $waived = $service->failed(
            $service->pending('INVENTORY', $this->source(), 'waived-summary', actor: $this->user),
            'Waive me',
            actor: $this->user,
        );
        $service->waive($waived, $this->user, 'Approved exception');
        $posted = $service->processing(
            $service->pending('PAYMENTS', $this->source(), 'posted-summary', actor: $this->user),
            $this->user,
        );
        $service->posted($posted, $this->postedJournal(), $this->user);

        $summary = app(AccountingCloseReadinessService::class)
            ->summary(today()->subDay(), today()->addDay(), $this->user);

        $this->assertFalse($summary['ready']);
        $this->assertSame(1, $summary['unresolved_failed_postings']);
        $this->assertSame(1, $summary['waived_postings']);
        $this->assertSame(1, $summary['posted_attempts']);
        $this->assertSame(1, $summary['failed_by_source_module']['BILLING']);
    }

    public function test_backfill_dry_run_performs_no_writes(): void
    {
        $this->historicalInvoice($this->postedJournal('Historical dry run'), null);

        $this->artisan('accounting:posting-attempts-backfill', [
            '--dry-run' => true,
            '--source-type' => 'invoice',
        ])->assertSuccessful();

        $this->assertDatabaseCount('accounting_posting_attempts', 0);
    }

    public function test_backfill_creates_metadata_only_and_no_journal(): void
    {
        $journal = $this->postedJournal('Historical write');
        $invoice = $this->historicalInvoice($journal, null);
        $journalCount = JournalEntry::count();

        $this->artisan('accounting:posting-attempts-backfill', [
            '--source-type' => 'invoice',
            '--source-id' => $invoice->id,
        ])->assertSuccessful();

        $this->assertSame($journalCount, JournalEntry::count());
        $this->assertDatabaseHas('accounting_posting_attempts', [
            'source_id' => $invoice->id,
            'status' => 'posted',
            'journal_entry_id' => $journal->id,
        ]);
    }

    public function test_unauthorized_user_cannot_view_attempts_or_manage_mappings(): void
    {
        $this->get(route('admin.accounting.posting-attempts.index'))->assertForbidden();
        $this->get(route('admin.accounting.mappings.create'))->assertForbidden();
    }

    public function test_module_middleware_blocks_phase_zero_routes(): void
    {
        foreach (['accounting.failed_postings.view', 'accounting.mappings.view', 'accounting.close_readiness.view'] as $permission) {
            $this->user->givePermissionTo(Permission::findOrCreate($permission));
        }
        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(\App\Services\ModuleService::class)->flush();

        $this->get(route('admin.accounting.posting-attempts.index'))->assertForbidden();
        $this->get(route('admin.accounting.mappings.index'))->assertForbidden();
        $this->get(route('admin.accounting.close-readiness'))->assertForbidden();
    }

    public function test_authorized_phase_zero_pages_render(): void
    {
        foreach (['accounting.failed_postings.view', 'accounting.mappings.view', 'accounting.close_readiness.view'] as $permission) {
            $this->user->givePermissionTo(Permission::findOrCreate($permission));
        }

        $this->get(route('admin.accounting.posting-attempts.index'))->assertOk();
        $this->get(route('admin.accounting.mappings.index'))->assertOk();
        $this->get(route('admin.accounting.close-readiness'))->assertOk();
    }

    public function test_lifecycle_and_mapping_state_changes_are_audited(): void
    {
        $service = app(AccountingPostingAttemptService::class);
        $attempt = $service->pending('TEST', $this->source(), 'audit', actor: $this->user);
        $service->failed($attempt, 'Audited failure', actor: $this->user);
        app(AccountingAccountMappingService::class)->create([
            'mapping_scope' => 'test',
            'mapping_key' => 'key',
            'mapping_value' => 'value',
            'account_id' => Account::where('code', '1110')->value('id'),
            'effective_from' => today()->toDateString(),
            'priority' => 0,
            'is_active' => true,
        ], $this->user);

        $this->assertDatabaseHas('activity_log', ['log_name' => 'ACCOUNTING', 'event' => 'ACCOUNTING_POSTING_ATTEMPT_FAILED']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'ACCOUNTING', 'event' => 'ACCOUNTING_ACCOUNT_MAPPING_CREATED']);
    }

    private function historicalInvoice(JournalEntry $journal, ?string $error): Invoice
    {
        $visit = Visit::factory()->create();

        return Invoice::create([
            'invoice_number' => 'INV-PH0-'.uniqid(),
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => 'cash',
            'total_amount' => 100,
            'balance' => 100,
            'status' => 'pending',
            'created_by' => $this->user->id,
            'journal_entry_id' => $journal->id,
            'accounting_posted_at' => now(),
            'accounting_status' => 'posted',
            'accounting_error' => $error,
        ]);
    }
}
