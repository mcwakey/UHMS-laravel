<?php

namespace Tests\Feature;

use App\Enums\InsuranceType;
use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Models\Account;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\InvoiceReceivable;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Payment;
use App\Models\ServiceCatalog;
use App\Models\ServicePrice;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingService;
use App\Services\InvoiceService;
use App\Services\VisitService;
use Database\Seeders\AccountingChartSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceCoverageSelectedPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_coverage_is_calculated_from_selected_insurance_price(): void
    {
        [$visit, $provider] = $this->insuredVisitWithTier(80);
        $service = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 70);

        $item = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $service,
            sourceType: 'service_catalog',
            sourceId: $service->id,
        );

        $this->assertSame(100.0, (float) $item->cash_price);
        $this->assertSame(70.0, (float) $item->selected_price);
        $this->assertSame(70.0, (float) $item->total_price);
        $this->assertSame(56.0, (float) $item->insurance_covered);
        $this->assertSame(14.0, (float) $item->patient_payable);
        $this->assertSame(14.0, (float) $item->balance);

        $patientReceivable = InvoiceReceivable::where('invoice_id', $item->invoice_id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->sole();
        $insuranceReceivable = InvoiceReceivable::where('invoice_id', $item->invoice_id)
            ->where('payer_type', InvoiceReceivable::PAYER_INSURANCE)
            ->sole();

        $this->assertSame($visit->patient_id, $patientReceivable->payer_id);
        $this->assertSame(14.0, (float) $patientReceivable->allocated_amount);
        $this->assertSame($provider->id, $insuranceReceivable->payer_id);
        $this->assertSame(56.0, (float) $insuranceReceivable->allocated_amount);
    }

    public function test_cash_fallback_does_not_calculate_insurance_coverage(): void
    {
        [$visit] = $this->insuredVisitWithTier(80);
        $service = $this->serviceWithProviderPrice(null, cashPrice: 100, insurancePrice: null);

        $item = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $service,
            sourceType: 'service_catalog',
            sourceId: $service->id,
        );

        $this->assertSame(100.0, (float) $item->selected_price);
        $this->assertSame('fallback_cash_no_insurance_price', $item->pricing_source);
        $this->assertSame(0.0, (float) $item->insurance_covered);
        $this->assertSame(100.0, (float) $item->patient_payable);
    }

    public function test_fully_covered_invoice_posts_insurance_receivable_journal_entry(): void
    {
        $this->seed(AccountingChartSeeder::class);

        [$visit, $provider] = $this->insuredVisitWithTier(100);
        $service = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 80);

        $item = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $service,
            sourceType: 'consultation',
            sourceId: $service->id,
        );

        $invoice = $item->invoice()->with(['receivables', 'journalEntry.lines'])->first();
        $insuranceReceivableAccountId = Account::where('code', '1220')->value('id');
        $revenueAccountId = Account::where('code', '4100')->value('id');

        $this->assertSame(0.0, (float) $item->patient_payable);
        $this->assertSame(80.0, (float) $item->insurance_covered);
        $this->assertSame(InvoiceStatus::PENDING, $invoice->status);
        $this->assertNotNull($invoice->journal_entry_id);

        $insuranceReceivable = $invoice->receivables
            ->where('payer_type', InvoiceReceivable::PAYER_INSURANCE)
            ->sole();

        $this->assertSame($provider->id, (int) $insuranceReceivable->payer_id);
        $this->assertSame(80.0, (float) $insuranceReceivable->allocated_amount);
        $this->assertSame(80.0, (float) $insuranceReceivable->balance);

        $insuranceDebit = $invoice->journalEntry->lines->firstWhere('account_id', $insuranceReceivableAccountId);
        $revenueCredit = $invoice->journalEntry->lines->firstWhere('account_id', $revenueAccountId);

        $this->assertEqualsWithDelta(80.0, (float) $insuranceDebit->debit, 0.001);
        $this->assertEqualsWithDelta(80.0, (float) $revenueCredit->credit, 0.001);
    }

    public function test_visit_service_attach_reposts_existing_fully_covered_invoice_item(): void
    {
        $this->seed(AccountingChartSeeder::class);

        [$visit, $provider, , $insurance] = $this->insuredVisitWithTier(100);
        $service = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 80);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-VISIT-INS-001',
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => BillingType::INSURANCE->value,
            'subtotal' => 80,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 80,
            'total_amount' => 0,
            'amount_paid' => 0,
            'balance' => 0,
            'status' => InvoiceStatus::DRAFT->value,
            'created_by' => $visit->created_by,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'service_catalog_id' => $service->id,
            'department_id' => $service->department_id,
            'source_type' => 'service_catalog',
            'source_id' => $service->id,
            'description' => $service->name,
            'quantity' => 1,
            'unit_price' => 80,
            'cash_price' => 100,
            'insurance_price' => 80,
            'selected_price' => 80,
            'insurance_covered' => 80,
            'discount_amount' => 0,
            'patient_payable' => 0,
            'paid_amount' => 0,
            'balance' => 0,
            'payment_status' => 'paid',
            'total_price' => 80,
            'payer_type' => BillingType::INSURANCE->value,
            'insurance_provider_id' => $provider->id,
            'patient_insurance_id' => $insurance->id,
            'insurance_type' => $provider->type,
            'pricing_source' => 'provider_specific_price',
            'created_by' => $visit->created_by,
        ]);

        app(VisitService::class)->attachServices($visit, [
            ['service_catalog_id' => $service->id, 'quantity' => 1],
        ]);

        $invoice = $invoice->fresh(['journalEntry.lines']);
        $insuranceReceivableAccountId = Account::where('code', '1220')->value('id');

        $this->assertSame(InvoiceStatus::PENDING, $invoice->status);
        $this->assertNotNull($invoice->journal_entry_id);
        $this->assertEqualsWithDelta(
            80.0,
            (float) $invoice->journalEntry->lines->firstWhere('account_id', $insuranceReceivableAccountId)->debit,
            0.001
        );
    }

    public function test_usage_summary_counts_invoice_item_covered_amount_against_tier_limits(): void
    {
        [$visit, $provider, $tier, $insurance] = $this->insuredVisitWithTier(80);
        $tier->forceFill([
            'annual_limit' => 100,
            'max_per_month' => 60,
        ])->save();
        $service = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 70);

        app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $service,
            sourceType: 'service_catalog',
            sourceId: $service->id,
        );

        $summary = app(\App\Services\InsuranceService::class)->getUsageSummary($insurance->fresh('insuranceTier'));

        $this->assertSame(56.0, (float) $summary['used_this_year']);
        $this->assertSame(56.0, (float) $summary['used_this_month']);
        $this->assertSame(44.0, (float) $summary['remaining_annual']);
        $this->assertSame(4.0, (float) $summary['remaining_monthly']);
    }

    public function test_item_added_after_visit_limit_is_exhausted_falls_back_to_cash(): void
    {
        [$visit, $provider, $tier] = $this->insuredVisitWithTier(80);
        $tier->forceFill(['per_visit_limit' => 56])->save();

        $first = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 70);
        app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $first,
            sourceType: 'service_catalog',
            sourceId: $first->id,
        );

        $second = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 70);
        $item = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $second,
            sourceType: 'service_catalog',
            sourceId: $second->id,
        );

        $this->assertSame('cash', $item->payer_type);
        $this->assertSame('cash_price', $item->pricing_source);
        $this->assertSame(100.0, (float) $item->selected_price);
        $this->assertSame(0.0, (float) $item->insurance_covered);
        $this->assertSame(100.0, (float) $item->patient_payable);

        $patientReceivable = InvoiceReceivable::where('invoice_id', $item->invoice_id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->sole();

        $this->assertSame(114.0, (float) $patientReceivable->allocated_amount);
        $this->assertSame(114.0, (float) $patientReceivable->balance);
    }

    public function test_patient_receivable_reopens_when_new_items_are_added_after_payment(): void
    {
        [$visit, $provider, $tier] = $this->insuredVisitWithTier(80);
        $tier->forceFill(['per_visit_limit' => 56])->save();

        $first = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 70);
        $firstItem = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $first,
            sourceType: 'service_catalog',
            sourceId: $first->id,
        );

        $paidPatientReceivable = InvoiceReceivable::where('invoice_id', $firstItem->invoice_id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->sole();
        Payment::create([
            'payment_number' => Payment::generateNumber('PAY', 'payments', 'payment_number'),
            'invoice_id' => $firstItem->invoice_id,
            'invoice_receivable_id' => $paidPatientReceivable->id,
            'patient_id' => $visit->patient_id,
            'payer_type' => InvoiceReceivable::PAYER_PATIENT,
            'payer_id' => $visit->patient_id,
            'amount' => 14.00,
            'payment_method' => 'cash',
            'received_by' => $visit->created_by,
            'paid_at' => now(),
        ]);

        $second = $this->serviceWithProviderPrice($provider, cashPrice: 100, insurancePrice: 70);
        $secondItem = app(BillingService::class)->addItemToVisitInvoice(
            visit: $visit,
            service: $second,
            sourceType: 'service_catalog',
            sourceId: $second->id,
        );

        $patientReceivable = InvoiceReceivable::where('invoice_id', $secondItem->invoice_id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->sole();

        $this->assertSame(114.0, (float) $patientReceivable->allocated_amount);
        $this->assertSame(14.0, (float) $patientReceivable->paid_amount);
        $this->assertSame(100.0, (float) $patientReceivable->balance);
    }

    public function test_recalculate_moves_visit_limit_excess_from_insurance_to_patient_payable(): void
    {
        [$visit, $provider, $tier, $insurance] = $this->insuredVisitWithTier(80);
        $tier->forceFill(['per_visit_limit' => 200])->save();

        $invoice = Invoice::create([
            'invoice_number' => 'INV-LIMIT-001',
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'billing_type' => BillingType::INSURANCE->value,
            'subtotal' => 416,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'nhis_amount' => 208,
            'total_amount' => 208,
            'amount_paid' => 0,
            'balance' => 208,
            'status' => InvoiceStatus::PENDING->value,
            'created_by' => $visit->created_by,
        ]);

        $this->invoiceItem($invoice, $visit, $provider, $insurance, 'Consultation', 80, 40, 40);
        $this->invoiceItem($invoice, $visit, $provider, $insurance, 'FBC', 64, 32, 32);
        $this->invoiceItem($invoice, $visit, $provider, $insurance, 'Blood Group', 64, 32, 32);
        $this->invoiceItem($invoice, $visit, $provider, $insurance, 'Widal', 48, 24, 24);
        $ultrasound = $this->invoiceItem($invoice, $visit, $provider, $insurance, 'Ultrasound', 160, 80, 80);

        app(InvoiceService::class)->recalculateTotals($invoice);

        $ultrasound->refresh();
        $invoice->refresh();

        $this->assertSame(72.0, (float) $ultrasound->insurance_covered);
        $this->assertSame(88.0, (float) $ultrasound->patient_payable);
        $this->assertSame(200.0, (float) $invoice->nhis_amount);
        $this->assertSame(216.0, (float) $invoice->total_amount);

        $patientReceivable = InvoiceReceivable::where('invoice_id', $invoice->id)
            ->where('payer_type', InvoiceReceivable::PAYER_PATIENT)
            ->sole();
        $insuranceReceivable = InvoiceReceivable::where('invoice_id', $invoice->id)
            ->where('payer_type', InvoiceReceivable::PAYER_INSURANCE)
            ->sole();

        $this->assertSame(216.0, (float) $patientReceivable->allocated_amount);
        $this->assertSame(200.0, (float) $insuranceReceivable->allocated_amount);
    }

    private function insuredVisitWithTier(float $coveragePercent): array
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $patient = Patient::factory()->create(['registered_by' => $user->id]);
        $provider = InsuranceProvider::create([
            'name' => 'Selected Price Assurance',
            'short_name' => 'SPA',
            'code' => 'SPA',
            'type' => InsuranceType::PRIVATE->value,
            'is_active' => true,
            'is_default' => false,
        ]);
        $tier = InsuranceTier::create([
            'insurance_provider_id' => $provider->id,
            'name' => 'Standard',
            'code' => 'STD',
            'coverage_percentage' => $coveragePercent,
            'is_default' => true,
            'is_active' => true,
        ]);
        $insurance = PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_provider_id' => $provider->id,
            'insurance_tier_id' => $tier->id,
            'member_type' => 'holder',
            'membership_number' => 'SPA-10001',
            'start_date' => now()->subMonth()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'is_primary' => true,
            'is_active' => true,
        ]);
        $visit = Visit::factory()->create([
            'patient_id' => $patient->id,
            'visit_insurance_id' => $insurance->id,
            'created_by' => $user->id,
        ]);

        return [$visit, $provider, $tier, $insurance];
    }

    private function serviceWithProviderPrice(
        ?InsuranceProvider $provider,
        float $cashPrice,
        ?float $insurancePrice,
    ): ServiceCatalog {
        $department = Department::factory()->create();
        $service = ServiceCatalog::create([
            'name' => 'Coverage Fixture',
            'code' => 'COV-FIX-'.fake()->unique()->numberBetween(1000, 9999),
            'category' => 'clinical',
            'department_id' => $department->id,
            'price' => $cashPrice,
            'is_active' => true,
            'is_billable' => true,
        ]);

        if ($provider && $insurancePrice !== null) {
            ServicePrice::create([
                'service_catalog_id' => $service->id,
                'insurance_type' => InsuranceType::PRIVATE->value,
                'insurance_provider_id' => $provider->id,
                'price' => $insurancePrice,
            ]);
        }

        return $service;
    }

    private function invoiceItem(
        Invoice $invoice,
        Visit $visit,
        InsuranceProvider $provider,
        PatientInsurance $insurance,
        string $description,
        float $selectedPrice,
        float $covered,
        float $patientPayable,
    ): InvoiceItem {
        return InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $selectedPrice,
            'cash_price' => $selectedPrice,
            'selected_price' => $selectedPrice,
            'insurance_covered' => $covered,
            'insurance_provider_id' => $provider->id,
            'patient_insurance_id' => $insurance->id,
            'discount_amount' => 0,
            'patient_payable' => $patientPayable,
            'paid_amount' => 0,
            'balance' => $patientPayable,
            'payment_status' => $patientPayable > 0 ? 'unpaid' : 'paid',
            'total_price' => $selectedPrice,
            'payer_type' => BillingType::INSURANCE->value,
            'pricing_source' => 'provider_specific_price',
            'created_by' => $visit->created_by,
        ]);
    }
}
