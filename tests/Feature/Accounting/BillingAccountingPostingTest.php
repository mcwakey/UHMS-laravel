<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingAccountingPostingService;
use Database\Seeders\AccountingChartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 2 — Billing → Accounting posting, plus the failed-posting / retry path
 * (Phase 18 §18). Invoice recognition debits a receivable control account and
 * credits the resolved revenue account(s); double-posting is prevented.
 */
class BillingAccountingPostingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Department $consultationDept;
    private Department $labDept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountingChartSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->consultationDept = Department::factory()->create(['type' => 'consultation']);
        $this->labDept = Department::factory()->create(['type' => 'investigation']);
    }

    private function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id');
    }

    private function makeInvoice(): Invoice
    {
        $patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'created_by' => $this->user->id]);

        return Invoice::create([
            'invoice_number' => 'INV-'.strtoupper(uniqid()),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => 'cash',
            'subtotal' => 0,
            'total_amount' => 0,
            'balance' => 0,
            'status' => 'pending',
            'created_by' => $this->user->id,
        ]);
    }

    private function addItem(Invoice $invoice, Department $dept, string $sourceType, float $payable): InvoiceItem
    {
        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $invoice->visit_id,
            'patient_id' => $invoice->patient_id,
            'department_id' => $dept->id,
            'source_type' => $sourceType,
            'description' => ucfirst($sourceType).' charge',
            'quantity' => 1,
            'unit_price' => $payable,
            'selected_price' => $payable,
            'total_price' => $payable,
            'patient_payable' => $payable,
            'balance' => $payable,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_invoice_posting_debits_receivable_and_credits_revenue(): void
    {
        $invoice = $this->makeInvoice();
        $this->addItem($invoice, $this->consultationDept, 'consultation', 200);

        $entry = app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());

        $this->assertNotNull($entry);
        $this->assertTrue($entry->is_balanced);

        $receivable = $entry->lines->firstWhere('account_id', $this->accountId('1210')); // Patient Receivables
        $revenue = $entry->lines->firstWhere('account_id', $this->accountId('4100'));    // Consultation Revenue
        $this->assertEqualsWithDelta(200, (float) $receivable->debit, 0.001);
        $this->assertEqualsWithDelta(200, (float) $revenue->credit, 0.001);

        $this->assertSame('posted', (string) $invoice->fresh()->accounting_status);
    }

    public function test_multi_category_invoice_credits_multiple_revenue_accounts(): void
    {
        $invoice = $this->makeInvoice();
        $this->addItem($invoice, $this->consultationDept, 'consultation', 200);
        $this->addItem($invoice, $this->labDept, 'investigation', 150);

        $entry = app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());

        $this->assertEqualsWithDelta(200, (float) $entry->lines->firstWhere('account_id', $this->accountId('4100'))->credit, 0.001);
        $this->assertEqualsWithDelta(150, (float) $entry->lines->firstWhere('account_id', $this->accountId('4200'))->credit, 0.001);
        // Single combined receivable debit of 350.
        $this->assertEqualsWithDelta(350, (float) $entry->lines->firstWhere('account_id', $this->accountId('1210'))->debit, 0.001);
    }

    public function test_payment_is_not_posted_as_revenue_on_invoice_recognition(): void
    {
        $invoice = $this->makeInvoice();
        $this->addItem($invoice, $this->consultationDept, 'consultation', 200);

        $entry = app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());

        // Invoice recognition must not touch cash/bank accounts (that is the payment's job).
        $this->assertNull($entry->lines->firstWhere('account_id', $this->accountId('1110')));
        $this->assertNull($entry->lines->firstWhere('account_id', $this->accountId('1120')));
    }

    public function test_duplicate_invoice_posting_is_prevented(): void
    {
        $invoice = $this->makeInvoice();
        $this->addItem($invoice, $this->consultationDept, 'consultation', 200);

        app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());
        app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());

        $this->assertSame(1, JournalEntry::where('reference_type', Invoice::class)->where('reference_id', $invoice->id)->count());
    }

    public function test_failed_posting_stores_error_and_retry_creates_one_journal(): void
    {
        $invoice = $this->makeInvoice();
        $this->addItem($invoice, $this->consultationDept, 'consultation', 200);

        // Break the revenue mapping → posting fails and is recorded, not thrown.
        AccountingSetting::where('key', 'consultation_revenue_account_id')->update(['account_id' => null]);

        $result = app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());
        $this->assertNull($result);
        $this->assertSame('failed', (string) $invoice->fresh()->accounting_status);
        $this->assertNotNull($invoice->fresh()->accounting_error);
        $this->assertSame(0, JournalEntry::where('reference_type', Invoice::class)->where('reference_id', $invoice->id)->count());

        // Fix the mapping and retry → exactly one journal entry, posted.
        AccountingSetting::where('key', 'consultation_revenue_account_id')->update(['account_id' => $this->accountId('4100')]);
        $entry = app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());

        $this->assertNotNull($entry);
        $this->assertSame('posted', (string) $invoice->fresh()->accounting_status);
        $this->assertSame(1, JournalEntry::where('reference_type', Invoice::class)->where('reference_id', $invoice->id)->count());
    }

    public function test_invoice_posting_logs_activity_with_context(): void
    {
        $invoice = $this->makeInvoice();
        $this->addItem($invoice, $this->consultationDept, 'consultation', 200);

        app(BillingAccountingPostingService::class)->postInvoice($invoice->fresh());

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'ACCOUNTING',
            'event' => 'ACCOUNTING_POSTED_FOR_INVOICE',
        ]);
    }
}
