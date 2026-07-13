<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateContext;
use App\Data\Billing\VisitPaymentArrangementApprovalData;
use App\Data\Billing\VisitPaymentArrangementData;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\User;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\VisitPaymentArrangementService;
use App\Enums\VisitType;
use Database\Seeders\PaymentTimingSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end typed payment-timing integration through BillingPolicyService.
 * When payment timing is enabled, typed visit policy is authoritative at every
 * payment gate that reaches PaymentGateService.
 */
class PaymentGateTypedIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['audit_streaming.async_writes' => false, 'billing_policy.enforce' => true, 'billing_policy.opd.payment_required_before_service' => true]);
        User::factory()->create(); // id 1
        (new PaymentTimingSettingsSeeder)->run();
    }

    private function setCutover(string $master, ?string $operationMode = null, string $operation = 'consultation.route.complete'): void
    {
        Setting::setValue('payment_timing', 'enabled', true, 'boolean');
        Setting::setValue('payment_timing', 'cutover_mode', $master, 'string');
        if ($operationMode !== null) {
            Setting::setValue('payment_gate_operations', $operation, ['mode' => $operationMode, 'compatibility_acknowledged' => true], 'json');
        }
    }

    private function enableGlobalPolicy(string $policy): void
    {
        Setting::setValue('payment_timing', 'enabled', true, 'boolean');
        Setting::setValue('payment_timing', 'default_policy', $policy, 'string');
        Setting::setValue('payment_timing', 'outpatient_policy', 'inherit', 'string');
    }

    private function visitWithArrangement(string $approvedPolicy, VisitType $type = VisitType::OUTPATIENT): Visit
    {
        $patient = Patient::factory()->create();
        $visit = Visit::factory()->create(['patient_id' => $patient->id, 'visit_type' => $type->value]);
        $svc = app(VisitPaymentArrangementService::class);
        $a = $svc->request($visit, VisitPaymentArrangementData::fromValidated([
            'requested_policy' => $approvedPolicy, 'request_reason' => 'x', 'effective_from' => now()->toDateString(),
        ]), User::factory()->create());
        $svc->approve($a, VisitPaymentArrangementApprovalData::fromValidated([]), User::factory()->create());

        return $visit->fresh();
    }

    /** In-memory unpaid item bound to a persisted visit (settlement reads attributes). */
    private function unpaidItem(Visit $visit): InvoiceItem
    {
        $item = new InvoiceItem([
            'visit_id' => $visit->id,
            'patient_payable' => 100, 'paid_amount' => 0, 'insurance_covered' => 0,
            'balance' => 100, 'payment_status' => 'unpaid',
        ]);

        return $item->setRelation('visit', $visit)->setRelation('invoice', new Invoice(['balance' => 100, 'adjustment_amount' => 0]));
    }

    private function decide(Visit $visit): \App\Services\Billing\BillingPolicyDecision
    {
        return app(BillingPolicyService::class)->getInvoiceItemPolicy(
            $this->unpaidItem($visit), null, PaymentGateContext::triageRouteCompletion(),
        );
    }

    public function test_disabled_payment_timing_blocks_unpaid_opd_as_legacy(): void
    {
        $visit = $this->visitWithArrangement('pay_after_all_services'); // arrangement present but payment timing disabled
        $decision = $this->decide($visit);
        $this->assertFalse($decision->allowed);
        $this->assertSame('legacy', $decision->decisionAuthority);
    }

    public function test_active_typed_with_pay_after_arrangement_allows_unpaid(): void
    {
        $visit = $this->visitWithArrangement('pay_after_all_services');
        $this->setCutover('active', 'typed');

        $decision = $this->decide($visit);
        $this->assertTrue($decision->allowed);
        $this->assertSame('approved_arrangement', $decision->decisionAuthority);
        $this->assertSame('TYPED_PAY_AFTER_SERVICES_ALLOWED', $decision->reason);
    }

    public function test_active_typed_with_pay_before_arrangement_blocks_unpaid(): void
    {
        $visit = $this->visitWithArrangement('pay_before_service');
        $this->setCutover('active', 'typed');

        $decision = $this->decide($visit);
        $this->assertFalse($decision->allowed);
        $this->assertSame('approved_arrangement', $decision->decisionAuthority);
    }

    public function test_enabled_global_running_bill_allows_unpaid_even_when_operation_is_legacy(): void
    {
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);
        $this->enableGlobalPolicy('running_bill');
        Setting::setValue('payment_gate_operations', 'consultation.route.complete', ['mode' => 'legacy'], 'json');

        $decision = $this->decide($visit);
        $this->assertTrue($decision->allowed);
        $this->assertSame('typed_baseline', $decision->decisionAuthority);
        $this->assertSame('TYPED_RUNNING_BILL_ALLOWED', $decision->reason);
    }

    public function test_enabled_pay_after_allows_paid_only_pharmacy_policy(): void
    {
        $visit = Visit::factory()->create(['patient_id' => Patient::factory()->create()->id, 'visit_type' => VisitType::OUTPATIENT->value]);
        $this->enableGlobalPolicy('pay_after_all_services');

        $decision = app(BillingPolicyService::class)->getPaidOnlyInvoiceItemPolicy(
            $this->unpaidItem($visit),
            PaymentGateContext::pharmacyDispense(),
            'This item cannot be dispensed until its bill is settled (paid).',
        );
        $this->assertTrue($decision->allowed);
        $this->assertSame('typed_baseline', $decision->decisionAuthority);
        $this->assertSame('TYPED_PAY_AFTER_SERVICES_ALLOWED', $decision->reason);
    }

    public function test_emergency_visit_uses_typed_running_bill_authority(): void
    {
        $visit = $this->visitWithArrangement('pay_after_all_services', VisitType::EMERGENCY);
        $this->setCutover('active', 'typed');

        $decision = $this->decide($visit);
        $this->assertTrue($decision->allowed);
        $this->assertSame('typed_baseline', $decision->decisionAuthority);
        $this->assertSame('running_bill', $decision->paymentTimingPolicy);
    }
}
