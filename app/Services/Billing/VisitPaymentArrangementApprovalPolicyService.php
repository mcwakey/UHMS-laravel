<?php

namespace App\Services\Billing;

use App\Data\Billing\VisitPaymentArrangementApprovalRequirement;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Visit;
use App\Models\VisitPaymentPolicy;

/**
 * Determines the ADMINISTRATIVE approval requirements for a requested per-visit
 * payment policy (Payment Timing Policy Phase 7).
 *
 * This makes NO operational payment decision and never touches a payment gate.
 * It only decides who must approve (and whether a separate approver / finance
 * manager is required) based on the requested policy, the request-time baseline/
 * recommendation/risk snapshot, and previous-balance context.
 */
class VisitPaymentArrangementApprovalPolicyService
{
    public function __construct(private readonly PatientOutstandingBalanceService $balanceService) {}

    /**
     * @param  array<string, mixed>  $snapshot  baseline_policy, recommended_policy, risk_level, finance_review
     */
    public function determine(Visit $visit, VisitPaymentTimingPolicy $requested, array $snapshot): VisitPaymentArrangementApprovalRequirement
    {
        $baseline = $this->policy($snapshot['baseline_policy'] ?? null);
        $recommended = $this->policy($snapshot['recommended_policy'] ?? null);
        $riskLevel = $snapshot['risk_level'] ?? null;
        $financeReview = (bool) ($snapshot['finance_review'] ?? false);

        $isDeferral = in_array($requested, [VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES, VisitPaymentTimingPolicy::RUNNING_BILL], true);
        $restrictiveRisk = in_array($riskLevel, [PatientFinancialRiskLevel::HIGH_RISK->value, PatientFinancialRiskLevel::BLOCKED_CREDIT->value], true);

        $reasons = [];
        $separate = false;
        $financeManager = false;

        if ($isDeferral) {
            if ($baseline === VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE) {
                $reasons[] = 'deferral_against_prepay_baseline';
                $separate = $financeManager = true;
            }
            if ($restrictiveRisk) {
                $reasons[] = 'deferral_against_restrictive_risk';
                $separate = $financeManager = true;
            }
            if ($recommended === VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE) {
                $reasons[] = 'deferral_against_prepay_recommendation';
                $separate = $financeManager = true;
            }
            if ($this->hasMaterialPreviousBalance($visit)) {
                $reasons[] = 'material_previous_balance';
                $separate = $financeManager = true;
            }
            if ($requested === VisitPaymentTimingPolicy::RUNNING_BILL
                && $this->visitType($visit) === VisitType::OUTPATIENT
                && $baseline !== VisitPaymentTimingPolicy::RUNNING_BILL) {
                $reasons[] = 'running_bill_on_outpatient';
                $separate = $financeManager = true;
            }
            if ($financeReview && ! $separate) {
                $reasons[] = 'finance_review_recommended';
            }
        } elseif ($requested === VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE) {
            // Prepayment is protective/restrictive — a decision + reason are still
            // required, but no separate approver / finance-manager escalation.
            $reasons[] = 'pre_service_request';
        }

        return new VisitPaymentArrangementApprovalRequirement(
            requiresApproval: true,
            requiresSeparateApprover: $separate,
            requiresFinanceManager: $financeManager,
            requiresManagementApproval: false,
            reasonCode: $reasons[0] ?? 'standard_request',
            context: array_values(array_unique($reasons)),
        );
    }

    private function hasMaterialPreviousBalance(Visit $visit): bool
    {
        $patient = $visit->relationLoaded('patient') ? $visit->patient : $visit->patient()->first();
        if ($patient === null) {
            return false;
        }
        $threshold = (float) config('visit_payment_arrangement.previous_balance_threshold', 0.0);

        return $this->balanceService->getPreviousOutstandingBalance($patient, $visit) > $threshold;
    }

    private function visitType(Visit $visit): ?VisitType
    {
        return $visit->visit_type instanceof VisitType
            ? $visit->visit_type
            : VisitType::tryFrom((string) $visit->visit_type);
    }

    private function policy(mixed $value): ?VisitPaymentTimingPolicy
    {
        if ($value instanceof VisitPaymentTimingPolicy) {
            return $value;
        }

        return is_string($value) ? VisitPaymentTimingPolicy::tryFrom($value) : null;
    }
}
