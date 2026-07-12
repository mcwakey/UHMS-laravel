<?php

namespace App\Data\Billing;

use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;

/**
 * A NON-OPERATIONAL payment-policy recommendation derived from a patient's
 * financial-risk profile (Payment Timing Policy Phase 6).
 *
 * This is advisory only. `approvedForResolution` is false for every risk level
 * in Phase 6, and no production caller may substitute this for the baseline
 * resolver result. Reason codes are stable machine strings — never translated.
 */
final readonly class PatientRiskPaymentRecommendation
{
    // Stable machine reason codes (not translated).
    public const NO_ACTIVE_RISK_PROFILE = 'no_active_risk_profile';
    public const RISK_PROFILE_NORMAL = 'risk_profile_normal';
    public const RISK_PROFILE_SUSPENDED = 'risk_profile_suspended';
    public const RISK_PROFILE_UNDER_REVIEW = 'risk_profile_under_review';
    public const WATCHLIST_REVIEW_RECOMMENDED = 'watchlist_review_recommended';
    public const HIGH_RISK_PREPAYMENT_RECOMMENDED = 'high_risk_prepayment_recommended';
    public const BLOCKED_CREDIT_PREPAYMENT_RECOMMENDED = 'blocked_credit_prepayment_recommended';
    public const RISK_RULE_NOT_APPROVED = 'risk_rule_not_approved';
    public const RISK_PROFILE_EXPIRED = 'risk_profile_expired';

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public ?VisitPaymentTimingPolicy $recommendedPolicy,
        public ?VisitPaymentPolicySource $source,
        public ?string $reasonCode,
        public bool $requiresFinanceReview,
        public bool $approvedForResolution,
        public array $context = [],
    ) {}

    /** Safe no-recommendation result. */
    public static function none(string $reasonCode = self::NO_ACTIVE_RISK_PROFILE): self
    {
        return new self(
            recommendedPolicy: null,
            source: null,
            reasonCode: $reasonCode,
            requiresFinanceReview: false,
            approvedForResolution: false,
            context: [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'recommended_policy' => $this->recommendedPolicy?->value,
            'recommendation_source' => $this->source?->value,
            'recommendation_reason_code' => $this->reasonCode,
            'requires_finance_review' => $this->requiresFinanceReview,
            'approved_for_resolution' => $this->approvedForResolution,
            'context' => $this->context,
        ];
    }
}
