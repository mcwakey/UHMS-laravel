<?php

namespace App\Enums;

enum VisitStatus: string
{
    case REGISTERED = 'registered';
    case WAITING = 'waiting';
    case TRIAGE = 'triage';
    case CONSULTING = 'consulting';
    case LAB = 'lab';
    case PHARMACY = 'pharmacy';
    case BILLING = 'billing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::REGISTERED => 'Registered',
            self::WAITING => 'Waiting',
            self::TRIAGE => 'Triage',
            self::CONSULTING => 'Consulting',
            self::LAB => 'Laboratory',
            self::PHARMACY => 'Pharmacy',
            self::BILLING => 'Billing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::REGISTERED => 'secondary',
            self::WAITING => 'warning',
            self::TRIAGE => 'info',
            self::CONSULTING => 'primary',
            self::LAB => 'purple',
            self::PHARMACY => 'orange',
            self::BILLING => 'dark',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Get valid next statuses from current status.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::REGISTERED => [self::WAITING, self::CANCELLED],
            self::WAITING => [self::TRIAGE, self::CONSULTING, self::CANCELLED],
            self::TRIAGE => [self::WAITING, self::CONSULTING, self::CANCELLED],
            self::CONSULTING => [self::LAB, self::PHARMACY, self::BILLING, self::COMPLETED, self::CANCELLED],
            self::LAB => [self::CONSULTING, self::PHARMACY, self::CANCELLED],
            self::PHARMACY => [self::BILLING, self::COMPLETED, self::CANCELLED],
            self::BILLING => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
