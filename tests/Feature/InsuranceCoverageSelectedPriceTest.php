<?php

namespace Tests\Feature;

use App\Enums\InsuranceType;
use App\Models\Department;
use App\Models\InsuranceProvider;
use App\Models\InsuranceTier;
use App\Models\InvoiceReceivable;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\ServiceCatalog;
use App\Models\ServicePrice;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillingService;
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
}
