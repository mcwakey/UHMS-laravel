<?php

namespace App\Services\Billing;

use App\Exceptions\BillingGateException;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Visit;

/**
 * The enforcement façade. Departments ask "can I render this now?" and get a
 * yes/no (or a thrown friendly BillingGateException). All decisions come from
 * BillingPolicyService — this class only resolves the relevant invoice line and
 * turns a decision into a guard.
 *
 * Wire the assert*() methods into the relevant controllers/services (consultation
 * start, lab process, pharmacy dispense, procedure start, service render). They
 * are safe no-ops for already-settled / running-bill / overridden contexts.
 */
class PaymentGateService
{
    public function __construct(
        protected BillingPolicyService $policy,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Invoice-item level (the primitive every other check builds on)
    |--------------------------------------------------------------------------
    */

    public function policyFor(InvoiceItem $item, ?User $user = null): BillingPolicyDecision
    {
        return $this->policy->getInvoiceItemPolicy($item, $user, 'invoice_item_policy');
    }

    public function canRenderInvoiceItem(InvoiceItem $item, ?User $user = null, string $gateOperation = 'invoice_item_render'): bool
    {
        return $this->policy->getInvoiceItemPolicy($item, $user, $gateOperation)->allowed;
    }

    public function assertCanRenderInvoiceItem(InvoiceItem $item, ?User $user = null, string $gateOperation = 'invoice_item_render'): void
    {
        $decision = $this->policy->getInvoiceItemPolicy($item, $user, $gateOperation);
        if (! $decision->allowed) {
            throw BillingGateException::fromDecision($decision);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Consultation
    |--------------------------------------------------------------------------
    */

    public function canStartConsultation(Visit $visit, ?InvoiceItem $consultationFee, User $user): bool
    {
        if ($consultationFee === null) {
            return true; // nothing billed to settle
        }

        return $this->canRenderInvoiceItem($consultationFee, $user, 'consultation_start');
    }

    public function assertCanStartConsultation(Visit $visit, ?InvoiceItem $consultationFee, User $user): void
    {
        if ($consultationFee !== null) {
            $this->assertCanRenderInvoiceItem($consultationFee, $user, 'consultation_start');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Department workflows — resolve the related invoice line and delegate.
    |--------------------------------------------------------------------------
    */

    public function canProcessInvestigation(mixed $investigationRequest, User $user): bool
    {
        return $this->canRenderResolved($investigationRequest, $user, 'investigation_process');
    }

    public function assertCanProcessInvestigation(mixed $investigationRequest, User $user): void
    {
        $this->assertCanRenderResolved($investigationRequest, $user, 'investigation_process');
    }

    public function canDispensePrescriptionItem(mixed $prescriptionItem, User $user): bool
    {
        return $this->canRenderResolved($prescriptionItem, $user, 'prescription_dispense');
    }

    public function assertCanDispensePrescriptionItem(mixed $prescriptionItem, User $user): void
    {
        $this->assertCanRenderResolved($prescriptionItem, $user, 'prescription_dispense');
    }

    public function canStartProcedure(mixed $procedureRequest, User $user): bool
    {
        return $this->canRenderResolved($procedureRequest, $user, 'procedure_start');
    }

    public function assertCanStartProcedure(mixed $procedureRequest, User $user): void
    {
        $this->assertCanRenderResolved($procedureRequest, $user, 'procedure_start');
    }

    public function canMarkServiceRendered(mixed $serviceRendering, User $user): bool
    {
        return $this->canRenderResolved($serviceRendering, $user, 'service_mark_rendered');
    }

    public function assertCanMarkServiceRendered(mixed $serviceRendering, User $user): void
    {
        $this->assertCanRenderResolved($serviceRendering, $user, 'service_mark_rendered');
    }

    /*
    |--------------------------------------------------------------------------
    | Resolution helpers
    |--------------------------------------------------------------------------
    */

    private function canRenderResolved(mixed $subject, User $user, string $gateOperation): bool
    {
        $item = $this->resolveInvoiceItem($subject);

        return $item === null ? true : $this->canRenderInvoiceItem($item, $user, $gateOperation);
    }

    private function assertCanRenderResolved(mixed $subject, User $user, string $gateOperation): void
    {
        $item = $this->resolveInvoiceItem($subject);
        if ($item !== null) {
            $this->assertCanRenderInvoiceItem($item, $user, $gateOperation);
        }
    }

    /**
     * Best-effort resolution of the InvoiceItem behind a domain object. Returns
     * null when none can be found (fail-open — the caller passes the explicit
     * item via assertCanRenderInvoiceItem when it knows the link).
     */
    private function resolveInvoiceItem(mixed $subject): ?InvoiceItem
    {
        if ($subject instanceof InvoiceItem) {
            return $subject;
        }
        if (is_object($subject)) {
            if (isset($subject->invoiceItem) && $subject->invoiceItem instanceof InvoiceItem) {
                return $subject->invoiceItem;
            }
            if (method_exists($subject, 'invoiceItem')) {
                $related = $subject->invoiceItem()->first();
                if ($related instanceof InvoiceItem) {
                    return $related;
                }
            }
            if (! empty($subject->invoice_item_id)) {
                return InvoiceItem::find($subject->invoice_item_id);
            }
        }

        return null;
    }
}
