<?php

namespace App\Console\Commands;

use App\Services\Billing\PaymentGateEnforcementEligibilityService;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use Illuminate\Console\Command;

/**
 * Read-only report of registered payment-gate operations: stage, wiring,
 * configured policy and future typed-enforcement eligibility (Payment Timing
 * Policy Phase 3, extended in Phase 4). Creates no invoices, payments,
 * overrides, settings or activity logs, and touches no patient data.
 */
class PaymentGateCoverageCommand extends Command
{
    protected $signature = 'billing:payment-gate-coverage
        {--operation= : Only this operation code}
        {--department= : Filter by department type (e.g. pharmacy)}
        {--wired-only : Only production-wired operations}
        {--unwired-only : Only unwired operations}
        {--eligible-only : Only operations eligible for future typed enforcement}
        {--ineligible-only : Only operations not yet eligible}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Report registered payment-gate stages, configured policy and eligibility without changing data.';

    public function handle(
        PaymentGateOperationRegistry $registry,
        PaymentGateOperationConfigurationService $configuration,
        PaymentGateEnforcementEligibilityService $eligibility,
    ): int {
        $rows = collect($registry->operations())
            ->map(function (array $entry, string $operation) use ($configuration, $eligibility): array {
                $policy = $configuration->policyFor($operation);
                $elig = $eligibility->evaluate($operation);

                return [
                    'operation' => $operation,
                    'stage' => $entry['stage']->value,
                    'department' => $entry['department_type']?->value,
                    'production_wired' => (bool) $entry['production_wired'],
                    'hard_enforcement' => (bool) $entry['hard_enforcement'],
                    'configured_mode' => $policy->mode->value,
                    'legacy_behaviour' => $entry['legacy_behaviour'],
                    'missing_billing_context' => $policy->missingBillingContext->value,
                    'emergency_exempt' => $policy->emergencyExempt,
                    'inpatient_exempt' => $policy->inpatientExempt,
                    'override_scope_rule' => $policy->overrideScopeRule->value,
                    'typed_enforcement_eligible' => $elig->eligible,
                    'eligibility_status' => $elig->status,
                ];
            })
            ->values()
            ->filter(fn (array $r) => $this->passesFilters($r))
            ->values();

        $result = [
            'registered' => collect($registry->operations())->count(),
            'shown' => $rows->count(),
            'production_wired' => $rows->where('production_wired', true)->count(),
            'unwired' => $rows->where('production_wired', false)->count(),
            'eligible' => $rows->where('typed_enforcement_eligible', true)->count(),
            'operations' => $rows->all(),
        ];

        if ($this->option('json')) {
            $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info("Registered operations: {$result['registered']} (showing {$result['shown']})");
        $this->info("Production wired: {$result['production_wired']} · Unwired: {$result['unwired']} · Eligible: {$result['eligible']}");
        $this->line('No enforcement was added; this report is read-only.');
        $this->table(
            ['Operation', 'Stage', 'Dept', 'Wired', 'Hard', 'Mode', 'Missing ctx', 'Override scope', 'Eligible', 'Eligibility'],
            $rows->map(fn (array $r) => [
                $r['operation'], $r['stage'], $r['department'] ?? '—',
                $r['production_wired'] ? 'yes' : 'no', $r['hard_enforcement'] ? 'yes' : 'no',
                $r['configured_mode'], $r['missing_billing_context'], $r['override_scope_rule'],
                $r['typed_enforcement_eligible'] ? 'yes' : 'no', $r['eligibility_status'],
            ])->all(),
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function passesFilters(array $row): bool
    {
        if (($op = $this->option('operation')) && $row['operation'] !== $op) {
            return false;
        }
        if (($dept = $this->option('department')) && $row['department'] !== $dept) {
            return false;
        }
        if ($this->option('wired-only') && ! $row['production_wired']) {
            return false;
        }
        if ($this->option('unwired-only') && $row['production_wired']) {
            return false;
        }
        if ($this->option('eligible-only') && ! $row['typed_enforcement_eligible']) {
            return false;
        }
        if ($this->option('ineligible-only') && $row['typed_enforcement_eligible']) {
            return false;
        }

        return true;
    }
}
