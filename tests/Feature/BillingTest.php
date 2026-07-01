<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReceivable;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Patient $patient;

    private Visit $visit;

    private Invoice $invoice;

    private function addPayableItem(Invoice $invoice, float $amount = 100.00): InvoiceItem
    {
        return InvoiceItem::create([
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
    }

    protected function setUp(): void
    {
        parent::setUp();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $role = Role::create(['name' => 'Accountant']);
        foreach ([
            'invoices.view', 'invoices.create', 'invoices.edit',
            'payments.view', 'payments.create',
            'patients.view', 'visits.view', 'services.manage',
        ] as $p) {
            $perm = Permission::create(['name' => $p]);
            $role->givePermissionTo($perm);
        }
        $this->user->assignRole($role);

        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $dept->id,
            'created_by' => $this->user->id,
        ]);
    }

    // ── Invoices ────────────────────────────────

    public function test_invoice_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.billing.invoices.index'));
        $response->assertStatus(200);
    }

    public function test_invoice_can_be_created(): void
    {
        $data = [
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'billing_type' => BillingType::CASH->value,
            'items' => [
                ['description' => 'Consultation Fee', 'unit_price' => 50.00, 'quantity' => 1],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('admin.billing.invoices.store'), $data);
        $response->assertRedirect();
    }

    // ── Payments ────────────────────────────────

    public function test_payment_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('admin.billing.payments.index'));
        $response->assertStatus(200);
    }

    public function test_payment_can_be_recorded(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV00001',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'billing_type' => BillingType::CASH->value,
            'subtotal' => 100.00,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 0,
            'total_amount' => 100.00,
            'amount_paid' => 0,
            'balance' => 100.00,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);
        $this->addPayableItem($invoice, 100.00);
        $invoice->load('items');

        $data = [
            'amount' => 100.00,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        ];

        $response = $this->actingAs($this->user)->post(route('admin.billing.payments.store', $invoice), $data);
        $response->assertRedirect();
    }

    public function test_receive_payment_records_against_patient_receivable_on_insured_invoice(): void
    {
        $provider = InsuranceProvider::create([
            'name' => 'National Health Insurance Scheme',
            'short_name' => 'NHIS',
            'code' => 'NHIS',
            'type' => 'nhia',
            'is_active' => true,
            'is_default' => false,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV00002',
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'billing_type' => BillingType::INSURANCE->value,
            'subtotal' => 80.00,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 40.00,
            'total_amount' => 40.00,
            'amount_paid' => 0,
            'balance' => 40.00,
            'status' => InvoiceStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'description' => 'General Consultation',
            'quantity' => 1,
            'unit_price' => 80.00,
            'cash_price' => 100.00,
            'selected_price' => 80.00,
            'insurance_covered' => 40.00,
            'insurance_provider_id' => $provider->id,
            'discount_amount' => 0,
            'patient_payable' => 40.00,
            'paid_amount' => 0,
            'balance' => 40.00,
            'payment_status' => 'unpaid',
            'total_price' => 80.00,
            'payer_type' => BillingType::INSURANCE->value,
            'created_by' => $this->user->id,
        ]);

        app(\App\Services\InvoiceReceivableService::class)->syncFromInvoice($invoice);

        $insuranceReceivable = InvoiceReceivable::where('invoice_id', $invoice->id)
            ->where('payer_type', InvoiceReceivable::PAYER_INSURANCE)
            ->sole();

        $response = $this->actingAs($this->user)->post(route('admin.billing.payments.store', $invoice), [
            'return_to' => 'receive',
            'amount' => 40.00,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
            'payer_type' => InvoiceReceivable::PAYER_INSURANCE,
            'payer_id' => $provider->id,
            'invoice_receivable_id' => $insuranceReceivable->id,
        ]);

        $response->assertRedirect(route('admin.billing.payments.receive'));

        $payment = $invoice->payments()->latest('id')->first();
        $patientReceivable = InvoiceReceivable::where('invoice_id', $invoice->id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->sole();
        $insuranceReceivable = InvoiceReceivable::where('invoice_id', $invoice->id)
            ->where('payer_type', InvoiceReceivable::PAYER_INSURANCE)
            ->sole();

        $this->assertSame(InvoiceReceivable::PAYER_PATIENT, $payment->payer_type);
        $this->assertSame($this->patient->id, (int) $payment->payer_id);
        $this->assertSame($patientReceivable->id, (int) $payment->invoice_receivable_id);
        $this->assertNull($payment->insurance_provider_id);

        $this->assertSame(40.0, (float) $patientReceivable->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $patientReceivable->fresh()->balance);
        $this->assertSame(0.0, (float) $insuranceReceivable->fresh()->paid_amount);
        $this->assertSame(40.0, (float) $insuranceReceivable->fresh()->balance);
    }
}
