<?php

namespace App\Data\Billing;

use App\Enums\PaymentTimingComparisonOutcome;

final readonly class PaymentTimingPolicyComparison
{
    public function __construct(
        public PaymentTimingComparisonOutcome $outcome,
        public LegacyPaymentGateSnapshot $legacy,
        public VisitPaymentTimingDecision $typed,
        public array $context = [],
    ) {}

    public function isMismatch(): bool
    {
        return in_array($this->outcome, [
            PaymentTimingComparisonOutcome::LEGACY_MORE_RESTRICTIVE,
            PaymentTimingComparisonOutcome::TYPED_MORE_RESTRICTIVE,
        ], true);
    }

    public function toArray(): array
    {
        return [
            'outcome' => $this->outcome->value,
            'legacy' => [
                'allowed' => $this->legacy->allowed,
                'advisory' => $this->legacy->advisory,
                'comparable' => $this->legacy->comparable,
                'expected_policy' => $this->legacy->expectedPolicy?->value,
                'reason_code' => $this->legacy->reasonCode,
                'mode' => $this->legacy->mode,
            ],
            'typed' => $this->typed->toArray(),
            'context' => $this->context,
        ];
    }
}
