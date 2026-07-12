<?php

namespace App\Console\Commands;

use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use App\Services\Billing\PaymentTimingCutoverConfigurationService;
use Illuminate\Console\Command;

/**
 * Read-only status of the payment-timing operational cutover (Payment Timing
 * Policy Phase 8). No writes, no activity logs; warnings never fail the exit
 * code.
 */
class PaymentTimingCutoverStatusCommand extends Command
{
    protected $signature = 'billing:payment-timing-cutover-status
        {--operation= : Only this operation}
        {--visit-type= : Filter operations supporting this visit type}
        {--typed-only : Only operations currently configured typed}
        {--problems-only : Only operations with blockers}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Report configured/effective cutover mode and per-operation typed eligibility (read-only).';

    public function handle(
        PaymentTimingCutoverConfigurationService $cutover,
        PaymentGateOperationRegistry $registry,
        PaymentGateOperationConfigurationService $config,
    ): int {
        $rows = [];
        foreach ($registry->operations() as $operation => $definition) {
            if (($op = $this->option('operation')) && $operation !== $op) {
                continue;
            }
            if (($vt = $this->option('visit-type')) && ! in_array($vt, $definition['typed_supported_visit_types'] ?? [], true)) {
                continue;
            }
            $policy = $config->policyFor($operation);
            if ($this->option('typed-only') && $policy->mode->value !== 'typed') {
                continue;
            }
            $usesTyped = $cutover->operationUsesTypedPolicy($operation);
            $blockers = ($policy->mode->value === 'typed' && ! $usesTyped) ? ['not_effective'] : [];
            if ($this->option('problems-only') && $blockers === []) {
                continue;
            }
            $rows[] = [
                'operation' => $operation,
                'wired' => (bool) ($definition['production_wired'] ?? false),
                'mode' => $policy->mode->value,
                'typed_eligible' => $config->operationTypedEligible($operation),
                'uses_typed' => $usesTyped,
                'supported_visit_types' => $definition['typed_supported_visit_types'] ?? [],
                'requires_acknowledgement' => (bool) ($definition['requires_compatibility_acknowledgement'] ?? false),
                'acknowledged' => $config->compatibilityAcknowledged($operation),
                'emergency_supported' => (bool) ($definition['emergency_supported'] ?? false),
                'blockers' => $blockers,
            ];
        }

        $summary = [
            'configured_mode' => $cutover->configuredMode()->value,
            'effective_mode' => $cutover->effectiveMode()->value,
            'force_legacy' => $cutover->forceLegacy(),
            'failure_fallback' => $cutover->fallbackToLegacyOnFailure(),
            'operations' => $rows,
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Configured: {$summary['configured_mode']} · Effective: {$summary['effective_mode']} · Force-legacy: ".($summary['force_legacy'] ? 'yes' : 'no'));
        $this->table(
            ['Operation', 'Wired', 'Mode', 'Typed eligible', 'Uses typed', 'Visit types', 'Ack', 'Emergency', 'Blockers'],
            array_map(fn ($r) => [
                $r['operation'], $r['wired'] ? 'yes' : 'no', $r['mode'],
                $r['typed_eligible'] ? 'yes' : 'no', $r['uses_typed'] ? 'yes' : 'no',
                implode(',', $r['supported_visit_types']) ?: '—',
                $r['requires_acknowledgement'] ? ($r['acknowledged'] ? 'yes' : 'no') : '—',
                $r['emergency_supported'] ? 'yes' : 'no',
                $r['blockers'] === [] ? '—' : implode(',', $r['blockers']),
            ], $rows),
        );
        $this->line('Read-only; no settings were modified.');

        return self::SUCCESS;
    }
}
