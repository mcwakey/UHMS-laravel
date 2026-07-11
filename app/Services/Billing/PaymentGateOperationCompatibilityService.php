<?php

namespace App\Services\Billing;

use Illuminate\Support\Collection;

/**
 * Compares registry default, stored operation policy, current production wiring,
 * legacy behaviour and future typed-enforcement eligibility (Payment Timing
 * Policy Phase 4). Diagnostic only — it changes nothing and every operation
 * remains configuration-non-operational this phase.
 */
final class PaymentGateOperationCompatibilityService
{
    public const COMPATIBLE = 'compatible';
    public const CONFIGURATION_NON_OPERATIONAL = 'configuration_non_operational';
    public const LEGACY_HARD_GATE_PROTECTED = 'legacy_hard_gate_protected';
    public const TYPED_CUTOVER_NOT_READY = 'typed_cutover_not_ready';
    public const UNWIRED_OPERATION = 'unwired_operation';
    public const EMERGENCY_BOUNDARY_MISSING = 'emergency_boundary_missing';
    public const INVOICE_RESOLUTION_MISSING = 'invoice_resolution_missing';
    public const COMPATIBILITY_RULE_CONFLICT = 'compatibility_rule_conflict';

    public function __construct(
        private readonly PaymentGateOperationRegistry $registry,
        private readonly PaymentGateOperationConfigurationService $configuration,
        private readonly PaymentGateEnforcementEligibilityService $eligibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function evaluate(string $operation): array
    {
        $definition = $this->registry->get($operation);
        if ($definition === null) {
            return [
                'operation' => $operation,
                'status' => self::UNWIRED_OPERATION,
                'operational' => false,
                'reasons' => ['unknown_operation'],
            ];
        }

        $policy = $this->configuration->policyFor($operation);
        $eligibility = $this->eligibility->evaluate($operation);

        return [
            'operation' => $operation,
            'status' => $this->status($definition),
            // Phase 4 configuration is never operational — it changes no outcome.
            'operational' => false,
            'production_wired' => (bool) ($definition['production_wired'] ?? false),
            'hard_enforcement' => (bool) ($definition['hard_enforcement'] ?? false),
            'configured_mode' => $policy->mode->value,
            'default_mode' => $definition['default_mode']->value,
            'mode_differs_from_default' => $policy->mode !== $definition['default_mode'],
            'legacy_behaviour' => $definition['legacy_behaviour'] ?? null,
            'typed_enforcement_eligible' => $eligibility->eligible,
            'eligibility_status' => $eligibility->status,
            'compatibility_note' => $definition['compatibility_note'] ?? null,
        ];
    }

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public function all(): Collection
    {
        return collect($this->registry->operationCodes())
            ->mapWithKeys(fn (string $operation) => [$operation => $this->evaluate($operation)]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function status(array $definition): string
    {
        return match (true) {
            ! ($definition['production_wired'] ?? false) => self::UNWIRED_OPERATION,
            ($definition['compatibility_requires_decision'] ?? false) => self::COMPATIBILITY_RULE_CONFLICT,
            ($definition['hard_enforcement'] ?? false) => self::LEGACY_HARD_GATE_PROTECTED,
            ($definition['emergency_sensitive'] ?? false) => self::EMERGENCY_BOUNDARY_MISSING,
            ! ($definition['invoice_resolution_confirmed'] ?? false) => self::INVOICE_RESOLUTION_MISSING,
            default => self::CONFIGURATION_NON_OPERATIONAL,
        };
    }
}
