<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\CreditNoteType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CreditNote;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Sponsor;
use App\Models\User;
use App\Models\Visit;
use App\Services\InvoiceBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'Accountant']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create', 'payments.refund',
            'credit_notes.view', 'credit_notes.create', 'credit_notes.write_off',
            'sponsors.manage',
            'patients.view', 'visits.view', 'services.manage',
            'reports.ar_aging.view', 'reports.statements.view',
        ] as $p) {
            $role->givePermissionTo(Permission::create(['name' => $p]));
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $dept->id,
            'created_by' => $this->user->id,
        ]);
    }

    private function makeInvoice(float $amount = 100.00): Invoice
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'billing_type' => BillingType::CASH->value,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => $amount,
            'amount_paid' => 0,
            'balance' => $amount,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'description' => 'Consultation',
            'quantity' => 1,
            'unit_price' => $amount,
            'cash_price' => $amount,
            'selected_price' => $amount,
            'insurance_covered' => 0,
            'discount_amount' => 0,
            'patient_payable' => $amount,
            'paid_amount' => 0,
            'balance' => $amount,
            'payment_status' => 'unpaid',
            'total_price' => $amount,
            'payer_type' => 'cash',
            'created_by' => $this->user->id,
        ]);

        return $invoice->fresh('items');
    }

    // ── Payment reversal ────────────────────────────────

    public function test_payment_can_be_reversed(): void
    {
        $invoice = $this->makeInvoice(100.00);

        $this->actingAs($this->user)->post(route('admin.billing.payments.store', $invoice), [
            'amount' => 100.00,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        ])->assertRedirect();

        $payment = Payment::where('invoice_id', $invoice->id)->whereNull('reversed_payment_id')->firstOrFail();
        $this->assertEquals(0.0, (float) $invoice->fresh()->balance);

        $response = $this->actingAs($this->user)->post(route('admin.billing.payments.reverse', $payment), [
            'reason' => 'Duplicate charge',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'is_reversal' => true,
            'reversed_payment_id' => $payment->id,
        ]);
        $this->assertEquals(PaymentStatus::REVERSED->value, $payment->fresh()->status->value);
        $this->assertEquals(100.0, (float) $invoice->fresh()->balance);
    }

    public function test_payment_reversal_requires_permission(): void
    {
        $this->user->removeRole('Accountant');
        $bareRole = Role::create(['name' => 'Clerk']);
        foreach (['payments.view', 'invoices.view'] as $p) {
            $bareRole->givePermissionTo(Permission::firstOrCreate(['name' => $p]));
        }
        $this->user->assignRole($bareRole);

        $invoice = $this->makeInvoice(100.00);
        $payment = Payment::create([
            'payment_number' => 'PAY00001',
            'invoice_id' => $invoice->id,
            'patient_id' => $this->patient->id,
            'amount' => 100.00,
            'payment_method' => PaymentMethod::CASH->value,
            'paid_at' => now(),
            'received_by' => $this->user->id,
        ]);

        $this->actingAs($this->user)->post(route('admin.billing.payments.reverse', $payment), [
            'reason' => 'Test',
        ])->assertStatus(403);
    }

    // ── Credit notes & write-offs ────────────────────────────────

    public function test_credit_note_can_be_issued_and_reduces_balance(): void
    {
        $invoice = $this->makeInvoice(100.00);

        $response = $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::CREDIT_NOTE->value,
            'amount' => 40.00,
            'reason' => 'Goodwill adjustment',
        ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('credit_notes', [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::CREDIT_NOTE->value,
            'amount' => 40.00,
            'status' => 'issued',
        ]);
        $this->assertEquals(40.0, (float) $invoice->fresh()->adjustment_amount);
        $this->assertEquals(60.0, (float) $invoice->fresh()->balance);
    }

    public function test_write_off_can_be_issued(): void
    {
        $invoice = $this->makeInvoice(100.00);

        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::WRITE_OFF->value,
            'amount' => 100.00,
            'reason' => 'Uncollectible',
        ])->assertRedirect();

        $this->assertDatabaseHas('credit_notes', [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::WRITE_OFF->value,
            'status' => 'issued',
        ]);
        $this->assertEquals(0.0, (float) $invoice->fresh()->balance);
    }

    public function test_full_write_off_settles_linked_investigation_item_for_result_entry(): void
    {
        $invoice = $this->makeInvoice(100.00);
        $invoiceItem = $invoice->items->firstOrFail();
        $request = LabRequest::create([
            'request_number' => 'INV-WRITEOFF-001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'requested_by' => $this->user->id,
            'department_id' => $this->user->department_id,
            'target_department_id' => $this->user->department_id,
            'urgency' => 'routine',
            'status' => 'processing',
        ]);
        $requestItem = LabRequestItem::create([
            'lab_request_id' => $request->id,
            'name' => 'Full Blood Count',
            'status' => 'accepted',
            'accepted_at' => now(),
            'accepted_by' => $this->user->id,
            'billed_at' => now(),
            'invoice_item_id' => $invoiceItem->id,
            'unit_price' => 100,
        ]);

        $this->assertFalse($requestItem->isBillSettled());

        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::WRITE_OFF->value,
            'amount' => 100.00,
            'reason' => 'Uncollectible',
        ])->assertRedirect();

        $this->assertTrue($requestItem->fresh()->isBillSettled());

        $creditNote = CreditNote::where('invoice_id', $invoice->id)->firstOrFail();
        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.reverse', $creditNote), [
            'reason' => 'Write-off entered in error',
        ])->assertRedirect();

        $this->assertFalse($requestItem->fresh()->isBillSettled());
        $this->assertDatabaseHas('credit_notes', [
            'reverses_credit_note_id' => $creditNote->id,
            'is_reversal' => true,
            'status' => 'reversal',
        ]);
    }

    public function test_credit_note_reversal_creates_a_linked_trace_record(): void
    {
        $invoice = $this->makeInvoice(100.00);
        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::CREDIT_NOTE->value,
            'amount' => 40.00,
            'reason' => 'Adjustment',
        ]);
        $creditNote = CreditNote::where('invoice_id', $invoice->id)->firstOrFail();

        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.reverse', $creditNote), [
            'reason' => 'Issued in error',
        ])->assertRedirect();

        $reversal = CreditNote::where('reverses_credit_note_id', $creditNote->id)->firstOrFail();

        $this->assertEquals('reversed', $creditNote->fresh()->status);
        $this->assertEquals('reversal', $reversal->status);
        $this->assertTrue($reversal->is_reversal);
        $this->assertEquals($creditNote->amount, $reversal->amount);
        $this->assertEquals($creditNote->type, $reversal->type);
        $this->assertDatabaseHas('credit_notes', [
            'id' => $creditNote->id,
            'status' => 'reversed',
        ]);
        $this->assertEquals(0.0, (float) $invoice->fresh()->adjustment_amount);
        $this->assertEquals(100.0, (float) $invoice->fresh()->balance);
    }

    public function test_credit_note_cannot_be_reversed_twice(): void
    {
        $invoice = $this->makeInvoice(100.00);
        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::CREDIT_NOTE->value,
            'amount' => 40.00,
            'reason' => 'Adjustment',
        ]);
        $creditNote = CreditNote::where('invoice_id', $invoice->id)->firstOrFail();

        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.reverse', $creditNote), [
            'reason' => 'Issued in error',
        ])->assertRedirect();

        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.reverse', $creditNote->fresh()), [
            'reason' => 'Second attempt',
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, CreditNote::where('reverses_credit_note_id', $creditNote->id)->count());
    }

    public function test_credit_note_index_loads_issuer_name_from_user_name_fields(): void
    {
        $invoice = $this->makeInvoice(100.00);
        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::CREDIT_NOTE->value,
            'amount' => 40.00,
            'reason' => 'Adjustment',
        ])->assertRedirect();

        $this->actingAs($this->user)
            ->get(route('admin.billing.credit-notes.index'))
            ->assertOk();
    }

    public function test_invoice_history_exposes_reversal_action_for_an_issued_write_off(): void
    {
        $invoice = $this->makeInvoice(100.00);

        $this->actingAs($this->user)->post(route('admin.billing.credit-notes.store'), [
            'invoice_id' => $invoice->id,
            'type' => CreditNoteType::WRITE_OFF->value,
            'amount' => 100.00,
            'reason' => 'Uncollectible',
        ])->assertRedirect();

        $writeOff = CreditNote::where('invoice_id', $invoice->id)->firstOrFail();
        $historyRow = app(InvoiceBalanceService::class)
            ->history($invoice->fresh())
            ->firstWhere('reference', $writeOff->credit_note_number);

        $this->assertTrue($historyRow['can_reverse']);
        $this->assertSame('write_off', $historyRow['reversal_kind']);
        $this->assertSame(route('admin.billing.credit-notes.reverse', $writeOff), $historyRow['reverse_url']);

        $this->actingAs($this->user)
            ->get(route('admin.billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('reverseSettlementModal')
            ->assertSee(__('invoices.reverse_entry'));
    }

    // ── Sponsors ────────────────────────────────

    public function test_sponsor_can_be_created_and_updated(): void
    {
        $this->actingAs($this->user)->post(route('admin.billing.sponsors.store'), [
            'name' => 'Acme Corp',
            'is_active' => true,
        ])->assertRedirect();

        $sponsor = Sponsor::where('name', 'Acme Corp')->firstOrFail();
        $this->assertNotEmpty($sponsor->code);

        $this->actingAs($this->user)->put(route('admin.billing.sponsors.update', $sponsor), [
            'name' => 'Acme Corporation',
            'is_active' => true,
        ])->assertRedirect();

        $this->assertEquals('Acme Corporation', $sponsor->fresh()->name);

        $this->actingAs($this->user)->patch(route('admin.billing.sponsors.toggle', $sponsor))->assertRedirect();
        $this->assertFalse((bool) $sponsor->fresh()->is_active);
    }

    public function test_sponsor_index_loads(): void
    {
        $this->actingAs($this->user)->get(route('admin.billing.sponsors.index'))->assertStatus(200);
    }

    // ── Reports & statements ────────────────────────────────

    public function test_aging_report_loads(): void
    {
        $this->makeInvoice(100.00);
        $this->actingAs($this->user)->get(route('admin.billing.reports.aging'))->assertStatus(200);
    }

    public function test_billing_dashboard_loads(): void
    {
        $this->actingAs($this->user)->get(route('admin.billing.dashboard'))->assertStatus(200);
    }

    public function test_statements_index_loads(): void
    {
        $this->actingAs($this->user)->get(route('admin.billing.statements.index'))->assertStatus(200);
    }

    // ── Invoice editing ────────────────────────────────

    public function test_invoice_header_can_be_edited(): void
    {
        $invoice = $this->makeInvoice(100.00);
        $sponsor = Sponsor::create(['code' => 'SPN-0001', 'name' => 'Payer Ltd', 'is_active' => true]);

        $this->actingAs($this->user)->put(route('admin.billing.invoices.update', $invoice), [
            'billing_type' => BillingType::CORPORATE->value,
            'sponsor_id' => $sponsor->id,
            'tax_amount' => 5.00,
            'notes' => 'Corporate billing',
        ])->assertRedirect();

        $fresh = $invoice->fresh();
        $this->assertEquals(BillingType::CORPORATE->value, $fresh->billing_type->value);
        $this->assertEquals($sponsor->id, $fresh->sponsor_id);
    }
}
