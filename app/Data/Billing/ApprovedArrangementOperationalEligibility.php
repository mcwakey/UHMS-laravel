<?php

namespace App\Data\Billing;

use App\Models\VisitPaymentArrangement;

/**
 * Whether a current approved per-visit arrangement may influence RUNTIME policy
 * for a given operation (Payment Timing Policy Phase 8). Read-only diagnosis —
 * it never mutates the arrangement.
 */
final readonly class ApprovedArrangementOperationalEligibility
{
    public const ELIGIBLE = 'eligible';
    public const NO_CURRENT_ARRANGEMENT = 'no_current_arrangement';
    public const NOT_YET_EFFECTIVE = 'not_yet_effective';
    public const EXPIRED = 'expired';
    public const REVOKED = 'revoked';
    public const REPLACED = 'replaced';
    public const STALE_RISK_CONTEXT = 'stale_risk_context';
    public const LINK_MISMATCH = 'link_mismatch';
    public const UNSUPPORTED_VISIT_TYPE = 'unsupported_visit_type';
    public const UNSUPPORTED_OPERATION = 'unsupported_operation';
    public const CONFLICTING_LEGACY_VISIT_OVERRIDE = 'conflicting_legacy_visit_override';
    public const MISSING_MATERIALISED_POLICY = 'missing_materialised_policy';
    public const INVALID_APPROVED_POLICY = 'invalid_approved_policy';

    public function __construct(
        public bool $eligible,
        public string $reasonCode,
        public ?VisitPaymentArrangement $arrangement = null,
    ) {}

    public static function eligible(VisitPaymentArrangement $arrangement): self
    {
        return new self(true, self::ELIGIBLE, $arrangement);
    }

    public static function ineligible(string $reasonCode, ?VisitPaymentArrangement $arrangement = null): self
    {
        return new self(false, $reasonCode, $arrangement);
    }
}
