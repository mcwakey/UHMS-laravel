<?php

namespace App\Services\Billing;

use App\Data\Billing\PatientFinancialRiskPolicyRule;
use App\Enums\PatientFinancialRiskLevel;
use App\Enums\VisitPaymentTimingPolicy;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Typed, non-operational risk-to-policy rule configuration (Payment Timing
 * Policy Phase 6). Mirrors Phase 4's safe pattern: rules are diagnostic and
 * preparatory only — `approved_for_resolution` is forced false for EVERY level
 * this phase, so no stored/config value can make a recommendation operational.
 *
 * Rules live in config/visit_payment_policy.php (not admin-editable in Phase 6).
 * Invalid values fall back to safe defaults.
 */
class PatientFinancialRiskPolicyConfigurationService
{
    public function ruleFor(PatientFinancialRiskLevel $level): PatientFinancialRiskPolicyRule
    {
        $raw = config('visit_payment_policy.risk_rules.'.$level->value);
        if (! is_array($raw)) {
            return PatientFinancialRiskPolicyRule::safeDefault($level);
        }

        return new PatientFinancialRiskPolicyRule(
            level: $level,
            recommendedPolicy: $this->policy($raw['recommended_policy'] ?? null),
            requiresFinanceReview: (bool) ($raw['requires_finance_review'] ?? false),
            // Phase 6 safety invariant: never operational, whatever config says.
            approvedForResolution: false,
        );
    }

    /** @return array<string, PatientFinancialRiskPolicyRule> */
    public function all(): array
    {
        $out = [];
        foreach (PatientFinancialRiskLevel::cases() as $level) {
            $out[$level->value] = $this->ruleFor($level);
        }

        return $out;
    }

    private function policy(mixed $value): ?VisitPaymentTimingPolicy
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $policy = VisitPaymentTimingPolicy::tryFrom($value);
        if ($policy === null) {
            try {
                Log::warning('Invalid risk-policy recommended_policy in config; ignoring.', ['value' => $value]);
            } catch (Throwable) {
                // configuration fallback must never depend on logging
            }
        }
        // A recommendation of `inherit` is meaningless — treat as no recommendation.
        return $policy === VisitPaymentTimingPolicy::INHERIT ? null : $policy;
    }
}
