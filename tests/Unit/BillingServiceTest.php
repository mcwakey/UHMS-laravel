<?php

namespace Tests\Unit;

use App\Enums\InvoiceStatus;
use App\Enums\BillingType;
use App\Enums\PaymentMethod;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $service;
    private User $user;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->service = app(BillingService::class);

        Role::firstOrCreate(['name' => 'Accountant', 'guard_name' => 'web']);

        $dept = Department::factory()->create();
        $this->user = User::factory()->create(['department_id' => $dept->id]);
        $this->patient = Patient::factory()->create(['registered_by' => $this->user->id]);
        $this->visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'current_department_id' => $dept->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_create_invoice(): void
    {
        $this->actingAs($this->user);

        $invoice = $this->service->createInvoice(
            [
                'visit_id' => $this->visit->id,
                'patient_id' => $this->patient->id,
                'billing_type' => BillingType::CASH->value,
            ],
            [
                ['description' => 'Consultation', 'unit_price' => 50.00, 'quantity' => 1],
            ]
        );

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals(InvoiceStatus::PENDING, $invoice->status);
    }

    public function test_record_payment(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'invoice_number' => 'INV00099',
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

        $payment = $this->service->recordPayment($invoice, [
            'amount' => 50.00,
            'payment_method' => PaymentMethod::CASH->value,
        ]);

        $invoice->refresh();
        $this->assertEquals(50.00, (float) $payment->amount);
        $this->assertEquals(50.00, (float) $invoice->amount_paid);
        $this->assertEquals(InvoiceStatus::PARTIALLY_PAID, $invoice->status);
    }

    public function test_record_full_payment_marks_invoice_paid(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'invoice_number' => 'INV00100',
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

        $this->service->recordPayment($invoice, [
            'amount' => 100.00,
            'payment_method' => PaymentMethod::CASH->value,
        ]);

        $invoice->refresh();
        $this->assertEquals(InvoiceStatus::PAID, $invoice->status);
        $this->assertEquals(0, (float) $invoice->balance);
    }

    public function test_cancel_invoice(): void
    {
        $this->actingAs($this->user);

        $invoice = Invoice::create([
            'invoice_number' => 'INV00101',
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

        $cancelled = $this->service->cancelInvoice($invoice);
        $this->assertEquals(InvoiceStatus::CANCELLED, $cancelled->status);
    }

    public function test_get_billing_stats(): void
    {
        $stats = $this->service->getStats();

        $this->assertIsArray($stats);
    }
}
