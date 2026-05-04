<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\InsuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Models\Claim;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NhisClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('Claims Tester', 'web');

        foreach (['claims.view', 'claims.create', 'claims.approve', 'claims.export', 'invoices.view'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($role);
    }

    public function test_nhis_claim_from_invoice_uses_only_nhis_approved_invoice_lines(): void
    {
        $invoice = $this->createNhisInvoice();

        $response = $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);

        $claim = Claim::with('items')->first();

        $response->assertRedirect(route('admin.claims.show', $claim));
        $this->assertNotNull($claim);
        $this->assertSame($invoice->id, $claim->invoice_id);
        $this->assertSame($invoice->visit->visitInsurance->insurance_provider_id, $claim->insurance_provider_id);
        $this->assertEquals(80.00, (float) $claim->total_amount);
        $this->assertCount(1, $claim->items);
        $this->assertSame('General Consultation', $claim->items->first()->service_name);
        $this->assertEquals(80.00, (float) $claim->items->first()->total_price);
    }

    public function test_invoice_claim_generation_does_not_duplicate_existing_claim(): void
    {
        $invoice = $this->createNhisInvoice();

        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);

        $this->assertSame(1, Claim::count());
        $this->assertSame(1, Claim::first()->items()->count());
    }

    public function test_invoice_without_nhis_covered_lines_cannot_create_claim(): void
    {
        $invoice = $this->createNhisInvoice(0);
        $invoice->items()->update([
            'is_nhis_covered' => false,
            'nhis_approved_amount' => 0,
        ]);

        $response = $this->actingAs($this->user)->from(route('admin.billing.invoices.show', $invoice))
            ->post(route('admin.claims.store-from-invoice'), [
                'invoice_id' => $invoice->id,
            ]);

        $response->assertRedirect(route('admin.billing.invoices.show', $invoice));
        $response->assertSessionHas('error', 'This invoice has no NHIS-covered items to claim.');
        $this->assertSame(0, Claim::count());
    }

    public function test_invoice_show_links_to_generate_or_view_nhis_claim(): void
    {
        $invoice = $this->createNhisInvoice();

        $this->actingAs($this->user)->get(route('admin.billing.invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Generate NHIS Claim');

        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);

        $this->actingAs($this->user)->get(route('admin.billing.invoices.show', $invoice->fresh()))
            ->assertOk()
            ->assertSee('View NHIS Claim');
    }

    private function createNhisInvoice(float $nhisAmount = 80.00): Invoice
    {
        $provider = InsuranceProvider::create([
            'name' => 'National Health Insurance Authority',
            'short_name' => 'NHIA',
            'type' => InsuranceType::NHIA->value,
            'is_active' => true,
            'is_default' => false,
        ]);

        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name' => 'Standard',
            'code' => 'STD',
            'is_default' => true,
            'is_active' => true,
            'coverage_percentage' => 100,
        ]);

        $patient = Patient::factory()->create([
            'registered_by' => $this->user->id,
            'status' => 'active',
        ]);

        $patientInsurance = PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id' => $tier->id,
            'membership_number' => 'NHIS-123456789',
            'start_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'is_primary' => true,
            'is_active' => true,
        ]);

        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_insurance_id' => $patientInsurance->id,
            'status' => VisitStatus::BILLING,
            'created_by' => $this->user->id,
        ]);

        $service = ServiceCatalog::create([
            'name' => 'General Consultation',
            'code' => 'CONS-001',
            'category' => 'consultation',
            'price' => 100,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-NHIS-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => BillingType::NHIS->value,
            'subtotal' => 150,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => $nhisAmount,
            'total_amount' => 150,
            'amount_paid' => $nhisAmount,
            'balance' => 150 - $nhisAmount,
            'status' => InvoiceStatus::PENDING->value,
            'due_date' => now()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_catalog_id' => $service->id,
            'description' => 'General Consultation',
            'quantity' => 1,
            'unit_price' => 100,
            'total_price' => 100,
            'is_nhis_covered' => $nhisAmount > 0,
            'nhis_approved_amount' => $nhisAmount,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'service_catalog_id' => null,
            'description' => 'Patient Folder',
            'quantity' => 1,
            'unit_price' => 50,
            'total_price' => 50,
            'is_nhis_covered' => false,
            'nhis_approved_amount' => 0,
        ]);

        return $invoice->fresh(['items', 'visit.visitInsurance.insuranceProvider']);
    }
}