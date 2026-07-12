<?php

namespace Tests\Feature;

use App\Data\Billing\PatientFinancialRiskData;
use App\Enums\PatientFinancialRiskLevel;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PatientFinancialRiskService;
use App\Services\Billing\PatientOutstandingBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Explicitly proves that recording a financial-risk profile changes NO
 * production payment behaviour (Payment Timing Policy Phase 5).
 */
class PatientFinancialRiskNonEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        User::factory()->create(); // id 1 for PatientFactory registered_by
    }

    private function classifyBlockedCredit(Patient $patient): void
    {
        app(PatientFinancialRiskService::class)->createOrClassify(
            $patient,
            PatientFinancialRiskData::fromValidated([
                'risk_level' => PatientFinancialRiskLevel::BLOCKED_CREDIT->value,
                'primary_reason' => 'management_decision',
                'reason_details' => 'test',
                'effective_from' => now()->toDateString(),
            ]),
            User::factory()->create(),
        );
    }

    public function test_classification_creates_no_visit_invoice_or_override(): void
    {
        $patient = Patient::factory()->create();
        config(['audit_streaming.async_writes' => false]);

        $this->classifyBlockedCredit($patient);

        $this->assertDatabaseCount('visits', 0);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame(1, \App\Models\PatientFinancialRiskProfile::count());
    }

    public function test_billing_policy_advisory_outcome_is_unchanged_by_risk_profile(): void
    {
        $patient = Patient::factory()->create();
        config(['audit_streaming.async_writes' => false, 'billing_policy.enforce' => false]);

        $before = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);
        $this->classifyBlockedCredit($patient);
        $after = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);

        $this->assertSame($before->allowed, $after->allowed);
        $this->assertSame($before->mode, $after->mode);
        $this->assertTrue($after->allowed);
    }

    public function test_previous_outstanding_balance_is_unaffected(): void
    {
        $patient = Patient::factory()->create();
        config(['audit_streaming.async_writes' => false]);

        $this->classifyBlockedCredit($patient);

        $this->assertSame(0.0, app(PatientOutstandingBalanceService::class)->getPreviousOutstandingBalance($patient));
    }
}
