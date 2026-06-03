<?php

namespace App\Services\Billing;

use App\Enums\AdmissionStatus;
use App\Enums\VisitType;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitBillingOverride;

/**
 * The brain of the context-aware billing policy. Given a visit (care context)
 * and an invoice line, it decides whether the underlying service may be rendered
 * now, in which enforcement MODE, and why.
 *
 * It NEVER mutates state and NEVER blocks emergency / admission care.
 */
class BillingPolicyService
{
    // Care contexts
    public const CONTEXT_OPD = 'OPD';
    public const CONTEXT_EMERGENCY = 'EMERGENCY';
    public const CONTEXT_ADMISSION = 'ADMISSION';

    // Enforcement modes
    public const MODE_STRICT_PAY_BEFORE_SERVICE = 'STRICT_PAY_BEFORE_SERVICE';
    public const MODE_DEFERRED_VISIT_SETTLEMENT = 'DEFERRED_VISIT_SETTLEMENT';
    public const MODE_RUNNING_BILL = 'RUNNING_BILL';
    public const MODE_INSURANCE_COVERED = 'INSURANCE_COVERED';
    public const MODE_CREDIT_APPROVED = 'CREDIT_APPROVED';
    public const MODE_WAIVED = 'WAIVED';
    public const MODE_PAYMENT_GATE_BYPASS = 'PAYMENT_GATE_BYPASS';
    public const MODE_ADVISORY = 'ADVISORY'; // global enforcement disabled

    public function __construct(
        protected InvoiceItemSettlementService $settlement,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Care context
    |--------------------------------------------------------------------------
    */

    public function careContext(Visit $visit): string
    {
        // An active admission wins (an admitted emergency patient is still a
        // running-bill inpatient). Then an open emergency case. Else OPD.
        $admission = $visit->relationLoaded('admission') ? $visit->admission : $visit->admission()->first();
        if ($admission && in_array($admission->status, [AdmissionStatus::ADMITTED, AdmissionStatus::ON_LEAVE], true)) {
            return self::CONTEXT_ADMISSION;
        }

        $emergency = $visit->relationLoaded('emergencyCase') ? $visit->emergencyCase : $visit->emergencyCase()->first();
        if ($emergency && ! in_array($emergency->emergency_status, [
            EmergencyCase::STATUS_DISPOSED,
            EmergencyCase::STATUS_CANCELLED,
        ], true)) {
            return self::CONTEXT_EMERGENCY;
        }

        // Fall back to the visit type for visits whose case/admission record is
        // not yet (or no longer) attached.
        if ($visit->visit_type === VisitType::INPATIENT) {
            return self::CONTEXT_ADMISSION;
        }
        if ($visit->visit_type === VisitType::EMERGENCY) {
            return self::CONTEXT_EMERGENCY;
        }

        return self::CONTEXT_OPD;
    }

    public function isRunningBillContext(Visit $visit): bool
    {
        $ctx = $this->careContext($visit);
        if (in_array($ctx, [self::CONTEXT_EMERGENCY, self::CONTEXT_ADMISSION], true)) {
            return true;
        }

        // OPD becomes a running bill when deferred settlement is active or when
        // the hospital does not require payment before OPD services.
        return $this->hasActiveOverride($visit, VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT)
            || ! config('billing_policy.opd.payment_required_before_service', true);
    }

    public function isDeferredSettlementAllowed(Visit $visit): bool
    {
        return $this->careContext($visit) === self::CONTEXT_OPD
            && (bool) config('billing_policy.opd.allow_deferred_visit_settlement', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    */

    public function getVisitBillingMode(Visit $visit): string
    {
        if (! config('billing_policy.enforce', true)) {
            return self::MODE_ADVISORY;
        }

        $ctx = $this->careContext($visit);
        if ($ctx === self::CONTEXT_EMERGENCY || $ctx === self::CONTEXT_ADMISSION) {
            return self::MODE_RUNNING_BILL;
        }

        // OPD
        if ($this->hasActiveOverride($visit, VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT)) {
            return self::MODE_DEFERRED_VISIT_SETTLEMENT;
        }
        if (! config('billing_policy.opd.payment_required_before_service', true)) {
            return self::MODE_RUNNING_BILL;
        }

        return self::MODE_STRICT_PAY_BEFORE_SERVICE;
    }

    public function requiresPaymentBeforeService(InvoiceItem $item): bool
    {
        $visit = $item->visit ?: ($item->visit_id ? Visit::find($item->visit_id) : null);
        if (! $visit) {
            return false;
        }

        return $this->getVisitBillingMode($visit) === self::MODE_STRICT_PAY_BEFORE_SERVICE
            && ! $this->settlement->canProceedWithoutCashPayment($item);
    }

    /*
    |--------------------------------------------------------------------------
    | The decision
    |--------------------------------------------------------------------------
    */

    public function getInvoiceItemPolicy(InvoiceItem $item, ?User $user = null): BillingPolicyDecision
    {
        $settlementStatus = $this->settlement->settlementStatus($item);
        $extra = ['settlementStatus' => $settlementStatus];

        // Global advisory mode — never block.
        if (! config('billing_policy.enforce', true)) {
            return BillingPolicyDecision::allow(self::MODE_ADVISORY, 'ENFORCEMENT_DISABLED',
                'Billing gate is in advisory mode.', $extra);
        }

        // Already settled by money or coverage — always allowed.
        if ($this->settlement->isPaid($item)) {
            return BillingPolicyDecision::allow(self::MODE_STRICT_PAY_BEFORE_SERVICE, 'ITEM_PAID',
                'This item has been paid for.', $extra);
        }
        if ($this->settlement->isCovered($item)) {
            return BillingPolicyDecision::allow(self::MODE_INSURANCE_COVERED, 'ITEM_COVERED',
                'This item is covered by insurance.', $extra);
        }
        if ($this->settlement->isWaived($item)) {
            return BillingPolicyDecision::allow(self::MODE_WAIVED, 'ITEM_WAIVED',
                'This item has been waived.', $extra);
        }
        if ($settlementStatus === InvoiceItemSettlementService::CANCELLED) {
            return BillingPolicyDecision::allow(self::MODE_STRICT_PAY_BEFORE_SERVICE, 'ITEM_CANCELLED',
                'This item is cancelled.', $extra);
        }

        $visit = $item->visit ?: ($item->visit_id ? Visit::find($item->visit_id) : null);
        if (! $visit) {
            // No visit context to evaluate — fail open (do not block isolated items).
            return BillingPolicyDecision::allow(self::MODE_ADVISORY, 'NO_VISIT_CONTEXT',
                'No visit context for this item.', $extra);
        }

        $ctx = $this->careContext($visit);

        // Emergency / Admission — running bill, care is never blocked.
        if ($ctx === self::CONTEXT_EMERGENCY || $ctx === self::CONTEXT_ADMISSION) {
            return BillingPolicyDecision::allow(self::MODE_RUNNING_BILL, 'RUNNING_BILL',
                $ctx === self::CONTEXT_EMERGENCY
                    ? 'Emergency running bill — care is not blocked by payment.'
                    : 'Admission running bill — care is not blocked by payment.',
                $extra + ['requiresPayment' => true]);
        }

        // ── OPD ──────────────────────────────────────────────────────────
        // Authorised overrides, most specific first.
        if ($this->hasActiveOverride($visit, VisitBillingOverride::TYPE_DEFERRED_OPD_SETTLEMENT)) {
            return BillingPolicyDecision::allow(self::MODE_DEFERRED_VISIT_SETTLEMENT, 'DEFERRED_APPROVED',
                'This OPD visit is approved for deferred settlement.',
                $extra + ['overrideUsed' => true, 'requiresPayment' => true, 'settlementStatus' => 'DEFERRED_APPROVED']);
        }
        if ($this->hasActiveOverride($visit, VisitBillingOverride::TYPE_CREDIT_APPROVAL, $item)) {
            return BillingPolicyDecision::allow(self::MODE_CREDIT_APPROVED, 'CREDIT_APPROVED',
                'This service is credit-approved.',
                $extra + ['overrideUsed' => true, 'settlementStatus' => 'CREDIT_APPROVED']);
        }
        if ($this->hasActiveOverride($visit, VisitBillingOverride::TYPE_PAYMENT_GATE_BYPASS, $item)) {
            return BillingPolicyDecision::allow(self::MODE_PAYMENT_GATE_BYPASS, 'GATE_BYPASS',
                'Payment gate bypass is active for this service.',
                $extra + ['overrideUsed' => true]);
        }

        // Hospital does not require payment before OPD services.
        if (! config('billing_policy.opd.payment_required_before_service', true)) {
            return BillingPolicyDecision::allow(self::MODE_RUNNING_BILL, 'OPD_RUNNING_BILL',
                'OPD payment-before-service is disabled by policy.', $extra);
        }

        // Partial payment may be allowed to proceed when configured.
        if ($settlementStatus === InvoiceItemSettlementService::PARTIALLY_PAID
            && config('billing_policy.opd.partial_payment_can_proceed', false)
            && $this->meetsMinimumPayment($item)) {
            return BillingPolicyDecision::allow(self::MODE_STRICT_PAY_BEFORE_SERVICE, 'PARTIAL_OK',
                'Minimum payment threshold met.', $extra);
        }

        // Strict OPD: not settled → blocked.
        return BillingPolicyDecision::block(self::MODE_STRICT_PAY_BEFORE_SERVICE, 'ITEM_UNPAID',
            $this->blockedMessageFor($item),
            $extra + ['requiresPayment' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    protected function meetsMinimumPayment(InvoiceItem $item): bool
    {
        $payable = (float) ($item->patient_payable ?? 0);
        if ($payable <= 0) {
            return true;
        }
        $paid = (float) ($item->paid_amount ?? 0);
        $percent = ($paid / $payable) * 100;

        return $percent >= (float) config('billing_policy.opd.minimum_payment_percent', 100);
    }

    /**
     * Whether an ACTIVE billing override of the given type applies to the visit
     * (VISIT scope) or to this specific item (INVOICE_ITEM / SERVICE / DEPARTMENT scope).
     */
    public function hasActiveOverride(Visit $visit, string $type, ?InvoiceItem $item = null): bool
    {
        $query = VisitBillingOverride::query()
            ->where('visit_id', $visit->id)
            ->where('override_type', $type)
            ->active();

        if ($item === null) {
            return $query->exists();
        }

        return $query->where(function ($q) use ($item) {
            $q->where('scope', VisitBillingOverride::SCOPE_VISIT)
                ->orWhere(fn ($q2) => $q2->where('scope', VisitBillingOverride::SCOPE_INVOICE_ITEM)->where('scope_id', $item->id))
                ->orWhere(fn ($q2) => $q2->where('scope', VisitBillingOverride::SCOPE_SERVICE)->where('scope_id', $item->service_catalog_id))
                ->orWhere(fn ($q2) => $q2->where('scope', VisitBillingOverride::SCOPE_DEPARTMENT)->where('scope_id', $item->department_id));
        })->exists();
    }

    protected function blockedMessageFor(InvoiceItem $item): string
    {
        return match ($item->source_type) {
            InvoiceItem::SOURCE_CONSULTATION_SERVICE => 'Consultation fee has not been settled.',
            InvoiceItem::SOURCE_INVESTIGATION_SERVICE, InvoiceItem::SOURCE_INVESTIGATION_CONSUMABLE => 'This investigation has not been paid for yet.',
            InvoiceItem::SOURCE_PHARMACY_PRODUCT, InvoiceItem::SOURCE_PHARMACY_BILLING_SELECTION => 'This drug has not been paid for yet.',
            InvoiceItem::SOURCE_PROCEDURE_SERVICE, InvoiceItem::SOURCE_PROCEDURE_CONSUMABLE => 'This procedure payment is still pending.',
            default => 'Please settle the bill before this service can be rendered.',
        };
    }
}
