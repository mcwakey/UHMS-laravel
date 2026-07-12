<?php

namespace App\Data\Billing;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentTimingPolicy;

/**
 * Typed, non-operational rule describing how a financial-risk level MIGHT
 * influence future policy resolution (Payment Timing Policy Phase 6).
 *
 * `approvedForResolution` is false for every level in Phase 6 — a rule can never
 * make a recommendation operational this phase.
 */
final readonly class PatientFinancialRiskPolicyRule
{
    public function __construct(
        public PatientFinancialRiskLevel $level,
        public ?VisitPaymentTimingPolicy $recommendedPolicy,
        public bool $requiresFinanceReview,
        public bool $approvedForResolution,
    ) {}

    /** Safe default: no recommendation, no review, never approved. */
    public static function safeDefault(PatientFinancialRiskLevel $level): self
    {
        return new self($level, null, false, false);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'risk_level' => $this->level->value,
            'recommended_policy' => $this->recommendedPolicy?->value,
            'requires_finance_review' => $this->requiresFinanceReview,
            'approved_for_resolution' => $this->approvedForResolution,
        ];
    }
}
