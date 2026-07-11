<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateOperationPolicy;
use App\Enums\MissingBillingContextPolicy;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentGateOverrideScopeRule;
use App\Enums\PaymentGateStage;
use App\Enums\PaymentGateVisitContextRule;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resolves the effective {@see PaymentGateOperationPolicy} for each registered
 * operation by merging stored settings over registry defaults (Payment Timing
 * Policy Phase 4).
 *
 * Safety contract: it reads settings + registry only — never invoices, visits,
 * patients or overrides — makes NO payment decision, and NEVER relaxes an
 * existing wired hard gate. Invalid stored values fall back to registry defaults.
 */
final class PaymentGateOperationConfigurationService
{
    /** Settings group storing one structured-JSON policy per operation. */
    public const GROUP = 'payment_gate_operations';

    /** Stored keys an administrator may override (allow_* flags are registry-only). */
    private const OVERRIDABLE_KEYS = [
        'mode', 'missing_context', 'visit_context_rule', 'override_scope_rule',
        'emergency_exempt', 'inpatient_exempt', 'typed_enforcement_eligible',
    ];

    public function __construct(private readonly PaymentGateOperationRegistry $registry) {}

    public function registered(string $operation): bool
    {
        return $this->registry->has($operation);
    }

    public function modeFor(string $operation): PaymentGateOperationMode
    {
        return $this->policyFor($operation)->mode;
    }

    /**
     * Resolved policy for one operation. Unknown operations resolve to a safe,
     * non-enforcing (DISABLED) representation — never an enforceable one.
     */
    public function policyFor(string $operation): PaymentGateOperationPolicy
    {
        $definition = $this->registry->get($operation);
        if ($definition === null) {
            return PaymentGateOperationPolicy::unknown($operation);
        }

        return $this->resolve($operation, $definition, $this->storedFor($operation));
    }

    /**
     * All registered operation policies, keyed by operation code. Uses a single
     * grouped settings read (no per-operation query).
     *
     * @return Collection<string, PaymentGateOperationPolicy>
     */
    public function policies(): Collection
    {
        $stored = $this->storedGroup();

        return collect($this->registry->operations())->map(
            fn (array $definition, string $operation) => $this->resolve($operation, $definition, $stored[$operation] ?? [])
        );
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $stored
     */
    private function resolve(string $operation, array $definition, array $stored): PaymentGateOperationPolicy
    {
        $isHardGate = (bool) ($definition['hard_enforcement'] ?? false);

        $mode = $this->enum(PaymentGateOperationMode::class, $stored['mode'] ?? null, $operation, 'mode')
            ?? $definition['default_mode'];

        // Safety: a wired hard gate must never be relaxed to DISABLED via stored
        // config. Registry compatibility default wins for operational safety.
        if ($isHardGate && $mode === PaymentGateOperationMode::DISABLED) {
            $this->warn('Refusing to disable a wired hard-gate operation from stored config; keeping legacy default.', $operation);
            $mode = $definition['default_mode'];
        }

        $missing = $this->enum(MissingBillingContextPolicy::class, $stored['missing_context'] ?? null, $operation, 'missing_context')
            ?? $definition['missing_billing_context'];
        $visitRule = $this->enum(PaymentGateVisitContextRule::class, $stored['visit_context_rule'] ?? null, $operation, 'visit_context_rule')
            ?? $definition['visit_context_rule'];
        $overrideRule = $this->enum(PaymentGateOverrideScopeRule::class, $stored['override_scope_rule'] ?? null, $operation, 'override_scope_rule')
            ?? $definition['override_scope_rule'];

        $stage = $definition['stage'] instanceof PaymentGateStage ? $definition['stage'] : PaymentGateStage::RENDER;

        return new PaymentGateOperationPolicy(
            operation: $operation,
            stage: $stage,
            mode: $mode,
            missingBillingContext: $missing,
            visitContextRule: $visitRule,
            overrideScopeRule: $overrideRule,
            allowZeroPatientResponsibility: (bool) ($definition['allow_zero_patient_responsibility'] ?? false),
            allowFullyInsured: (bool) ($definition['allow_fully_insured'] ?? false),
            allowWaived: (bool) ($definition['allow_waived'] ?? false),
            allowFullyAdjusted: (bool) ($definition['allow_fully_adjusted'] ?? false),
            allowPartialPayment: (bool) ($definition['allow_partial_payment'] ?? false),
            emergencyExempt: $this->boolOverride($stored, 'emergency_exempt', (bool) ($definition['emergency_exempt'] ?? false)),
            inpatientExempt: $this->boolOverride($stored, 'inpatient_exempt', (bool) ($definition['inpatient_exempt'] ?? false)),
            enabledForFutureTypedEnforcement: $this->boolOverride($stored, 'typed_enforcement_eligible', (bool) ($definition['typed_enforcement_eligible'] ?? false)),
            metadata: [
                'workflow_family' => $definition['workflow_family'] ?? null,
                'department_type' => $definition['department_type']?->value,
                'production_wired' => (bool) ($definition['production_wired'] ?? false),
                'hard_enforcement' => $isHardGate,
                'compatibility_requires_decision' => (bool) ($definition['compatibility_requires_decision'] ?? false),
                'compatibility_note' => $definition['compatibility_note'] ?? null,
                'has_stored_override' => $stored !== [],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $stored
     */
    private function boolOverride(array $stored, string $key, bool $default): bool
    {
        return array_key_exists($key, $stored) ? (bool) $stored[$key] : $default;
    }

    /**
     * @template T of \BackedEnum
     * @param  class-string<T>  $enum
     * @return T|null
     */
    private function enum(string $enum, mixed $value, string $operation, string $key): ?object
    {
        if (! is_string($value) || $value === '') {
            return null;
        }
        $resolved = $enum::tryFrom($value);
        if ($resolved === null) {
            $this->warn("Invalid stored {$key} value; using registry default.", $operation);
        }

        return $resolved;
    }

    /**
     * Stored (json-decoded) policy for one operation, restricted to overridable keys.
     *
     * @return array<string, mixed>
     */
    private function storedFor(string $operation): array
    {
        try {
            $raw = Setting::getValue(self::GROUP, $operation);
        } catch (Throwable $e) {
            $this->warn('Payment gate operation settings unavailable; using registry defaults.', $operation);

            return [];
        }

        return $this->sanitise($raw);
    }

    /**
     * All stored operation policies via a single grouped read.
     *
     * @return array<string, array<string, mixed>>
     */
    private function storedGroup(): array
    {
        try {
            $group = Setting::getGroup(self::GROUP);
        } catch (Throwable $e) {
            $this->warn('Payment gate operation settings group unavailable; using registry defaults.', self::GROUP);

            return [];
        }

        $out = [];
        foreach ($group as $operation => $rawJson) {
            $decoded = is_string($rawJson) ? json_decode($rawJson, true) : $rawJson;
            $out[$operation] = $this->sanitise($decoded);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitise(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return array_intersect_key($raw, array_flip(self::OVERRIDABLE_KEYS));
    }

    private function warn(string $message, string $operation): void
    {
        try {
            Log::warning($message, ['group' => self::GROUP, 'operation' => $operation]);
        } catch (Throwable) {
            // Configuration fallback must never depend on logging availability.
        }
    }
}
