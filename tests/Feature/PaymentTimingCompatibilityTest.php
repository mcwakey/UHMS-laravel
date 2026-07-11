<?php

namespace Tests\Feature;

use App\Enums\VisitPaymentTimingPolicy;
use App\Services\Billing\BillingPolicyDecision;
use App\Services\Billing\BillingPolicyService;
use App\Services\Billing\PaymentTimingLegacyCompatibilityService;
use Tests\TestCase;

class PaymentTimingCompatibilityTest extends TestCase
{
    public function test_comparable_legacy_modes_map_to_typed_policies(): void
    {
        $mapper = app(PaymentTimingLegacyCompatibilityService::class);
        $blocked = $mapper->describeLegacyDecision(BillingPolicyDecision::block(
            BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE,
            'ITEM_UNPAID',
            'Unpaid',
        ));
        $this->assertTrue($blocked->comparable);
        $this->assertSame(VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, $blocked->expectedPolicy);

        $running = $mapper->describeLegacyDecision(BillingPolicyDecision::allow(
            BillingPolicyService::MODE_RUNNING_BILL,
            'RUNNING_BILL',
            'Continue',
        ));
        $this->assertSame(VisitPaymentTimingPolicy::RUNNING_BILL, $running->expectedPolicy);
    }

    public function test_advisory_and_settlement_specific_decisions_are_not_forced_into_timing_equivalence(): void
    {
        $mapper = app(PaymentTimingLegacyCompatibilityService::class);
        $advisory = $mapper->describeLegacyDecision(BillingPolicyDecision::allow(
            BillingPolicyService::MODE_ADVISORY,
            'ENFORCEMENT_DISABLED',
            'Advisory',
        ));
        $this->assertTrue($advisory->advisory);
        $this->assertFalse($advisory->comparable);

        $covered = $mapper->describeLegacyDecision(BillingPolicyDecision::allow(
            BillingPolicyService::MODE_INSURANCE_COVERED,
            'ITEM_COVERED',
            'Covered',
        ));
        $this->assertFalse($covered->comparable);
        $this->assertNull($covered->expectedPolicy);
    }
}
