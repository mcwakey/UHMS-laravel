<?php

namespace App\Data\Billing;

use App\Enums\PaymentGateStage;

final readonly class PaymentGateContext
{
    public function __construct(
        public PaymentGateStage $stage,
        public string $operation,
        public ?int $departmentId = null,
        public ?string $departmentType = null,
        public ?string $serviceType = null,
        public ?bool $emergencyStabilisation = null,
        public array $metadata = [],
    ) {}

    public static function triageRouteCompletion(?int $departmentId = null): self
    {
        return new self(PaymentGateStage::COMPLETE, 'consultation.route.complete', $departmentId, 'consultation', 'consultation_service');
    }

    public static function consultationReadiness(?int $departmentId = null): self
    {
        return new self(PaymentGateStage::READINESS, 'consultation.next_patient.readiness', $departmentId, 'consultation', 'consultation_service');
    }

    public static function laboratoryResultEntry(?int $departmentId = null, ?string $serviceType = null): self
    {
        return new self(PaymentGateStage::RESULT, 'laboratory.result.enter', $departmentId, 'investigation', $serviceType ?? 'investigation_service');
    }

    public static function pharmacyDispense(?int $departmentId = null): self
    {
        return new self(PaymentGateStage::DISPENSE, 'pharmacy.item.dispense', $departmentId, 'pharmacy', 'pharmacy_product');
    }

    public static function forOperation(PaymentGateStage $stage, string $operation, ?string $departmentType = null, ?string $serviceType = null): self
    {
        return new self($stage, $operation, departmentType: $departmentType, serviceType: $serviceType);
    }

    public static function legacy(string $operation): self
    {
        return new self(PaymentGateStage::RENDER, $operation);
    }

    public function observationContext(bool $invoiceItemPresent): array
    {
        return [
            'payment_gate_stage' => $this->stage->value,
            'gate_operation' => $this->operation,
            'department_id' => $this->departmentId,
            'department_type' => $this->departmentType,
            'service_type' => $this->serviceType,
            'invoice_item_present' => $invoiceItemPresent,
            'emergency_stabilisation' => $this->emergencyStabilisation,
        ];
    }
}
