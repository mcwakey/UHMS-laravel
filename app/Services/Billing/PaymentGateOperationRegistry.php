<?php

namespace App\Services\Billing;

use App\Enums\DepartmentType;
use App\Enums\MissingBillingContextPolicy;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentGateOverrideScopeRule;
use App\Enums\PaymentGateStage;
use App\Enums\PaymentGateVisitContextRule;

/**
 * Descriptive registry of payment-gate operations (Payment Timing Policy Phase
 * 3, extended in Phase 4). It DECLARES metadata and safe defaults only — it never
 * evaluates invoices, reads settings, or makes payment decisions.
 *
 * Phase 4 preserves the original keys (stage, facade_method, production_wired,
 * hard_enforcement, production_caller, legacy_behaviour) for existing consumers,
 * and adds departmental-enforcement policy defaults per operation.
 */
final class PaymentGateOperationRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function operations(): array
    {
        return [
            // ── Wired hard gates — default to LEGACY, preserve current behaviour ──
            'consultation.route.complete' => $this->entry(
                stage: PaymentGateStage::COMPLETE, department: DepartmentType::CONSULTATION, family: 'consultation',
                facadeMethod: 'policyFor', wired: true, hardGate: true, caller: 'TriageController::store',
                legacy: 'priced missing item blocks; unpriced/non-billable skips',
                allow: ['zero' => true, 'insured' => true, 'waived' => true, 'adjusted' => true, 'partial' => false],
                typedApproved: true, typedVisitTypes: ['outpatient'],
            ),
            'consultation.next_patient.readiness' => $this->entry(
                stage: PaymentGateStage::READINESS, department: DepartmentType::CONSULTATION, family: 'consultation',
                facadeMethod: 'policyFor', wired: true, hardGate: true, caller: 'ConsultationNextPatientService::preview/openNext',
                legacy: 'missing item skips readiness check; preview advises and openNext enforces',
                allow: ['zero' => true, 'insured' => true, 'waived' => true, 'adjusted' => true, 'partial' => false],
                note: 'readiness/activation check; not every readiness failure is a hard payment gate',
                typedApproved: true, typedVisitTypes: ['outpatient'],
            ),
            'laboratory.result.enter' => $this->entry(
                stage: PaymentGateStage::RESULT, department: DepartmentType::INVESTIGATION, family: 'laboratory',
                facadeMethod: 'policyForLabResultEntry', wired: true, hardGate: true, caller: 'LabService::enterResult',
                legacy: 'missing item allowed',
                allow: ['zero' => true, 'insured' => true, 'waived' => true, 'adjusted' => true, 'partial' => false],
                compatibilityDecision: true,
                note: 'intrinsic settlement compatibility; enforcement stage unresolved (acceptance/collection/result/verification/release)',
                typedApproved: true, typedVisitTypes: ['outpatient', 'inpatient'], requiresAck: true,
                compatibilityDescription: 'Typed laboratory mode changes the old intrinsic paid/covered/waived-only rule: pay-after/running-bill visits may enter results before payment.',
            ),
            'pharmacy.item.dispense' => $this->entry(
                stage: PaymentGateStage::DISPENSE, department: DepartmentType::PHARMACY, family: 'pharmacy',
                facadeMethod: 'policyForPaidPharmacyItem', wired: true, hardGate: true, caller: 'PharmacyBillingSelectionService::isItemSettled',
                legacy: 'missing billed selection or item blocks',
                allow: ['zero' => false, 'insured' => false, 'waived' => false, 'adjusted' => false, 'partial' => false],
                compatibilityDecision: true,
                note: 'stricter paid-only rule; requires explicit policy-convergence decision before typed enforcement',
                typedApproved: true, typedVisitTypes: ['outpatient', 'inpatient'], requiresAck: true,
                compatibilityDescription: 'Typed payment timing relaxes the former paid-only dispensing rule: pay-after/running-bill visits may dispense before payment (clinical/stock checks unchanged).',
            ),

            // ── Unwired operations — default to DISABLED, add no operational check ──
            'consultation.start' => $this->unwired(
                stage: PaymentGateStage::START, department: DepartmentType::CONSULTATION, family: 'consultation',
                facadeMethod: 'canStartConsultation/assertCanStartConsultation', legacy: 'missing consultation fee allowed',
            ),
            'investigation.perform' => $this->unwired(
                stage: PaymentGateStage::PERFORM, department: DepartmentType::INVESTIGATION, family: 'investigation',
                facadeMethod: 'canProcessInvestigation/assertCanProcessInvestigation', legacy: 'unresolved invoice item allowed',
                note: 'final enforcement stage undecided (acceptance/sample collection/performance/result entry/verification/release)',
            ),
            'procedure.start' => $this->unwired(
                stage: PaymentGateStage::START, department: DepartmentType::PROCEDURE, family: 'procedure',
                facadeMethod: 'canStartProcedure/assertCanStartProcedure', legacy: 'unresolved invoice item allowed',
            ),
            'service.render' => $this->unwired(
                stage: PaymentGateStage::RENDER, department: null, family: 'service',
                facadeMethod: 'canMarkServiceRendered/assertCanMarkServiceRendered', legacy: 'unresolved invoice item allowed',
            ),
            'theatre.perform' => $this->unwired(
                stage: PaymentGateStage::PERFORM, department: DepartmentType::THEATRE, family: 'theatre',
                facadeMethod: 'evaluateInvoiceItem', legacy: 'no existing payment check', emergencySensitive: true,
                note: 'emergency and life-saving boundaries unresolved',
            ),
            'treatment.perform' => $this->unwired(
                stage: PaymentGateStage::PERFORM, department: DepartmentType::TREATMENT, family: 'treatment',
                facadeMethod: 'evaluateInvoiceItem', legacy: 'no existing payment check',
            ),
            'nursing.service.render' => $this->unwired(
                stage: PaymentGateStage::RENDER, department: DepartmentType::NURSING, family: 'nursing',
                facadeMethod: 'evaluateInvoiceItem', legacy: 'no existing payment check', emergencySensitive: true,
                note: 'medication administration and essential nursing care must never be blocked',
            ),
            'blood_bank.unit.issue' => $this->unwired(
                stage: PaymentGateStage::ISSUE, department: DepartmentType::BLOOD_BANK, family: 'blood_bank',
                facadeMethod: 'evaluateInvoiceItem', legacy: 'no existing payment check', emergencySensitive: true,
                note: 'emergency and life-saving blood issue must remain protected',
            ),
            'ambulance.service.render' => $this->unwired(
                stage: PaymentGateStage::RENDER, department: DepartmentType::AMBULANCE, family: 'ambulance',
                facadeMethod: 'evaluateInvoiceItem', legacy: 'no existing payment check', noConfirmedWorkflow: true,
                note: 'no dedicated billable operational workflow confirmed',
            ),
        ];
    }

    /** @return array<int, string> */
    public function operationCodes(): array
    {
        return array_keys($this->operations());
    }

    public function has(string $operation): bool
    {
        return array_key_exists($operation, $this->operations());
    }

    /** @return array<string, mixed>|null */
    public function get(string $operation): ?array
    {
        return $this->operations()[$operation] ?? null;
    }

    /**
     * A wired hard-gate operation defaulting to LEGACY compatibility.
     *
     * @param  array{zero: bool, insured: bool, waived: bool, adjusted: bool, partial: bool}  $allow
     */
    private function entry(
        PaymentGateStage $stage,
        ?DepartmentType $department,
        string $family,
        string $facadeMethod,
        bool $wired,
        bool $hardGate,
        ?string $caller,
        string $legacy,
        array $allow,
        bool $compatibilityDecision = false,
        ?string $note = null,
        // Phase 8 typed-cutover metadata.
        bool $typedApproved = false,
        array $typedVisitTypes = [],
        bool $requiresAck = false,
        ?string $compatibilityDescription = null,
        bool $emergencySupported = false,
    ): array {
        return [
            // Original Phase 3 keys (kept for existing consumers).
            'stage' => $stage,
            'facade_method' => $facadeMethod,
            'production_wired' => $wired,
            'hard_enforcement' => $hardGate,
            'production_caller' => $caller,
            'legacy_behaviour' => $legacy,
            // Phase 4 departmental-enforcement metadata + defaults.
            'department_type' => $department,
            'workflow_family' => $family,
            'default_mode' => PaymentGateOperationMode::LEGACY,
            'missing_billing_context' => MissingBillingContextPolicy::PRESERVE_LEGACY,
            'visit_context_rule' => PaymentGateVisitContextRule::PRESERVE_LEGACY,
            'override_scope_rule' => PaymentGateOverrideScopeRule::PRESERVE_LEGACY,
            'typed_enforcement_eligible' => true,
            // Phase 8: eligible wired operations may be approved for typed cutover.
            'approved_for_typed_enforcement' => $typedApproved,
            'typed_supported_visit_types' => $typedVisitTypes,
            'requires_compatibility_acknowledgement' => $requiresAck,
            'compatibility_change_description' => $compatibilityDescription,
            'emergency_supported' => $emergencySupported,
            'typed_missing_context_rule' => MissingBillingContextPolicy::PRESERVE_LEGACY,
            'emergency_sensitive' => false,
            'invoice_resolution_confirmed' => ! $compatibilityDecision,
            'compatibility_requires_decision' => $compatibilityDecision,
            'no_confirmed_workflow' => false,
            'allow_zero_patient_responsibility' => $allow['zero'],
            'allow_fully_insured' => $allow['insured'],
            'allow_waived' => $allow['waived'],
            'allow_fully_adjusted' => $allow['adjusted'],
            'allow_partial_payment' => $allow['partial'],
            'emergency_exempt' => false,
            'inpatient_exempt' => false,
            'compatibility_note' => $note,
        ];
    }

    /**
     * An unwired operation defaulting to DISABLED (adds no operational check).
     * These currently have no gate, so they descriptively allow every item state.
     */
    private function unwired(
        PaymentGateStage $stage,
        ?DepartmentType $department,
        string $family,
        string $facadeMethod,
        string $legacy,
        bool $emergencySensitive = false,
        bool $noConfirmedWorkflow = false,
        ?string $note = null,
    ): array {
        return [
            'stage' => $stage,
            'facade_method' => $facadeMethod,
            'production_wired' => false,
            'hard_enforcement' => false,
            'production_caller' => null,
            'legacy_behaviour' => $legacy,
            'department_type' => $department,
            'workflow_family' => $family,
            'default_mode' => PaymentGateOperationMode::DISABLED,
            'missing_billing_context' => MissingBillingContextPolicy::PRESERVE_LEGACY,
            'visit_context_rule' => PaymentGateVisitContextRule::PRESERVE_LEGACY,
            'override_scope_rule' => PaymentGateOverrideScopeRule::PRESERVE_LEGACY,
            'typed_enforcement_eligible' => false,
            'approved_for_typed_enforcement' => false,
            // Phase 8: unwired operations can never become typed.
            'typed_supported_visit_types' => [],
            'requires_compatibility_acknowledgement' => false,
            'compatibility_change_description' => null,
            'emergency_supported' => false,
            'typed_missing_context_rule' => MissingBillingContextPolicy::PRESERVE_LEGACY,
            'emergency_sensitive' => $emergencySensitive,
            'invoice_resolution_confirmed' => false,
            'compatibility_requires_decision' => false,
            'no_confirmed_workflow' => $noConfirmedWorkflow,
            // No existing gate → currently allows all item states.
            'allow_zero_patient_responsibility' => true,
            'allow_fully_insured' => true,
            'allow_waived' => true,
            'allow_fully_adjusted' => true,
            'allow_partial_payment' => true,
            'emergency_exempt' => false,
            'inpatient_exempt' => false,
            'compatibility_note' => $note,
        ];
    }
}
