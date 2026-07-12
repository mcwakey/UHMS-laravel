<?php

namespace App\Data\Billing;

use App\Enums\VisitPaymentArrangementSource;
use App\Enums\VisitPaymentTimingPolicy;
use Illuminate\Support\Carbon;

/**
 * Validated input to request or update a per-visit payment arrangement (Payment
 * Timing Policy Phase 7). Carries administrative request data only.
 */
final readonly class VisitPaymentArrangementData
{
    public function __construct(
        public VisitPaymentTimingPolicy $requestedPolicy,
        public VisitPaymentArrangementSource $source,
        public ?string $requestReasonCode,
        public string $requestReason,
        public ?string $supportingReference,
        public Carbon $effectiveFrom,
        public ?Carbon $expiresAt,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            requestedPolicy: VisitPaymentTimingPolicy::from($validated['requested_policy']),
            source: VisitPaymentArrangementSource::from($validated['source'] ?? VisitPaymentArrangementSource::MANUAL_REQUEST->value),
            requestReasonCode: self::nullTrim($validated['request_reason_code'] ?? null),
            requestReason: trim((string) ($validated['request_reason'] ?? '')),
            supportingReference: self::nullTrim($validated['supporting_reference'] ?? null),
            effectiveFrom: Carbon::parse($validated['effective_from'] ?? now()->toDateString()),
            expiresAt: ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(): array
    {
        return [
            'requested_policy' => $this->requestedPolicy->value,
            'source' => $this->source->value,
            'request_reason_code' => $this->requestReasonCode,
            'request_reason' => $this->requestReason,
            'supporting_reference' => $this->supportingReference,
            'effective_from' => $this->effectiveFrom->toDateString(),
            'expires_at' => $this->expiresAt?->toDateString(),
        ];
    }

    private static function nullTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
