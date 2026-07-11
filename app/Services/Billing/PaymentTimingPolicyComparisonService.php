<?php

namespace App\Services\Billing;

use App\Data\Billing\LegacyPaymentGateSnapshot;
use App\Data\Billing\PaymentTimingPolicyComparison;
use App\Data\Billing\VisitPaymentTimingDecision;
use App\Enums\PaymentTimingComparisonOutcome;
use App\Models\Visit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentTimingPolicyComparisonService
{
    public function __construct(private PaymentTimingConfigurationService $configuration) {}

    public function compare(
        Visit $visit,
        LegacyPaymentGateSnapshot $legacy,
        VisitPaymentTimingDecision $typed,
        array $context = [],
        bool $recordDiagnostics = true,
    ): PaymentTimingPolicyComparison {
        $outcome = match (true) {
            $legacy->allowed === null => PaymentTimingComparisonOutcome::MISSING_CONTEXT,
            ! $legacy->comparable => PaymentTimingComparisonOutcome::NOT_COMPARABLE,
            $legacy->allowed === $typed->allowsServiceBeforePayment() => PaymentTimingComparisonOutcome::MATCH,
            ! $legacy->allowed && $typed->allowsServiceBeforePayment() => PaymentTimingComparisonOutcome::LEGACY_MORE_RESTRICTIVE,
            default => PaymentTimingComparisonOutcome::TYPED_MORE_RESTRICTIVE,
        };

        $visitType = $visit->visit_type;
        $safeContext = [
            'visit_id' => $visit->getKey(),
            'visit_type' => $visitType instanceof \BackedEnum ? (string) $visitType->value : ($visitType !== null ? (string) $visitType : null),
            'payment_gate_stage' => isset($context['payment_gate_stage']) ? (string) $context['payment_gate_stage'] : null,
            'gate_operation' => (string) ($context['gate_operation'] ?? 'invoice_item_policy'),
            'department_type' => isset($context['department_type']) ? (string) $context['department_type'] : null,
            'service_type' => isset($context['service_type']) ? (string) $context['service_type'] : null,
            'invoice_item_present' => isset($context['invoice_item_present']) ? (bool) $context['invoice_item_present'] : null,
            'emergency_stabilisation' => isset($context['emergency_stabilisation']) ? (bool) $context['emergency_stabilisation'] : null,
            'integration_mode' => $this->configuration->integrationMode()->value,
        ];
        $comparison = new PaymentTimingPolicyComparison($outcome, $legacy, $typed, $safeContext);

        if ($recordDiagnostics) {
            $this->record($comparison);
        }

        return $comparison;
    }

    private function record(PaymentTimingPolicyComparison $comparison): void
    {
        $shouldLog = $comparison->isMismatch()
            ? $this->configuration->logsIntegrationMismatches()
            : ($comparison->outcome === PaymentTimingComparisonOutcome::MATCH
                && $this->configuration->logsIntegrationMatches());
        if (! $shouldLog) {
            return;
        }

        try {
            $payload = $comparison->toArray();
            $fingerprint = hash('sha256', json_encode([
                $comparison->context['visit_id'],
                $comparison->context['gate_operation'],
                $comparison->outcome->value,
                $comparison->legacy->reasonCode,
                $comparison->typed->reasonCode,
            ], JSON_THROW_ON_ERROR));
            $ttl = $this->configuration->integrationLogDeduplicationSeconds();
            if ($ttl > 0 && ! Cache::add("payment_timing.comparison.{$fingerprint}", true, $ttl)) {
                return;
            }

            if ($comparison->isMismatch()) {
                Log::warning('payment_timing_policy_mismatch', $payload);
            } else {
                Log::info('payment_timing_policy_match', $payload);
            }
        } catch (Throwable $exception) {
            // Diagnostics must never affect the legacy gate result.
            try {
                Log::debug('payment_timing_observation_logging_failed', ['exception' => $exception::class]);
            } catch (Throwable) {
                // There is deliberately no further fallback from diagnostic logging.
            }
        }
    }
}
