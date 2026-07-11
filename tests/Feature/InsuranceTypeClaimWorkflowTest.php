<?php

namespace Tests\Feature;

use App\Enums\BillingType;
use App\Enums\ClaimStatus;
use App\Enums\InsuranceType as LegacyInsuranceType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Models\Claim;
use App\Models\ClaimPayment;
use App\Models\ClaimStatusLog;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\InsuranceType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Services\Claims\ClaimWorkflowManager;
use App\Services\Claims\NhiaClaimWorkflow;
use Database\Seeders\InsuranceProviderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class InsuranceTypeClaimWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->user = User::factory()->create();
        $role = Role::findOrCreate('NHIA Claims Tester', 'web');

        foreach ([
            'claims.view',
            'claims.create',
            'claims.approve',
            'claims.export',
            'claims.nhia.view',
            'claims.nhia.prepare',
            'invoices.view',
            'visits.view',
        ] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $this->user->assignRole($role);
    }

    public function test_nhia_type_and_nhis_provider_are_seeded_without_provider_workflow_hardcoding(): void
    {
        $this->seed(InsuranceProviderSeeder::class);

        $type = InsuranceType::where('code', 'NHIA')->first();
        $provider = InsuranceProvider::where('code', 'NHIS')->first();

        $this->assertNotNull($type);
        $this->assertNotNull($provider);
        $this->assertSame($type->id, $provider->insurance_type_id);
        $this->assertSame('NHIA', $provider->claimWorkflowCode());
        $this->assertSame('CCC Code', $provider->verificationCodeLabel());
    }

    public function test_workflow_manager_resolves_nhia_for_any_provider_under_nhia_type(): void
    {
        $type = $this->createNhiaType();
        $provider = InsuranceProvider::create([
            'name' => 'District Mutual NHIA Desk',
            'short_name' => 'DMN',
            'code' => 'DMN',
            'type' => LegacyInsuranceType::NHIA->value,
            'insurance_type_id' => $type->id,
            'is_active' => true,
            'is_default' => false,
        ]);

        $workflow = app(ClaimWorkflowManager::class)->forProvider($provider);

        $this->assertInstanceOf(NhiaClaimWorkflow::class, $workflow);
    }

    public function test_nhia_eligible_visits_page_shows_nhis_visits_and_excludes_cash_and_carry(): void
    {
        $nhiaInvoice = $this->createInsuranceInvoice();
        $cashInvoice = $this->createInsuranceInvoice(insuranceAmount: 0, provider: $this->createCashProvider());

        $response = $this->actingAs($this->user)->get(route('admin.claims.nhia.eligible-visits'));

        $response->assertOk()
            ->assertSee($nhiaInvoice->visit->visit_number)
            ->assertDontSee($cashInvoice->visit->visit_number);
    }

    public function test_valid_patient_nhia_insurance_makes_visit_eligible_even_without_ccc_or_visit_link(): void
    {
        $invoice = $this->createInsuranceInvoice(insuranceAmount: 0, cccCode: null);
        $nhiaInsurance = PatientInsurance::where('patient_id', $invoice->patient_id)
            ->where('insurance_provider_id', $invoice->items->first()->insurance_provider_id)
            ->firstOrFail();
        $cashProvider = $this->createCashProvider();
        $cashInsurance = PatientInsurance::create([
            'patient_id' => $invoice->patient_id,
            'insurance_provider_id' => $cashProvider->id,
            'membership_number' => 'CASH',
            'start_date' => now()->subYear()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'is_primary' => true,
            'is_active' => true,
        ]);
        $invoice->visit->forceFill(['visit_insurance_id' => $cashInsurance->id])->save();

        $this->actingAs($this->user)
            ->get(route('admin.claims.nhia.eligible-visits'))
            ->assertOk()
            ->assertSee($invoice->visit->visit_number)
            ->assertSee('National Health Insurance Scheme');

        $response = $this->actingAs($this->user)
            ->post(route('admin.claims.nhia.prepare-from-visit', $invoice->visit));

        $claim = Claim::first();

        $response->assertRedirect(route('admin.claims.show', $claim));
        $this->assertSame($nhiaInsurance->id, $claim->patient_insurance_id);
        $this->assertSame($nhiaInsurance->insurance_provider_id, $claim->insurance_provider_id);
        $this->assertSame('NHIS-123456789', $claim->membership_number);
        $this->assertNull($claim->verification_code);
        $this->assertEquals(80.00, (float) $claim->total_claim_amount);
        $this->assertEquals(80.00, (float) $claim->items->first()->claim_amount);
    }

    public function test_nhia_claim_preparation_stores_type_workflow_and_invoice_item_snapshots(): void
    {
        $invoice = $this->createInsuranceInvoice();
        $invoiceItem = $invoice->items->first();
        $invoiceItem->serviceCatalog->update(['price' => 9999]);

        $response = $this->actingAs($this->user)->post(route('admin.claims.nhia.prepare-from-visit', $invoice->visit));

        $claim = Claim::with(['items.invoiceItem', 'insuranceType', 'insuranceProvider'])->first();

        $response->assertRedirect(route('admin.claims.show', $claim));
        $this->assertSame('NHIA', $claim->claim_type_code);
        $this->assertSame('NHIA', $claim->claim_workflow_code);
        $this->assertSame($invoice->visit->visitInsurance->insurance_provider_id, $claim->insurance_provider_id);
        $this->assertSame($invoice->visit->visitInsurance->insurance_provider_id, $claim->insurance_provider_id);
        $this->assertEquals(80.00, (float) $claim->total_claim_amount);
        $this->assertEquals(80.00, (float) $claim->items->first()->claim_amount);
        $this->assertSame($invoiceItem->id, $claim->items->first()->invoice_item_id);
        $this->assertSame(1, ClaimStatusLog::where('claim_id', $claim->id)->count());
    }

    public function test_nhia_claim_does_not_require_ccc_before_ready_or_submission(): void
    {
        $invoice = $this->createInsuranceInvoice(cccCode: null);
        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);
        $claim = Claim::first();

        $this->actingAs($this->user)->post(route('admin.claims.mark-ready', $claim))
            ->assertSessionHas('success');

        $this->assertSame(ClaimStatus::READY, $claim->fresh()->status);

        $this->actingAs($this->user)->post(route('admin.claims.submit', $claim), [
            'submission_mode' => 'EXPORT',
        ])->assertRedirect(route('admin.claims.show', $claim));

        $claim->refresh();
        $this->assertSame(ClaimStatus::SUBMITTED, $claim->status);
        $this->assertNull($claim->verification_code);
    }

    public function test_nhia_claim_can_be_exported_and_manually_submitted_after_validation(): void
    {
        $invoice = $this->createInsuranceInvoice(cccCode: 'CCC-READY');
        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);
        $claim = Claim::first();

        $this->actingAs($this->user)->get(route('admin.claims.export-one', $claim))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->user)->post(route('admin.claims.submit', $claim), [
            'submission_mode' => 'EXPORT',
            'submission_reference' => 'PORTAL-REF-001',
        ])->assertRedirect(route('admin.claims.show', $claim));

        $claim->refresh();
        $this->assertSame(ClaimStatus::SUBMITTED, $claim->status);
        $this->assertSame('EXPORT', $claim->submission_mode);
        $this->assertSame('PORTAL-REF-001', $claim->submission_reference);
        $this->assertNotNull($claim->submitted_at);
    }

    public function test_claim_payment_is_recorded_separately_from_patient_invoice_payment(): void
    {
        $invoice = $this->createInsuranceInvoice(cccCode: 'CCC-PAID');
        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);
        $claim = Claim::first();
        $claim->update([
            'status' => ClaimStatus::APPROVED,
            'approved_amount' => 80,
        ]);
        $invoicePaidBefore = (float) $invoice->amount_paid;

        $this->actingAs($this->user)->post(route('admin.claims.payments.store', $claim), [
            'payment_date' => now()->toDateString(),
            'amount' => 30,
            'payment_reference' => 'BANK-001',
            'payment_method' => 'Bank Transfer',
        ])->assertSessionHas('success');

        $this->assertSame(1, ClaimPayment::count());
        $this->assertEquals(30.00, (float) $claim->fresh()->paid_amount);
        $this->assertSame(ClaimStatus::PARTIALLY_PAID, $claim->fresh()->status);
        $this->assertEquals($invoicePaidBefore, (float) $invoice->fresh()->amount_paid);
    }

    public function test_duplicate_active_claims_are_not_created_for_same_invoice_items(): void
    {
        $invoice = $this->createInsuranceInvoice(cccCode: 'CCC-DUP');

        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);
        $this->actingAs($this->user)->post(route('admin.claims.store-from-invoice'), [
            'invoice_id' => $invoice->id,
        ]);

        $this->assertSame(1, Claim::count());
        $this->assertSame(1, Claim::first()->items()->count());
    }

    private function createNhiaType(): InsuranceType
    {
        return InsuranceType::updateOrCreate(
            ['code' => 'NHIA'],
            [
                'name' => 'National Health Insurance Authority',
                'claim_workflow' => 'NHIA',
                'requires_claim_submission' => true,
                'requires_verification_code' => true,
                'verification_code_label' => 'CCC Code',
                'requires_diagnosis' => true,
                'requires_doctor' => true,
                'default_claim_export_format' => 'CSV',
                'is_active' => true,
            ]
        );
    }

    private function createNhiaProvider(?InsuranceType $type = null): InsuranceProvider
    {
        $type ??= $this->createNhiaType();

        return InsuranceProvider::create([
            'name' => 'National Health Insurance Scheme',
            'short_name' => 'NHIS',
            'code' => 'NHIS',
            'type' => LegacyInsuranceType::NHIA->value,
            'insurance_type_id' => $type->id,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    private function createCashProvider(): InsuranceProvider
    {
        return InsuranceProvider::create([
            'name' => 'Cash & Carry',
            'short_name' => 'CASH',
            'code' => 'CASH',
            'type' => LegacyInsuranceType::SELF->value,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function createInsuranceInvoice(
        float $insuranceAmount = 80.00,
        ?InsuranceProvider $provider = null,
        ?string $cccCode = 'CCC-0001',
    ): Invoice {
        $provider ??= $this->createNhiaProvider();

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
            'membership_number' => $provider->is_default ? 'CASH' : 'NHIS-123456789',
            'ccc_code' => $cccCode,
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
            'code' => 'CONS-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'category' => 'consultation',
            'price' => 100,
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-INS-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'billing_type' => $provider->is_default ? BillingType::CASH->value : BillingType::INSURANCE->value,
            'subtotal' => 100,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => $insuranceAmount,
            'total_amount' => 100,
            'amount_paid' => $insuranceAmount,
            'balance' => 100 - $insuranceAmount,
            'status' => InvoiceStatus::PENDING->value,
            'due_date' => now()->addDays(30)->toDateString(),
            'created_by' => $this->user->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $patient->id,
            'service_catalog_id' => $service->id,
            'description' => 'General Consultation',
            'quantity' => 1,
            'unit_price' => 100,
            'cash_price' => 100,
            'insurance_price' => 80,
            'selected_price' => 80,
            'total_price' => 100,
            'insurance_covered' => $insuranceAmount,
            'is_nhis_covered' => $insuranceAmount > 0,
            'nhis_approved_amount' => $insuranceAmount,
            'patient_payable' => 100 - $insuranceAmount,
            'balance' => 100 - $insuranceAmount,
            'insurance_provider_id' => $provider->is_default ? null : $provider->id,
            'patient_insurance_id' => $provider->is_default ? null : $patientInsurance->id,
            'insurance_type' => $provider->is_default ? null : $provider->type->value,
        ]);

        return $invoice->fresh(['items.serviceCatalog', 'visit.visitInsurance.insuranceProvider.insuranceType']);
    }
}
