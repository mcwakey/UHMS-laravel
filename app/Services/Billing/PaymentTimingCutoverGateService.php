<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateContext;
use App\Models\InvoiceItem;
use App\Models\Visit;
use Throwable;

/**
 * Central runtime payment-timing authority for every PaymentGateService call.
 *
 * When configurable payment timing is disabled we preserve the historical
 * decision exactly. When it is enabled, the typed visit policy is authoritative
 * for payment-related blocks across all registered gate operations that reach
 * this service. This service never mutates arrangements, invoices or payments.
 */
class PaymentTimingCutoverGateService
{
    public function __construct(
        private readonly PaymentTimingConfigurationService $paymentTimingConfiguration,
        private readonly PaymentTimingCutoverConfigurationService $cutover,
        private readonly PaymentGateOperationConfigurationService $operationConfiguration,
        private readonly OperationalVisitPaymentTimingResolver $operationalResolver,
        private readonly TypedPaymentGateDecisionService $typedDecisionService,
        private readonly PaymentTimingCutoverDiagnostics $diagnostics,
    ) {}

    public function apply(BillingPolicyDecision $legacy, ?InvoiceItem $item, PaymentGateContext $context): BillingPolicyDecision
    {
        $mode = $this->cutover->effectiveMode();

        if (! $this->paymentTimingConfiguration->enabled()) {
            return $legacy;
        }

        try {
            $visit = $this->resolveVisit($item);
            if ($visit === null) {
                return $legacy; // no visit context; cannot resolve typed policy
            }

            $operationPolicy = $this->operationConfiguration->policyFor($context->operation);
            $operational = $this->operationalResolver->resolve($visit, $context);
            $typed = $this->typedDecisionService->evaluate($legacy, $visit, $item, $operational->policy, $operationPolicy, $context);

            if ($legacy->allowed !== $typed->allowed) {
                $this->diagnostics->record(PaymentTimingCutoverDiagnostics::OBSERVED, [
                    'visit_id' => $visit->id,
                    'operation' => $context->operation,
                    'cutover_mode' => $mode->value,
                    'legacy_allowed' => $legacy->allowed,
                    'typed_allowed' => $typed->allowed,
                    'policy' => $operational->policy->value,
                    'source' => $operational->source->value,
                ]);
            }

            if (! $operational->usedApprovedArrangement) {
                $this->diagnostics->record(PaymentTimingCutoverDiagnostics::ARRANGEMENT_INELIGIBLE, [
                    'visit_id' => $visit->id,
                    'operation' => $context->operation,
                    'cutover_mode' => $mode->value,
                    'reason' => $operational->arrangementEligibilityReason,
                ]);
            }

            return $typed->withAuthority([
                'decisionAuthority' => $operational->usedApprovedArrangement ? 'approved_arrangement' : 'typed_baseline',
                'paymentTimingPolicy' => $operational->policy->value,
                'paymentTimingSource' => $operational->source->value,
                'operation' => $context->operation,
                'cutoverMode' => $mode->value,
                'approvedArrangementId' => $operational->approvedArrangementId,
            ]);
        } catch (Throwable $e) {
            // Any failure falls back to the historical decision and records a bounded diagnostic.
            $this->diagnostics->record(PaymentTimingCutoverDiagnostics::LEGACY_FALLBACK, [
                'operation' => $context->operation,
                'cutover_mode' => $mode->value,
                'reason' => TypedPaymentGateReason::TYPED_FAILURE_LEGACY_FALLBACK,
            ]);

            return $legacy->withAuthority([
                'decisionAuthority' => 'legacy_fallback',
                'operation' => $context->operation,
                'cutoverMode' => $mode->value,
                'fallbackReason' => TypedPaymentGateReason::TYPED_FAILURE_LEGACY_FALLBACK,
            ]);
        }
    }

    private function resolveVisit(?InvoiceItem $item): ?Visit
    {
        if ($item === null) {
            return null;
        }

        if ($item->relationLoaded('visit') && $item->visit instanceof Visit) {
            return $item->visit;
        }

        return $item->visit_id ? $item->visit()->first() : null;
    }
}
