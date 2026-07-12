<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateContext;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentTimingCutoverMode;
use App\Enums\VisitType;
use App\Models\InvoiceItem;
use App\Models\Visit;
use Throwable;

/**
 * The single Phase 8 cutover orchestrator. Given a legacy decision + item +
 * context it returns EITHER the legacy decision (disabled/observe/ineligible/
 * failure — the safe default) or a typed decision (active + operation typed +
 * eligible context).
 *
 * Safety contract: DISABLED returns legacy immediately with no arrangement/
 * visit-policy/typed queries; OBSERVE returns the EXACT legacy decision (only
 * records a bounded diagnostic); ANY exception returns legacy; emergency visits
 * always fall back to legacy; it never mutates arrangements/invoices/payments.
 */
class PaymentTimingCutoverGateService
{
    public function __construct(
        private readonly PaymentTimingCutoverConfigurationService $cutover,
        private readonly PaymentGateOperationConfigurationService $operationConfiguration,
        private readonly OperationalVisitPaymentTimingResolver $operationalResolver,
        private readonly TypedPaymentGateDecisionService $typedDecisionService,
        private readonly PaymentTimingCutoverDiagnostics $diagnostics,
    ) {}

    public function apply(BillingPolicyDecision $legacy, ?InvoiceItem $item, PaymentGateContext $context): BillingPolicyDecision
    {
        $mode = $this->cutover->effectiveMode();

        // DISABLED (default): legacy unchanged, zero extra queries.
        if ($mode === PaymentTimingCutoverMode::DISABLED) {
            return $legacy;
        }

        try {
            $visit = $this->resolveVisit($item);
            if ($visit === null) {
                return $legacy; // no visit context — cannot resolve typed policy
            }

            // Emergency always stays legacy in Phase 8 (no stabilisation boundary).
            if ($this->isEmergency($visit)) {
                $this->diagnostics->record(PaymentTimingCutoverDiagnostics::EMERGENCY_SCOPE_FALLBACK, [
                    'visit_id' => $visit->id, 'operation' => $context->operation, 'cutover_mode' => $mode->value,
                    'reason' => TypedPaymentGateReason::TYPED_EMERGENCY_FALLBACK,
                ]);

                return $legacy;
            }

            $operationPolicy = $this->operationConfiguration->policyFor($context->operation);
            $operational = $this->operationalResolver->resolve($visit, $context);
            $typed = $this->typedDecisionService->evaluate($legacy, $visit, $item, $operational->policy, $operationPolicy, $context);

            $applyTyped = $mode === PaymentTimingCutoverMode::ACTIVE
                && $operationPolicy->mode === PaymentGateOperationMode::TYPED;

            if (! $applyTyped) {
                // OBSERVE (or ACTIVE-but-not-typed): keep EXACT legacy, record diff.
                if ($legacy->allowed !== $typed->allowed) {
                    $this->diagnostics->record(PaymentTimingCutoverDiagnostics::OBSERVED, [
                        'visit_id' => $visit->id, 'operation' => $context->operation, 'cutover_mode' => $mode->value,
                        'legacy_allowed' => $legacy->allowed, 'typed_allowed' => $typed->allowed,
                        'policy' => $operational->policy->value, 'source' => $operational->source->value,
                    ]);
                }

                return $legacy;
            }

            // ACTIVE + typed operation: apply the typed decision, tagged with authority.
            if (! $operational->usedApprovedArrangement) {
                $this->diagnostics->record(PaymentTimingCutoverDiagnostics::ARRANGEMENT_INELIGIBLE, [
                    'visit_id' => $visit->id, 'operation' => $context->operation, 'cutover_mode' => $mode->value,
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
            // Any failure → legacy (fail-safe), record a bounded diagnostic.
            $this->diagnostics->record(PaymentTimingCutoverDiagnostics::LEGACY_FALLBACK, [
                'operation' => $context->operation, 'cutover_mode' => $mode->value,
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

    private function isEmergency(Visit $visit): bool
    {
        return ($visit->visit_type instanceof VisitType ? $visit->visit_type : VisitType::tryFrom((string) $visit->visit_type))
            === VisitType::EMERGENCY;
    }
}
