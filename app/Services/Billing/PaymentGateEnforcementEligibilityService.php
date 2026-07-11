<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateEnforcementEligibility;
use Illuminate\Support\Collection;

/**
 * Evaluates whether an operation is architecturally eligible for FUTURE typed
 * enforcement (Payment Timing Policy Phase 4). Diagnostic only: it reads
 * registry + configuration metadata, NEVER enables enforcement, and NEVER
 * changes a workflow outcome. In Phase 4 no operation is fully eligible — the
 * hospital has not yet approved any typed policy.
 */
final class PaymentGateEnforcementEligibilityService
{
    public function __construct(
        private readonly PaymentGateOperationRegistry $registry,
        private readonly PaymentGateOperationConfigurationService $configuration,
    ) {}

    public function evaluate(string $operation): PaymentGateEnforcementEligibility
    {
        $definition = $this->registry->get($operation);
        if ($definition === null) {
            return PaymentGateEnforcementEligibility::ineligible(
                $operation,
                PaymentGateEnforcementEligibility::INELIGIBLE_UNWIRED,
                ['unknown_operation'],
            );
        }

        $policy = $this->configuration->policyFor($operation);
        $reasons = [];

        if (! ($definition['production_wired'] ?? false)) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_UNWIRED;
        }
        if (! isset($definition['stage'])) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_MISSING_STAGE;
        }
        if ($definition['compatibility_requires_decision'] ?? false) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_COMPATIBILITY_RULE;
        }
        if ($definition['emergency_sensitive'] ?? false) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_EMERGENCY_BOUNDARY;
        }
        if (! ($definition['invoice_resolution_confirmed'] ?? false)) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_MISSING_INVOICE_RESOLUTION;
        }
        // Provisional defaults only in Phase 4 — no operation is approved for cutover.
        if (! ($definition['approved_for_typed_enforcement'] ?? false) || ! $policy->enabledForFutureTypedEnforcement) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_UNAPPROVED_POLICY;
        }

        if ($reasons === []) {
            return PaymentGateEnforcementEligibility::eligible($operation);
        }

        $reasons = array_values(array_unique($reasons));

        return PaymentGateEnforcementEligibility::ineligible($operation, $this->primary($reasons), $reasons);
    }

    /**
     * @return Collection<string, PaymentGateEnforcementEligibility>
     */
    public function all(): Collection
    {
        return collect($this->registry->operationCodes())
            ->mapWithKeys(fn (string $operation) => [$operation => $this->evaluate($operation)]);
    }

    /**
     * @param  array<int, string>  $reasons
     */
    private function primary(array $reasons): string
    {
        foreach ([
            PaymentGateEnforcementEligibility::INELIGIBLE_UNWIRED,
            PaymentGateEnforcementEligibility::INELIGIBLE_MISSING_STAGE,
            PaymentGateEnforcementEligibility::INELIGIBLE_COMPATIBILITY_RULE,
            PaymentGateEnforcementEligibility::INELIGIBLE_EMERGENCY_BOUNDARY,
            PaymentGateEnforcementEligibility::INELIGIBLE_MISSING_INVOICE_RESOLUTION,
            PaymentGateEnforcementEligibility::INELIGIBLE_UNAPPROVED_POLICY,
        ] as $candidate) {
            if (in_array($candidate, $reasons, true)) {
                return $candidate;
            }
        }

        return $reasons[0];
    }
}
