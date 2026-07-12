<?php

namespace App\Services\Billing;

use App\Data\Billing\ApprovedArrangementOperationalEligibility as Eligibility;
use App\Data\Billing\OperationalVisitPaymentTimingDecision;
use App\Data\Billing\PaymentGateContext;
use App\Enums\VisitPaymentPolicySource;
use App\Models\Visit;

/**
 * Resolves the runtime operational payment-timing policy (Payment Timing Policy
 * Phase 8): the current typed baseline from {@see VisitPaymentTimingResolver},
 * overridden by an eligible current approved arrangement.
 *
 * It NEVER modifies the Phase 6 stored baseline, never treats a risk
 * recommendation as a source, and never mutates the arrangement. Results are
 * memoised per visit+operation for the request.
 */
class OperationalVisitPaymentTimingResolver
{
    /** @var array<string, OperationalVisitPaymentTimingDecision> */
    private array $memo = [];

    public function __construct(
        private readonly VisitPaymentTimingResolver $baselineResolver,
        private readonly ApprovedArrangementOperationalEligibilityService $arrangementEligibility,
    ) {}

    public function resolve(Visit $visit, PaymentGateContext $context): OperationalVisitPaymentTimingDecision
    {
        $key = $visit->id.'|'.$context->operation;
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        $baseline = $this->baselineResolver->resolve($visit);
        $eligibility = $this->arrangementEligibility->evaluate($visit, $context->operation);

        if ($eligibility->eligible && $eligibility->arrangement?->approved_policy !== null) {
            $decision = new OperationalVisitPaymentTimingDecision(
                policy: $eligibility->arrangement->approved_policy,
                source: VisitPaymentPolicySource::APPROVED_ARRANGEMENT,
                reasonCode: Eligibility::ELIGIBLE,
                usedApprovedArrangement: true,
                approvedArrangementId: $eligibility->arrangement->id,
                arrangementEligibilityReason: Eligibility::ELIGIBLE,
                baseline: $baseline,
            );
        } else {
            $decision = new OperationalVisitPaymentTimingDecision(
                policy: $baseline->policy,
                source: $baseline->source,
                reasonCode: $baseline->reasonCode,
                usedApprovedArrangement: false,
                approvedArrangementId: $eligibility->arrangement?->id,
                arrangementEligibilityReason: $eligibility->reasonCode,
                baseline: $baseline,
            );
        }

        return $this->memo[$key] = $decision;
    }

    /** Clear memoisation (used by preview/tests). */
    public function flush(): void
    {
        $this->memo = [];
    }
}
