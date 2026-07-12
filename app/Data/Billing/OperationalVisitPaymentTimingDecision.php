<?php

namespace App\Data\Billing;

use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;

/**
 * Runtime operational payment-timing decision (Payment Timing Policy Phase 8):
 * the typed baseline, possibly overridden by an eligible approved arrangement.
 * This never mutates the Phase 6 stored baseline and never carries a risk
 * recommendation as a source.
 */
final readonly class OperationalVisitPaymentTimingDecision
{
    public function __construct(
        public VisitPaymentTimingPolicy $policy,
        public VisitPaymentPolicySource $source,
        public string $reasonCode,
        public bool $usedApprovedArrangement,
        public ?int $approvedArrangementId,
        public string $arrangementEligibilityReason,
        public VisitPaymentTimingDecision $baseline,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'policy' => $this->policy->value,
            'source' => $this->source->value,
            'reason_code' => $this->reasonCode,
            'used_approved_arrangement' => $this->usedApprovedArrangement,
            'approved_arrangement_id' => $this->approvedArrangementId,
            'arrangement_eligibility_reason' => $this->arrangementEligibilityReason,
            'baseline_policy' => $this->baseline->policy->value,
            'baseline_source' => $this->baseline->source->value,
        ];
    }
}
