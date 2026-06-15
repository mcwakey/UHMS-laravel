<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingPostingAttempt;
use App\Models\AccountingReconciliationRun;
use App\Models\AccountingSetting;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\Invoice;
use App\Models\InvoiceReceivable;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\SupplierPayable;
use App\Models\User;
use App\Models\Visit;
use App\Services\AccountingCloseReadinessService;
use App\Services\AccountingPostingAttemptService;
use App\Services\JournalEntryService;
use App\Services\ModuleService;
use App\Services\ReconciliationResolutionService;
use App\Services\SubledgerReconciliationService;
use Database\Seeders\AccountingChartSeeder;
use Database\Seeders\ModuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubledgerReconciliationPhaseDTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private const PERMISSIONS = [
        'accounting.subledger_reconciliation.view',
        'accounting.subledger_reconciliation.run',
        'accounting.subledger_reconciliation.resolve',
        'accounting.subledger_reconciliation.approve',
        'accounting.subledger_reconciliation.cancel',
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
        $this->actingAs($this->user);
    }

    public function test_dashboard_is_permission_protected(): void
    {
        $this->get(route('admin.accounting.subledger-reconciliation.index'))->assertForbidden();
        $this->grant('accounting.subledger_reconciliation.view');
        $this->get(route('admin.accounting.subledger-reconciliation.index'))->assertOk();
    }

    public function test_module_middleware_blocks_direct_routes(): void
    {
        $this->grant('accounting.subledger_reconciliation.view');
        Module::where('slug', 'accounting_advanced')->update(['is_enabled' => false]);
        app(ModuleService::class)->flush();

        $this->get(route('admin.accounting.subledger-reconciliation.index'))->assertForbidden();
    }

    public function test_ar_reconciliation_calculates_subledger_and_gl_totals(): void
    {
        $receivable = $this->receivable(125, 'posted');
        $receivable->update(['journal_entry_id' => $this->postedJournal('1210', '4900', 125)->id]);

        $run = $this->reconciliationRun('accounts_receivable');

        $this->assertSame(125.0, (float) $run->subledger_total);
        $this->assertSame(125.0, (float) $run->gl_total);
        $this->assertSame(0.0, (float) $run->difference_amount);
    }

    public function test_ap_reconciliation_calculates_subledger_and_gl_totals(): void
    {
        $supplier = Supplier::create(['name' => 'Phase D Supplier']);
        $journal = $this->postedJournal('5200', '2110', 80);
        SupplierPayable::create([
            'supplier_id' => $supplier->id,
            'original_amount' => 80,
            'balance' => 80,
            'aging_start_date' => today(),
            'status' => 'pending',
            'journal_entry_id' => $journal->id,
            'accounting_status' => 'posted',
        ]);

        $run = $this->reconciliationRun('accounts_payable');

        $this->assertSame(80.0, (float) $run->subledger_total);
        $this->assertSame(80.0, (float) $run->gl_total);
    }

    public function test_inventory_reconciliation_calculates_valuation_vs_gl(): void
    {
        $product = Product::create(['name' => 'Test medicine', 'code' => 'PD-1', 'product_type' => 'drug', 'unit' => 'unit']);
        $location = StockLocation::query()->firstOrFail();
        StockBalance::create([
            'product_id' => $product->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 10,
            'average_cost' => 6,
            'total_value' => 60,
        ]);
        $this->postedJournal('1310', '4900', 60);

        $run = $this->reconciliationRun('inventory');

        $this->assertSame(60.0, (float) $run->subledger_total);
        $this->assertSame(60.0, (float) $run->gl_total);
    }

    public function test_payroll_paye_and_pension_are_honestly_partially_available(): void
    {
        foreach (['payroll', 'paye', 'pension'] as $type) {
            $run = $this->reconciliationRun($type);
            $this->assertSame('partially_available', $run->availability());
            $this->assertNotEmpty(data_get($run->summary_snapshot, 'availability_reason'));
        }
    }

    public function test_cash_bank_reconciliation_uses_phase_b_position(): void
    {
        $gl = Account::where('code', '1120')->firstOrFail();
        $bank = BankAccount::create([
            'name' => 'Operating',
            'bank_name' => 'GCB',
            'account_number_masked' => '******1234',
            'account_number_hash' => hash('sha256', '1234'),
            'currency' => 'GHS',
            'gl_account_id' => $gl->id,
            'opening_balance' => 0,
            'is_active' => true,
        ]);
        BankReconciliation::create([
            'bank_account_id' => $bank->id,
            'period_start' => today()->startOfMonth(),
            'period_end' => today(),
            'statement_opening_balance' => 0,
            'statement_closing_balance' => 200,
            'book_opening_balance' => 0,
            'book_closing_balance' => 200,
            'outstanding_deposits_total' => 0,
            'outstanding_withdrawals_total' => 0,
            'adjustments_total' => 0,
            'difference' => 0,
            'status' => 'approved',
        ]);
        $this->postedJournal('1120', '4900', 200);

        $run = $this->reconciliationRun('cash_bank');

        $this->assertSame(200.0, (float) $run->subledger_total);
        $this->assertSame(200.0, (float) $run->gl_total);
    }

    public function test_failed_posting_is_classified_separately(): void
    {
        $receivable = $this->receivable(90, 'failed');
        $attempts = app(AccountingPostingAttemptService::class);
        $attempt = $attempts->pending('BILLING', $receivable, 'receivable', actor: $this->user);
        $attempts->failed($attempt, 'Mapping missing', actor: $this->user);

        $run = $this->reconciliationRun('accounts_receivable');

        $this->assertTrue($run->items()->where('classification', 'failed_posting')->exists());
    }

    public function test_manual_control_account_journal_is_classified_separately(): void
    {
        AccountingSetting::where('key', 'allow_manual_control_account_posting')->update(['value' => '1']);
        $this->postedJournal('1210', '4900', 25, 'MANUAL');

        $run = $this->reconciliationRun('accounts_receivable');

        $this->assertTrue($run->items()->where('classification', 'manual_journal')->exists());
    }

    public function test_unposted_source_is_classified_separately(): void
    {
        $this->receivable(45, null);

        $run = $this->reconciliationRun('accounts_receivable');

        $this->assertTrue($run->items()->where('classification', 'unposted_source')->exists());
    }

    public function test_resolution_note_and_evidence_links_are_retained(): void
    {
        $run = $this->reconciliationRun('accounts_receivable');
        $item = $run->items()->firstOrFail();

        $resolution = app(ReconciliationResolutionService::class)->add($run, $item, [
            'resolution_type' => 'source_corrected',
            'resolution_note' => 'Source reviewed and corrected through the billing workflow.',
            'linked_source_type' => InvoiceReceivable::class,
            'linked_source_id' => 10,
        ], $this->user);

        $this->assertSame('resolved', $item->fresh()->resolution_status);
        $this->assertSame('Source reviewed and corrected through the billing workflow.', $resolution->resolution_note);
    }

    public function test_approval_route_requires_elevated_permission(): void
    {
        $this->grant('accounting.subledger_reconciliation.view');
        $run = $this->reconciliationRun('accounts_receivable');

        $this->post(route('admin.accounting.subledger-reconciliation.approve', $run))->assertForbidden();
    }

    public function test_unresolved_differences_require_explicit_elevated_override(): void
    {
        $this->receivable(100, null);
        $run = $this->reconciliationRun('accounts_receivable');

        try {
            app(SubledgerReconciliationService::class)->approve($run, $this->user);
            $this->fail('Expected unresolved approval to be rejected.');
        } catch (ValidationException) {
            $approved = app(SubledgerReconciliationService::class)->approve($run->fresh(), $this->user, true);
            $this->assertSame('approved', $approved->status);
        }
    }

    public function test_close_readiness_includes_reconciliation_status(): void
    {
        $run = $this->reconciliationRun('accounts_receivable');

        $summary = app(AccountingCloseReadinessService::class)->summary(today()->startOfMonth(), today()->endOfMonth(), $this->user);

        $this->assertSame($run->id, $summary['latest_reconciliation_by_domain']['accounts_receivable']['id']);
        $this->assertContains('accounts_payable', $summary['domains_not_run_for_period']);
    }

    public function test_reconciliation_actions_are_audited(): void
    {
        $this->receivable(15, null);
        $run = $this->reconciliationRun('accounts_receivable');
        $item = $run->items()->where('classification', 'unposted_source')->firstOrFail();
        app(ReconciliationResolutionService::class)->add($run, $item, [
            'resolution_type' => 'waived_after_review',
            'resolution_note' => 'Reviewed as immaterial for this close.',
        ], $this->user);
        app(SubledgerReconciliationService::class)->approve($run->fresh(), $this->user, true);

        foreach ([
            'SUBLEDGER_RECONCILIATION_STARTED',
            'SUBLEDGER_RECONCILIATION_COMPLETED',
            'SUBLEDGER_RECONCILIATION_ITEM_WAIVED',
            'SUBLEDGER_RECONCILIATION_APPROVED',
        ] as $event) {
            $this->assertDatabaseHas('activity_log', ['log_name' => 'ACCOUNTING', 'event' => $event]);
        }
    }

    public function test_phase_d_localisation_keys_exist_in_english_and_french(): void
    {
        foreach (['subledger_reconciliation', 'run_reconciliation', 'classification_manual_journal', 'resolution_type_waived_after_review'] as $key) {
            $this->assertNotSame("accounting.$key", trans("accounting.$key", [], 'en'));
            $this->assertNotSame("accounting.$key", trans("accounting.$key", [], 'fr'));
        }
    }

    private function grant(string ...$permissions): void
    {
        $this->user->givePermissionTo($permissions);
    }

    private function reconciliationRun(string $type): AccountingReconciliationRun
    {
        return app(SubledgerReconciliationService::class)->start([
            'reconciliation_type' => $type,
            'period_start' => today()->startOfMonth()->toDateString(),
            'period_end' => today()->endOfMonth()->toDateString(),
            'as_of_date' => today()->toDateString(),
            'tolerance_amount' => 0.01,
        ], $this->user);
    }

    private function receivable(float $amount, ?string $accountingStatus): InvoiceReceivable
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-PD-'.uniqid(),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => 'cash',
            'subtotal' => $amount,
            'total_amount' => $amount,
            'balance' => $amount,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);

        return InvoiceReceivable::create([
            'invoice_id' => $invoice->id,
            'patient_id' => $patient->id,
            'visit_id' => $visit->id,
            'payer_type' => 'patient',
            'payer_id' => $patient->id,
            'original_amount' => $amount,
            'allocated_amount' => $amount,
            'balance' => $amount,
            'aging_start_date' => today(),
            'status' => 'pending',
            'accounting_status' => $accountingStatus,
            'created_by' => $this->user->id,
        ]);
    }

    private function postedJournal(string $debitCode, string $creditCode, float $amount, string $sourceModule = 'TEST')
    {
        $journal = app(JournalEntryService::class)->createDraft([
            'entry_date' => today()->toDateString(),
            'description' => 'Phase D reconciliation fixture',
            'source_module' => $sourceModule,
            'lines' => [
                ['account_id' => Account::where('code', $debitCode)->value('id'), 'debit' => $amount, 'credit' => 0],
                ['account_id' => Account::where('code', $creditCode)->value('id'), 'debit' => 0, 'credit' => $amount],
            ],
        ]);

        return app(JournalEntryService::class)->post($journal, $this->user);
    }
}
