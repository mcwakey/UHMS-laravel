<?php

namespace App\Services\Billing;

use App\Enums\PaymentGateStage;

final class PaymentGateOperationRegistry
{
    public function operations(): array
    {
        return [
            'consultation.route.complete' => $this->entry(PaymentGateStage::COMPLETE, 'policyFor', true, true, 'TriageController::store', 'priced missing item blocks; unpriced/non-billable skips'),
            'consultation.next_patient.readiness' => $this->entry(PaymentGateStage::READINESS, 'policyFor', true, true, 'ConsultationNextPatientService::preview/openNext', 'missing item skips readiness check; preview advises and openNext enforces'),
            'laboratory.result.enter' => $this->entry(PaymentGateStage::RESULT, 'policyForLabResultEntry', true, true, 'LabService::enterResult', 'missing item allowed'),
            'pharmacy.item.dispense' => $this->entry(PaymentGateStage::DISPENSE, 'policyForPaidPharmacyItem', true, true, 'PharmacyBillingSelectionService::isItemSettled', 'missing billed selection or item blocks'),
            'consultation.start' => $this->entry(PaymentGateStage::START, 'canStartConsultation/assertCanStartConsultation', false, false, null, 'missing consultation fee allowed'),
            'investigation.perform' => $this->entry(PaymentGateStage::PERFORM, 'canProcessInvestigation/assertCanProcessInvestigation', false, false, null, 'unresolved invoice item allowed'),
            'procedure.start' => $this->entry(PaymentGateStage::START, 'canStartProcedure/assertCanStartProcedure', false, false, null, 'unresolved invoice item allowed'),
            'service.render' => $this->entry(PaymentGateStage::RENDER, 'canMarkServiceRendered/assertCanMarkServiceRendered', false, false, null, 'unresolved invoice item allowed'),
            'theatre.perform' => $this->entry(PaymentGateStage::PERFORM, 'evaluateInvoiceItem', false, false, null, 'no existing payment check'),
            'treatment.perform' => $this->entry(PaymentGateStage::PERFORM, 'evaluateInvoiceItem', false, false, null, 'no existing payment check'),
            'nursing.service.render' => $this->entry(PaymentGateStage::RENDER, 'evaluateInvoiceItem', false, false, null, 'no existing payment check'),
            'blood_bank.unit.issue' => $this->entry(PaymentGateStage::ISSUE, 'evaluateInvoiceItem', false, false, null, 'no existing payment check'),
            'ambulance.service.render' => $this->entry(PaymentGateStage::RENDER, 'evaluateInvoiceItem', false, false, null, 'no existing payment check'),
        ];
    }

    private function entry(
        PaymentGateStage $stage,
        string $facadeMethod,
        bool $productionWired,
        bool $hardEnforcement,
        ?string $caller,
        string $missingItemBehaviour,
    ): array {
        return [
            'stage' => $stage,
            'facade_method' => $facadeMethod,
            'production_wired' => $productionWired,
            'hard_enforcement' => $hardEnforcement,
            'production_caller' => $caller,
            'legacy_behaviour' => $missingItemBehaviour,
        ];
    }
}
