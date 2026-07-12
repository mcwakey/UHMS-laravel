<?php

namespace App\Data\Billing;

/**
 * Non-persisted preview of what a visit's materialised payment policy would look
 * like (Payment Timing Policy Phase 6). Observational only — computing a preview
 * never writes a record, history or activity log.
 */
final readonly class VisitPaymentPolicyPreview
{
    /**
     * @param  array<string, mixed>  $baselineContext
     * @param  array<string, mixed>  $riskSnapshot
     */
    public function __construct(
        public VisitPaymentTimingDecision $baseline,
        public PatientRiskPaymentRecommendation $recommendation,
        public array $baselineContext,
        public array $riskSnapshot,
        public string $resolutionVersion,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'baseline' => $this->baseline->toArray(),
            'recommendation' => $this->recommendation->toArray(),
            'baseline_context' => $this->baselineContext,
            'risk_snapshot' => $this->riskSnapshot,
            'resolution_version' => $this->resolutionVersion,
        ];
    }
}
