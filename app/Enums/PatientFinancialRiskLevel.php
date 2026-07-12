<?php

namespace App\Enums;

/**
 * Administrative classification of the hospital's willingness to extend payment
 * flexibility to a patient (Payment Timing Policy Phase 5). This is NOT clinical
 * risk, invoice status, receivable state or a payment-gate decision, and it must
 * never make a payment decision on its own.
 */
enum PatientFinancialRiskLevel: string
{
    case NORMAL = 'normal';
    case WATCHLIST = 'watchlist';
    case HIGH_RISK = 'high_risk';
    case BLOCKED_CREDIT = 'blocked_credit';

    public function label(): string
    {
        return __('patient_financial_risk.levels.'.$this->value.'.label');
    }

    public function description(): string
    {
        return __('patient_financial_risk.levels.'.$this->value.'.description');
    }

    /** A level that records an actual financial restriction (i.e. not normal). */
    public function isRestrictive(): bool
    {
        return $this !== self::NORMAL;
    }

    public function color(): string
    {
        return match ($this) {
            self::NORMAL => 'secondary',
            self::WATCHLIST => 'info',
            self::HIGH_RISK => 'warning',
            self::BLOCKED_CREDIT => 'danger',
        };
    }

    /** @return array<int, self> */
    public static function selectable(): array
    {
        return self::cases();
    }

    /** @return array<int, self> Levels that represent an actual restriction. */
    public static function restrictive(): array
    {
        return array_values(array_filter(self::cases(), fn (self $level) => $level->isRestrictive()));
    }
}
