<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateEnforcementEligibility;
use Illuminate\Support\Collection;

/**
 * Reports payment-gate coverage for the Departmental Payment Enforcement screen.
 *
 * Runtime authority now lives in PaymentTimingCutoverGateService whenever
 * Payment Timing Policies are enabled. This service is therefore diagnostic:
 * active production gate calls are reported as covered; rows without a workflow
 * gate call remain coverage notes only.
 */
final class PaymentGateEnforcementEligibilityService
{
    public function __construct(
        private readonly PaymentGateOperationRegistry $registry,
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

        $reasons = [];

        if (! ($definition['production_wired'] ?? false)) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_UNWIRED;
        }

        if (! isset($definition['stage'])) {
            $reasons[] = PaymentGateEnforcementEligibility::INELIGIBLE_MISSING_STAGE;
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
        ] as $candidate) {
            if (in_array($candidate, $reasons, true)) {
                return $candidate;
            }
        }

        return $reasons[0];
    }
}
