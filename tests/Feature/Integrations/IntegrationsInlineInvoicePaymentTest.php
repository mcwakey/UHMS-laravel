<?php

namespace Tests\Feature\Integrations;

use App\Enums\InvoiceStatus;
use App\Models\Department;
use App\Models\IntegrationProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentProviderTransaction;
use App\Models\User;
use App\Models\Visit;
use App\Services\Integrations\IntegrationProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Third payment path: inline mobile-money on the invoice screen. Verifies the
 * charge/verify endpoints + the self-contained component, and that verification
 * deducts the invoice through the normal PaymentService.
 */
class IntegrationsInlineInvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function userWith(array $permissions = []): User
    {
        $role = Role::findOrCreate('InlineRole-' . uniqid(), 'web');
        foreach ($permissions as $p) {
            $role->givePermissionTo(Permission::findOrCreate($p, 'web'));
        }
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function fakePaymentProvider(): IntegrationProvider
    {
        return IntegrationProvider::create(array_merge([
            'module_type' => 'payment', 'code' => 'fake_payment', 'name' => 'Fake Payment',
            'environment' => 'sandbox', 'status' => 'active', 'is_active' => true,
        ], IntegrationProviderService::capabilityFlags('payment', 'fake_payment')));
    }

    private function payableInvoice(float $amount = 100.0): Invoice
    {
        Role::findOrCreate('Accountant', 'web');
        $dept = Department::factory()->create();
        $user = User::factory()->create(['department_id' => $dept->id]);
        $patient = Patient::factory()->create(['registered_by' => $user->id, 'phone' => '0241234567']);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'current_department_id' => $dept->id, 'created_by' => $user->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV' . uniqid(), 'visit_id' => $visit->id, 'patient_id' => $patient->id,
            'billing_type' => 'cash', 'subtotal' => $amount, 'tax_amount' => 0, 'discount_amount' => 0,
            'nhis_amount' => 0, 'total_amount' => $amount, 'amount_paid' => 0, 'balance' => $amount,
            'status' => InvoiceStatus::PENDING, 'created_by' => $user->id,
        ]);
        InvoiceItem::create([
            'invoice_id' => $invoice->id, 'visit_id' => $visit->id, 'patient_id' => $patient->id,
            'description' => 'Service', 'quantity' => 1, 'unit_price' => $amount, 'cash_price' => $amount,
            'selected_price' => $amount, 'insurance_covered' => 0, 'discount_amount' => 0, 'patient_payable' => $amount,
            'paid_amount' => 0, 'balance' => $amount, 'payment_status' => 'unpaid', 'total_price' => $amount,
            'payer_type' => 'cash', 'created_by' => $user->id,
        ]);
        return $invoice->load('items');
    }

    public function test_inline_charge_creates_transaction_without_uhms_payment(): void
    {
        $this->fakePaymentProvider();
        $invoice = $this->payableInvoice(100);

        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.transactions.initiate']))
            ->post(route('admin.integrations.payments.invoices.charge', $invoice), [
                'payer_phone' => '0241234567', 'payment_method' => 'mtn_momo',
            ])
            ->assertRedirect(route('admin.billing.invoices.show', $invoice));

        $this->assertDatabaseHas('payment_provider_transactions', ['invoice_id' => $invoice->id, 'status' => 'pending']);
        $this->assertSame(0, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_inline_verify_creates_payment_and_deducts_invoice(): void
    {
        $this->fakePaymentProvider();
        $invoice = $this->payableInvoice(100);

        $actor = $this->userWith([
            'integrations.payments.view', 'integrations.payments.transactions.initiate', 'integrations.payments.transactions.verify',
        ]);
        $this->actingAs($actor)->post(route('admin.integrations.payments.invoices.charge', $invoice), [
            'payer_phone' => '0241234567', 'payment_method' => 'mtn_momo',
        ]);

        $txn = PaymentProviderTransaction::where('invoice_id', $invoice->id)->latest()->first();

        $this->actingAs($actor)
            ->post(route('admin.integrations.payments.transactions.verify-inline', $txn))
            ->assertRedirect(route('admin.billing.invoices.show', $invoice));

        $this->assertSame(1, Payment::where('invoice_id', $invoice->id)->count());
        $this->assertSame(InvoiceStatus::PAID->value, $invoice->fresh()->status->value ?? $invoice->fresh()->status);
    }

    public function test_inline_charge_blocked_on_paid_invoice(): void
    {
        $this->fakePaymentProvider();
        $invoice = $this->payableInvoice(100);
        $invoice->update(['status' => InvoiceStatus::PAID, 'balance' => 0, 'amount_paid' => 100]);

        $this->actingAs($this->userWith(['integrations.payments.view', 'integrations.payments.transactions.initiate']))
            ->post(route('admin.integrations.payments.invoices.charge', $invoice), [
                'payer_phone' => '0241234567', 'payment_method' => 'mtn_momo',
            ])
            ->assertRedirect();

        $this->assertSame(0, PaymentProviderTransaction::where('invoice_id', $invoice->id)->count());
    }

    public function test_inline_charge_requires_permission(): void
    {
        $this->fakePaymentProvider();
        $invoice = $this->payableInvoice(100);

        $this->actingAs($this->userWith(['integrations.payments.view']))
            ->post(route('admin.integrations.payments.invoices.charge', $invoice), [
                'payer_phone' => '0241234567', 'payment_method' => 'mtn_momo',
            ])
            ->assertForbidden();
    }

    public function test_component_renders_when_gateway_active_and_hides_when_off(): void
    {
        $invoice = $this->payableInvoice(100);
        $this->actingAs($this->userWith(['integrations.payments.transactions.initiate']));

        // No active provider → component renders nothing.
        $empty = Blade::render('<x-integrations.invoice-payment :invoice="$invoice" />', ['invoice' => $invoice]);
        $this->assertStringNotContainsString(__('payments.gateway.pay_with_mobile_money'), $empty);

        // Active provider → the inline card appears.
        $this->fakePaymentProvider();
        $shown = Blade::render('<x-integrations.invoice-payment :invoice="$invoice" />', ['invoice' => $invoice->fresh()]);
        $this->assertStringContainsString(__('payments.gateway.pay_with_mobile_money'), $shown);
    }
}
