<?php

namespace App\Data\Billing;

use App\Enums\MissingBillingContextPolicy;
use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentGateOverrideScopeRule;
use App\Enums\PaymentGateStage;
use App\Enums\PaymentGateVisitContextRule;

/**
 * Immutable, resolved policy for one registered payment-gate operation
 * (Payment Timing Policy Phase 4). Descriptive and diagnostic only — it holds
 * NO patient data and makes NO payment decisions. It represents the effective
 * configuration (stored settings merged over registry defaults).
 */
final readonly class PaymentGateOperationPolicy
{
    public function __construct(
        public string $operation,
        public PaymentGateStage $stage,
        public PaymentGateOperationMode $mode,
        public MissingBillingContextPolicy $missingBillingContext,
        public PaymentGateVisitContextRule $visitContextRule,
        public PaymentGateOverrideScopeRule $overrideScopeRule,
        public bool $allowZeroPatientResponsibility,
        public bool $allowFullyInsured,
        public bool $allowWaived,
        public bool $allowFullyAdjusted,
        public bool $allowPartialPayment,
        public bool $emergencyExempt,
        public bool $inpatientExempt,
        public bool $enabledForFutureTypedEnforcement,
        public array $metadata = [],
    ) {}

    /**
     * A safe, non-enforcing representation for an unknown / unregistered
     * operation. It is always DISABLED and never enforceable.
     */
    public static function unknown(string $operation): self
    {
        return new self(
            operation: $operation,
            stage: PaymentGateStage::RENDER,
            mode: PaymentGateOperationMode::DISABLED,
            missingBillingContext: MissingBillingContextPolicy::NOT_APPLICABLE,
            visitContextRule: PaymentGateVisitContextRule::NOT_APPLICABLE,
            overrideScopeRule: PaymentGateOverrideScopeRule::NONE,
            allowZeroPatientResponsibility: false,
            allowFullyInsured: false,
            allowWaived: false,
            allowFullyAdjusted: false,
            allowPartialPayment: false,
            emergencyExempt: false,
            inpatientExempt: false,
            enabledForFutureTypedEnforcement: false,
            metadata: ['unknown_operation' => true],
        );
    }

    /**
     * Diagnostic-safe array (no patient data). Enum values only.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'operation' => $this->operation,
            'stage' => $this->stage->value,
            'mode' => $this->mode->value,
            'missing_billing_context' => $this->missingBillingContext->value,
            'visit_context_rule' => $this->visitContextRule->value,
            'override_scope_rule' => $this->overrideScopeRule->value,
            'allow_zero_patient_responsibility' => $this->allowZeroPatientResponsibility,
            'allow_fully_insured' => $this->allowFullyInsured,
            'allow_waived' => $this->allowWaived,
            'allow_fully_adjusted' => $this->allowFullyAdjusted,
            'allow_partial_payment' => $this->allowPartialPayment,
            'emergency_exempt' => $this->emergencyExempt,
            'inpatient_exempt' => $this->inpatientExempt,
            'typed_enforcement_eligible' => $this->enabledForFutureTypedEnforcement,
        ];
    }
}
