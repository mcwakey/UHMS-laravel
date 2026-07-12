<?php

namespace App\Enums;

/**
 * Lifecycle status of a patient financial-risk profile (Payment Timing Policy
 * Phase 5). No status affects a live payment gate or visit policy in Phase 5.
 */
enum PatientFinancialRiskStatus: string
{
    case ACTIVE = 'active';
    case UNDER_REVIEW = 'under_review';
    case SUSPENDED = 'suspended';
    case CLEARED = 'cleared';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return __('patient_financial_risk.statuses.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'danger',
            self::UNDER_REVIEW => 'warning',
            self::SUSPENDED => 'secondary',
            self::CLEARED => 'success',
            self::EXPIRED => 'dark',
        };
    }

    /**
     * A profile that currently occupies the patient's single "restrictive slot".
     * Only one profile per patient may be ACTIVE or UNDER_REVIEW at a time.
     */
    public function occupiesActiveSlot(): bool
    {
        return match ($this) {
            self::ACTIVE, self::UNDER_REVIEW => true,
            default => false,
        };
    }

    /** Terminal statuses cannot transition further. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::CLEARED, self::EXPIRED => true,
            default => false,
        };
    }

    /**
     * Statuses this status may transition to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::ACTIVE => [self::UNDER_REVIEW, self::SUSPENDED, self::CLEARED, self::EXPIRED],
            self::UNDER_REVIEW => [self::ACTIVE, self::SUSPENDED, self::CLEARED, self::EXPIRED],
            self::SUSPENDED => [self::ACTIVE, self::UNDER_REVIEW, self::CLEARED, self::EXPIRED],
            self::CLEARED, self::EXPIRED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** @return array<int, self> */
    public static function activeSlotStatuses(): array
    {
        return [self::ACTIVE, self::UNDER_REVIEW];
    }
}
