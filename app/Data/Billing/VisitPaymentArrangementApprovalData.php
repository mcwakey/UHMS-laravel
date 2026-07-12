<?php

namespace App\Data\Billing;

use Illuminate\Support\Carbon;

/**
 * Validated input for approving a per-visit payment arrangement (Payment Timing
 * Policy Phase 7).
 */
final readonly class VisitPaymentArrangementApprovalData
{
    public function __construct(
        public ?string $decisionReason,
        public ?Carbon $effectiveFrom,
        public ?Carbon $expiresAt,
        public bool $confirmStaleRisk,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            decisionReason: isset($validated['decision_reason']) && trim((string) $validated['decision_reason']) !== ''
                ? trim((string) $validated['decision_reason'])
                : null,
            effectiveFrom: ! empty($validated['effective_from']) ? Carbon::parse($validated['effective_from']) : null,
            expiresAt: ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
            confirmStaleRisk: (bool) ($validated['confirm_stale_risk'] ?? false),
        );
    }
}
