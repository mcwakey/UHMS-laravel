<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateContext;
use App\Data\Billing\PaymentGateOperationPolicy;
use App\Enums\MissingBillingContextPolicy;
use App\Enums\VisitPaymentTimingPolicy;
use App\Models\InvoiceItem;
use App\Models\Visit;

/**
 * Turns a runtime typed payment-timing policy into a {@see BillingPolicyDecision}
 * (Payment Timing Policy Phase 8).
 *
 * It changes ONLY payment-timing permission — it reuses the existing settlement
 * service (no second balance/accounting calc), preserves every non-payment
 * failure (clinical/stock/cancelled/invalid), and NEVER marks anything paid,
 * creates a payment/waiver/override, or changes an invoice/receivable.
 */
class TypedPaymentGateDecisionService
{
    public function __construct(private readonly InvoiceItemSettlementService $settlement) {}

    public function evaluate(
        BillingPolicyDecision $legacyDecision,
        Visit $visit,
        ?InvoiceItem $invoiceItem,
        VisitPaymentTimingPolicy $policy,
        PaymentGateOperationPolicy $operationPolicy,
        PaymentGateContext $context,
    ): BillingPolicyDecision {
        // Typed timing only governs PAYMENT permission. A legacy block for a
        // non-payment reason (cancelled, missing clinical prerequisite, invalid
        // entity, stock, …) is preserved unchanged.
        if (! $legacyDecision->allowed && ! $this->isPaymentBlock($legacyDecision)) {
            return $legacyDecision;
        }

        // Missing billing context → operation's configured missing-context rule.
        if ($invoiceItem === null) {
            return $this->missingContext($legacyDecision, $policy, $operationPolicy);
        }

        return match ($policy) {
            VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES => $this->allow(
                BillingPolicyService::MODE_DEFERRED_VISIT_SETTLEMENT,
                TypedPaymentGateReason::TYPED_PAY_AFTER_SERVICES_ALLOWED,
                $legacyDecision,
            ),
            VisitPaymentTimingPolicy::RUNNING_BILL => $this->allow(
                BillingPolicyService::MODE_RUNNING_BILL,
                TypedPaymentGateReason::TYPED_RUNNING_BILL_ALLOWED,
                $legacyDecision,
            ),
            VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE => $this->prepay($legacyDecision, $invoiceItem, $operationPolicy),
            // INHERIT should never reach runtime; preserve legacy defensively.
            default => $legacyDecision,
        };
    }

    private function prepay(BillingPolicyDecision $legacy, InvoiceItem $item, PaymentGateOperationPolicy $operationPolicy): BillingPolicyDecision
    {
        if ($this->isSettledForPrepay($legacy, $item, $operationPolicy)) {
            return $this->allow(
                BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE,
                TypedPaymentGateReason::TYPED_PREPAYMENT_SETTLED,
                $legacy,
            );
        }

        return BillingPolicyDecision::block(
            BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE,
            TypedPaymentGateReason::TYPED_PREPAYMENT_REQUIRED,
            TypedPaymentGateReason::message(TypedPaymentGateReason::TYPED_PREPAYMENT_REQUIRED),
            ['requiresPayment' => true, 'settlementStatus' => $this->settlement->settlementStatus($item)],
        );
    }

    private function isSettledForPrepay(BillingPolicyDecision $legacy, InvoiceItem $item, PaymentGateOperationPolicy $operationPolicy): bool
    {
        // A compatible narrow override already recognised by the legacy layer stands.
        if ($legacy->overrideUsed) {
            return true;
        }

        return match ($this->settlement->settlementStatus($item)) {
            InvoiceItemSettlementService::PAID => true,
            InvoiceItemSettlementService::CANCELLED => true,
            InvoiceItemSettlementService::COVERED_BY_INSURANCE => $operationPolicy->allowFullyInsured,
            InvoiceItemSettlementService::WAIVED => $operationPolicy->allowWaived,
            InvoiceItemSettlementService::ADJUSTED => $operationPolicy->allowFullyAdjusted,
            InvoiceItemSettlementService::PARTIALLY_PAID => $operationPolicy->allowPartialPayment,
            InvoiceItemSettlementService::UNBILLED => $operationPolicy->allowZeroPatientResponsibility,
            default => false, // BILLED_UNPAID
        };
    }

    private function missingContext(BillingPolicyDecision $legacy, VisitPaymentTimingPolicy $policy, PaymentGateOperationPolicy $operationPolicy): BillingPolicyDecision
    {
        return match ($operationPolicy->missingBillingContext) {
            MissingBillingContextPolicy::ALLOW => $this->allow(
                BillingPolicyService::MODE_ADVISORY,
                TypedPaymentGateReason::TYPED_PAY_AFTER_SERVICES_ALLOWED,
                $legacy,
            ),
            MissingBillingContextPolicy::BLOCK => BillingPolicyDecision::block(
                BillingPolicyService::MODE_STRICT_PAY_BEFORE_SERVICE,
                TypedPaymentGateReason::TYPED_PREPAYMENT_REQUIRED,
                TypedPaymentGateReason::message(TypedPaymentGateReason::TYPED_PREPAYMENT_REQUIRED),
                ['requiresPayment' => true],
            ),
            // PRESERVE_LEGACY / NOT_APPLICABLE → do not normalise; keep legacy.
            default => $legacy,
        };
    }

    private function allow(string $mode, string $reason, BillingPolicyDecision $legacy): BillingPolicyDecision
    {
        return BillingPolicyDecision::allow($mode, $reason, TypedPaymentGateReason::message($reason), [
            'settlementStatus' => $legacy->settlementStatus,
            'overrideUsed' => $legacy->overrideUsed,
        ]);
    }

    /** A legacy block is payment-related when it requires payment (the only thing typed timing may relax). */
    private function isPaymentBlock(BillingPolicyDecision $decision): bool
    {
        return $decision->requiresPayment || in_array($decision->reason, ['ITEM_UNPAID'], true);
    }
}
