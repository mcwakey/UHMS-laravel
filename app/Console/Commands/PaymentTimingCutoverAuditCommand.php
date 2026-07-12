<?php

namespace App\Console\Commands;

use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentTimingCutoverMode;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\VisitPaymentArrangement;
use App\Services\Billing\ApprovedArrangementOperationalEligibilityService;
use App\Services\Billing\PaymentGateOperationConfigurationService;
use App\Services\Billing\PaymentGateOperationRegistry;
use App\Services\Billing\PaymentTimingCutoverConfigurationService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Read-only audit of unsafe cutover configuration (Payment Timing Policy Phase
 * 8). No writes/repair/activity logs; findings never fail the exit code.
 */
class PaymentTimingCutoverAuditCommand extends Command
{
    protected $signature = 'billing:payment-timing-cutover-audit
        {--operation= : Only this operation}
        {--visit= : Only arrangements for this visit}
        {--active-only : Only approved arrangements}
        {--problems-only : Only rows with findings}
        {--limit=2000 : Maximum arrangements to scan}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Audit payment-timing cutover configuration and approved arrangements for unsafe states (read-only).';

    public function handle(
        PaymentTimingCutoverConfigurationService $cutover,
        PaymentGateOperationRegistry $registry,
        PaymentGateOperationConfigurationService $config,
        ApprovedArrangementOperationalEligibilityService $eligibility,
    ): int {
        try {
            $findings = [];

            $forceLegacy = $cutover->forceLegacy();
            $configured = $cutover->configuredMode();

            // Master-level findings.
            if ($configured === PaymentTimingCutoverMode::ACTIVE && $forceLegacy) {
                $findings['master'][] = 'master_active_while_force_legacy';
            }
            if ($configured === PaymentTimingCutoverMode::ACTIVE && ! $cutover->fallbackToLegacyOnFailure()) {
                $findings['master'][] = 'active_without_rollback_fallback';
            }

            // Operation-level findings.
            foreach ($registry->operations() as $operation => $definition) {
                if (($op = $this->option('operation')) && $operation !== $op) {
                    continue;
                }
                $stored = $config->storedForPublic($operation);
                $storedMode = $stored['mode'] ?? null;
                if ($storedMode !== PaymentGateOperationMode::TYPED->value) {
                    continue;
                }
                $problems = [];
                if (! ($definition['production_wired'] ?? false)) {
                    $problems[] = 'typed_on_unwired_operation';
                }
                if (! $config->operationTypedEligible($operation)) {
                    $problems[] = 'typed_on_ineligible_operation';
                }
                if (($definition['requires_compatibility_acknowledgement'] ?? false) && ! (bool) ($stored['compatibility_acknowledged'] ?? false)) {
                    $problems[] = 'missing_compatibility_acknowledgement';
                }
                if (($definition['typed_supported_visit_types'] ?? []) === []) {
                    $problems[] = 'no_supported_visit_types';
                }
                if ($problems !== []) {
                    $findings['operation:'.$operation] = $problems;
                }
            }

            // Approved-arrangement findings.
            $query = VisitPaymentArrangement::query()->with('visit.paymentPolicy');
            if ($v = $this->option('visit')) {
                $query->where('visit_id', $v);
            }
            if ($this->option('active-only')) {
                $query->approved();
            } else {
                $query->approved();
            }
            $query->orderBy('id')->limit(max(1, (int) $this->option('limit')))->chunkById(200, function ($rows) use (&$findings, $eligibility): void {
                foreach ($rows as $a) {
                    if ($a->visit === null) {
                        continue;
                    }
                    $problems = [];
                    if ($a->approved_policy === null || $a->approved_policy === VisitPaymentTimingPolicy::INHERIT) {
                        $problems[] = 'resolved_inherit_policy';
                    }
                    // Evaluate against a representative typed-eligible operation.
                    $result = $eligibility->evaluate($a->visit, 'laboratory.result.enter', $a);
                    if (! $result->eligible && in_array($result->reasonCode, ['stale_risk_context', 'expired', 'link_mismatch', 'conflicting_legacy_visit_override'], true)) {
                        $problems[] = $result->reasonCode;
                    }
                    if ($problems !== []) {
                        $findings['arrangement:'.$a->id] = $problems;
                    }
                }
            }, 'id');

            return $this->render($findings);
        } catch (Throwable $e) {
            $this->error('Cutover audit failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string, array<int,string>> $findings */
    private function render(array $findings): int
    {
        if ($this->option('problems-only')) {
            $findings = array_filter($findings, fn ($f) => $f !== []);
        }

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'entries_with_findings' => count($findings),
                'findings' => $findings,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Payment timing cutover audit (read-only)');
        $this->info('Entries with findings: '.count($findings));
        foreach ($findings as $entry => $problems) {
            $this->line("• {$entry}: ".implode(', ', $problems));
        }
        if ($findings === []) {
            $this->line('No unsafe cutover configuration detected.');
        }
        $this->line('Findings are advisory; no data was modified.');

        return self::SUCCESS;
    }
}
