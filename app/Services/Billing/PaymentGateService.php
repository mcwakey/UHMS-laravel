<?php

namespace App\Services\Billing;

use App\Data\Billing\PaymentGateContext;
use App\Enums\PaymentGateStage;
use App\Exceptions\BillingGateException;
use App\Models\InvoiceItem;
use App\Models\LabRequestItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Model;

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

    public function evaluateInvoiceItem(
        InvoiceItem $item,
        PaymentGateContext $context,
        ?User $user = null,
    ): BillingPolicyDecision {
        return $this->policy->getInvoiceItemPolicy($item, $user, $context);
    }

    public function policyFor(
        InvoiceItem $item,
        ?User $user = null,
        PaymentGateContext|string|null $context = null,
    ): BillingPolicyDecision {
        return $this->evaluateInvoiceItem(
            $item,
            $this->context($context, PaymentGateStage::RENDER, 'invoice_item.policy'),
            $user,
        );
    }

    public function canRenderInvoiceItem(
        InvoiceItem $item,
        ?User $user = null,
        PaymentGateContext|string $context = 'invoice_item.render',
    ): bool {
        return $this->policyFor($item, $user, $this->context($context, PaymentGateStage::RENDER, 'invoice_item.render'))->allowed;
    }

    public function assertCanRenderInvoiceItem(
        InvoiceItem $item,
        ?User $user = null,
        PaymentGateContext|string $context = 'invoice_item.render',
    ): void {
        $this->assertAllowed($this->policyFor($item, $user, $this->context($context, PaymentGateStage::RENDER, 'invoice_item.render')));
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

        return $this->canRenderInvoiceItem(
            $consultationFee,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::START, 'consultation.start', 'consultation', 'consultation_service'),
        );
    }

    public function assertCanStartConsultation(Visit $visit, ?InvoiceItem $consultationFee, User $user): void
    {
        if ($consultationFee !== null) {
            $this->assertCanRenderInvoiceItem(
                $consultationFee,
                $user,
                PaymentGateContext::forOperation(PaymentGateStage::START, 'consultation.start', 'consultation', 'consultation_service'),
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Department workflows — resolve the related invoice line and delegate.
    |--------------------------------------------------------------------------
    */

    public function canProcessInvestigation(mixed $investigationRequest, User $user): bool
    {
        return $this->canRenderResolved(
            $investigationRequest,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::PERFORM, 'investigation.perform', 'investigation', 'investigation_service'),
        );
    }

    public function assertCanProcessInvestigation(mixed $investigationRequest, User $user): void
    {
        $this->assertCanRenderResolved(
            $investigationRequest,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::PERFORM, 'investigation.perform', 'investigation', 'investigation_service'),
        );
    }

    public function canDispensePrescriptionItem(mixed $prescriptionItem, User $user): bool
    {
        return $this->canRenderResolved($prescriptionItem, $user, PaymentGateContext::pharmacyDispense());
    }

    public function assertCanDispensePrescriptionItem(mixed $prescriptionItem, User $user): void
    {
        $this->assertCanRenderResolved($prescriptionItem, $user, PaymentGateContext::pharmacyDispense());
    }

    public function canStartProcedure(mixed $procedureRequest, User $user): bool
    {
        return $this->canRenderResolved(
            $procedureRequest,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::START, 'procedure.start', 'procedure', 'procedure_service'),
        );
    }

    public function assertCanStartProcedure(mixed $procedureRequest, User $user): void
    {
        $this->assertCanRenderResolved(
            $procedureRequest,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::START, 'procedure.start', 'procedure', 'procedure_service'),
        );
    }

    public function canMarkServiceRendered(mixed $serviceRendering, User $user): bool
    {
        return $this->canRenderResolved(
            $serviceRendering,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::RENDER, 'service.render', serviceType: 'service_rendering'),
        );
    }

    public function assertCanMarkServiceRendered(mixed $serviceRendering, User $user): void
    {
        $this->assertCanRenderResolved(
            $serviceRendering,
            $user,
            PaymentGateContext::forOperation(PaymentGateStage::RENDER, 'service.render', serviceType: 'service_rendering'),
        );
    }

    public function policyForLabResultEntry(LabRequestItem $requestItem, ?User $user = null): BillingPolicyDecision
    {
        $labRequest = $requestItem->relationLoaded('labRequest')
            ? $requestItem->labRequest
            : ($requestItem->lab_request_id ? $requestItem->labRequest()->first() : null);
        $context = PaymentGateContext::laboratoryResultEntry(
            $labRequest?->target_department_id ?? $labRequest?->department_id,
        );
        if (! $requestItem->invoice_item_id) {
            return $this->missingItemAllowed($context);
        }
        $item = $this->resolveInvoiceItem($requestItem);
        if (! $item) {
            return $this->missingItemAllowed($context);
        }

        return $this->policy->getIntrinsicSettlementPolicy(
            $item,
            $context,
            'Payment required: this investigation must be paid before results can be entered.',
        );
    }

    public function canEnterLabResult(LabRequestItem $requestItem, ?User $user = null): bool
    {
        return $this->policyForLabResultEntry($requestItem, $user)->allowed;
    }

    public function assertCanEnterLabResult(LabRequestItem $requestItem, ?User $user = null): void
    {
        $this->assertAllowed($this->policyForLabResultEntry($requestItem, $user));
    }

    public function policyForPaidPharmacyItem(InvoiceItem $item, ?User $user = null): BillingPolicyDecision
    {
        return $this->policy->getPaidOnlyInvoiceItemPolicy(
            $item,
            PaymentGateContext::pharmacyDispense($item->department_id),
            'This item cannot be dispensed until its bill is settled (paid).',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolution helpers
    |--------------------------------------------------------------------------
    */

    private function canRenderResolved(mixed $subject, User $user, PaymentGateContext $context): bool
    {
        $item = $this->resolveInvoiceItem($subject);

        return $item === null ? true : $this->canRenderInvoiceItem($item, $user, $context);
    }

    private function assertCanRenderResolved(mixed $subject, User $user, PaymentGateContext $context): void
    {
        $item = $this->resolveInvoiceItem($subject);
        if ($item !== null) {
            $this->assertCanRenderInvoiceItem($item, $user, $context);
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
            if ($subject instanceof Model && $subject->relationLoaded('invoiceItem') && $subject->invoiceItem instanceof InvoiceItem) {
                return $subject->invoiceItem;
            }
            if ($subject instanceof Model && array_key_exists('invoice_item_id', $subject->getAttributes())) {
                return ! empty($subject->invoice_item_id) ? InvoiceItem::find($subject->invoice_item_id) : null;
            }
            if (method_exists($subject, 'invoiceItem')) {
                $related = $subject->invoiceItem()->first();
                if ($related instanceof InvoiceItem) {
                    return $related;
                }
            }
        }

        return null;
    }

    private function context(
        PaymentGateContext|string|null $context,
        PaymentGateStage $defaultStage,
        string $defaultOperation,
    ): PaymentGateContext {
        return match (true) {
            $context instanceof PaymentGateContext => $context,
            is_string($context) => PaymentGateContext::forOperation($defaultStage, $context),
            default => PaymentGateContext::forOperation($defaultStage, $defaultOperation),
        };
    }

    private function missingItemAllowed(PaymentGateContext $context): BillingPolicyDecision
    {
        return BillingPolicyDecision::allow(
            BillingPolicyService::MODE_ADVISORY,
            'INVOICE_ITEM_MISSING_ALLOWED',
            'No invoice item is linked; existing workflow allows this operation.',
            ['settlementStatus' => InvoiceItemSettlementService::UNBILLED],
        );
    }

    private function assertAllowed(BillingPolicyDecision $decision): void
    {
        if (! $decision->allowed) {
            throw BillingGateException::fromDecision($decision);
        }
    }
}
