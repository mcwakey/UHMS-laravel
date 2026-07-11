<?php

namespace App\Services\Billing;

use App\Data\Billing\LegacyPaymentGateSnapshot;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Models\VisitBillingOverride;

class PaymentTimingLegacyCompatibilityService
{
    public function describeLegacyDecision(BillingPolicyDecision $decision): LegacyPaymentGateSnapshot
    {
        if ($decision->reason === 'NO_VISIT_CONTEXT') {
            return new LegacyPaymentGateSnapshot(null, false, false, null, $decision->reason, $decision->mode);
        }

        if ($decision->mode === BillingPolicyService::MODE_ADVISORY) {
            return new LegacyPaymentGateSnapshot(
                $decision->allowed,
                true,
                false,
                null,
                $decision->reason,
                $decision->mode,
                ['settlement_status' => $decision->settlementStatus],
            );
        }

        $policy = match ($decision->reason) {
            'ITEM_UNPAID' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE,
            'RUNNING_BILL', 'OPD_RUNNING_BILL' => VisitPaymentTimingPolicy::RUNNING_BILL,
            'DEFERRED_APPROVED', 'CREDIT_APPROVED', 'GATE_BYPASS' => VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES,
            default => null,
        };

        return new LegacyPaymentGateSnapshot(
            $decision->allowed,
            false,
            $policy !== null,
            $policy,
            $decision->reason,
            $decision->mode,
            [
                'override_used' => $decision->overrideUsed,
                'settlement_status' => $decision->settlementStatus,
            ],
        );
    }

    /** Safe visit-level expectation used by the read-only audit command. */
    public function describeLegacyVisitContext(Visit $visit): LegacyPaymentGateSnapshot
    {
        if (! config('billing_policy.enforce', true)) {
            return new LegacyPaymentGateSnapshot(true, true, false, null, 'ENFORCEMENT_DISABLED', BillingPolicyService::MODE_ADVISORY);
        }

        $type = $visit->visit_type instanceof VisitType ? $visit->visit_type : VisitType::tryFrom((string) $visit->visit_type);
        if (! $type) {
            return new LegacyPaymentGateSnapshot(null, false, false, null, 'UNKNOWN_VISIT_TYPE');
        }

        if ($type === VisitType::EMERGENCY || $type === VisitType::INPATIENT) {
            return new LegacyPaymentGateSnapshot(true, false, true, VisitPaymentTimingPolicy::RUNNING_BILL, 'RUNNING_BILL', BillingPolicyService::MODE_RUNNING_BILL);
        }

        $overrides = $visit->relationLoaded('billingOverrides') ? $visit->billingOverrides : collect();
        $deferred = $overrides->first(fn (VisitBillingOverride $override) => $override->isCurrentlyActive()
            && $override->scope === VisitBillingOverride::SCOPE_VISIT
            && $override->override_type === VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT
        );
        if ($deferred) {
            return new LegacyPaymentGateSnapshot(true, false, true, VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, 'DEFERRED_APPROVED', BillingPolicyService::MODE_DEFERRED_VISIT_SETTLEMENT);
        }

        if (! config('billing_policy.opd.payment_required_before_service', true)) {
            return new LegacyPaymentGateSnapshot(true, false, true, VisitPaymentTimingPolicy::RUNNING_BILL, 'OPD_RUNNING_BILL', BillingPolicyService::MODE_RUNNING_BILL);
        }

        return new LegacyPaymentGateSnapshot(false, false, true, VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE, 'ITEM_UNPAID', BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE);
    }
}
