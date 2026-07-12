<?php

namespace App\Services\Billing;

use App\Data\Billing\ApprovedArrangementOperationalEligibility as Eligibility;
use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Models\VisitBillingOverride;
use App\Models\VisitPaymentArrangement;

/**
 * Decides whether the current approved per-visit arrangement may influence
 * RUNTIME policy for an operation (Payment Timing Policy Phase 8).
 *
 * READ-ONLY: it never updates, revokes or refreshes the arrangement. A stale,
 * expired, revoked, replaced, future, link-mismatched, conflicting or
 * unsupported arrangement is reported ineligible so the caller falls back to the
 * typed baseline / legacy.
 */
class ApprovedArrangementOperationalEligibilityService
{
    /** Legacy visit-wide override types that grant deferral (conflict with a pay-before arrangement). */
    private const DEFERRAL_OVERRIDE_TYPES = [
        VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT,
        VisitBillingOverride::TYPE_CREDIT_APPROVAL,
        VisitBillingOverride::TYPE_PAYMENT_GATE_BYPASS,
    ];

    public function __construct(
        private readonly PaymentGateOperationRegistry $registry,
        private readonly PaymentGateOperationConfigurationService $operationConfiguration,
        private readonly VisitPaymentArrangementService $arrangementService,
    ) {}

    public function evaluate(Visit $visit, string $operation, ?VisitPaymentArrangement $current = null): Eligibility
    {
        $current ??= $visit->relationLoaded('currentApprovedPaymentArrangement')
            ? $visit->currentApprovedPaymentArrangement
            : $visit->currentApprovedPaymentArrangement()->first();

        if ($current === null) {
            return Eligibility::ineligible(Eligibility::NO_CURRENT_ARRANGEMENT);
        }

        if ($current->status !== VisitPaymentArrangementStatus::APPROVED) {
            return Eligibility::ineligible(match ($current->status) {
                VisitPaymentArrangementStatus::REVOKED => Eligibility::REVOKED,
                VisitPaymentArrangementStatus::EXPIRED => Eligibility::EXPIRED,
                VisitPaymentArrangementStatus::REPLACED => Eligibility::REPLACED,
                default => Eligibility::NO_CURRENT_ARRANGEMENT,
            }, $current);
        }

        $approved = $current->approved_policy;
        if ($approved === null || $approved === VisitPaymentTimingPolicy::INHERIT) {
            return Eligibility::ineligible(Eligibility::INVALID_APPROVED_POLICY, $current);
        }

        $policy = $visit->relationLoaded('paymentPolicy') ? $visit->paymentPolicy : $visit->paymentPolicy()->first();
        if ($policy === null) {
            return Eligibility::ineligible(Eligibility::MISSING_MATERIALISED_POLICY, $current);
        }
        if ((int) $policy->current_approved_arrangement_id !== (int) $current->id) {
            return Eligibility::ineligible(Eligibility::LINK_MISMATCH, $current);
        }

        $today = now()->startOfDay();
        if ($current->effective_from !== null && $current->effective_from->startOfDay()->gt($today)) {
            return Eligibility::ineligible(Eligibility::NOT_YET_EFFECTIVE, $current);
        }
        if ($current->expires_at !== null && $current->expires_at->startOfDay()->lt($today)) {
            return Eligibility::ineligible(Eligibility::EXPIRED, $current);
        }

        if (! $this->operationConfiguration->operationTypedEligible($operation)) {
            return Eligibility::ineligible(Eligibility::UNSUPPORTED_OPERATION, $current);
        }

        if (! $this->visitTypeSupported($operation, $visit)) {
            return Eligibility::ineligible(Eligibility::UNSUPPORTED_VISIT_TYPE, $current);
        }

        if ($this->arrangementService->riskIsStale($current)) {
            return Eligibility::ineligible(Eligibility::STALE_RISK_CONTEXT, $current);
        }

        if ($this->hasConflictingLegacyOverride($visit, $approved)) {
            return Eligibility::ineligible(Eligibility::CONFLICTING_LEGACY_VISIT_OVERRIDE, $current);
        }

        return Eligibility::eligible($current);
    }

    private function visitTypeSupported(string $operation, Visit $visit): bool
    {
        $definition = $this->registry->get($operation);
        $supported = $definition['typed_supported_visit_types'] ?? [];
        $visitType = $visit->visit_type instanceof VisitType ? $visit->visit_type->value : (string) $visit->visit_type;

        // Emergency is never supported for typed cutover in Phase 8.
        if ($visitType === VisitType::EMERGENCY->value) {
            return false;
        }

        return in_array($visitType, $supported, true);
    }

    /**
     * A restrictive (pay-before) arrangement conflicts with an active visit-wide
     * legacy override that grants deferral. Matching deferral semantics coexist.
     */
    private function hasConflictingLegacyOverride(Visit $visit, VisitPaymentTimingPolicy $approved): bool
    {
        if ($approved !== VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE) {
            return false;
        }

        $overrides = $visit->relationLoaded('billingOverrides')
            ? $visit->billingOverrides->filter(fn (VisitBillingOverride $o) => $o->isCurrentlyActive())
            : $visit->billingOverrides()->active()->get();

        return $overrides->contains(fn (VisitBillingOverride $o) => $o->scope === VisitBillingOverride::SCOPE_VISIT
            && in_array($o->override_type, self::DEFERRAL_OVERRIDE_TYPES, true));
    }
}
