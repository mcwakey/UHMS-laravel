<?php

namespace Tests\Feature;

use App\Data\Billing\VisitPaymentArrangementApprovalData;
use App\Data\Billing\VisitPaymentArrangementData;
use App\Enums\VisitType;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\VisitPaymentArrangementService;
use App\Services\Billing\VisitPaymentTimingResolver;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves an approved per-visit arrangement remains ADMINISTRATIVE only (Payment
 * Timing Policy Phase 7) — it changes no production payment behaviour.
 */
class VisitPaymentArrangementNonEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private User $u1;
    private User $u2;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false]);
        $this->u1 = User::factory()->create(); // id 1
        $this->u2 = User::factory()->create();
        (new PaymentTimingSettingsSeeder)->run();
    }

    private function approve(Visit $visit, string $policy): void
    {
        $svc = app(VisitPaymentArrangementService::class);
        $a = $svc->request($visit, VisitPaymentArrangementData::fromValidated([
            'requested_policy' => $policy, 'request_reason' => 'x', 'effective_from' => now()->toDateString(),
        ]), $this->u1);
        $svc->approve($a, VisitPaymentArrangementApprovalData::fromValidated([]), $this->u2);
    }

    public function test_approved_arrangement_does_not_change_resolver(): void
    {
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::INPATIENT->value]);
        $before = app(VisitPaymentTimingResolver::class)->resolve($visit->fresh());

        $this->approve($visit, 'pay_after_all_services');

        $after = app(VisitPaymentTimingResolver::class)->resolve($visit->fresh());
        $this->assertSame($before->policy, $after->policy);
        $this->assertSame($before->source, $after->source);
    }

    public function test_approved_arrangement_does_not_change_billing_policy(): void
    {
        config(['billing_policy.enforce' => false]);
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);

        $before = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);
        $this->approve($visit, 'pay_after_all_services');
        $after = app(BillingPolicyService::class)->getInvoiceItemPolicy(new InvoiceItem);

        $this->assertSame($before->allowed, $after->allowed);
        $this->assertSame($before->mode, $after->mode);
        $this->assertTrue($after->allowed);
    }

    public function test_approval_creates_no_invoice_receivable_or_override(): void
    {
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);
        $this->approve($visit, 'pay_before_service');

        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('visit_billing_overrides', 0);
        // Baseline on the observational record is unchanged (still not patient_risk/arrangement sourced).
        $this->assertNotSame('patient_risk', $visit->paymentPolicy->fresh()->resolution_source->value);
    }
}
