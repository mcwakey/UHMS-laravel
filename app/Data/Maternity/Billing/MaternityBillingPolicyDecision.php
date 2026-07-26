<?php

namespace App\Data\Maternity\Billing;

use App\Enums\MaternityBillingDeduplicationPolicy as Policy;

/**
 * Phase 14R.6 — the typed, read-only outcome of evaluating billing
 * de-duplication for one clinical event.
 *
 * Advisory: it records which source WOULD own the charge once Phase 14.2
 * posting exists. Producing this object creates no invoice item, no invoice, no
 * maternity billing event and no consultation billing application, and it never
 * recalculates or reverses anything.
 */
final class MaternityBillingPolicyDecision
{
    public const STATUS_POLICY_DISABLED = 'policy_disabled';
    public const STATUS_NO_CONFLICT = 'no_conflict';
    public const STATUS_DUPLICATE_SOURCE_SUPPRESSED = 'duplicate_source_suppressed';
    public const STATUS_BOTH_DISABLED = 'both_disabled';
    public const STATUS_MANUAL_REVIEW_REQUIRED = 'manual_review_required';

    public const SOURCE_CONSULTATION = 'consultation';
    public const SOURCE_MATERNITY_EVENT = 'maternity_event';
    public const SOURCE_NONE = 'none';

    /**
     * @param  array<string, mixed>  $duplicateIdentity  the stable clinical identity
     * @param  list<string>  $warnings  localisation keys
     * @param  array<string, bool>  $configuration
     */
    public function __construct(
        public readonly Policy $policy,
        public readonly string $status,
        public readonly ?int $consultationRouteId = null,
        public readonly ?string $maternitySourceType = null,
        public readonly mixed $maternitySourceId = null,
        public readonly ?string $maternityMappingKey = null,
        public readonly ?string $consultationBillingSource = null,
        public readonly string $allowedBillingSource = self::SOURCE_NONE,
        public readonly ?string $suppressedBillingSource = null,
        public readonly ?string $reasonCode = null,
        public readonly ?int $consultationBillingApplicationId = null,
        public readonly ?int $maternityBillingEventId = null,
        public readonly array $duplicateIdentity = [],
        public readonly array $warnings = [],
        public readonly bool $requiresManualReview = false,
        public readonly array $configuration = [],
    ) {}

    public static function disabled(?int $consultationRouteId = null): self
    {
        return new self(
            policy: Policy::CONSULTATION_ONLY,
            status: self::STATUS_POLICY_DISABLED,
            consultationRouteId: $consultationRouteId,
            reasonCode: 'policy_disabled',
        );
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_POLICY_DISABLED;
    }

    public function suppressesConsultationCharge(): bool
    {
        return $this->suppressedBillingSource === self::SOURCE_CONSULTATION;
    }

    public function suppressesMaternityCharge(): bool
    {
        return $this->suppressedBillingSource === self::SOURCE_MATERNITY_EVENT;
    }

    public function statusLabel(): string
    {
        return __('maternity_billing_policy.statuses.'.$this->status);
    }

    /** @return list<string> */
    public function warningMessages(): array
    {
        return array_map(
            fn (string $code) => __('maternity_billing_policy.warnings.'.$code),
            $this->warnings
        );
    }

    /**
     * Identifier-only shape. Carries no amounts, no patient identifiers beyond
     * the source record ids, and no clinical content.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'policy' => $this->policy->value,
            'status' => $this->status,
            'consultation_route_id' => $this->consultationRouteId,
            'maternity_source_type' => $this->maternitySourceType,
            'maternity_source_id' => $this->maternitySourceId,
            'maternity_mapping_key' => $this->maternityMappingKey,
            'consultation_billing_source' => $this->consultationBillingSource,
            'allowed_billing_source' => $this->allowedBillingSource,
            'suppressed_billing_source' => $this->suppressedBillingSource,
            'reason_code' => $this->reasonCode,
            'consultation_billing_application_id' => $this->consultationBillingApplicationId,
            'maternity_billing_event_id' => $this->maternityBillingEventId,
            'duplicate_identity' => $this->duplicateIdentity,
            'warnings' => $this->warnings,
            'requires_manual_review' => $this->requiresManualReview,
            'configuration' => $this->configuration,
        ];
    }
}
