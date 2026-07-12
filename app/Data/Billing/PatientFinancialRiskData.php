<?php

namespace App\Data\Billing;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskReason;
use Illuminate\Support\Carbon;

/**
 * Validated input for creating or updating a patient financial-risk
 * classification (Payment Timing Policy Phase 5). Carries only administrative
 * classification data — no payment, invoice or visit state.
 */
final readonly class PatientFinancialRiskData
{
    public function __construct(
        public PatientFinancialRiskLevel $riskLevel,
        public PatientFinancialRiskReason $primaryReason,
        public ?string $reasonDetails,
        public ?float $creditLimit,
        public Carbon $effectiveFrom,
        public ?Carbon $reviewDueAt,
        public ?Carbon $expiresAt,
        public ?string $reference,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            riskLevel: PatientFinancialRiskLevel::from($validated['risk_level']),
            primaryReason: PatientFinancialRiskReason::from($validated['primary_reason']),
            reasonDetails: self::trimmedOrNull($validated['reason_details'] ?? null),
            creditLimit: isset($validated['credit_limit']) && $validated['credit_limit'] !== null && $validated['credit_limit'] !== ''
                ? (float) $validated['credit_limit']
                : null,
            effectiveFrom: Carbon::parse($validated['effective_from']),
            reviewDueAt: ! empty($validated['review_due_at']) ? Carbon::parse($validated['review_due_at']) : null,
            expiresAt: ! empty($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
            reference: self::trimmedOrNull($validated['reference'] ?? null),
        );
    }

    /** @return array<string, mixed> Attributes for persisting on the profile. */
    public function toAttributes(): array
    {
        return [
            'risk_level' => $this->riskLevel->value,
            'primary_reason' => $this->primaryReason->value,
            'reason_details' => $this->reasonDetails,
            'credit_limit' => $this->creditLimit,
            'effective_from' => $this->effectiveFrom->toDateString(),
            'review_due_at' => $this->reviewDueAt?->toDateString(),
            'expires_at' => $this->expiresAt?->toDateString(),
            'reference' => $this->reference,
        ];
    }

    private static function trimmedOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
