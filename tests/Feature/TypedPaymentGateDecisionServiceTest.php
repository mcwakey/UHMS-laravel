<?php

namespace Tests\Feature;

use App\Data\Billing\PaymentGateContext;
use App\Enums\PaymentGateStage;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Visit;
use App\Services\Billing\BillingPolicyDecision;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\TypedPaymentGateDecisionService;
use Tests\TestCase;

class TypedPaymentGateDecisionServiceTest extends TestCase
{
    private TypedPaymentGateDecisionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TypedPaymentGateDecisionService::class);
    }

    private function item(string $status, float $balance, float $paid, float $covered): InvoiceItem
    {
        $visit = (new Visit(['visit_type' => VisitType::OUTPATIENT]))->setRelation('billingOverrides', collect());
        $item = new InvoiceItem([
            'patient_payable' => $covered > 0 ? 0 : 100,
            'paid_amount' => $paid,
            'insurance_covered' => $covered,
            'balance' => $balance,
            'payment_status' => $status,
        ]);

        return $item->setRelation('visit', $visit)->setRelation('invoice', new Invoice(['balance' => $balance, 'adjustment_amount' => 0]));
    }

    private function operationPolicy(string $operation = 'laboratory.result.enter')
    {
        return app(PaymentGateOperationConfigurationService::class)->policyFor($operation);
    }

    private function legacyBlock(): BillingPolicyDecision
    {
        return BillingPolicyDecision::block(BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE, 'ITEM_UNPAID', 'unpaid', ['requiresPayment' => true]);
    }

    private function ctx(): PaymentGateContext
    {
        return PaymentGateContext::forOperation(PaymentGateStage::RESULT, 'laboratory.result.enter');
    }

    // ── pay_before ─────────────────────────────────────────────────────────

    public function test_pay_before_blocks_unpaid_and_allows_paid(): void
    {
        $unpaid = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('unpaid', 100, 0, 0), VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $this->operationPolicy(), $this->ctx());
        $this->assertFalse($unpaid->allowed);
        $this->assertSame('TYPED_PREPAYMENT_REQUIRED', $unpaid->reason);

        $paid = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('paid', 0, 100, 0), VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $this->operationPolicy(), $this->ctx());
        $this->assertTrue($paid->allowed);
        $this->assertSame('TYPED_PREPAYMENT_SETTLED', $paid->reason);
    }

    public function test_pay_before_follows_operation_allow_flags_for_insured_and_waived(): void
    {
        // Lab allows insured/waived/adjusted (registry defaults).
        $insured = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('unpaid', 0, 0, 100), VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $this->operationPolicy(), $this->ctx());
        $this->assertTrue($insured->allowed);

        $waived = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('waived', 100, 0, 0), VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $this->operationPolicy(), $this->ctx());
        $this->assertTrue($waived->allowed);

        // Pharmacy allows none of these — insured item still blocks under pay_before.
        $pharmacyInsured = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('unpaid', 0, 0, 100), VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $this->operationPolicy('pharmacy.item.dispense'), $this->ctx());
        $this->assertFalse($pharmacyInsured->allowed);
    }

    // ── pay_after / running_bill ───────────────────────────────────────────

    public function test_pay_after_allows_unpaid_without_touching_finances(): void
    {
        $decision = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('unpaid', 100, 0, 0), VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $this->operationPolicy(), $this->ctx());
        $this->assertTrue($decision->allowed);
        $this->assertSame('TYPED_PAY_AFTER_SERVICES_ALLOWED', $decision->reason);
        $this->assertFalse($decision->requiresPayment);
    }

    public function test_running_bill_allows_unpaid(): void
    {
        $decision = $this->service->evaluate($this->legacyBlock(), new Visit, $this->item('unpaid', 100, 0, 0), VisitPaymentTimingPolicy::RUNNING_BILL, $this->operationPolicy(), $this->ctx());
        $this->assertTrue($decision->allowed);
        $this->assertSame('TYPED_RUNNING_BILL_ALLOWED', $decision->reason);
    }

    // ── non-payment failures preserved ─────────────────────────────────────

    public function test_non_payment_block_is_preserved_even_under_pay_after(): void
    {
        // A legacy block that is NOT a payment block (e.g. cancelled / clinical).
        $legacyNonPayment = BillingPolicyDecision::block('SOME_MODE', 'ITEM_CANCELLED', 'cancelled', ['requiresPayment' => false]);
        $decision = $this->service->evaluate($legacyNonPayment, new Visit, $this->item('unpaid', 100, 0, 0), VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, $this->operationPolicy(), $this->ctx());
        $this->assertFalse($decision->allowed);
        $this->assertSame('ITEM_CANCELLED', $decision->reason);
    }

    public function test_missing_item_preserves_legacy_by_default(): void
    {
        $legacyAllow = BillingPolicyDecision::allow('ADVISORY', 'INVOICE_ITEM_MISSING_ALLOWED', 'ok');
        $decision = $this->service->evaluate($legacyAllow, new Visit, null, VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $this->operationPolicy(), $this->ctx());
        $this->assertTrue($decision->allowed);
        $this->assertSame('INVOICE_ITEM_MISSING_ALLOWED', $decision->reason);
    }
}
