<?php

namespace App\Services\Billing;

use App\Data\Billing\PatientRiskPaymentRecommendation;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskStatus;
use App\Enums\VisitPaymentPolicySource;
use App\Models\Patient;
use App\Models\PatientFinancialRiskProfile;
use Throwable;

/**
 * Derives a NON-OPERATIONAL payment recommendation from a patient's financial-
 * risk profile (Payment Timing Policy Phase 6).
 *
 * It ONLY reads risk state + typed rule config and returns advice. It never
 * calls the resolver or payment gate, never writes anything, and never returns
 * an approved recommendation (every rule is unapproved this phase). On any
 * failure it degrades to a safe no-recommendation result.
 */
class PatientRiskPaymentRecommendationService
{
    public function __construct(
        private readonly PatientFinancialRiskService $riskService,
        private readonly PatientFinancialRiskPolicyConfigurationService $ruleConfig,
    ) {}

    public function recommendForPatient(Patient $patient): PatientRiskPaymentRecommendation
    {
        try {
            return $this->recommendFor($this->riskService->currentFor($patient));
        } catch (Throwable) {
            return PatientRiskPaymentRecommendation::none();
        }
    }

    /**
     * Recommendation for a specific (possibly historical) profile. `currentFor`
     * only returns active-slot profiles, but this method also handles suspended/
     * cleared/expired profiles for preview and staleness diagnostics.
     */
    public function recommendFor(?PatientFinancialRiskProfile $profile): PatientRiskPaymentRecommendation
    {
        if ($profile === null) {
            return PatientRiskPaymentRecommendation::none(PatientRiskPaymentRecommendation::NO_ACTIVE_RISK_PROFILE);
        }

        // Non-active-slot profiles never produce an active recommendation.
        $terminalReason = match ($profile->status) {
            PatientFinancialRiskStatus::SUSPENDED => PatientRiskPaymentRecommendation::RISK_PROFILE_SUSPENDED,
            PatientFinancialRiskStatus::CLEARED => PatientRiskPaymentRecommendation::NO_ACTIVE_RISK_PROFILE,
            PatientFinancialRiskStatus::EXPIRED => PatientRiskPaymentRecommendation::RISK_PROFILE_EXPIRED,
            default => null,
        };
        if ($terminalReason !== null) {
            return PatientRiskPaymentRecommendation::none($terminalReason);
        }

        // Under review: preserve the snapshotted level but require finance review;
        // do NOT recommend a prepayment policy.
        if ($profile->status === PatientFinancialRiskStatus::UNDER_REVIEW) {
            return new PatientRiskPaymentRecommendation(
                recommendedPolicy: null,
                source: VisitPaymentPolicySource::PATIENT_RISK,
                reasonCode: PatientRiskPaymentRecommendation::RISK_PROFILE_UNDER_REVIEW,
                requiresFinanceReview: true,
                approvedForResolution: false,
                context: ['risk_level' => $profile->risk_level->value],
            );
        }

        return $this->forActiveLevel($profile->risk_level);
    }

    private function forActiveLevel(PatientFinancialRiskLevel $level): PatientRiskPaymentRecommendation
    {
        if ($level === PatientFinancialRiskLevel::NORMAL) {
            return PatientRiskPaymentRecommendation::none(PatientRiskPaymentRecommendation::RISK_PROFILE_NORMAL);
        }

        $rule = $this->ruleConfig->ruleFor($level);
        $reason = match ($level) {
            PatientFinancialRiskLevel::WATCHLIST => PatientRiskPaymentRecommendation::WATCHLIST_REVIEW_RECOMMENDED,
            PatientFinancialRiskLevel::HIGH_RISK => PatientRiskPaymentRecommendation::HIGH_RISK_PREPAYMENT_RECOMMENDED,
            PatientFinancialRiskLevel::BLOCKED_CREDIT => PatientRiskPaymentRecommendation::BLOCKED_CREDIT_PREPAYMENT_RECOMMENDED,
            default => PatientRiskPaymentRecommendation::RISK_RULE_NOT_APPROVED,
        };

        return new PatientRiskPaymentRecommendation(
            recommendedPolicy: $rule->recommendedPolicy,
            source: $rule->recommendedPolicy !== null || $rule->requiresFinanceReview
                ? VisitPaymentPolicySource::PATIENT_RISK
                : null,
            reasonCode: $reason,
            requiresFinanceReview: $rule->requiresFinanceReview,
            approvedForResolution: false, // never operational in Phase 6
            context: ['risk_level' => $level->value],
        );
    }
}
