<?php

namespace App\Console\Commands;

use App\Enums\MissingBillingContextPolicy;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentGateOverrideScopeRule;
use App\Enums\PaymentGateVisitContextRule;
use App\Models\Setting;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use Illuminate\Console\Command;
use Throwable;

/**
 * Read-only audit of departmental payment-gate policy configuration (Payment
 * Timing Policy Phase 4). It identifies unsafe or inconsistent combinations but
 * NEVER modifies settings. Warnings do not cause a failure exit code — it exits
 * non-zero only on a technical command failure.
 */
class PaymentGatePolicyAuditCommand extends Command
{
    protected $signature = 'billing:payment-gate-policy-audit
        {--operation= : Only this operation code}
        {--department= : Filter by department type}
        {--problems-only : Only show operations with findings}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Audit payment-gate operation policy for unsafe combinations (read-only; no writes).';

    /** @var array<string, string> mapping of enum override key => enum class */
    private const ENUM_KEYS = [
        'mode' => PaymentGateOperationMode::class,
        'missing_context' => MissingBillingContextPolicy::class,
        'visit_context_rule' => PaymentGateVisitContextRule::class,
        'override_scope_rule' => PaymentGateOverrideScopeRule::class,
    ];

    public function handle(
        PaymentGateOperationRegistry $registry,
        PaymentGateOperationConfigurationService $configuration,
    ): int {
        try {
            $stored = $this->storedGroup();
            $operations = $registry->operations();

            $findings = [];

            // Stored settings that do not correspond to a registered operation.
            foreach (array_keys($stored) as $storedOperation) {
                if (! $registry->has($storedOperation)) {
                    $findings[$storedOperation][] = 'unknown_operation_setting';
                }
            }

            foreach ($operations as $operation => $definition) {
                if (($op = $this->option('operation')) && $operation !== $op) {
                    continue;
                }
                if (($dept = $this->option('department')) && $definition['department_type']?->value !== $dept) {
                    continue;
                }

                $rawStored = $stored[$operation] ?? [];
                $policy = $configuration->policyFor($operation);
                $problems = [];

                // Invalid stored enum values.
                foreach (self::ENUM_KEYS as $key => $enum) {
                    if (isset($rawStored[$key]) && is_string($rawStored[$key]) && $enum::tryFrom($rawStored[$key]) === null) {
                        $problems[] = "invalid_stored_enum:{$key}";
                    }
                }

                // Missing registry defaults (defensive).
                foreach (['default_mode', 'missing_billing_context', 'visit_context_rule', 'override_scope_rule', 'stage'] as $required) {
                    if (! isset($definition[$required])) {
                        $problems[] = "missing_registry_default:{$required}";
                    }
                }

                $wired = (bool) ($definition['production_wired'] ?? false);
                $hardGate = (bool) ($definition['hard_enforcement'] ?? false);

                // Wired operation whose stored intent is disabled (config safely clamps it).
                if ($wired && ($rawStored['mode'] ?? null) === PaymentGateOperationMode::DISABLED->value) {
                    $problems[] = 'wired_configured_disabled';
                }
                // Unwired operation configured as legacy enforcement.
                if (! $wired && $policy->mode === PaymentGateOperationMode::LEGACY) {
                    $problems[] = 'unwired_configured_legacy';
                }
                // Hard gate that resolved to a non-protective mode (should never happen — clamp).
                if ($hardGate && $policy->mode === PaymentGateOperationMode::DISABLED) {
                    $problems[] = 'hard_gate_without_compatibility_protection';
                }
                // Typed-eligible operation still missing invoice resolution.
                if (($definition['typed_enforcement_eligible'] ?? false) && ! ($definition['invoice_resolution_confirmed'] ?? false)) {
                    $problems[] = 'typed_eligible_missing_invoice_resolution';
                }
                // Emergency-sensitive operation without a reliable stabilisation boundary.
                if ($definition['emergency_sensitive'] ?? false) {
                    $problems[] = 'emergency_sensitive_without_boundary';
                }

                if ($problems !== []) {
                    $findings[$operation] = array_values(array_unique(array_merge($findings[$operation] ?? [], $problems)));
                } elseif (isset($findings[$operation])) {
                    $findings[$operation] = array_values(array_unique($findings[$operation]));
                }
            }

            return $this->render($operations, $findings);
        } catch (Throwable $e) {
            $this->error('Policy audit failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $operations
     * @param  array<string, array<int, string>>  $findings
     */
    private function render(array $operations, array $findings): int
    {
        $problemsOnly = (bool) $this->option('problems-only');

        $rows = [];
        foreach ($operations as $operation => $definition) {
            if (($op = $this->option('operation')) && $operation !== $op) {
                continue;
            }
            if (($dept = $this->option('department')) && $definition['department_type']?->value !== $dept) {
                continue;
            }
            $ops = $findings[$operation] ?? [];
            if ($problemsOnly && $ops === []) {
                continue;
            }
            $rows[] = ['operation' => $operation, 'findings' => $ops];
        }
        // Include unknown-operation-setting findings even if not in the registry.
        foreach ($findings as $operation => $ops) {
            if (! isset($operations[$operation])) {
                $rows[] = ['operation' => $operation, 'findings' => $ops];
            }
        }

        $totalProblems = collect($findings)->flatten()->count();

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'operations_audited' => count($operations),
                'operations_with_findings' => count($findings),
                'total_findings' => $totalProblems,
                'findings' => $rows,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Payment gate policy audit (read-only)');
        $this->info('Operations with findings: '.count($findings).' · Total findings: '.$totalProblems);
        if ($rows === []) {
            $this->line('No findings for the selected filters.');
        } else {
            $this->table(['Operation', 'Findings'], array_map(fn (array $r) => [
                $r['operation'], $r['findings'] === [] ? '—' : implode(', ', $r['findings']),
            ], $rows));
        }
        $this->line('Findings are advisory; no settings were modified.');

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function storedGroup(): array
    {
        $out = [];
        foreach (Setting::getGroup(PaymentGateOperationConfigurationService::GROUP) as $operation => $rawJson) {
            $decoded = is_string($rawJson) ? json_decode($rawJson, true) : $rawJson;
            $out[$operation] = is_array($decoded) ? $decoded : [];
        }

        return $out;
    }
}
