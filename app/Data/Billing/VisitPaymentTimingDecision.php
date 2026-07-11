<?php

namespace App\Data\Billing;

use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;

final readonly class VisitPaymentTimingDecision
{
    public function __construct(
        public VisitPaymentTimingPolicy $policy,
        public VisitPaymentPolicySource $source,
        public string $reasonCode,
        public array $context = [],
    ) {}

    public function allowsServiceBeforePayment(): bool
    {
        return in_array($this->policy, [
            VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES,
            VisitPaymentTimingPolicy::RUNNING_BILL,
        ], true);
    }

    public function requiresPreServicePayment(): bool
    {
        return $this->policy === VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE;
    }

    public function toArray(): array
    {
        return [
            'policy' => $this->policy->value,
            'source' => $this->source->value,
            'reason_code' => $this->reasonCode,
            'context' => $this->context,
        ];
    }
}
