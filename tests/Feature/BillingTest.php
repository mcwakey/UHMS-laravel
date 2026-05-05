<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\BillingType;
use App\Enums\PaymentMethod;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        $data = [
            'amount' => 100.00,
            'payment_method' => PaymentMethod::BANK_TRANSFER->value,
        ];

        $response = $this->actingAs($this->user)->post(route('admin.billing.payments.store', $invoice), $data);
        $response->assertRedirect();
    }
}
