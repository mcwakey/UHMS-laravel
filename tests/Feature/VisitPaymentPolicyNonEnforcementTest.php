<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitType;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PatientFinancialRiskService;
use App\Services\Billing\VisitPaymentTimingResolver;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves the observational visit-payment-policy record + risk recommendation
 * change NO production payment behaviour (Payment Timing Policy Phase 6).
 */
class VisitPaymentPolicyNonEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
    }

    private function classify(Patient $patient, PatientFinancialRiskLevel $level): void
    {
        app(PatientFinancialRiskService::class)->createOrClassify($patient, PatientFinancialRiskData::fromValidated([
            'risk_level' => $level->value,
            'primary_reason' => 'management_decision', 'reason_details' => 'x',
            'effective_from' => now()->toDateString(),
        ]), User::factory()->create());
    }

    public function test_resolver_remains_risk_unaware(): void
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'visit_type' => VisitType::INPATIENT->value]);
        $before = app(VisitPaymentTimingResolver::class)->resolve($visit->fresh());

        $this->classify($patient, PatientFinancialRiskLevel::BLOCKED_CREDIT);

        $after = app(VisitPaymentTimingResolver::class)->resolve($visit->fresh());
        $this->assertSame($before->policy, $after->policy);
        $this->assertSame($before->source, $after->source);
    }

    public function test_billing_policy_advisory_outcome_unchanged_by_recommendation(): void
    {
        config(['billing_policy.enforce' => false]);
        $patient = Patient::factory()->create();

        $before = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);
        $this->classify($patient, PatientFinancialRiskLevel::HIGH_RISK);
        Visit::factory()->create(['patient_id' => $patient->id, 'visit_type' => VisitType::OUTPATIENT->value]);
        $after = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);

        $this->assertSame($before->allowed, $after->allowed);
        $this->assertSame($before->mode, $after->mode);
        $this->assertTrue($after->allowed);
    }

    public function test_materialisation_creates_no_invoice_override_or_visit_state_change(): void
    {
        $patient = Patient::factory()->create();
        $this->classify($patient, PatientFinancialRiskLevel::BLOCKED_CREDIT);
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'visit_type' => VisitType::OUTPATIENT->value]);

        $this->assertNotNull($visit->paymentPolicy); // observational record exists
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('visit_billing_overrides', 0);
    }
}
